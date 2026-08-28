<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\Menu;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
            ['title' => ['fa' => 'طلا زنانه'], 'kind' => 'model', 'slug' => 'women-gold', 'sort' => 1],
            ['title' => ['fa' => 'طلا مردانه'], 'kind' => 'model', 'slug' => 'men-gold', 'sort' => 2],
            ['title' => ['fa' => 'طلا بچه‌گانه'], 'kind' => 'model', 'slug' => 'children-gold', 'sort' => 3],
            ['title' => ['fa' => 'نقره زنانه'], 'kind' => 'model', 'slug' => 'women-silver', 'sort' => 4],
            ['title' => ['fa' => 'نقره مردانه'], 'kind' => 'model', 'slug' => 'men-silver', 'sort' => 5],
            ['title' => ['fa' => 'نقره بچه‌گانه'], 'kind' => 'model', 'slug' => 'children-silver', 'sort' => 6],
            ['title' => ['fa' => 'ارتباط با ما'], 'kind' => 'direct', 'meta' => '/contact-us', 'sort' => 7],
        ];

        foreach ($itemsData as $data) {
            $item = new \App\Models\Item();
            $item->user_id = 1;
            $item->menu_id = $menu->id;
            $item->sort = $data['sort'];
            $item->kind = $data['kind'];
            $item->title = $data['title'];
            if ($data['kind'] === 'model') {
                $cat = \App\Models\Category::where('slug', $data['slug'])->first();
                if ($cat) {
                    $item->menuable_id = $cat->id;
                    $item->menuable_type = \App\Models\Category::class;
                    $item->meta = null;
                }
            } else {
                $item->meta = $data['meta'];
                $item->menuable_id = null;
                $item->menuable_type = null;
            }
            $item->save();
        }
    }
}
