<?php

namespace Innoboxrr\AwsFileManager\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class FileManagerController extends Controller
{
    
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function listFilesWithMetadata()
    {
        $disk = Storage::disk(config('aws-file-manager.disk')); // Recupera el disco configurado
        $files = $disk->allFiles(); // Recupera todos los archivos en el bucket

        foreach ($files as $file) {
            $metadata = $disk->getMetaData($file);
            
            echo "Archivo: {$file}\n";
            echo "Metadata: " . json_encode($metadata) . "\n";
            echo "Tamaño: {$metadata['size']} bytes\n";
            echo "Última modificación: " . date('Y-m-d H:i:s', $metadata['timestamp']) . "\n";
            echo str_repeat("-", 60) . "\n";
        }
    }

}
