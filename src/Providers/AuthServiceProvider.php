<?php

namespace Innoboxrr\AwsFileManager\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * El paquete no tiene modelos: los archivos viven en S3. Si llega a tenerlos,
 * cada modelo declara su politica con #[UsePolicy], que Laravel resuelve solo,
 * y este proveedor queda para gates sueltos.
 */
class AuthServiceProvider extends ServiceProvider
{

    public function boot(): void
    {
        $this->defineGates();
    }

    protected function defineGates(): void
    {
        // Gate::define('nombre-de-la-habilidad', fn ($user) => ...);
    }

}
