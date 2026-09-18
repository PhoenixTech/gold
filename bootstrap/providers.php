<?php

use App\Providers\AppServiceProvider;
use App\Providers\BladeServiceProvider;
use Spatie\Permission\PermissionServiceProvider;

return [
    AppServiceProvider::class,
    BladeServiceProvider::class,
    PermissionServiceProvider::class,
];
