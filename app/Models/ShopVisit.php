<?php

namespace App\Models;

use App\Enums\ShopVisitStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopVisit extends Model
{
    /** @use HasFactory<\Database\Factories\ShopVisitFactory> */
    use HasFactory;

    /**
     * @var array<string, string>
     */
    public const CATEGORIES = [
        'gold' => 'Gold',
        'silver' => 'Silver',
        'hallmarked' => 'Hallmarked',
        'licensed' => 'Licensed',
    ];

    /**
     * @var array<string, string>
     */
    public const WORK_STYLES = [
        'stone_set' => 'Stone-set',
        'flat' => 'Flat',
        'minimal' => 'Minimal',
    ];

    protected $fillable = [
        'user_id',
        'status',
        'mobile',
        'first_name',
        'last_name',
        'has_purchase',
        'has_own_workshop',
        'other_reason',
        'categories',
        'work_styles',
        'state_id',
        'city_id',
        'mall',
        'address',
        'submitted_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ShopVisitStatus::class,
            'has_purchase' => 'boolean',
            'has_own_workshop' => 'boolean',
            'categories' => 'array',
            'work_styles' => 'array',
            'submitted_at' => 'datetime',
        ];
    }

    /**
     * @return list<string>
     */
    public static function malls(): array
    {
        return [
            'بازار بزرگ تهران',
            'پاساژ افشار',
            'پاساژ طلافروشان',
            'بازار زرگرها',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', [
            ShopVisitStatus::Collecting,
            ShopVisitStatus::StepTwo,
        ]);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', ShopVisitStatus::Completed);
    }

    public function isCollecting(): bool
    {
        return $this->status === ShopVisitStatus::Collecting;
    }

    public function isAwaitingAddress(): bool
    {
        return $this->status === ShopVisitStatus::StepTwo;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            ShopVisitStatus::Collecting => __('Collecting'),
            ShopVisitStatus::StepTwo => __('Waiting for address'),
            ShopVisitStatus::Completed => __('Completed'),
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            ShopVisitStatus::Collecting => 'badge bg-secondary-subtle text-secondary border border-secondary-subtle',
            ShopVisitStatus::StepTwo => 'badge bg-warning-subtle text-warning border border-warning-subtle',
            ShopVisitStatus::Completed => 'badge bg-success-subtle text-success border border-success-subtle',
        };
    }

    public function purchaseLabel(): string
    {
        if ($this->has_purchase === null) {
            return '-';
        }

        return $this->has_purchase ? __('Yes') : __('No');
    }

    public function submittedAtLabel(): string
    {
        $date = $this->submitted_at ?? $this->created_at;
        if ($date->timestamp === 0) {
            return '-';
        }

        return $date->jdate('Y/m/d H:i');
    }

    /**
     * @return list<string>
     */
    public function categoryLabels(): array
    {
        $labels = [];
        foreach ($this->categories ?? [] as $key) {
            if (isset(self::CATEGORIES[$key])) {
                $labels[] = __(self::CATEGORIES[$key]);
            }
        }

        return $labels;
    }

    /**
     * @return list<string>
     */
    public function workStyleLabels(): array
    {
        $labels = [];
        foreach ($this->work_styles ?? [] as $key) {
            if (isset(self::WORK_STYLES[$key])) {
                $labels[] = __(self::WORK_STYLES[$key]);
            }
        }

        return $labels;
    }

    public static function tehranStateId(): ?int
    {
        return self::locationIdByName(State::query()->get(), 'تهران', 'Tehran');
    }

    public static function tehranCityId(?int $stateId = null): ?int
    {
        $stateId ??= self::tehranStateId();
        $query = City::query();
        if ($stateId !== null) {
            $query->where('state_id', $stateId);
        }

        return self::locationIdByName($query->get(), 'تهران', 'Tehran');
    }

    /**
     * @param  iterable<int, State|City>  $locations
     */
    private static function locationIdByName(iterable $locations, string $persian, string $english): ?int
    {
        foreach ($locations as $location) {
            $names = [
                $location->name,
                $location->getTranslation('name', 'fa', false),
                $location->getTranslation('name', 'en', false),
            ];
            foreach ($names as $name) {
                if (! is_string($name) || trim($name) === '') {
                    continue;
                }
                if (trim($name) === $persian || strcasecmp(trim($name), $english) === 0) {
                    return $location->id;
                }
            }
        }

        return null;
    }

    public static function toEnglishDigits(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $western = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];

        return str_replace($arabic, $western, str_replace($persian, $western, $value));
    }
}
