<?php

namespace Chisnall\Squiz;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/squiz.php', 'squiz'
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Route::middleware('web')->group(__DIR__.'/../routes/web.php');

        Route::middleware('api')->group(__DIR__.'/../routes/api.php');

        Blade::directive('squiz', function ($expression) {
            return "<?php squiz($expression); ?>";
        });

        Blade::directive('squizd', function ($expression) {
            return "<?php squizd($expression); ?>";
        });
    }
}
