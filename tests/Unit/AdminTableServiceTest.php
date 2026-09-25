<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Services\Admin\AdminTableService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class AdminTableServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sorts_records_by_allowed_column(): void
    {
        $uniqueA = 'AAA_'.uniqid();
        $uniqueB = 'ZZZ_'.uniqid();

        Category::create(['name' => $uniqueB, 'slug' => 'b-'.uniqid()]);
        Category::create(['name' => $uniqueA, 'slug' => 'a-'.uniqid()]);

        $service = new AdminTableService;
        $request = Request::create('/admin/categories', 'GET', [
            'sort' => 'name',
            'sortType' => 'asc',
        ]);

        $data = $service->for(Category::query())
            ->columns(['name'])
            ->build($request);

        $names = collect($data['items']->items())->pluck('name')->toArray();
        $posA = array_search($uniqueA, $names, true);
        $posB = array_search($uniqueB, $names, true);

        $this->assertNotFalse($posA);
        $this->assertNotFalse($posB);
        $this->assertLessThan($posB, $posA);
    }

    public function test_it_filters_records_by_exact_and_array_values(): void
    {
        $uniqueColorA = 'color_a_'.uniqid();
        $uniqueColorB = 'color_b_'.uniqid();
        $uniqueColorC = 'color_c_'.uniqid();

        Category::create(['name' => 'Cat A', 'slug' => 'cat-a-'.uniqid(), 'color' => $uniqueColorA]);
        Category::create(['name' => 'Cat B', 'slug' => 'cat-b-'.uniqid(), 'color' => $uniqueColorB]);
        Category::create(['name' => 'Cat C', 'slug' => 'cat-c-'.uniqid(), 'color' => $uniqueColorC]);

        $service = new AdminTableService;
        $request = Request::create('/admin/categories', 'GET', [
            'filter' => ['color' => [$uniqueColorA, $uniqueColorB]],
        ]);

        $data = $service->for(Category::query())
            ->columns(['name', 'color'])
            ->build($request);

        $this->assertCount(2, $data['items']);
    }

    public function test_it_searches_records_by_keyword(): void
    {
        $uniqueTerm = 'XUniqueSearchTermX_'.uniqid();
        Category::create(['name' => $uniqueTerm, 'slug' => 'match-'.uniqid()]);
        Category::create(['name' => 'DifferentName_'.uniqid(), 'slug' => 'other-'.uniqid()]);

        $service = new AdminTableService;
        $request = Request::create('/admin/categories', 'GET', [
            'q' => $uniqueTerm,
        ]);

        $data = $service->for(Category::query())
            ->columns(['name'])
            ->searchable(['name'])
            ->build($request);

        $this->assertCount(1, $data['items']);
        $this->assertEquals($uniqueTerm, $data['items']->items()[0]->name);
    }

    public function test_it_computes_quick_counts(): void
    {
        $initialAll = Category::count();
        $initialTrashed = Category::onlyTrashed()->count();

        Category::create(['name' => 'Cat 1', 'slug' => 'cat-1-'.uniqid()]);
        $cat2 = Category::create(['name' => 'Cat 2', 'slug' => 'cat-2-'.uniqid()]);
        $cat2->delete();

        $service = new AdminTableService;
        $request = Request::create('/admin/categories', 'GET');

        $data = $service->for(Category::class)
            ->columns(['name'])
            ->build($request);

        $this->assertEquals($initialAll + 1, $data['quickCounts']['all']);
        $this->assertEquals($initialTrashed + 1, $data['quickCounts']['trashed']);
    }
}
