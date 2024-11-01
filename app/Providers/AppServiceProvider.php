<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Predis\Client as PredisClient;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register Predis client as a singleton
        $this->app->singleton(PredisClient::class, function ($app) {
            return new PredisClient([
                'scheme'   => 'tcp',
                'host'     => env('REDIS_HOST', '127.0.0.1'),
                'port'     => env('REDIS_PORT', 6379),
                'password' => env('REDIS_PASSWORD', null),
                'database' => env('REDIS_DB', 0),
            ]);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
