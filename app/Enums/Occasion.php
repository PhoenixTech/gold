<?php

namespace App\Enums;

/**
 * The "مناسبت‌ها" taxonomy of the shop.
 *
 * A single source of truth shared by three consumers:
 *   - the product form checkbox grid (`product-step1.blade.php`)
 *   - the storefront catalog filter (`?occasion=…`)
 *   - the home page campaign slot, which auto-fills its box with every
 *     published product carrying any of the selected occasions.
 */
enum Occasion: string
{
    case Valentine = 'valentine';
    case MothersDay = 'mothers_day';
    case GirlsDay = 'girls_day';
    case WomensDay = 'womens_day';
    case Birthday = 'birthday';
    case Anniversary = 'anniversary';
    case Yalda = 'yalda';
    case Wedding = 'wedding';
    case BlackFriday = 'black_friday';
    case Christmas = 'christmas';
    case Nowruz = 'nowruz';
    case FathersDay = 'fathers_day';

    /**
     * Whether the occasion is a retail event rather than a personal milestone.
     *
     * The tile styles these more urgently. The enum deliberately does not know
     * about CSS: only the taxonomy fact lives here, the class name stays in the
     * view.
     */
    public function isSeasonal(): bool
    {
        return match ($this) {
            self::BlackFriday, self::Christmas, self::Nowruz => true,
            default => false,
        };
    }

    /**
     * The human readable label.
     *
     * The translation key is deliberately NOT the enum value: `fa.json` is keyed
     * by human readable English ("Valentine", "Black Friday"), and matching
     * those keys keeps the existing 8 translations working unchanged.
     */
    public function label(): string
    {
        return match ($this) {
            self::Valentine => __('Valentine'),
            self::MothersDay => __("Mother's Day"),
            self::GirlsDay => __("Girl's Day"),
            self::WomensDay => __("Women's Day"),
            self::Birthday => __('Birthday'),
            self::Anniversary => __('Anniversary'),
            self::Yalda => __('Yalda'),
            self::Wedding => __('Wedding'),
            self::BlackFriday => __('Black Friday'),
            self::Christmas => __('Christmas'),
            self::Nowruz => __('Nowruz'),
            self::FathersDay => __('Fathers Day'),
        };
    }

    /**
     * @return array<string, string> value => translated label
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Normalise an arbitrary list of occasion keys into a de-duplicated list of
     * enums, silently dropping unknown keys so a stale stored value can never
     * break a storefront query.
     *
     * @return list<self>
     */
    public static function parse(mixed $values): array
    {
        if ($values instanceof self) {
            return [$values];
        }

        if (is_string($values)) {
            $decoded = json_decode($values, true);
            $values = is_array($decoded) ? $decoded : [$values];
        }

        if (! is_iterable($values)) {
            return [];
        }

        $resolved = [];

        foreach ($values as $value) {
            $case = $value instanceof self ? $value : (self::tryFrom((string) $value));

            if ($case instanceof self) {
                $resolved[$case->value] = $case;
            }
        }

        return array_values($resolved);
    }

    /**
     * @return list<string>
     */
    public static function normalize(mixed $values): array
    {
        return array_map(static fn (self $case) => $case->value, self::parse($values));
    }
}
