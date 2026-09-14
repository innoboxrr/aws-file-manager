<?php

namespace Innoboxrr\AwsFileManager\Tests\Feature;

use Innoboxrr\AwsFileManager\Services\S3Service;
use PHPUnit\Framework\Attributes\Test;

/**
 * El S3Service de verdad, construido desde la configuracion, como en una
 * aplicacion recien instalada. Construir el cliente no toca la red.
 */
final class NotConfiguredTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->forgetInstance(S3Service::class);
    }

    #[Test]
    public function sin_bucket_ni_region_responde_503_con_el_motivo(): void
    {
        config(['aws-file-manager.bucket' => null, 'aws-file-manager.region' => null]);

        $this->actingAs($this->makeUser('Ana'))
            ->getJson(route('afm.file_manager.index'))
            ->assertStatus(503)
            ->assertJsonPath('message', 'The file manager is not configured. Set AWS_BUCKET and AWS_DEFAULT_REGION.');
    }

    #[Test]
    public function con_llaves_en_la_configuracion_las_usa(): void
    {
        config([
            'aws-file-manager.credentials.key' => 'key-from-config',
            'aws-file-manager.credentials.secret' => 'secret-from-config',
        ]);

        $this->assertSame('key-from-config', $this->accessKeyOf(app(S3Service::class)));
    }

    #[Test]
    public function sin_llaves_en_la_configuracion_usa_la_cadena_de_credenciales_del_sdk(): void
    {
        config([
            'aws-file-manager.credentials.key' => null,
            'aws-file-manager.credentials.secret' => null,
        ]);

        // El primer eslabon de la cadena del SDK son las variables de entorno;
        // en un servidor seria el rol de la instancia.
        putenv('AWS_ACCESS_KEY_ID=key-from-environment');
        putenv('AWS_SECRET_ACCESS_KEY=secret-from-environment');

        try {
            $this->assertSame('key-from-environment', $this->accessKeyOf(app(S3Service::class)));
        } finally {
            putenv('AWS_ACCESS_KEY_ID');
            putenv('AWS_SECRET_ACCESS_KEY');
        }
    }

    private function accessKeyOf(S3Service $service): string
    {
        $client = (fn () => $this->s3Client)->call($service);

        return $client->getCredentials()->wait()->getAccessKeyId();
    }
}
