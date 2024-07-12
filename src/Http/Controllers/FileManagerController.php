<?php

namespace Innoboxrr\AwsFileManager\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class FileManagerController extends Controller
{
    
    private $s3Client;

    public function __construct()
    {
        $this->s3Client = new S3Client([
            'version' => 'latest',
            'region' => env('AWS_DEFAULT_REGION'),
            'credentials' => [
                'key' => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ],
        ]);
    }

    public function index(Request $request)
    {
        $bucket = env('AWS_BUCKET');
        $directory = $request->input('directory', 'public'); // Directorio de entrada (por defecto es vacío)

        $results = $this->s3Client->listObjectsV2([
            'Bucket' => $bucket,
            'Prefix' => $directory, // Especifica el prefijo para el directorio de entrada
        ]);

        dd($results);

        if (isset($results['Contents'])) {
            foreach ($results['Contents'] as $object) {
                $key = $object['Key'];
                $metadata = $this->s3Client->headObject([
                    'Bucket' => $bucket,
                    'Key' => $key,
                ]);

                echo "Archivo: {$key}\n";
                echo "Metadata: " . json_encode($metadata['Metadata']) . "\n";
                echo "Tamaño: {$metadata['ContentLength']} bytes\n";
                echo "Última modificación: {$metadata['LastModified']}\n";
                echo str_repeat("-", 60) . "\n";
            }
        } else {
            echo "No se encontraron archivos en el bucket.";
        }
    }

}
