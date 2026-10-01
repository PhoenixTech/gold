<?php

namespace App\Support;

/**
 * Persian amount-in-words.
 *
 * Iranian legal invoices conventionally state the total in words as well as
 * digits, so the printable invoice needs this alongside the numeric figure.
 */
class PersianNumberToWords
{
    /** @var array<int, string> */
    private const ONES = [
        0 => '', 1 => 'یک', 2 => 'دو', 3 => 'سه', 4 => 'چهار', 5 => 'پنج',
        6 => 'شش', 7 => 'هفت', 8 => 'هشت', 9 => 'نه',
    ];

    /** @var array<int, string> */
    private const TEENS = [
        10 => 'ده', 11 => 'یازده', 12 => 'دوازده', 13 => 'سیزده', 14 => 'چهارده',
        15 => 'پانزده', 16 => 'شانزده', 17 => 'هفده', 18 => 'هجده', 19 => 'نوزده',
    ];

    /** @var array<int, string> */
    private const TENS = [
        2 => 'بیست', 3 => 'سی', 4 => 'چهل', 5 => 'پنجاه',
        6 => 'شصت', 7 => 'هفتاد', 8 => 'هشتاد', 9 => 'نود',
    ];

    /** @var array<int, string> */
    private const HUNDREDS = [
        1 => 'صد',
        2 => 'دویست',
        3 => 'سیصد',
        4 => 'چهارصد',
        5 => 'پانصد',
        6 => 'ششصد',
        7 => 'هفتصد',
        8 => 'هشتصد',
        9 => 'نهصد',
    ];

    /**
     * Group labels, indexed by the triple's position: 0 = units, 1 = thousands,
     * 2 = millions, 3 = milliards.
     *
     * @var array<int, string>
     */
    private const SCALES = [
        0 => '',
        1 => 'هزار',
        2 => 'میلیون',
        3 => 'میلیارد',
    ];

    /**
     * @param  int|string  $number
     */
    public static function toWords($number): string
    {
        $original = (int) $number;

        if ($original === 0) {
            return 'صفر';
        }

        $negative = $original < 0;
        $remaining = abs($original);

        $triples = [];
        while ($remaining > 0) {
            $triples[] = $remaining % 1000;
            $remaining = intdiv($remaining, 1000);
        }

        // The table covers units .. milliards (four groups). Beyond that there is
        // no meaningful Persian scale word, so fall back to the digits rather
        // than truncating the amount.
        if (count($triples) > 4) {
            return number_format($original);
        }

        $parts = [];
        for ($i = count($triples) - 1; $i >= 0; $i--) {
            if ($triples[$i] === 0) {
                continue;
            }

            $parts[] = trim(self::tripleToWords($triples[$i]).' '.self::SCALES[$i]);
        }

        $words = implode(' و ', array_filter($parts));

        return $negative ? 'منفی '.$words : $words;
    }

    private static function tripleToWords(int $number): string
    {
        $parts = [];

        $hundreds = intdiv($number, 100);
        $remainder = $number % 100;

        if ($hundreds > 0) {
            $parts[] = self::HUNDREDS[$hundreds];
        }

        if ($remainder >= 10 && $remainder < 20) {
            $parts[] = self::TEENS[$remainder];
        } else {
            $tens = intdiv($remainder, 10);
            $ones = $remainder % 10;

            if ($tens > 0) {
                $parts[] = self::TENS[$tens];
            }
            if ($ones > 0) {
                $parts[] = self::ONES[$ones];
            }
        }

        return implode(' و ', $parts);
    }
}
