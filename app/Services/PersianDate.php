<?php

namespace App\Services;

use DateTimeInterface;
use IntlCalendar;
use IntlDateFormatter;

class PersianDate
{
    public static function format(DateTimeInterface|int $date, string $format = 'Y/m/d H:i', string $locale = 'en'): string
    {
        $timestamp = $date instanceof DateTimeInterface ? $date->getTimestamp() : $date;

        $patternMap = [
            'Y' => 'yyyy',
            'm' => 'MM',
            'd' => 'dd',
            'j' => 'd',
            'n' => 'M',
            'H' => 'HH',
            'i' => 'mm',
            's' => 'ss',
            'F' => 'MMMM',
        ];

        $intlPattern = strtr($format, $patternMap);
        $intlLocale = $locale === 'fa' ? 'fa_IR@calendar=persian' : 'en_US@calendar=persian';

        $formatter = new IntlDateFormatter(
            $intlLocale,
            IntlDateFormatter::FULL,
            IntlDateFormatter::NONE,
            'Asia/Tehran',
            IntlDateFormatter::TRADITIONAL,
            $intlPattern
        );

        return (string) $formatter->format($timestamp);
    }

    public static function toGregorian(int $jy, int $jm, int $jd): array
    {
        $calendar = IntlCalendar::createInstance('Asia/Tehran', 'fa_IR@calendar=persian');
        $calendar->set($jy, $jm - 1, $jd, 12, 0, 0);

        $timestamp = (int) ($calendar->getTime() / 1000);

        return [
            (int) date('Y', $timestamp),
            (int) date('m', $timestamp),
            (int) date('d', $timestamp),
        ];
    }
}
