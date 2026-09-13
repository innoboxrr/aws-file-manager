<?php

namespace Innoboxrr\AwsFileManager\Tests\Feature;

use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\Test;

final class VisibilitySpellingTest extends FeatureTestCase
{
    #[Test]
    public function la_subida_acepta_public_y_public_read(): void
    {
        $ana = $this->makeUser('Ana');

        $cases = ['public' => 'public-read', 'public-read' => 'public-read', 'private' => 'private'];

        foreach ($cases as $sent => $acl) {
            $this->actingAs($ana)
                ->withHeader('Accept', 'application/json')
                ->post(route('afm.file_manager.upload'), [
                    'visibility' => $sent,
                    'files' => [UploadedFile::fake()->createWithContent("{$sent}.txt", 'x')],
                ])
                ->assertOk();

            $this->assertSame($acl, $this->s3->objects["file-manager/{$ana->id}/{$sent}.txt"]['acl'], "Subida con {$sent}.");
        }
    }

    #[Test]
    public function cambiar_la_visibilidad_acepta_public_y_public_read(): void
    {
        $ana = $this->makeUser('Ana');
        $key = "file-manager/{$ana->id}/notas.txt";
        $this->s3->put($key, 'hola');

        foreach (['public-read' => 'public-read', 'private' => 'private', 'public' => 'public-read'] as $sent => $acl) {
            $this->actingAs($ana)
                ->postJson(route('afm.file.change-visibility'), ['file' => $key, 'visibility' => $sent])
                ->assertOk();

            $this->assertSame($acl, $this->s3->objects[$key]['acl'], "Cambio a {$sent}.");
        }
    }

    #[Test]
    public function una_visibilidad_desconocida_responde_422_en_los_dos(): void
    {
        $ana = $this->makeUser('Ana');
        $key = "file-manager/{$ana->id}/notas.txt";
        $this->s3->put($key, 'hola');

        $this->actingAs($ana)
            ->withHeader('Accept', 'application/json')
            ->post(route('afm.file_manager.upload'), [
                'visibility' => 'hidden',
                'files' => [UploadedFile::fake()->createWithContent('a.txt', 'x')],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('visibility');

        $this->actingAs($ana)
            ->postJson(route('afm.file.change-visibility'), ['file' => $key, 'visibility' => 'hidden'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('visibility');

        $this->assertSame([], $this->s3->sent('PutObject'));
        $this->assertSame([], $this->s3->sent('PutObjectAcl'));
    }
}
