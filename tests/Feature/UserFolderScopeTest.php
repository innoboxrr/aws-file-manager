<?php

namespace Innoboxrr\AwsFileManager\Tests\Feature;

use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\Test;

/**
 * Cada usuario solo alcanza file-manager/{su id}/.
 */
final class UserFolderScopeTest extends FeatureTestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        // Con ACL, para que un cambio de visibilidad permitido llegue hasta S3.
        $app['config']->set('aws-file-manager.use_acl', true);
    }

    #[Test]
    public function cambiar_la_visibilidad_dentro_de_la_carpeta_con_la_clave_completa_o_relativa(): void
    {
        $ana = $this->makeUser('Ana');
        $key = "file-manager/{$ana->id}/docs/a.txt";
        $this->s3->put($key, 'a');

        $this->actingAs($ana)
            ->postJson(route('afm.file.change-visibility'), ['file' => $key, 'visibility' => 'public'])
            ->assertOk();

        $this->assertSame('public-read', $this->s3->objects[$key]['acl']);

        $this->actingAs($ana)
            ->postJson(route('afm.file.change-visibility'), ['file' => 'docs/a.txt', 'visibility' => 'private'])
            ->assertOk();

        $this->assertSame('private', $this->s3->objects[$key]['acl']);
    }

    #[Test]
    public function cambiar_la_visibilidad_fuera_de_la_carpeta_responde_403(): void
    {
        $ana = $this->makeUser('Ana');
        $beto = $this->makeUser('Beto');

        $this->s3->put("file-manager/{$beto->id}/secreto.txt", 'de beto');
        // Para Ana (id 1), la carpeta del usuario 10 empieza igual que la suya.
        $this->s3->put("file-manager/{$ana->id}0/ajeno.txt", 'del usuario 10');

        $outside = [
            "file-manager/{$beto->id}/secreto.txt",
            "/file-manager/{$beto->id}/secreto.txt",
            "file-manager/{$ana->id}0/ajeno.txt",
            "file-manager/{$ana->id}/../{$beto->id}/secreto.txt",
            "docs/../../{$beto->id}/secreto.txt",
        ];

        foreach ($outside as $file) {
            $this->actingAs($ana)
                ->postJson(route('afm.file.change-visibility'), ['file' => $file, 'visibility' => 'public'])
                ->assertForbidden()
                ->assertJsonPath('message', 'Access to this path is denied.');
        }

        $this->assertSame([], $this->s3->sent('PutObjectAcl'));
        $this->assertSame('private', $this->s3->objects["file-manager/{$beto->id}/secreto.txt"]['acl']);
    }

    #[Test]
    public function cambiar_la_visibilidad_de_un_archivo_que_no_existe_responde_404(): void
    {
        $this->actingAs($this->makeUser('Ana'))
            ->postJson(route('afm.file.change-visibility'), ['file' => 'no-existe.txt', 'visibility' => 'public'])
            ->assertNotFound()
            ->assertExactJson(['message' => 'File not found.']);
    }

    #[Test]
    public function borrar_con_la_clave_que_devuelve_la_subida(): void
    {
        $ana = $this->makeUser('Ana');

        $key = $this->actingAs($ana)
            ->withHeader('Accept', 'application/json')
            ->post(route('afm.file_manager.upload'), [
                'files' => [UploadedFile::fake()->createWithContent('a.txt', 'a')],
            ])
            ->assertOk()
            ->json('0.file');

        $this->assertTrue($this->s3->has($key));

        $this->actingAs($ana)
            ->postJson(route('afm.file.delete'), ['file' => $key])
            ->assertOk();

        $this->assertFalse($this->s3->has($key));
    }

    #[Test]
    public function borrar_fuera_de_la_carpeta_responde_403(): void
    {
        $ana = $this->makeUser('Ana');
        $beto = $this->makeUser('Beto');
        $this->s3->put("file-manager/{$beto->id}/secreto.txt", 'de beto');

        foreach (["file-manager/{$beto->id}/secreto.txt", "../{$beto->id}/secreto.txt"] as $file) {
            $this->actingAs($ana)
                ->postJson(route('afm.file.delete'), ['file' => $file])
                ->assertForbidden();
        }

        $this->assertTrue($this->s3->has("file-manager/{$beto->id}/secreto.txt"));
        $this->assertSame([], $this->s3->sent('DeleteObject'));
    }

    #[Test]
    public function el_indice_la_subida_y_crear_directorio_no_salen_de_la_carpeta(): void
    {
        $ana = $this->makeUser('Ana');
        $beto = $this->makeUser('Beto');

        $this->actingAs($ana)
            ->getJson(route('afm.file_manager.index', ['directory' => "../{$beto->id}"]))
            ->assertForbidden();

        $this->actingAs($ana)
            ->postJson(route('afm.file_manager.create-directory'), ['directory' => "../{$beto->id}/intruso"])
            ->assertForbidden();

        $this->actingAs($ana)
            ->withHeader('Accept', 'application/json')
            ->post(route('afm.file_manager.upload'), [
                'directory' => "../{$beto->id}",
                'files' => [UploadedFile::fake()->createWithContent('intruso.txt', 'x')],
            ])
            ->assertForbidden();

        $this->assertSame([], $this->s3->commands);
    }

    #[Test]
    public function el_indice_da_la_clave_que_esperan_borrar_y_cambiar_la_visibilidad(): void
    {
        $ana = $this->makeUser('Ana');
        $this->s3->put("file-manager/{$ana->id}/notas.txt", 'hola');
        $this->s3->put("file-manager/{$ana->id}/fotos/");

        $files = collect(
            $this->actingAs($ana)->getJson(route('afm.file_manager.index'))->assertOk()->json('files')
        )->keyBy('name');

        $this->assertSame("file-manager/{$ana->id}/notas.txt", $files['notas.txt']['key']);
        $this->assertSame("file-manager/{$ana->id}/fotos/", $files['fotos']['key']);
    }
}
