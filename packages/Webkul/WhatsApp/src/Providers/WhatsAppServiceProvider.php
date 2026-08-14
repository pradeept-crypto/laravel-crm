<?php

namespace Webkul\WhatsApp\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Webkul\WhatsApp\Services\WhatsAppMediaService;
use Webkul\WhatsApp\Services\WhatsAppService;

class WhatsAppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/whatsapp.php', 'whatsapp');
        $this->mergeConfigFrom(__DIR__.'/../Config/menu.php', 'menu.admin');
        $this->mergeConfigFrom(__DIR__.'/../Config/acl.php', 'acl');

        $this->app->singleton(WhatsAppService::class, function ($app) {
            return new WhatsAppService(
                config('whatsapp.phone_number_id'),
                config('whatsapp.access_token'),
                config('whatsapp.api_version', 'v19.0'),
            );
        });

        $this->app->singleton(WhatsAppMediaService::class, function ($app) {
            return new WhatsAppMediaService($app->make(WhatsAppService::class));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'whatsapp');

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'whatsapp');

        $this->registerRoutes();

        $this->app->register(ModuleServiceProvider::class);

        $this->publishes([
            __DIR__.'/../Config/whatsapp.php' => config_path('whatsapp.php'),
        ], 'whatsapp-config');
    }

    /**
     * Load package routes.
     */
    protected function registerRoutes(): void
    {
        Route::group(['middleware' => ['web']], function () {
            $this->loadRoutesFrom(__DIR__.'/../Routes/webhook.php');
        });

        Route::group([
            'middleware' => ['web', 'admin_locale', 'user'],
            'prefix' => config('app.admin_path', 'admin'),
        ], function () {
            $this->loadRoutesFrom(__DIR__.'/../Routes/admin.php');
        });
    }
}
