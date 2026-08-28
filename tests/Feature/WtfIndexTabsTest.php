<?php

namespace Tests\Feature;

use Tests\TestCase;

class WtfIndexTabsTest extends TestCase
{
    public function test_homepage_category_tabs_split_into_two_equal_columns_on_mobile(): void
    {
        $scss = file_get_contents(resource_path('sass/client/_home.scss'));
        $blade = file_get_contents(resource_path('views/client/home.blade.php'));

        $this->assertNotFalse($scss);
        $this->assertNotFalse($blade);
        $this->assertStringContainsString('id="wtf-main-btns"', $blade);
        $this->assertStringNotContainsString('overflow-auto', $blade);
        $this->assertStringContainsString('@media (max-width: 767.98px)', $scss);
        $this->assertStringContainsString('grid-template-columns: 1fr 1fr', $scss);
    }
}
