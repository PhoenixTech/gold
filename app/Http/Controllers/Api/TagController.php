<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Spatie\Tags\Tag;

class TagController extends Controller
{
    public function search(string $q): array
    {
        $tags = Tag::where('name->'.config('app.locale'), 'like', '%'.$q.'%')->limit(10)->pluck('name');

        return ['OK' => true, 'data' => $tags];
    }
}
