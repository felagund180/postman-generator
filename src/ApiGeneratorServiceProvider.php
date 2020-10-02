<?php

namespace Ab\ApiGenerator;

use Ab\ApiGenerator\Commands\Generate;
use Illuminate\Support\ServiceProvider;

class ApiGeneratorServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([Generate::class]);
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
    }
}
