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
        $userId = auth()->id();

        // currentDir() devuelve la forma de un directorio, con `/` al final; la
        // clave de un archivo no la lleva.
        $fileKey = rtrim($this->s3Service->currentDir($userId, $this->input('file')), '/');

        $this->s3Service->deleteObject($bucket, $fileKey);

        return response()->json(['message' => 'File deleted successfully.']);
    }
}
