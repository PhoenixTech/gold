<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Item;
use App\Models\Menu;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $menu = Menu::firstOrCreate(['name' => 'main-menu'], ['user_id' => 1]);
        $menu->items()->delete();

        $itemsData = [
            ['title' => ['fa' => 'خانه'], 'kind' => 'direct', 'meta' => '/', 'sort' => 0],
            ['title' => ['fa' => 'طلا زنانه'], 'kind' => 'direct', 'meta' => '/products?metal=gold&target_group=women', 'sort' => 1],
            ['title' => ['fa' => 'طلا مردانه'], 'kind' => 'direct', 'meta' => '/products?metal=gold&target_group=men', 'sort' => 2],
            ['title' => ['fa' => 'طلا بچه‌گانه'], 'kind' => 'direct', 'meta' => '/products?metal=gold&target_group=children', 'sort' => 3],
            ['title' => ['fa' => 'نقره زنانه'], 'kind' => 'direct', 'meta' => '/products?metal=silver&target_group=women', 'sort' => 4],
            ['title' => ['fa' => 'نقره مردانه'], 'kind' => 'direct', 'meta' => '/products?metal=silver&target_group=men', 'sort' => 5],
            ['title' => ['fa' => 'نقره بچه‌گانه'], 'kind' => 'direct', 'meta' => '/products?metal=silver&target_group=children', 'sort' => 6],
            ['title' => ['fa' => 'ارتباط با ما'], 'kind' => 'direct', 'meta' => '/contact-us', 'sort' => 7],
        ];

        foreach ($itemsData as $data) {
            $item = new Item;
            $item->user_id = 1;
            $item->menu_id = $menu->id;
            $item->sort = $data['sort'];
            $item->kind = $data['kind'];
            $item->title = $data['title'];
            $item->meta = $data['meta'];
            $item->menuable_id = null;
            $item->menuable_type = null;
            $item->save();
        }
    }
}
