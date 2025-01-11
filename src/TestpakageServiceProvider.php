<?php

namespace vendornamespace\Testpakage;

use Illuminate\Support\ServiceProvider;

class TestpakageServiceProvider extends ServiceProvider
{
    public function register()
    {
        // 这里可以注册任何需要的服务绑定或配置
    }

    public function boot()
    {
        // 如果您有命令需要注册，可以在这里注册
        // if ($this->app->runningInConsole()) {
        //     $this->commands([
        //         \YourVendor\YourPackageName\Commands\YourCommand::class,
        //     ]);
        // }

        // 加载路由 - 保留此部分以便将来使用
        // if (file_exists($routes = __DIR__.'/../routes/web.php')) {
        //     $this->loadRoutesFrom($routes);
        // }

        // 加载数据库迁移 - 保留此部分以便将来使用
        // if (is_dir($migrations = __DIR__.'/../database/migrations')) {
        //     $this->loadMigrationsFrom($migrations);
        // }

        // 加载翻译文件 - 保留此部分以便将来使用
        // if (is_dir($lang = __DIR__.'/../resources/lang')) {
        //     $this->loadTranslationsFrom($lang, 'yourpackagename');
        // }

        // 发布配置文件 - 保留此部分以便将来使用
        // if (file_exists($config = __DIR__.'/../config/yourpackagename.php')) {
        //     $this->publishes([
        //         $config => config_path('yourpackagename.php'),
        //     ]);
        // }
    }
}
