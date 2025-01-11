<?php

namespace YourVendor\YourPackageName;

use Illuminate\Support\ServiceProvider;

class YourPackageNameServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'yourpackagename');
        $this->publishes([
            __DIR__.'/../config/yourpackagename.php' => config_path('yourpackagename.php'),
        ]);
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/yourpackagename.php', 'yourpackagename'
        );
    }
}
