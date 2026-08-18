<?php

namespace Tests\Feature;

use App\Models\Part;
use RuntimeException;
use Tests\TestCase;

class ThemePartClassLoadTest extends TestCase
{
    public function test_typical_footer_loads_from_segment_file_without_composer_classmap(): void
    {
        $part = new Part;
        $part->segment = 'footer';
        $part->part = 'TypicalFooter';

        $result = $part->getBladeWithData();

        $this->assertSame('segments.footer.TypicalFooter.TypicalFooter', $result['blade']);
        $this->assertSame($part, $result['data']);
        $this->assertSame(
            \Resources\Views\Segments\TypicalFooter::class,
            $part->handleClass()
        );
        $this->assertTrue(class_exists(\Resources\Views\Segments\TypicalFooter::class, false));
    }

    public function test_missing_theme_part_class_file_throws(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Theme part class file not found: footer/MissingFooter');

        Part::segmentClass('footer', 'MissingFooter');
    }
}
