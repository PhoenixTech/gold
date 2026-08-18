<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Part;
use Illuminate\Database\Seeder;

class PartSeeder extends Seeder
{
    /**
     * Active theme parts used by this shop (matches the live `parts` table).
     *
     * @return list<array{area: string, segment: string, part: string, sort: int}>
     */
    private function parts(): array
    {
        return [
            ['area' => 'defaultHeader', 'segment' => 'menu', 'part' => 'AplMenu', 'sort' => 0],
            ['area' => 'defaultHeader', 'segment' => 'header', 'part' => 'ParallaxHeader', 'sort' => 1],
            ['area' => 'defaultFooter', 'segment' => 'footer', 'part' => 'TypicalFooter', 'sort' => 0],
            ['area' => 'index', 'segment' => 'menu', 'part' => 'ZarMenu', 'sort' => 0],
            ['area' => 'index', 'segment' => 'index', 'part' => 'WTFIndex', 'sort' => 1],
            ['area' => 'index', 'segment' => 'index', 'part' => 'Natalia2Categories', 'sort' => 2],
            ['area' => 'index', 'segment' => 'index', 'part' => 'NeginNews', 'sort' => 3],
            ['area' => 'index', 'segment' => 'index', 'part' => 'BottomBar', 'sort' => 4],
            ['area' => 'index', 'segment' => 'footer', 'part' => 'WTFFooter', 'sort' => 5],
            ['area' => 'post', 'segment' => 'post', 'part' => 'PostSidebar', 'sort' => 0],
            ['area' => 'post', 'segment' => 'attachments', 'part' => 'SimpleAttachmentList', 'sort' => 1],
            ['area' => 'posts-list', 'segment' => 'posts_page', 'part' => 'GridPostListSidebar', 'sort' => 1],
            ['area' => 'clip', 'segment' => 'clip', 'part' => 'DorClip', 'sort' => 1],
            ['area' => 'clips-list', 'segment' => 'clips_page', 'part' => 'ClipListGrid', 'sort' => 1],
            ['area' => 'gallery', 'segment' => 'gallery', 'part' => 'GallaryGrid', 'sort' => 1],
            ['area' => 'galleries-list', 'segment' => 'galleries_page', 'part' => 'GalleriesList', 'sort' => 1],
            ['area' => 'product', 'segment' => 'product', 'part' => 'ProductAria', 'sort' => 0],
            ['area' => 'products-list', 'segment' => 'products_page', 'part' => 'ProductGridSidebar', 'sort' => 1],
            ['area' => 'attachment', 'segment' => 'attachment', 'part' => 'AttachmentWithPreview', 'sort' => 1],
            ['area' => 'attachments-list', 'segment' => 'attachments_page', 'part' => 'DenaAttachList', 'sort' => 1],
            ['area' => 'category', 'segment' => 'category', 'part' => 'SubCategoriesGrid', 'sort' => 0],
            ['area' => 'category', 'segment' => 'products_page', 'part' => 'ProductGridHiddenSidebar', 'sort' => 1],
            ['area' => 'category', 'segment' => 'category', 'part' => 'ParallelCategoriesGrid', 'sort' => 2],
            ['area' => 'group', 'segment' => 'posts_page', 'part' => 'GridPostListSidebar', 'sort' => 1],
            ['area' => 'card', 'segment' => 'card', 'part' => 'NsCard', 'sort' => 1],
            ['area' => 'login', 'segment' => 'login', 'part' => 'LoginPatternBg', 'sort' => 1],
            ['area' => 'register', 'segment' => 'register', 'part' => 'SimpleRegister', 'sort' => 1],
            ['area' => 'customer', 'segment' => 'customer', 'part' => 'AvisaCustomer', 'sort' => 1],
            ['area' => 'invoice', 'segment' => 'invoice', 'part' => 'LianaInvoice', 'sort' => 1],
            ['area' => 'compare', 'segment' => 'compare', 'part' => 'CompareProducts', 'sort' => 1],
            ['area' => 'contact-us', 'segment' => 'contact', 'part' => 'MeloContact', 'sort' => 1],
            ['area' => 'product-grid', 'segment' => 'product_grid', 'part' => 'ShivaProductGrid', 'sort' => 0],
        ];
    }

    public function run(): void
    {
        foreach ($this->parts() as $row) {
            $area = Area::query()->where('name', $row['area'])->first();
            if ($area === null) {
                continue;
            }

            $part = new Part;
            $part->segment = $row['segment'];
            $part->part = $row['part'];
            $part->area_id = $area->id;
            $part->sort = $row['sort'];
            $part->save();
        }
    }
}
