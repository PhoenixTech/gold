<?php

namespace App\Models;

use App\Http\Resources\CommentMarkupCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Plank\Metable\Metable;
use Spatie\Image\Enums\AlignPosition;
use Spatie\Image\Enums\Fit;
use Spatie\Image\Enums\Unit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Tags\HasTags;
use Spatie\Translatable\HasTranslations;

class Product extends Model implements HasMedia
{
    use HasFactory, HasTags, HasTranslations, InteractsWithMedia, Metable, SoftDeletes;

    public static $stock_status = ['IN_STOCK', 'OUT_STOCK', 'BACK_ORDER'];

    public $translatable = ['name', 'excerpt', 'description', 'table'];

    protected $casts = [
        'qz' => 'array',
        'qidz' => 'array',
        'plating_colors' => 'array',
        'stones' => 'array',
        'accessories' => 'array',
        'occasions' => 'array',
    ];

    protected static function booted()
    {
        static::saving(function (Product $product) {
            if ($product->category_id) {
                $targetGroup = $product->target_group ?: 'unisex';
                $metalType = $product->metal_type ?: 'gold';
                $expectedPrefix = static::skuPrefix($targetGroup, $metalType, (int) $product->category_id);

                $isSkuValid = $product->sku
                    && str_starts_with($product->sku, $expectedPrefix)
                    && ! static::withTrashed()
                        ->where('sku', $product->sku)
                        ->when($product->id, fn ($q) => $q->where('id', '!=', $product->id))
                        ->exists();

                if (! $isSkuValid) {
                    $product->sku = static::generateSku(
                        $targetGroup,
                        $metalType,
                        (int) $product->category_id,
                        $product->id
                    );
                }
            }
        });
    }

    public static function skuPrefix(?string $targetGroup, ?string $metalType, ?int $categoryId): string
    {
        $targets = [
            'women' => 'F', 'female' => 'F', 'f' => 'F',
            'men' => 'M', 'male' => 'M', 'm' => 'M',
            'children' => 'C', 'child' => 'C', 'c' => 'C',
            'unisex' => 'U', 'u' => 'U',
        ];
        $t = $targets[strtolower((string) $targetGroup)] ?? 'U';

        $metalNorm = strtolower((string) $metalType);
        $m = ($metalNorm === 'silver' || $metalNorm === '2' || $metalNorm === 's') ? '2' : '1';

        $c = Category::resolveSkuCode($categoryId);

        return "{$t}{$m}{$c}";
    }

    public static function generateSku(?string $targetGroup, ?string $metalType, ?int $categoryId, ?int $productId = null): string
    {
        $prefix = static::skuPrefix($targetGroup, $metalType, $categoryId);

        $query = static::withTrashed()->where('sku', 'LIKE', "{$prefix}%");
        if ($productId) {
            $query->where('id', '!=', $productId);
        }

        $existingSkus = $query->pluck('sku')->toArray();
        $existingNumbers = [];
        $pattern = '/^'.preg_quote($prefix, '/').'(\d+)$/';

        foreach ($existingSkus as $sku) {
            if (preg_match($pattern, (string) $sku, $matches)) {
                $existingNumbers[(int) $matches[1]] = true;
            }
        }

        $nextNumber = ! empty($existingNumbers) ? max(array_keys($existingNumbers)) + 1 : 1;
        $n = sprintf('%04d', $nextNumber);

        return "{$prefix}{$n}";
    }

    public function attachs()
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    protected $guarded = [];

    public function isLowStock(): bool
    {
        return (int) ($this->min_stock_level ?? 0) > 0 && (int) ($this->stock_quantity ?? 0) < (int) $this->min_stock_level;
    }

    public function scopeLowStock($query)
    {
        return $query->where('min_stock_level', '>', 0)
            ->whereColumn('stock_quantity', '<', 'min_stock_level');
    }

    public function isBelowBuyPrice(): bool
    {
        return (int) ($this->buy_price ?? 0) > 0 && (int) ($this->price ?? 0) < (int) $this->buy_price;
    }

    public function canBeSold(): bool
    {
        if ($this->isBelowBuyPrice()) {
            return false;
        }

        return $this->stock_status === 'IN_STOCK' && (int) ($this->stock_quantity ?? 0) > 0;
    }

    public function scopeBelowBuyPrice($query)
    {
        return $query->where('buy_price', '>', 0)
            ->whereColumn('price', '<', 'buy_price');
    }

    public function totalStockWeight(): float
    {
        $available = $this->relationLoaded('quantities')
            ? $this->quantities->filter->isAvailable()
            : $this->availableQuantities()->get();

        if ($available->isNotEmpty()) {
            return round((float) $available->sum(fn ($q) => (float) ($q->weight ?? 0) * (int) ($q->count ?? 1)), 3);
        }

        return round((float) ($this->weight ?? 0) * (int) ($this->stock_quantity ?? 0), 3);
    }

    public function totalStockPrice(): int
    {
        $available = $this->relationLoaded('quantities')
            ? $this->quantities->filter->isAvailable()
            : $this->availableQuantities()->get();

        if ($available->isNotEmpty()) {
            return (int) $available->sum(fn ($q) => (int) ($q->price ?? 0) * (int) ($q->count ?? 1));
        }

        return (int) (($this->price ?? 0) * ($this->stock_quantity ?? 0));
    }

    public function getTotalWeightAttribute(): float
    {
        return $this->totalStockWeight();
    }

    public function getTotalPriceAttribute(): int
    {
        return $this->totalStockPrice();
    }

    public function getQzAttribute()
    {
        $result = [];
        foreach ($this->quantities as $q) {
            if ($q->count > 0) {
                $q->data = json_decode($q->data);
                $result[] = $q;
            }
        }

        return $result;
    }

    public function getQidzAttribute()
    {
        return $this->quantities()->pluck('id')->toArray();
    }

    public function registerMediaConversions(?Media $media = null): void
    {

        $optimize = getSetting('optimize');
        if ($optimize == false) {
            $optimize = 'webp';
        }
        $ti = imageSizeConvertValidate('product_image');
        $t = imageSizeConvertValidate('product_thumb');

        $mc = $this->addMediaConversion('product-thumb')
            ->width($t[0])
            ->height($t[1])
            ->crop($t[0], $t[1])
            ->optimize()
            ->sharpen(10)
            ->nonQueued()
            ->format($optimize);

        $mc2 = $this->addMediaConversion('product-image')
            ->width($ti[0])
            ->height($ti[1])
            ->crop($ti[0], $ti[1])
            ->optimize()
            ->sharpen(10)
            ->nonQueued()
            ->format($optimize);

        if (getSetting('watermark')) {
            $mc->watermark(public_path('upload/images/logo.png'),
                AlignPosition::BottomLeft, 5, 5, Unit::Percent,
                config('app.media.watermark_size'), Unit::Percent,
                config('app.media.watermark_size'), Unit::Percent, Fit::Contain,
                config('app.media.watermark_opacity'));

            $mc2->watermark(public_path('upload/images/logo.png'),
                AlignPosition::BottomLeft, 5, 5, Unit::Percent,
                config('app.media.watermark_size'), Unit::Percent,
                config('app.media.watermark_size'), Unit::Percent, Fit::Contain,
                config('app.media.watermark_opacity'));
        }

        $this->addMediaConversion('product-optimized')
            ->optimize()
            ->sharpen(10)
            ->nonQueued()
            ->format('webp');
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function approvedComments()
    {
        return $this->morphMany(Comment::class, 'commentable')->where('status', 1);
    }

    public function likedBy()
    {
        return $this->belongsToMany(Customer::class, 'customer_product');
    }

    public function bookmarks()
    {
        return $this->belongsToMany(Customer::class, 'customer_bookmarks');
    }

    public function bookmarkedBy()
    {
        return $this->bookmarks();
    }

    public function likedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_product_favorites');
    }

    public function bookmarkedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_product_bookmarks');
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? $this->getRouteKeyName(), $value)->first()
            ?? (is_numeric($value) ? $this->where('id', $value)->first() : null)
            ?? abort(404);
    }

    public function quantities()
    {
        if ($this->stock_status == 'OUT_STOCK') {
            $this->hasMany(Quantity::class, 'product_id', 'idd');
        }

        return $this->hasMany(Quantity::class);
    }

    public function availableQuantities()
    {
        return $this->hasMany(Quantity::class)->available()->orderBy('id');
    }

    public function scrappedQuantities()
    {
        return $this->hasMany(Quantity::class)->scrapped()->orderBy('id');
    }

    public function soldQuantities()
    {
        return $this->hasMany(Quantity::class)->sold()->orderBy('id');
    }

    public function totalOrderedCount(): int
    {
        return (int) ($this->attributes['total_ordered_count']
            ?? ($this->relationLoaded('quantities') ? $this->quantities->count() : $this->quantities()->count()));
    }

    public function scrappedPiecesCount(): int
    {
        return (int) ($this->attributes['scrapped_pieces_count']
            ?? ($this->relationLoaded('quantities') ? $this->quantities->filter->isScrapped()->count() : $this->scrappedQuantities()->count()));
    }

    public function soldPiecesCount(): int
    {
        return (int) ($this->attributes['sold_pieces_count']
            ?? ($this->relationLoaded('quantities') ? $this->quantities->filter->isSold()->count() : $this->soldQuantities()->count()));
    }

    public function firstAvailableQuantity(): ?Quantity
    {
        if ($this->relationLoaded('availableQuantities')) {
            return $this->availableQuantities->first();
        }

        return $this->availableQuantities()->first();
    }

    public function lowestAvailablePrice(): int
    {
        $piece = $this->firstAvailableQuantity();
        if ($piece !== null) {
            return (int) $piece->price;
        }

        return (int) ($this->price ?? 0);
    }

    public function discounts()
    {
        return $this->hasMany(Discount::class, 'product_id', 'id');
    }

    public function activeDiscounts()
    {
        return $this->hasMany(Discount::class, 'product_id', 'id')
            ->where(function ($query) {
                $query->where('expire', '>=', date('Y-m-d'))
                    ->orWhereNull('expire');
            });
    }

    public function quesions()
    {
        return $this->hasMany(Question::class);
    }

    public function hasDiscount()
    {
        if (! $this->isAvailable()) {
            return false;
        }

        if ($this->relationLoaded('activeDiscounts')) {
            return $this->activeDiscounts->isNotEmpty();
        }

        return $this->discounts()
            ->where(function ($query) {
                $query->where('expire', '>=', date('Y-m-d'))
                    ->orWhereNull('expire');
            })->count() > 0;
    }

    public function imgUrl()
    {
        if ($this->getMedia()->count() > 0) {
            return $this->getMedia()[$this->image_index]->getUrl('product-image');
        } else {
            return asset('assets/upload/logo.svg');
        }
    }

    public function originalImageUrl()
    {
        if ($this->getMedia()->count() > 0) {
            return $this->getMedia()[$this->image_index]->getUrl();
        } else {
            return asset('assets/upload/logo.svg');
        }
    }

    public function originalOptimizedImageUrl()
    {
        if ($this->getMedia()->count() > 0) {
            return $this->getMedia()[$this->image_index]->getUrl('product-optimized');
        } else {
            return asset('assets/upload/logo.svg');
        }
    }

    public function imgUrl2()
    {
        if ($this->getMedia()->count() > 0 && isset($this->getMedia()[1])) {
            return $this->getMedia()[1]->getUrl('product-image');
        } else {
            return asset('assets/upload/logo.svg');
        }
    }

    public function thumbUrl()
    {
        if ($this->getMedia()->count() > 0) {
            return $this->getMedia()[$this->image_index]->getUrl('product-thumb');
        } else {
            return asset('assets/upload/logo.svg');
        }
    }

    public function thumbUrl2()
    {
        if ($this->getMedia()->count() > 0 && isset($this->getMedia()[1])) {
            return $this->getMedia()[1]->getUrl('product-thumb');
        } else {
            return asset('assets/upload/logo.svg');
        }
    }

    public function fullMeta($limit = 99)
    {
        $metas = $this->getAllMeta()->toArray();
        $result = [];
        $i = 0;
        foreach ($metas as $key => $value) {
            if ($result[$key]['data']['type'] ?? null == null) {
                continue;
            }
            $result[$key] = [
                'value' => $value,
                'data' => Prop::where('name', $key)->first(),
            ];
            switch ($result[$key]['data']['type']) {
                case 'color':
                    $result[$key]['human_value'] = "<div style='background:  $value' class='color-bullet'> &nbsp; </div>";
                    break;
                case 'checkbox':
                    $result[$key]['human_value'] = $value ? '<i class="ri-checkbox-circle-line"></i>' : '<i class="ri-close-circle-line"></i>';
                    break;
                case 'select':
                case 'singlemulti':
                    if (! is_array($value)) {
                        if (isset($result[$key]['data']->datas[$value])) {

                            $result[$key]['human_value'] =
                                $result[$key]['data']->datas[$value];
                        } else {
                            $result[$key]['human_value'] = '-';
                        }
                    } else {
                        $result[$key]['human_value'] = '';
                        foreach ($value as $k => $v) {
                            $result[$key]['human_value'] = $result[$key]['data']->datas[$v].', ';
                        }
                        $result[$key]['human_value'] = trim($result[$key]['human_value'], ' ,');
                    }
                    break;
                default:
                    if (is_array($value)) {
                        $result[$key]['human_value'] = '<span class="meta-tag">'.implode('</span> <span class="meta-tag">', $value).'</span>';
                    } else {
                        if ($value == '' || $value == null) {
                            $result[$key]['human_value'] = '-';
                        } else {
                            $result[$key]['human_value'] = $value;
                        }
                    }
            }

            $result[$key]['human_value'] .= ' '.$result[$key]['data']['unit'];
        }

        usort($result, function ($a, $b) {
            return $a['data']['sort'] - $b['data']['sort'];
        });

        $result = array_slice($result, 0, $limit);

        return $result;
    }

    public function webUrl()
    {
        return fixUrlLang(route('client.product', $this->slug));
    }

    public function getPrice()
    {
        $price = $this->lowestAvailablePrice();

        if (! $this->isAvailable()) {
            return __('Unavailable');
        }

        if ($this->hasDiscount()) {
            $d = $this->relationLoaded('activeDiscounts')
                ? $this->activeDiscounts->first()
                : $this->activeDiscounts()->first();
            if ($d) {
                if ($d->type == 'PRICE') {
                    $price -= $d->amount;
                } else {
                    $price = ((100 - $d->amount) * $price) / 100;
                }
            }
        }

        if ($price == 0 || $price == '' || $price == null) {
            return __('Call us!');
        }

        return number_format($price).' '.config('app.currency.symbol');
    }

    public function oldPricePure()
    {
        $price = $this->lowestAvailablePrice();

        if ($price == 0 || $price == '' || $price == null) {
            return __('Call us!');
        }

        return $price;
    }

    public function oldPrice()
    {
        $price = $this->lowestAvailablePrice();

        if ($price == 0 || $price == '' || $price == null) {
            return __('Call us!');
        }

        return number_format($price).' '.config('app.currency.symbol');
    }

    public function isFav(): int
    {
        if (auth('customer')->check()) {
            return isset(auth('customer')->user()->favoriteProductIds()[$this->id]) ? 1 : 0;
        }

        if (auth('web')->check()) {
            return isset(auth('web')->user()->favoriteProductIds()[$this->id]) ? 1 : 0;
        }

        return -1;
    }

    public function isLiked(): int
    {
        return $this->isFav();
    }

    public function isBookmarked(): int
    {
        if (auth('customer')->check()) {
            return isset(auth('customer')->user()->bookmarkProductIds()[$this->id]) ? 1 : 0;
        }

        if (auth('web')->check()) {
            return isset(auth('web')->user()->bookmarkProductIds()[$this->id]) ? 1 : 0;
        }

        return -1;
    }

    public function isAvailable()
    {
        if ($this->stock_quantity == 0) {
            return false;
        }

        if ($this->stock_status != 'IN_STOCK') {
            return false;
        }

        return true;
    }

    public function markup()
    {

        $currency = config('app.currency.code');
        $reviews = CommentMarkupCollection::collection($this->approvedComments)->toJson();

        return <<<RESULT
<script type="application/ld+json">
{
  "@context": "https://schema.org/",
  "@type": "Product",
  "name": "name",
  "image": "{$this->name}",
  "description": "{$this->excerpt}",
  "brand": {
    "@type": "Brand",
    "name": "{$this->category->name}"
  },
  "sku": "{$this->sku}",
  "offers": {
    "@type": "Offer",
    "url": "{$this->webUrl()}",
    "priceCurrency": "$currency",
    "price": "{{$this->price}}"
  },
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": "{$this->average_rating}",
    "ratingCount": "{$this->rating_count}",
    "reviewCount": "{$this->approvedComments()->count()}"
  },
  "review": $reviews
}
</script>
RESULT;

    }

    public function seoDesc()
    {
        $template = getSetting('product_description');
        if ($template == null || $template == '') {
            $template = __('%name% sale in our shop by %price% %category.name%');
        }
        $template = str_replace('%name%', $this->name, $template);
        $template = str_replace('%price%', $this->getPrice(), $template);
        $template = str_replace('%excerpt%', $this->excerpt, $template);
        $template = str_replace('%stock_quantity%', $this->stock_quantity, $template);
        $template = str_replace('%category.name%', $this->category->name, $template);

        return $template;

    }

    public function tagsList()
    {
        if ($this->tags()->count() == 0) {
            return getSetting('keyword');
        } else {
            return implode(',', $this->tags()->pluck('name')->toArray());
        }
    }

    public function evaluations()
    {

        return Evaluation::where(function ($query) {
            $query->whereNull('evaluationable_type')
                ->whereNull('evaluationable_id');
        })->orWhere(function ($query) {
            $query->where('evaluationable_type', Product::class)
                ->whereNull('evaluationable_id');
        })->orWhere(function ($query) {
            $query->where('evaluationable_type', Product::class)
                ->where('evaluationable_id', $this->id);
        })->orWhere(function ($query) {
            $query->where('evaluationable_type', Category::class)
                ->where('evaluationable_id', $this->category_id);
        })->get();
    }

    public static function platingColorOptions(): array
    {
        return [
            'yellow_gold' => __('Yellow Gold'),
            'rose_gold' => __('Rose Gold'),
            'silver_yellow_gold_plated' => __('Silver Yellow Gold Plated'),
            'silver_rose_gold_plated' => __('Silver Rose Gold Plated'),
            'silver_white_gold_plated' => __('Silver White Gold Plated'),
        ];
    }

    public static function stoneOptions(): array
    {
        return [
            'none' => __('None'),
            'diamond' => __('Diamond'),
            'pearl' => __('Pearl'),
            'crystal' => __('Crystal'),
            'agate' => __('Agate'),
            'turquoise' => __('Turquoise'),
            'zirconium' => __('Zirconium'),
            'amethyst' => __('Amethyst'),
            'onyx' => __('Onyx'),
            'opal' => __('Opal'),
            'jade' => __('Jade'),
            'lapis_lazuli' => __('Lapis Lazuli'),
            'quartz' => __('Quartz'),
            'coral' => __('Coral'),
            'shell' => __('Shell'),
        ];
    }

    public static function accessoryOptions(): array
    {
        return [
            'none' => __('None'),
            'leather_bracelet' => __('Leather Bracelet'),
        ];
    }

    public static function occasionOptions(): array
    {
        return [
            'valentine' => __('Valentine'),
            'mothers_day' => __("Mother's Day"),
            'girls_day' => __("Girl's Day"),
            'womens_day' => __("Women's Day"),
            'birthday' => __('Birthday'),
            'anniversary' => __('Anniversary'),
            'yalda' => __('Yalda'),
            'wedding' => __('Wedding'),
        ];
    }

    public function getPlatingColorLabels(): array
    {
        $options = static::platingColorOptions();
        $selected = is_array($this->plating_colors) ? $this->plating_colors : [];

        return array_values(array_filter(array_map(fn ($key) => $options[$key] ?? null, $selected)));
    }

    public function getStoneLabels(): array
    {
        $options = static::stoneOptions();
        $selected = is_array($this->stones) ? $this->stones : [];

        return array_values(array_filter(array_map(fn ($key) => $options[$key] ?? null, $selected)));
    }

    public function getAccessoryLabels(): array
    {
        $options = static::accessoryOptions();
        $selected = is_array($this->accessories) ? $this->accessories : [];

        return array_values(array_filter(array_map(fn ($key) => $options[$key] ?? null, $selected)));
    }

    public function getOccasionLabels(): array
    {
        $options = static::occasionOptions();
        $selected = is_array($this->occasions) ? $this->occasions : [];

        return array_values(array_filter(array_map(fn ($key) => $options[$key] ?? null, $selected)));
    }

    public function scopeWithPlatingColor($query, string $color)
    {
        return $query->whereJsonContains('plating_colors', $color);
    }

    public function scopeWithStone($query, string $stone)
    {
        return $query->whereJsonContains('stones', $stone);
    }

    public function scopeWithAccessory($query, string $accessory)
    {
        return $query->whereJsonContains('accessories', $accessory);
    }

    public function scopeWithOccasion($query, string $occasion)
    {
        return $query->whereJsonContains('occasions', $occasion);
    }
}
