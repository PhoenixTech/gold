<?php

namespace Tests\Feature;

use Tests\TestCase;

class ActiveThemeSegmentsTest extends TestCase
{
    /**
     * Segment folders that belong to the live shop theme.
     *
     * @return list<string>
     */
    private function activeParts(): array
    {
        return [
            'attachment/AttachmentWithPreview',
            'attachments/SimpleAttachmentList',
            'attachments_page/DenaAttachList',
            'card/NsCard',
            'category/ParallelCategoriesGrid',
            'category/SubCategoriesGrid',
            'clip/DorClip',
            'clips_page/ClipListGrid',
            'compare/CompareProducts',
            'contact/MeloContact',
            'customer/AvisaCustomer',
            'footer/TypicalFooter',
            'footer/WTFFooter',
            'galleries_page/GalleriesList',
            'gallery/GallaryGrid',
            'header/ParallaxHeader',
            'index/BottomBar',
            'index/Natalia2Categories',
            'index/NeginNews',
            'index/WTFIndex',
            'invoice/LianaInvoice',
            'login/LoginPatternBg',
            'menu/AplMenu',
            'menu/ZarMenu',
            'post/PostSidebar',
            'posts_page/GridPostListSidebar',
            'product/ProductAria',
            'product_grid/ShivaProductGrid',
            'products_page/ProductGridHiddenSidebar',
            'products_page/ProductGridSidebar',
            'register/SimpleRegister',
        ];
    }

    public function test_only_active_theme_part_folders_remain(): void
    {
        $root = resource_path('views/segments');
        $found = [];

        foreach (glob($root.'/*', GLOB_ONLYDIR) ?: [] as $segmentDir) {
            $segment = basename($segmentDir);
            if ($segment === 'default-assets') {
                continue;
            }

            foreach (glob($segmentDir.'/*', GLOB_ONLYDIR) ?: [] as $partDir) {
                $found[] = $segment.'/'.basename($partDir);
            }
        }

        sort($found);
        $expected = $this->activeParts();
        sort($expected);

        $this->assertSame($expected, $found);
    }

    public function test_removed_catalog_parts_are_gone(): void
    {
        $this->assertDirectoryDoesNotExist(resource_path('views/segments/product/ProductKaren'));
        $this->assertDirectoryDoesNotExist(resource_path('views/segments/footer/WaveFooter'));
        $this->assertDirectoryDoesNotExist(resource_path('views/segments/index/AuthorSlider'));
        $this->assertDirectoryDoesNotExist(resource_path('views/segments/preloader'));
        $this->assertDirectoryExists(resource_path('views/segments/default-assets'));
        $this->assertFileExists(resource_path('views/segments/product/ProductAria/inc/comment-detail.blade.php'));
    }
}
