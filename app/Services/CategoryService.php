<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoryService
{
    public function __construct(
        protected AdminMediaService $mediaService
    ) {}

    public function handleUploads(Category $category, Request $request): void
    {
        $imageFields = ['image', 'silver_image', 'bg'];

        foreach ($imageFields as $field) {
            $this->mediaService->handleOptimizedImage($request, $category, $field, 'categories');
        }

        if ($request->hasFile('svg')) {
            $svgFile = $request->file('svg');
            $name = time().'-'.$svgFile->getClientOriginalName();
            $svgFile->storeAs('public/categories', $name);
            $category->svg = $name;
        }
    }

    public function importNestedFromHtml(string $html): void
    {
        $list = $this->parseTableData($html);

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Category::truncate();
        $this->insertCategories($list);
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    protected function parseTableData(string $tableData): array
    {
        $list = [];
        $doc = new \DOMDocument;
        @$doc->loadHTML($tableData);
        $topUl = $doc->getElementsByTagName('ul')->item(0);

        if ($topUl) {
            $list = $this->getCategories($topUl);
        }

        return $list;
    }

    protected function getCategories(\DOMElement $ul): array
    {
        $categories = [];
        $lis = $ul->getElementsByTagName('li');

        foreach ($lis as $li) {
            $categoryName = trim($li->childNodes->item(0)->textContent ?? '');
            $subUl = $li->getElementsByTagName('ul')->item(0);
            $subcategories = [];

            if ($subUl) {
                $subcategories = $this->getCategories($subUl);
            }

            if (! empty($categoryName)) {
                $categories[] = [
                    'name' => $categoryName,
                    'subcategories' => $subcategories,
                ];
            }
        }

        return $categories;
    }

    protected function insertCategories(array $list, ?int $parentId = null): void
    {
        foreach ($list as $item) {
            if (Category::where('slug', sluger($item['name']))->count() === 0) {
                $category = Category::create([
                    'name' => $item['name'],
                    'slug' => sluger($item['name']),
                    'parent_id' => $parentId,
                ]);

                $this->insertCategories($item['subcategories'], $category->id);
            }
        }
    }
}
