<?php

namespace Innoboxrr\AwsFileManager\Providers;

use Illuminate\Support\ServiceProvider;
use Innoboxrr\AwsFileManager\Services\S3Service;

class AppServiceProvider extends ServiceProvider
{

    public function register()
    {
        
        $this->mergeConfigFrom(__DIR__ . '/../../config/aws-file-manager.php', 'aws-file-manager');

        // Una sola instancia por aplicacion, y reemplazable: la aplicacion puede
        // enlazar un S3Service con su propio cliente, y los tests uno sin red.
        $this->app->singleton(S3Service::class, fn () => new S3Service());

    }

    public function boot()
    {
        
        // $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // $this->loadViewsFrom(__DIR__.'/../../resources/views', 'innoboxrrawsfilemanager');

        if ($this->app->runningInConsole()) {
            
            // $this->publishes([__DIR__.'/../../resources/views' => resource_path('views/vendor/innoboxrrawsfilemanager'),], 'views');

            $this->publishes([__DIR__.'/../../config/aws-file-manager.php' => config_path('aws-file-manager.php')], 'config');

        }

    }
    
}