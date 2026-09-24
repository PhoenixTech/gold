<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Group;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $latestGroup = Group::orderByDesc('updated_at')->first();
        $latestCategory = Category::orderByDesc('updated_at')->first();

        $latestUpdate = null;

        if ($latestGroup) {
            $latestUpdate = $latestGroup->updated_at;
        }

        if ($latestCategory && (! $latestUpdate || $latestCategory->updated_at > $latestUpdate)) {
            $latestUpdate = $latestCategory->updated_at;
        }

        $xmlContent = '<?xml version="1.0" encoding="utf-8" ?>'.PHP_EOL;
        $xmlContent .= view('website.sitemaps.sitemap', compact('latestUpdate'))->render();

        return response($xmlContent, 200)->header('Content-Type', 'text/xml');
    }

    public function categories(): Response
    {
        $xmlContent = '<?xml version="1.0" encoding="utf-8" ?>'.PHP_EOL;
        $xmlContent .= view('website.sitemaps.sitemap-groups-category')->render();

        return response($xmlContent, 200)->header('Content-Type', 'text/xml');
    }

    public function posts(): Response
    {
        $xmlContent = '<?xml version="1.0" encoding="utf-8" ?>'.PHP_EOL;
        $xmlContent .= view('website.sitemaps.sitemap-posts')->render();

        return response($xmlContent, 200)->header('Content-Type', 'text/xml');
    }

    public function products(): Response
    {
        $xmlContent = '<?xml version="1.0" encoding="utf-8" ?>'.PHP_EOL;
        $xmlContent .= view('website.sitemaps.sitemap-products')->render();

        return response($xmlContent, 200)->header('Content-Type', 'text/xml');
    }

    public function clips(): Response
    {
        $xmlContent = '<?xml version="1.0" encoding="utf-8" ?>'.PHP_EOL;
        $xmlContent .= view('website.sitemaps.sitemap-clips')->render();

        return response($xmlContent, 200)->header('Content-Type', 'text/xml');
    }

    public function galleries(): Response
    {
        $xmlContent = '<?xml version="1.0" encoding="utf-8" ?>'.PHP_EOL;
        $xmlContent .= view('website.sitemaps.sitemap-gallries')->render();

        return response($xmlContent, 200)->header('Content-Type', 'text/xml');
    }

    public function attachments(): Response
    {
        $xmlContent = '<?xml version="1.0" encoding="utf-8" ?>'.PHP_EOL;
        $xmlContent .= view('website.sitemaps.sitemap-attachments')->render();

        return response($xmlContent, 200)->header('Content-Type', 'text/xml');
    }
}
