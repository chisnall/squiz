<?php

namespace Chisnall\Squiz;

use Chisnall\Squiz\Http\Middleware\SquizToken;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;

class SquizServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Load config
        $this->mergeConfigFrom(
            __DIR__.'/../config/squiz.php', 'squiz'
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register SquizToken middleware as alias
        $this->app->make(Router::class)->aliasMiddleware('squizToken', SquizToken::class);

        // Add routes
        Route::middleware('web')->group(__DIR__.'/../routes/web.php');
        Route::middleware('api')->group(__DIR__.'/../routes/api.php');

        // Register @squiz blade directive
        Blade::directive('squiz', function ($expression) {
            return "<?php squiz($expression); ?>";
        });

        // Register @squizd blade directive
        Blade::directive('squizd', function ($expression) {
            return "<?php squizd($expression); ?>";
        });
    }
}
