<?php

namespace Innoboxrr\AwsFileManager\Tests\Feature;

use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\Test;

final class UploadContentTypeTest extends FeatureTestCase
{
    #[Test]
    public function la_subida_guarda_el_tipo_y_el_indice_lo_informa(): void
    {
        $ana = $this->makeUser('Ana');

        $this->actingAs($ana)
            ->withHeader('Accept', 'application/json')
            ->post(route('afm.file_manager.upload'), [
                'files' => [UploadedFile::fake()->create('informe.pdf', 1, 'application/pdf')],
            ])
            ->assertOk();

        $this->assertSame('application/pdf', $this->s3->objects["file-manager/{$ana->id}/informe.pdf"]['content_type']);

        $this->actingAs($ana)
            ->getJson(route('afm.file_manager.index'))
            ->assertOk()
            ->assertJsonPath('files.0.name', 'informe.pdf')
            ->assertJsonPath('files.0.information.type', 'pdf');
    }
}
