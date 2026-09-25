<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Services\SlugService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SlugServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_unique_slug_incrementing_on_collision(): void
    {
        Category::create(['name' => 'Gold Coin', 'slug' => 'gold-coin']);

        $service = new SlugService;
        $slug1 = $service->makeUnique(Category::class, 'Gold Coin');
        $this->assertEquals('gold-coin-1', $slug1);

        Category::create(['name' => 'Gold Coin 1', 'slug' => 'gold-coin-1']);
        $slug2 = $service->makeUnique(Category::class, 'Gold Coin');
        $this->assertEquals('gold-coin-2', $slug2);
    }

    public function test_it_ignores_own_id_when_updating(): void
    {
        $cat = Category::create(['name' => 'Gold Coin', 'slug' => 'gold-coin']);

        $service = new SlugService;
        $slug = $service->makeUnique(Category::class, 'Gold Coin', $cat->id);
        $this->assertEquals('gold-coin', $slug);
    }
}
