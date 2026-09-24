<?php

namespace App\Providers;

use App\Console\Commands\AssetsBuild;
use App\Console\Commands\GoldFreePriceUpdate;
use App\Console\Commands\GoldPriceUpdate;
use App\Http\Middleware\Acl;
use App\Models\Setting;
use App\Observers\SettingObserver;
use App\Services\PersianDate;
use Carbon\Carbon;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Translator\Framework\TranslatorCommand;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->commands([
            TranslatorCommand::class,
            AssetsBuild::class,
            GoldPriceUpdate::class,
            GoldFreePriceUpdate::class,
        ]);
        foreach (config('xshop.payment.gateways') as $gateway) {
            $gateway::registerService();
        }

        \Route::bind('gateway', function ($gatewayName) {
            return app("$gatewayName-gateway");
        });
    }

    public function boot(): void
    {
        $router = $this->app['router'];
        $router->pushMiddlewareToGroup('web', Acl::class);

        Paginator::useBootstrap();
        Carbon::macro('jdate', function ($format, $tr_num = 'fa') {
            return PersianDate::format(self::this(), $format, $tr_num);
        });
        Carbon::macro('ldate', function ($format) {
            if (self::this()->timestamp === 0) {
                return null;
            }
            if (config('app.locale') === 'fa') {
                $format = str_replace('-', '/', $format);

                return self::this()->jdate($format);
            }

            return date($format, self::this()->timestamp);
        });

        Setting::observe(SettingObserver::class);
    }
}
