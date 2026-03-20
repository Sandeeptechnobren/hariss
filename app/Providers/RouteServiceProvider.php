<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        $this->routes(function () {
            // ===============================
            // API Routes
            // ===============================
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/master/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));


            // ===============================
            // Web Routes
            // ===============================
            Route::middleware('api')
                ->prefix('web')
                ->group(base_path('routes/master/web/master_web.php'));

            Route::middleware('api')
                ->prefix('web')
                ->group(base_path('routes/merchendiser/web/merchendiser_web.php'));

            Route::middleware('api')
                ->prefix('web')
                ->group(base_path('routes/settings/web/setting_web.php'));

            Route::middleware('api')
                ->prefix('web')
                ->group(base_path('routes/assets/web/assets_web.php'));


            // ===============================
            // Mobile Routes
            // ===============================
            Route::middleware('api')
                ->prefix('mob')
                ->group(base_path('routes/master/mob/master_mob.php'));

            Route::middleware('api')
                ->prefix('mob')
                ->group(base_path('routes/merchendiser/mob/merchendiser_mob.php'));

            Route::middleware('api')
                ->prefix('mob')
                ->group(base_path('routes/settings/mob/setting_mob.php'));

            Route::middleware('api')
                ->prefix('mob')
                ->group(base_path('routes/assets/mob/assets_mob.php'));
        });
    }
}
