<?php

namespace Innoboxrr\AwsFileManager\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

/**
 * Hereda de ServiceProvider y no del EventServiceProvider de Foundation: aquel
 * registra al arrancar otro listener SendEmailVerificationNotification para
 * Registered, y con uno por paquete la aplicacion mandaba el correo de
 * verificacion repetido.
 *
 * Los observers no se descubren aqui: cada modelo declara el suyo con
 * #[ObservedBy]. Este proveedor solo enlaza los eventos de Http/Events.
 */
class EventServiceProvider extends ServiceProvider
{

    public function boot(): void
    {
        $this->registerEvents();
    }

    protected function registerEvents(): void
    {
        // Sin cache a proposito: leerla al arrancar rompe `php artisan migrate`
        // con CACHE_STORE=database antes de que exista la tabla `cache`.
        foreach ($this->discoverEvents() as $event => $listeners) {
            foreach ($listeners as $listener) {
                Event::listen($event, $listener);
            }
        }
    }

    /**
     * Recorre Http/Events/{Modelo}/Events/*.php y empareja cada evento con los
     * listeners de Http/Events/{Modelo}/Listeners/{Evento}/*.php.
     *
     * @return array<class-string, array<int, class-string>>
     */
    protected function discoverEvents(): array
    {
        $events = [];
        $basePath = realpath(__DIR__ . '/../Http/Events');

        // Sin la carpeta, realpath() da false y el glob recorreria la raiz del
        // disco.
        if ($basePath === false) {
            return $events;
        }

        $namespace = 'Innoboxrr\AwsFileManager\Http\Events\\';

        foreach (glob("{$basePath}/*", GLOB_ONLYDIR) ?: [] as $modelPath) {
            $model = basename($modelPath);

            foreach (glob("{$modelPath}/Events/*.php") ?: [] as $eventPath) {
                $eventName = pathinfo($eventPath, PATHINFO_FILENAME);
                $eventClass = "{$namespace}{$model}\\Events\\{$eventName}";

                foreach (glob("{$modelPath}/Listeners/{$eventName}/*.php") ?: [] as $listenerPath) {
                    $listenerName = pathinfo($listenerPath, PATHINFO_FILENAME);

                    $events[$eventClass][] = "{$namespace}{$model}\\Listeners\\{$eventName}\\{$listenerName}";
                }
            }
        }

        return $events;
    }

}
