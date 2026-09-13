<?php

namespace Innoboxrr\AwsFileManager\Tests\Feature;

use Illuminate\Http\UploadedFile;
use Orchestra\Testbench\Attributes\DefineEnvironment;
use PHPUnit\Framework\Attributes\Test;

/**
 * `use_acl` contra los dos tipos de bucket: uno creado desde abril de 2023, con
 * los ACL desactivados, y uno que todavia los admite.
 */
final class AclSettingTest extends FeatureTestCase
{
    protected function withAcl($app): void
    {
        $app['config']->set('aws-file-manager.use_acl', true);
    }

    #[Test]
    public function por_defecto_no_se_usan_acl(): void
    {
        $this->assertFalse((bool) config('aws-file-manager.use_acl'));
    }

    #[Test]
    public function sin_acl_se_sube_a_un_bucket_nuevo_sin_mandar_el_parametro(): void
    {
        $this->s3->aclsEnabled = false;
        $ana = $this->makeUser('Ana');

        foreach ([null, 'private'] as $i => $visibility) {
            $this->actingAs($ana)
                ->withHeader('Accept', 'application/json')
                ->post(route('afm.file_manager.upload'), array_filter([
                    'visibility' => $visibility,
                    'files' => [UploadedFile::fake()->createWithContent("nota-{$i}.txt", 'x')],
                ]))
                ->assertOk();
        }

        $puts = $this->s3->sent('PutObject');

        $this->assertCount(2, $puts);

        foreach ($puts as $params) {
            $this->assertArrayNotHasKey('ACL', $params);
        }
    }

    #[Test]
    public function sin_acl_subir_como_publico_responde_422_sin_subir_nada(): void
    {
        $this->s3->aclsEnabled = false;

        $response = $this->actingAs($this->makeUser('Ana'))
            ->withHeader('Accept', 'application/json')
            ->post(route('afm.file_manager.upload'), [
                'visibility' => 'public',
                'files' => [UploadedFile::fake()->createWithContent('foto.txt', 'x')],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('visibility');

        $this->assertStringContainsString('AWS_FILE_MANAGER_USE_ACL', $response->json('message'));
        $this->assertSame([], $this->s3->sent('PutObject'));
    }

    #[Test]
    public function sin_acl_cambiar_la_visibilidad_responde_422_con_un_mensaje_claro(): void
    {
        $this->s3->aclsEnabled = false;
        $ana = $this->makeUser('Ana');
        $key = "file-manager/{$ana->id}/notas.txt";
        $this->s3->put($key, 'hola');

        $response = $this->actingAs($ana)
            ->postJson(route('afm.file.change-visibility'), ['file' => $key, 'visibility' => 'public'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('visibility');

        $this->assertStringContainsString('AWS_FILE_MANAGER_USE_ACL', $response->json('message'));
        $this->assertSame([], $this->s3->sent('PutObjectAcl'));
        $this->assertSame('private', $this->s3->objects[$key]['acl']);
    }

    #[Test]
    public function sin_acl_el_indice_no_pide_acl_e_informa_private(): void
    {
        $this->s3->aclsEnabled = false;
        $ana = $this->makeUser('Ana');
        $this->s3->put("file-manager/{$ana->id}/notas.txt", 'hola');

        $this->actingAs($ana)
            ->getJson(route('afm.file_manager.index'))
            ->assertOk()
            ->assertJsonPath('files.0.information.visibility', 'private');

        $this->assertSame([], $this->s3->sent('GetObjectAcl'));
    }

    #[Test]
    #[DefineEnvironment('withAcl')]
    public function con_acl_se_sube_como_publico_y_se_cambia_la_visibilidad(): void
    {
        $ana = $this->makeUser('Ana');
        $key = "file-manager/{$ana->id}/foto.txt";

        $this->actingAs($ana)
            ->withHeader('Accept', 'application/json')
            ->post(route('afm.file_manager.upload'), [
                'visibility' => 'public',
                'files' => [UploadedFile::fake()->createWithContent('foto.txt', 'x')],
            ])
            ->assertOk();

        $this->assertSame('public-read', $this->s3->objects[$key]['acl']);

        $this->actingAs($ana)
            ->getJson(route('afm.file_manager.index'))
            ->assertOk()
            ->assertJsonPath('files.0.information.visibility', 'public');

        $this->actingAs($ana)
            ->postJson(route('afm.file.change-visibility'), ['file' => $key, 'visibility' => 'private'])
            ->assertOk()
            ->assertExactJson(['message' => 'File visibility changed successfully.']);

        $this->assertSame('private', $this->s3->objects[$key]['acl']);
    }

    #[Test]
    #[DefineEnvironment('withAcl')]
    public function con_acl_encendido_y_un_bucket_que_no_los_admite_responde_422(): void
    {
        $this->s3->aclsEnabled = false;
        $ana = $this->makeUser('Ana');
        $key = "file-manager/{$ana->id}/notas.txt";
        $this->s3->put($key, 'hola');

        $change = $this->actingAs($ana)
            ->postJson(route('afm.file.change-visibility'), ['file' => $key, 'visibility' => 'public'])
            ->assertStatus(422);

        $this->assertStringContainsString('Object Ownership', $change->json('message'));

        $upload = $this->actingAs($ana)
            ->withHeader('Accept', 'application/json')
            ->post(route('afm.file_manager.upload'), [
                'files' => [UploadedFile::fake()->createWithContent('otra.txt', 'x')],
            ])
            ->assertStatus(422);

        $this->assertStringContainsString('Object Ownership', $upload->json('message'));
        $this->assertFalse($this->s3->has("file-manager/{$ana->id}/otra.txt"));
    }
}
