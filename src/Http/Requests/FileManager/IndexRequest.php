<?php

namespace Innoboxrr\AwsFileManager\Http\Requests\FileManager;

use Illuminate\Foundation\Http\FormRequest;
use Innoboxrr\AwsFileManager\Support\Utils\MimeTypeMapper;
use Innoboxrr\AwsFileManager\Services\S3Service;

class IndexRequest extends FormRequest
{
    private $s3Service;

    public function __construct()
    {
        parent::__construct();
        $this->s3Service = new S3Service();
    }

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            // Añade reglas de validación si es necesario
        ];
    }

    public function messages()
    {
        return [
            // Añade mensajes personalizados si es necesario
        ];
    }

    public function attributes()
    {
        return [
            // Añade atributos personalizados si es necesario
        ];
    }

    public function handle()
    {
        $bucket = config('aws-file-manager.bucket');
        $userId = auth()->id();
        $directory = $this->s3Service->currentDir($userId, $this->input('directory', ''));
        $baseUrl = config('aws-file-manager.url');

        // Validar que el usuario no acceda fuera de su directorio
        $this->s3Service->validateUserPath(config('aws-file-manager.root'), $userId, $directory);

        // Si el directorio no existe, crearlo
        if (!$this->s3Service->directoryExists($bucket, $directory)) {
            $this->s3Service->createDirectory($bucket, $directory);
        }

        $results = $this->s3Service->listObjects($bucket, $directory);

        $files = $this->processFiles($results, $bucket, $baseUrl, $directory);

        return response()->json([
            'files' => $files['files'],
            'currentFile' => $files['currentFile'],
        ]);
    }

    private function processFiles($results, $bucket, $baseUrl, $directory)
    {
        $files = [];
        $currentFile = null;

        if (isset($results['CommonPrefixes'])) {
            foreach ($results['CommonPrefixes'] as $prefix) {
                if ($prefix['Prefix'] !== rtrim($directory, '/') . '/') {
                    $files[] = [
                        'name' => basename(rtrim($prefix['Prefix'], '/')),
                        'size' => 'N/A',
                        'source' => null,
                        'current' => false,
                        'information' => [
                            'type' => 'directory',
                            'created_at' => 'N/A',
                            'updated_at' => 'N/A',
                        ],
                    ];
                }
            }
        }

        if (isset($results['Contents'])) {
            foreach ($results['Contents'] as $object) {
                $key = $object['Key'];

                // Omitir la carpeta raíz
                if ($key === rtrim($directory, '/') . '/') {
                    continue;
                }

                $metadata = $this->s3Service->getObjectMetadata($bucket, $key);
                $acl = $this->s3Service->getObjectAcl($bucket, $key);
                $file = $this->formatFileData($bucket, $key, $metadata, $baseUrl, $acl);
                $files[] = $file;

                if (!$currentFile) {
                    $currentFile = $file;
                    $currentFile['current'] = true;
                }
            }
        }

        return ['files' => $files, 'currentFile' => $currentFile];
    }

    private function formatFileData($bucket, $key, $metadata, $baseUrl, $acl)
    {
        $file = [
            'name' => basename($key),
            'size' => $this->s3Service->formatSizeUnits($metadata['ContentLength']),
            'source' => $baseUrl . '/' . $key,
            'signedUrl' => $this->s3Service->getSignedUrl($bucket, $key),
            'url' => $this->s3Service->getObjectUrl($bucket, $key),
            'current' => false,
            'information' => [
                'type' => MimeTypeMapper::getMimeTypeFromContent($metadata['ContentType']),
                'created_at' => $metadata['LastModified']->format('Y-m-d H:i:s'),
                'updated_at' => $metadata['LastModified']->format('Y-m-d H:i:s'),
                'visibility' => $this->s3Service->determineVisibility($acl),
            ],
        ];

        // Si es una imagen, agrega información adicional
        /*
        if (strpos($metadata['ContentType'], 'image') !== false) {
            $file['information']['dimensions'] = null; // No se puede obtener dinámicamente
            $file['information']['resolution'] = null; // No se puede obtener dinámicamente
        }
        */

        return $file;
    }
}
