<?php

use App\Http\Middleware\EnsureCourier;
use App\Http\Middleware\EnsureVisitor;
use App\Http\Middleware\IgnoreFirstPage;
use App\Http\Middleware\RestrictCourierToPanel;
use App\Http\Middleware\RestrictVisitorToPanel;
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
            IgnoreFirstPage::class,
        ]);

        $middleware->redirectGuestsTo(function ($request) {
            if ($request->routeIs('client.card.check') || $request->is('card/check')) {
                return route('client.sign-in', ['redirect' => route('client.card')]);
            }

            return route('login');
        });

        $middleware->alias([
            'visitor' => EnsureVisitor::class,
            'courier' => EnsureCourier::class,
        ]);

        $middleware->web(append: [
            RestrictVisitorToPanel::class,
            RestrictCourierToPanel::class,
            // MinifyHtml removed: was encoding Persian/Arabic digits to HTML entities and breaking inline JS regexes
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
