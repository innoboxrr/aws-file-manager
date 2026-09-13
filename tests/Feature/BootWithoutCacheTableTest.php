<?php

namespace Innoboxrr\AwsFileManager\Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Innoboxrr\AwsFileManager\Tests\TestCase;
use Orchestra\Testbench\Attributes\DefineEnvironment;
use PHPUnit\Framework\Attributes\Test;

/**
 * Una aplicacion nueva de Laravel 13 trae CACHE_STORE=database, y la tabla
 * `cache` no existe hasta que corre la primera migracion. Si el paquete lee la
 * cache al arrancar, `php artisan migrate` revienta antes de poder crearla.
 */
final class BootWithoutCacheTableTest extends TestCase
{
    protected function usesDatabaseCacheWithoutTable($app): void
    {
        $app['config']->set('cache.default', 'database');
        $app['config']->set('cache.stores.database', [
            'driver' => 'database',
            'connection' => 'testing',
            'table' => 'cache',
            'lock_connection' => null,
            'lock_table' => null,
        ]);
    }

    #[Test]
    #[DefineEnvironment('usesDatabaseCacheWithoutTable')]
    public function el_paquete_arranca_sin_tabla_de_cache(): void
    {
        // Llegar aqui ya prueba que el arranque no toco la cache: con el
        // almacen de base de datos y sin tabla, leerla lanza QueryException
        // durante setUp.
        $this->assertSame('database', config('cache.default'));
        $this->assertFalse(Schema::hasTable('cache'));

        $this->artisan('migrate')->assertSuccessful();
    }

    #[Test]
    public function el_arranque_no_escribe_listas_bajo_claves_compartidas(): void
    {
        // laravel-options y laravel-notifications usaban estas mismas claves:
        // lo que guardaba uno lo leian los demas.
        $this->assertFalse(Cache::has('auth_policies'));
        $this->assertFalse(Cache::has('events_and_observers'));
    }
}
