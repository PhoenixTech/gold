<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Product;
use Illuminate\Http\Response;

class RssFeedController extends Controller
{
    public function posts(): Response
    {
        $posts = Post::published()->orderBy('created_at', 'desc')->take(10)->get();

        $xmlContent = '<?xml version="1.0" encoding="UTF-8" ?>'.PHP_EOL;
        $xmlContent .= view('website.rss.post', compact('posts'))->render();

        return response($xmlContent, 200)->header('Content-Type', 'text/xml');
    }

    public function products(): Response
    {
        $products = Product::published()->orderBy('created_at', 'desc')->take(10)->get();

        $xmlContent = '<?xml version="1.0" encoding="UTF-8" ?>'.PHP_EOL;
        $xmlContent .= view('website.rss.product', compact('products'))->render();

        return response($xmlContent, 200)->header('Content-Type', 'text/xml');
    }
}
