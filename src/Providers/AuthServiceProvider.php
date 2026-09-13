<?php

namespace Innoboxrr\AwsFileManager\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{

    public function boot()
    {
        $this->mapPolicies();
    }

    public function mapPolicies()
    {
        // Sin cache a proposito. La clave `auth_policies` la compartian otros
        // paquetes, que registraban las politicas de este y al reves, y leer la
        // cache al arrancar rompe `php artisan migrate` en una aplicacion nueva
        // con CACHE_STORE=database: la tabla `cache` todavia no existe.
        $policies = $this->customDiscoverPolicies();

        foreach ($policies as $model => $policy) {
            Gate::policy($model, $policy);
        }
    }

    /**
     * Descubre las políticas de los modelos.
     *
     * @return array
     */
    protected function customDiscoverPolicies()
    {
        $policies = [];

        foreach (glob(__DIR__ . '/../Policies/*.php') ?: [] as $file) {
            $policy = 'Innoboxrr\AwsFileManager\Policies\\' . substr(basename($file), 0, -4);
            $model = 'Innoboxrr\AwsFileManager\Models\\' . str_replace('Policy', '', $policy);

            if (class_exists($model) && class_exists($policy)) {
                $policies[$model] = $policy;
            }
        }

        return $policies;
    }

}
