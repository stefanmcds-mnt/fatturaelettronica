<?php

namespace StefanmcdsMnt\FatturaElettronica;

use Illuminate\Support\ServiceProvider;

class FatturaElettronicaServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot(): void
    {
        // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'stefanmcds-mnt');
        // $this->loadViewsFrom(__DIR__.'/../resources/views', 'stefanmcds-mnt');
        // $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        // $this->loadRoutesFrom(__DIR__.'/routes.php');

        // Publishing is only necessary when using the CLI.
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/fatturaelettronica.php', 'fatturaelettronica');

        // Register the service the package provides.
        $this->app->singleton('fatturaelettronica', function ($app) {
            return new FatturaElettronica;
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['fatturaelettronica'];
    }

    /**
     * Console-specific booting.
     *
     * @return void
     */
    protected function bootForConsole(): void
    {
        // Publishing the configuration file.
        $this->publishes([
            __DIR__ . '/../config/fatturaelettronica.php' => config_path('fatturaelettronica.php'),
        ], 'fatturaelettronica.config');

        // Publishing the views.
        /*$this->publishes([
            __DIR__.'/../resources/views' => base_path('resources/views/vendor/stefanmcds-mnt'),
        ], 'fatturaelettronica.views');*/

        // Publishing assets.
        /*$this->publishes([
            __DIR__.'/../resources/assets' => public_path('vendor/stefanmcds-mnt'),
        ], 'fatturaelettronica.views');*/

        // Publishing the translation files.
        /*$this->publishes([
            __DIR__.'/../resources/lang' => resource_path('lang/vendor/stefanmcds-mnt'),
        ], 'fatturaelettronica.views');*/

        // Registering package commands.
        // $this->commands([]);
    }
}
