<?php

namespace Innoboxrr\AwsFileManager\Tests\Feature;

use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as FoundationRouteServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Innoboxrr\AwsFileManager\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Lo que el paquete no debe hacerle a la aplicacion que lo instala.
 */
final class HostApplicationIsolationTest extends TestCase
{
    public static int $appRouteLoads = 0;

    protected function defineEnvironment($app): void
    {
        // Es lo que hace bootstrap/app.php con withRouting(): deja el cargador
        // de rutas de la aplicacion en la clase base de Foundation.
        self::$appRouteLoads = 0;

        FoundationRouteServiceProvider::loadRoutesUsing(function () {
            self::$appRouteLoads++;
        });
    }

    protected function tearDown(): void
    {
        FoundationRouteServiceProvider::loadRoutesUsing(null);

        parent::tearDown();
    }

    #[Test]
    public function no_vuelve_a_cargar_las_rutas_de_la_aplicacion(): void
    {
        $this->assertSame(0, self::$appRouteLoads);
    }

    #[Test]
    public function registra_sus_propias_rutas_con_los_mismos_nombres_y_uris(): void
    {
        $expected = [
            'afm.file.change-visibility' => 'afm/file/change-visibility',
            'afm.file.delete' => 'afm/file/delete',
            'afm.file_manager.index' => 'afm/file_manager/index',
            'afm.file_manager.upload' => 'afm/file_manager/upload',
            'afm.file_manager.create-directory' => 'afm/file_manager/create-directory',
        ];

        foreach ($expected as $name => $uri) {
            $this->assertTrue(Route::has($name), "Falta la ruta {$name}.");
            $this->assertSame($uri, Route::getRoutes()->getByName($name)->uri());
        }
    }

    #[Test]
    public function no_agrega_otro_envio_del_correo_de_verificacion(): void
    {
        $this->assertArrayNotHasKey(Registered::class, Event::getRawListeners());
    }
}
