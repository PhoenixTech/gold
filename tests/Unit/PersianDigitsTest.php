<?php

namespace Tests\Unit;

use Tests\TestCase;

class PersianDigitsTest extends TestCase
{
    public function test_it_converts_every_latin_digit(): void
    {
        $this->assertSame('۰۱۲۳۴۵۶۷۸۹', toPersianDigits('0123456789'));
    }

    public function test_it_keeps_thousand_separators_and_other_characters(): void
    {
        $this->assertSame('۶,۷۵۰,۰۰۰', toPersianDigits(number_format(6750000)));
    }

    public function test_it_accepts_numbers_and_keeps_decimals(): void
    {
        $this->assertSame('۱۲۳', toPersianDigits(123));
        $this->assertSame('۱۲.۵', toPersianDigits(12.5));
    }

    public function test_it_leaves_text_without_digits_untouched(): void
    {
        $this->assertSame('', toPersianDigits(null));
        $this->assertSame('Call us!', toPersianDigits('Call us!'));
    }
}
