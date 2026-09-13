<?php

namespace Innoboxrr\AwsFileManager\Tests\Feature;

use PHPUnit\Framework\Attributes\Test;

final class DeleteFileTest extends FeatureTestCase
{
    #[Test]
    public function borra_un_archivo_de_la_carpeta_del_usuario(): void
    {
        $ana = $this->makeUser('Ana');
        $this->s3->put("file-manager/{$ana->id}/notas.txt", 'hola');
        $this->s3->put("file-manager/{$ana->id}/otra.txt", 'se queda');

        $this->actingAs($ana)
            ->postJson(route('afm.file.delete'), ['file' => 'notas.txt'])
            ->assertOk()
            ->assertExactJson(['message' => 'File deleted successfully.']);

        $this->assertFalse($this->s3->has("file-manager/{$ana->id}/notas.txt"));
        $this->assertTrue($this->s3->has("file-manager/{$ana->id}/otra.txt"));

        $deletes = $this->s3->sent('DeleteObject');

        $this->assertCount(1, $deletes);
        $this->assertSame('test-bucket', $deletes[0]['Bucket']);
        $this->assertSame("file-manager/{$ana->id}/notas.txt", $deletes[0]['Key']);
    }

    #[Test]
    public function borra_un_archivo_dentro_de_una_subcarpeta(): void
    {
        $ana = $this->makeUser('Ana');
        $this->s3->put("file-manager/{$ana->id}/docs/a.txt", 'a');

        $this->actingAs($ana)
            ->postJson(route('afm.file.delete'), ['file' => '/docs/a.txt'])
            ->assertOk();

        $this->assertFalse($this->s3->has("file-manager/{$ana->id}/docs/a.txt"));
    }

    #[Test]
    public function borrar_sin_file_responde_422(): void
    {
        $this->actingAs($this->makeUser('Ana'))
            ->postJson(route('afm.file.delete'))
            ->assertStatus(422)
            ->assertJsonValidationErrors('file');

        $this->assertSame([], $this->s3->sent('DeleteObject'));
    }
}
