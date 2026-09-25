<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Image\Enums\AlignPosition;
use Spatie\Image\Enums\Fit;
use Spatie\Image\Enums\Unit;
use Spatie\Image\Image;

class CategoryService
{
    public function handleUploads(Category $category, Request $request): void
    {
        $imageFields = ['image', 'silver_image', 'bg'];

        foreach ($imageFields as $field) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $name = time().'-'.$file->getClientOriginalName();
                $file->storeAs('public/categories', $name);
                $category->{$field} = $name;

                $format = strtolower((string) $file->guessExtension()) === 'png' ? 'webp' : $file->guessExtension();

                $img = Image::load($file->getPathname())
                    ->optimize()
                    ->format($format);

                if (getSetting('watermark2') && file_exists(public_path('upload/images/logo.png'))) {
                    $img->watermark(
                        public_path('upload/images/logo.png'),
                        AlignPosition::BottomLeft, 5, 5, Unit::Percent,
                        config('app.media.watermark_size', 10), Unit::Percent,
                        config('app.media.watermark_size', 10), Unit::Percent,
                        Fit::Contain,
                        config('app.media.watermark_opacity', 50)
                    );
                }

                $img->save(storage_path('app/public/categories/optimized-'.$name));
            }
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
