<?php

namespace Innoboxrr\AwsFileManager\Http\Requests\File;

use Illuminate\Foundation\Http\FormRequest;
use Innoboxrr\AwsFileManager\Services\S3Service;

class DeleteFileRequest extends FormRequest
{
    private $s3Service;

    public function __construct()
    {
        parent::__construct();
        $this->s3Service = app(S3Service::class);
    }

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'file' => 'required|string',
        ];
    }

    public function messages()
    {
        return [
            'file.required' => 'Please provide a file key.',
        ];
    }

    public function handle()
    {
        $bucket = config('aws-file-manager.bucket');

        // La clave completa o una ruta relativa a la carpeta del usuario; fuera
        // de ella es un 403.
        $fileKey = $this->s3Service->userFileKey(auth()->id(), $this->input('file'));

        $this->s3Service->deleteObject($bucket, $fileKey);

        return response()->json(['message' => 'File deleted successfully.']);
    }
}
