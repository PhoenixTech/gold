<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    /**
     * Segment folders that still exist on disk (active theme parts only).
     *
     * @var list<string>
     */
    public static $allSegments = [
        'attachment',
        'attachments',
        'attachments_page',
        'card',
        'category',
        'clip',
        'clips_page',
        'compare',
        'contact',
        'customer',
        'footer',
        'galleries_page',
        'gallery',
        'header',
        'index',
        'invoice',
        'login',
        'menu',
        'post',
        'posts_page',
        'product',
        'product_grid',
        'products_page',
        'register',
    ];

    protected $casts = [
        'segments',
    ];

    public function getSegmentAttribute()
    {
        return json_decode($this->valid_segments, true);
    }

    public function getRouteKeyName()
    {
        return 'name';
    }

    public function parts()
    {
        return $this->hasMany(Part::class);
    }

    public function defPart()
    {
        $p = $this->parts()->first();

        return 'segments.'.$p->segment.'.'.$p->part.'.'.$p->part;
    }
}
