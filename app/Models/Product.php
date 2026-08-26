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
    ];

    protected static function booted()
    {
        static::saving(function (Product $product) {
            if ($product->category_id) {
                $product->sku = static::generateSku(
                    $product->target_group,
                    $product->metal_type,
                    $product->category_id,
                    $product->id
                );
            }
        });
    }

    public static function generateSku(?string $targetGroup, ?string $metalType, ?int $categoryId, ?int $productId = null): string
    {
        $targets = ['women' => 'F', 'female' => 'F', 'f' => 'F', 'men' => 'M', 'male' => 'M', 'm' => 'M', 'children' => 'C', 'child' => 'C', 'c' => 'C'];
        $t = $targets[strtolower((string) $targetGroup)] ?? 'U';
        $m = strtolower((string) $metalType) === 'silver' ? 'S' : 'G';
        $c = sprintf('%02d', (int) $categoryId);

        if ($productId) {
            $count = self::where('category_id', $categoryId)
                ->where('id', '<=', $productId)
                ->count();
            $count = max(1, $count);
        } else {
            $count = self::where('category_id', $categoryId)->count() + 1;
        }

        $n = sprintf('%04d', $count);

        return "{$t}{$m}{$c}{$n}";
    }

    public function attachs()
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    protected $guarded = [];

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

    public function quantities()
    {
        if ($this->stock_status == 'OUT_STOCK') {
            $this->hasMany(Quantity::class, 'product_id', 'idd');
        }

        return $this->hasMany(Quantity::class);
    }

    public function availableQuantities()
    {
        return $this->hasMany(Quantity::class)->where('count', '>', 0)->orderBy('id');
    }

    public function firstAvailableQuantity(): ?Quantity
    {
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
            $d = $this->activeDiscounts()->first();
            if ($d->type == 'PRICE') {
                $price -= $d->amount;
            } else {
                $price = ((100 - $d->amount) * $price) / 100;
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
            return auth('customer')->user()->favorites()->where('product_id', $this->id)->exists() ? 1 : 0;
        }

        if (auth('web')->check()) {
            return auth('web')->user()->favorites()->where('product_id', $this->id)->exists() ? 1 : 0;
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
            return auth('customer')->user()->bookmarks()->where('product_id', $this->id)->exists() ? 1 : 0;
        }

        if (auth('web')->check()) {
            return auth('web')->user()->bookmarks()->where('product_id', $this->id)->exists() ? 1 : 0;
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
   "interactionStatistic": {
    "@type": "InteractionCounter",
    "interactionType": "http://schema.org/PlayAction",
    "userInteractionCount": {$this->view}
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
}
