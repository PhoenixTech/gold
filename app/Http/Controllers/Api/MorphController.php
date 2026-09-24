<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Clip;
use App\Models\Gallery;
use App\Models\Group;
use App\Models\Post;
use App\Models\Product;
use Illuminate\Http\Request;

class MorphController extends Controller
{
    public int $limit = 5;

    public function search(Request $request): array
    {
        if (auth()->check()) {
            return abort(403);
        }

        $morph = $request->input('morph', Product::class);
        $q = '%'.$request->input('q').'%';

        switch ($morph) {
            case Product::class:
                $query = Product::where('name', 'LIKE', $q)
                    ->orWhere('description', 'LIKE', $q);
                break;
            case Post::class:
                $query = Post::where('title', 'LIKE', $q)
                    ->orWhere('subtitle', 'LIKE', $q)
                    ->orWhere('body', 'LIKE', $q);
                break;
            case Group::class:
                $query = Group::where('name', 'LIKE', $q)
                    ->orWhere('subtitle', 'LIKE', $q)
                    ->orWhere('description', 'LIKE', $q);
                break;
            case Category::class:
                $query = Category::where('name', 'LIKE', $q)
                    ->orWhere('subtitle', 'LIKE', $q)
                    ->orWhere('description', 'LIKE', $q);
                break;
            case Clip::class:
                $query = Clip::where('title', 'LIKE', $q)
                    ->orWhere('body', 'LIKE', $q);
                break;
            case Gallery::class:
                $query = Gallery::where('title', 'LIKE', $q)
                    ->orWhere('description', 'LIKE', $q);
                break;
            default:
                return ['OK' => false, 'error' => __('Invalid morph')];
        }

        return ['OK' => true, 'data' => $query->orderByDesc('updated_at')->limit($this->limit)->get()];
    }
}
