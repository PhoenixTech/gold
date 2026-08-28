<?php

namespace App\Providers;
use App\Console\Commands\AssetsBuild;
use App\Console\Commands\GoldFreePriceUpdate;
use App\Console\Commands\GoldPriceUpdate;
use App\Helpers\TDate;
use App\Http\Middleware\Acl;
use App\Models\Area;
use App\Models\Part;
use App\Models\Setting;
use App\Observers\PartObsever;
use App\Observers\SettingObsever;
use Carbon\Carbon;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Translator\Framework\TranslatorCommand;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
        $this->commands([
            TranslatorCommand::class,
            AssetsBuild::class,
            GoldPriceUpdate::class,
            GoldFreePriceUpdate::class,
        ]);
        foreach (config('xshop.payment.gateways') as $gateway){
            /** @var \App\Contracts\Payment $gateway */
            $gateway::registerService();
        }

        \Route::bind('gateway', function ($gatewayName) {
            return app("$gatewayName-gateway");
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /** @var Router $router */
        $router = $this->app['router'];
        $router->pushMiddlewareToGroup('web', Acl::class);

        Paginator::useBootstrap();
        Carbon::macro('jdate', function ($format, $tr_num = 'fa') {
            $dt = TDate::GetInstance();
            return $dt->PDate($format, self::this()->timestamp);
        });
        Carbon::macro('ldate', function ($format) {
            if (self::this()->timestamp == 0){
                return null;
            }
            if (config('app.locale') == 'fa'){
                $format = str_replace('-','/',$format);
                return self::this()->jdate($format);
            }else{
                return date($format,self::this()->timestamp);
            }
        });

        Part::observe(PartObsever::class);
        Setting::observe(SettingObsever::class);

        \Illuminate\Database\Eloquent\Relations\Relation::morphMap([
            'Category' => \App\Models\Category::class,
            'App\Models\Category' => \App\Models\Category::class,
            'App\Category' => \App\Models\Category::class,
            'Group' => \App\Models\Group::class,
            'App\Models\Group' => \App\Models\Group::class,
            'App\Group' => \App\Models\Group::class,
            'Product' => \App\Models\Product::class,
            'App\Models\Product' => \App\Models\Product::class,
            'App\Product' => \App\Models\Product::class,
            'Post' => \App\Models\Post::class,
            'App\Models\Post' => \App\Models\Post::class,
            'App\Post' => \App\Models\Post::class,
        ]);
    }
}
