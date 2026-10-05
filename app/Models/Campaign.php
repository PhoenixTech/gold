<?php

namespace App\Models;

use App\Enums\CampaignStatus;
use App\Enums\MetalType;
use App\Enums\Occasion;
use App\Services\CampaignCache;
use App\Services\CampaignService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Spatie\Translatable\HasTranslations;

/**
 * A scheduled collection that fills the 12th cell of the home page category
 * grid.
 *
 * A campaign has exactly one destination: its own product page, served by the
 * catalog route (`client.products`). There is deliberately no link-type concept:
 * the campaign page renders the exact combined set, so any other target would
 * advertise something different from the tile.
 */
class Campaign extends Model
{
    use HasFactory, HasTranslations, SoftDeletes;

    protected $guarded = [];

    public $translatable = ['name', 'subtitle', 'description', 'badge_text'];

    protected $casts = [
        'status' => CampaignStatus::class,
        'occasions' => 'array',
        'metal_scope' => 'array',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'priority' => 'integer',
        'limit' => 'integer',
    ];

    protected static function booted(): void
    {
        // Any campaign edit can change which products fill its tile.
        static::saved(static fn () => CampaignCache::flush());

        static::deleted(static fn () => CampaignCache::flush());
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    /**
     * The include/exclude rows. Read and written collectively through
     * {@see CampaignProductSource} and {@see CampaignService},
     * so this is a plain hasMany rather than two filtered belongsToMany
     * shortcuts nobody asked for.
     */
    public function productLinks(): HasMany
    {
        return $this->hasMany(CampaignProduct::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', CampaignStatus::Published->value);
    }

    /**
     * Inside the schedule window. A null `starts_at` means "as soon as it is
     * published" and a null `ends_at` means "no end date". The end bound is
     * exclusive so the tile disappears at the exact moment it expires, with no
     * scheduled job.
     */
    public function scopeLive(Builder $query): Builder
    {
        $now = now();

        return $query
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', $now));
    }

    /*
    |--------------------------------------------------------------------------
    | State
    |--------------------------------------------------------------------------
    */

    public function isLive(): bool
    {
        if ($this->status !== CampaignStatus::Published) {
            return false;
        }

        if ($this->starts_at !== null && $this->starts_at->isFuture()) {
            return false;
        }

        return $this->ends_at === null || $this->ends_at->isFuture();
    }

    public function hasStarted(): bool
    {
        return $this->starts_at !== null && $this->starts_at->isPast();
    }

    public function appliesToMetal(string $metal): bool
    {
        // No explicit scope means the campaign may appear on every tab.
        if ($this->metal_scope === null || $this->metal_scope === []) {
            return true;
        }

        return in_array($metal, $this->metal_scope, true);
    }

    /**
     * @return list<Occasion>
     */
    public function occasionList(): array
    {
        return Occasion::parse($this->occasions);
    }

    public function primaryOccasion(): ?Occasion
    {
        return $this->occasionList()[0] ?? null;
    }

    /*
    |--------------------------------------------------------------------------
    | Admin list labels
    |--------------------------------------------------------------------------
    | "Live" / "Scheduled" / "Expired" are derived from the schedule instead of
    | being stored, so they can never drift out of sync with the dates.
    */

    public function statusLabel(): string
    {
        return match (true) {
            $this->status !== CampaignStatus::Published => $this->status->label(),
            $this->isLive() => __('Live'),
            $this->hasStarted() => __('Expired'),
            default => __('Scheduled'),
        };
    }

    public function statusBadgeClass(): string
    {
        return match (true) {
            $this->status !== CampaignStatus::Published => $this->status->badgeClass(),
            $this->isLive() => 'badge bg-success text-white',
            $this->hasStarted() => 'badge bg-warning-subtle text-warning-emphasis border border-warning-subtle',
            default => 'badge bg-info-subtle text-info-emphasis border border-info-subtle',
        };
    }

    /**
     * A Jalali schedule summary, e.g. "۱۴۰۴/۰۸/۱۹ → ۱۴۰۴/۰۸/۲۹".
     */
    public function scheduleLabel(): string
    {
        $start = $this->starts_at?->ldate('Y/m/d');
        $end = $this->ends_at?->ldate('Y/m/d');

        return match (true) {
            $start !== null && $end !== null => $start.' → '.$end,
            $start !== null => __('From :date', ['date' => $start]),
            $end !== null => __('Until :date', ['date' => $end]),
            default => __('Always on'),
        };
    }

    /**
     * @return list<string>
     */
    public function metalLabels(): array
    {
        $scope = $this->metal_scope ?: MetalType::values();

        return array_values(array_filter(array_map(
            static fn (string $metal) => MetalType::tryFrom($metal)?->label(),
            $scope
        )));
    }

    /**
     * @return list<string>
     */
    public function occasionLabels(): array
    {
        return array_map(static fn (Occasion $occasion) => $occasion->label(), $this->occasionList());
    }

    /*
    |--------------------------------------------------------------------------
    | Media & links
    |--------------------------------------------------------------------------
    */

    public function imgUrl(): ?string
    {
        return $this->image ? Storage::url('campaigns/optimized-'.$this->image) : null;
    }

    public function imgOriginalUrl(): ?string
    {
        return $this->image ? Storage::url($this->image) : null;
    }

    public function mobileImgUrl(): ?string
    {
        if (! $this->mobile_image) {
            return $this->imgUrl();
        }

        return Storage::url('campaigns/optimized-'.$this->mobile_image);
    }

    public function hasImage(): bool
    {
        return filled($this->image) || filled($this->mobile_image);
    }

    /**
     * The campaign product page. The metal is passed through so the tile on the
     * silver tab lands on silver products rather than the catalog default.
     */
    public function url(?string $metal = null): string
    {
        return route('client.products', array_filter([
            'campaign' => $this->slug,
            'metal' => $metal,
        ]));
    }

    /*
    |--------------------------------------------------------------------------
    | Slot collisions
    |--------------------------------------------------------------------------
    */

    /**
     * Published campaigns whose schedule overlaps this one and that outrank it,
     * i.e. the ones that will silently win the slot during the overlap.
     *
     * @return EloquentCollection<int, Campaign>
     */
    public function clashingCampaigns(): EloquentCollection
    {
        return static::query()
            ->published()
            ->whereKeyNot($this->getKey())
            ->where('priority', '>=', $this->priority)
            ->orderByDesc('priority')
            ->get()
            ->filter(fn (Campaign $other) => $this->overlaps($other))
            ->values();
    }

    /**
     * Schedule + metal overlap test. A null bound means "open ended", so an
     * endless campaign overlaps everything.
     */
    public function overlaps(self $other): bool
    {
        if (! $this->overlapsMetals($other)) {
            return false;
        }

        // $other must still be running when this one starts.
        if ($other->ends_at !== null && $this->starts_at !== null && $other->ends_at <= $this->starts_at) {
            return false;
        }

        // $other must start before this one ends.
        if ($other->starts_at !== null && $this->ends_at !== null && $other->starts_at >= $this->ends_at) {
            return false;
        }

        return true;
    }

    private function overlapsMetals(self $other): bool
    {
        $mine = $this->metal_scope ?: null;
        $theirs = $other->metal_scope ?: null;

        if ($mine === null || $theirs === null) {
            return true;
        }

        return array_intersect($mine, $theirs) !== [];
    }
}
