<?php

namespace Innoboxrr\AwsFileManager\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Innoboxrr\AwsFileManager\Services\S3Service;
use Innoboxrr\AwsFileManager\Tests\Fakes\FakeS3;
use Innoboxrr\AwsFileManager\Tests\Fixtures\User;
use Innoboxrr\AwsFileManager\Tests\TestCase;
use Laravel\Sanctum\SanctumServiceProvider;

/**
 * Una aplicacion como la que deja laravel-setup, con Sanctum para la sesion de
 * la SPA y un bucket en memoria en lugar de S3.
 *
 * Sobre CSRF: las rutas van en el grupo `web`, que verifica el token. Estos
 * tests no lo desactivan con withoutMiddleware: PreventRequestForgery ya se lo
 * salta solo mientras la aplicacion corre tests (APP_ENV=testing). En la SPA
 * lo resuelve axios devolviendo la cookie XSRF-TOKEN en X-XSRF-TOKEN.
 */
abstract class FeatureTestCase extends TestCase
{
    protected FakeS3 $s3;

    protected function getPackageProviders($app): array
    {
        return [...parent::getPackageProviders($app), SanctumServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        // El grupo `web` cifra las cookies: sin APP_KEY no responde nada.
        $app['config']->set('app.key', 'base64:' . base64_encode(str_repeat('k', 32)));
        $app['config']->set('session.driver', 'array');
        $app['config']->set('auth.providers.users.model', User::class);

        $app['config']->set('aws-file-manager.bucket', 'test-bucket');
        $app['config']->set('aws-file-manager.region', 'us-east-1');
        $app['config']->set('aws-file-manager.url', 'https://test-bucket.s3.amazonaws.com');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->s3 = new FakeS3;

        $this->app->instance(S3Service::class, new S3Service($this->s3->client()));
    }

    protected function defineDatabaseMigrations(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    protected function makeUser(string $name = 'Ana'): User
    {
        return User::create([
            'name' => $name,
            'email' => strtolower($name) . '@example.com',
            'password' => 'secret',
        ]);
    }
}
