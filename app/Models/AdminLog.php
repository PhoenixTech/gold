<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

class AdminLog extends Model
{
    use Prunable;

    protected $guarded = [];

    public function prunable()
    {
        return static::where('created_at', '<=', now()->subMonth());
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
