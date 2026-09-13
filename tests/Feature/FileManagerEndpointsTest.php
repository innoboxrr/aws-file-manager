<?php

namespace Innoboxrr\AwsFileManager\Tests\Feature;

use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\Test;

final class FileManagerEndpointsTest extends FeatureTestCase
{
    #[Test]
    public function un_invitado_recibe_401_en_json_y_no_se_toca_s3(): void
    {
        $endpoints = [
            ['GET', 'afm.file_manager.index'],
            ['POST', 'afm.file_manager.upload'],
            ['POST', 'afm.file_manager.create-directory'],
            ['POST', 'afm.file.change-visibility'],
            ['POST', 'afm.file.delete'],
        ];

        foreach ($endpoints as [$method, $name]) {
            $this->json($method, route($name))
                ->assertStatus(401)
                ->assertExactJson(['message' => 'Unauthenticated.']);
        }

        $this->assertSame([], $this->s3->commands);
    }

    #[Test]
    public function el_indice_lista_solo_la_carpeta_del_usuario(): void
    {
        $ana = $this->makeUser('Ana');
        $beto = $this->makeUser('Beto');

        $this->s3->put("file-manager/{$ana->id}/");
        $this->s3->put("file-manager/{$ana->id}/notas.txt", 'hola', contentType: 'text/plain');
        $this->s3->put("file-manager/{$ana->id}/fotos/");
        $this->s3->put("file-manager/{$ana->id}/fotos/playa.png", 'png', contentType: 'image/png');
        $this->s3->put("file-manager/{$beto->id}/secreto.txt", 'de beto');
        $this->s3->put('otra-carpeta/fuera.txt', 'x');

        $response = $this->actingAs($ana)
            ->getJson(route('afm.file_manager.index'))
            ->assertOk()
            ->assertJsonPath('currentFile.name', 'notas.txt')
            ->assertJsonPath('currentFile.information.type', 'txt')
            ->assertJsonPath('currentFile.size', '4 bytes');

        $this->assertEqualsCanonicalizing(['fotos', 'notas.txt'], array_column($response->json('files'), 'name'));

        foreach ($this->s3->sent('ListObjectsV2') as $params) {
            $this->assertSame("file-manager/{$ana->id}/", $params['Prefix']);
        }
    }

    #[Test]
    public function el_indice_de_una_subcarpeta(): void
    {
        $ana = $this->makeUser('Ana');

        $this->s3->put("file-manager/{$ana->id}/fotos/");
        $this->s3->put("file-manager/{$ana->id}/fotos/playa.png", 'png', contentType: 'image/png');
        $this->s3->put("file-manager/{$ana->id}/notas.txt", 'hola');

        $this->actingAs($ana)
            ->getJson(route('afm.file_manager.index', ['directory' => 'fotos']))
            ->assertOk()
            ->assertJsonCount(1, 'files')
            ->assertJsonPath('files.0.name', 'playa.png')
            ->assertJsonPath('files.0.information.type', 'png');
    }

    #[Test]
    public function el_indice_crea_la_carpeta_del_usuario_la_primera_vez(): void
    {
        $ana = $this->makeUser('Ana');

        $this->actingAs($ana)
            ->getJson(route('afm.file_manager.index'))
            ->assertOk()
            ->assertExactJson(['files' => [], 'currentFile' => null]);

        $this->assertTrue($this->s3->has("file-manager/{$ana->id}/"));
    }

    #[Test]
    public function subir_guarda_los_archivos_en_la_carpeta_del_usuario(): void
    {
        $ana = $this->makeUser('Ana');

        $this->actingAs($ana)
            ->withHeader('Accept', 'application/json')
            ->post(route('afm.file_manager.upload'), [
                'directory' => 'docs',
                'files' => [
                    UploadedFile::fake()->createWithContent('a.txt', 'contenido a'),
                    UploadedFile::fake()->createWithContent('b.txt', 'contenido b'),
                ],
            ])
            ->assertOk()
            ->assertExactJson([
                ['message' => 'File uploaded successfully.', 'file' => "file-manager/{$ana->id}/docs/a.txt"],
                ['message' => 'File uploaded successfully.', 'file' => "file-manager/{$ana->id}/docs/b.txt"],
            ]);

        $this->assertSame('contenido a', $this->s3->objects["file-manager/{$ana->id}/docs/a.txt"]['body']);
        $this->assertSame('contenido b', $this->s3->objects["file-manager/{$ana->id}/docs/b.txt"]['body']);
    }

    #[Test]
    public function subir_sin_archivos_responde_422(): void
    {
        $this->actingAs($this->makeUser('Ana'))
            ->postJson(route('afm.file_manager.upload'))
            ->assertStatus(422)
            ->assertJsonValidationErrors('files');

        $this->assertSame([], $this->s3->sent('PutObject'));
    }

    #[Test]
    public function crear_un_directorio(): void
    {
        $ana = $this->makeUser('Ana');

        $this->actingAs($ana)
            ->postJson(route('afm.file_manager.create-directory'), ['directory' => 'proyectos'])
            ->assertOk()
            ->assertExactJson([
                'message' => 'Directory created successfully.',
                'directory' => "file-manager/{$ana->id}/proyectos/",
            ]);

        $this->assertTrue($this->s3->has("file-manager/{$ana->id}/proyectos/"));
    }

    #[Test]
    public function crear_un_directorio_que_ya_existe_responde_400(): void
    {
        $ana = $this->makeUser('Ana');
        $this->s3->put("file-manager/{$ana->id}/proyectos/");

        $this->actingAs($ana)
            ->postJson(route('afm.file_manager.create-directory'), ['directory' => 'proyectos'])
            ->assertStatus(400)
            ->assertExactJson(['message' => 'Directory already exists.']);

        $this->assertSame([], $this->s3->sent('PutObject'));
    }
}
