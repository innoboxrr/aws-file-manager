<?php

namespace Innoboxrr\AwsFileManager\Providers;

use Illuminate\Support\ServiceProvider;
use Innoboxrr\AwsFileManager\Services\S3Service;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AppServiceProvider extends ServiceProvider
{

    public function register()
    {

        $this->mergeConfigFrom(__DIR__ . '/../../config/aws-file-manager.php', 'aws-file-manager');

        // Una sola instancia por aplicacion, y reemplazable: la aplicacion puede
        // enlazar un S3Service con su propio cliente, y los tests uno sin red.
        $this->app->singleton(S3Service::class, function () {
            // Sin bucket o region el SDK lanza un InvalidArgumentException que la
            // SPA recibe como un 500 sin explicacion, y una aplicacion recien
            // instalada todavia no tiene esas variables.
            if (blank(config('aws-file-manager.bucket')) || blank(config('aws-file-manager.region'))) {
                throw new HttpException(503, 'The file manager is not configured. Set AWS_BUCKET and AWS_DEFAULT_REGION.');
            }

            return new S3Service();
        });

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
