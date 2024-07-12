<?php

namespace Innoboxrr\AwsFileManager\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{

    public function register()
    {
        
        // $this->mergeConfigFrom(__DIR__ . '/../../config/innoboxrrawsfilemanager.php', 'innoboxrrawsfilemanager');

    }

    public function boot()
    {
        
        // $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // $this->loadViewsFrom(__DIR__.'/../../resources/views', 'innoboxrrawsfilemanager');

        if ($this->app->runningInConsole()) {
            
            // $this->publishes([__DIR__.'/../../resources/views' => resource_path('views/vendor/innoboxrrawsfilemanager'),], 'views');

            // $this->publishes([__DIR__.'/../../config/innoboxrrawsfilemanager.php' => config_path('innoboxrrawsfilemanager.php')], 'config');

        }

    }
    
}