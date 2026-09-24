<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PropCollection;
use App\Models\Category;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    public function props(int|string $id): AnonymousResourceCollection
    {
        $category = Category::findOrFail($id);

        return PropCollection::collection($category->props()->orderBy('sort')->get());
    }
}
