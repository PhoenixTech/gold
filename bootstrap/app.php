<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
        $middleware->use([
            \App\Http\Middleware\IgnoreFirstPage::class,
        ]);

        $middleware->redirectGuestsTo(function ($request) {
            if ($request->routeIs('client.card.check') || $request->is('card/check')) {
                return route('client.sign-in', ['redirect' => route('client.card')]);
            }

            return route('login');
        });

        $middleware->alias([
            'visitor' => \App\Http\Middleware\EnsureVisitor::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\RestrictVisitorToPanel::class,
            \Fahlisaputra\Minify\Middleware\MinifyHtml::class,
            \Fahlisaputra\Minify\Middleware\MinifyCss::class,
            // MinifyJavascript breaks inline scripts (Laravel Boost logger, etc.)
        ]);

    })
    ->withCommands([
        \App\Console\Commands\clientAssetGenerator::class,
    ])
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
