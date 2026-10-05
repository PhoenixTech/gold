<?php

namespace App\Models;

use App\Enums\CampaignProductRole;
use App\Services\CampaignCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignProduct extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * Singular table name, matching the `category_product` pivot convention of
     * this project (Laravel would otherwise infer `campaign_products`).
     */
    protected $table = 'campaign_product';

    protected $casts = [
        'role' => CampaignProductRole::class,
        'sort' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saved(static fn () => CampaignCache::flush());

        static::deleted(static fn () => CampaignCache::flush());
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
