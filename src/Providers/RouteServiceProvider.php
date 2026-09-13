<?php

namespace Innoboxrr\AwsFileManager\Providers;

use Illuminate\Contracts\Foundation\CachesRoutes;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Hereda de ServiceProvider y no del RouteServiceProvider de Foundation. Aquel
 * guarda en una propiedad estatica de la clase base el cargador de rutas que
 * bootstrap/app.php registra con withRouting(), y cada subclase lo vuelve a
 * ejecutar: la aplicacion cargaba sus routes/web.php y api.php una vez mas por
 * cada paquete.
 */
class RouteServiceProvider extends ServiceProvider
{

    public function boot(): void
    {
        // Cuando las rutas estan cacheadas no hay que volver a registrarlas.
        if ($this->app instanceof CachesRoutes && $this->app->routesAreCached()) {
            return;
        }

        $this->mapApiRoutes();
    }

    protected function mapApiRoutes(): void
    {
        foreach (glob(__DIR__ . '/../../routes/api/models/*.php') ?: [] as $file) {

            $name = basename($file, '.php');

            // En `web` y no en `api`: la interfaz las llama con la sesion por
            // cookie de Sanctum y el token XSRF.
            Route::middleware(['web'])
                ->prefix('afm/' . $name)
                ->as('afm.' . $name . '.')
                ->group($file);

        }
    }

}
