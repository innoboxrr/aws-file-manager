<?php

namespace Innoboxrr\AwsFileManager\Http\Requests\FileManager;

use Illuminate\Foundation\Http\FormRequest;
use Innoboxrr\AwsFileManager\Services\S3Service;

class CreateDirectoryRequest extends FormRequest
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
            'directory' => 'required|string',
        ];
    }

    public function messages()
    {
        return [
            'directory.required' => 'Please provide a directory name.',
        ];
    }

    public function handle()
    {
        $bucket = config('aws-file-manager.bucket');
        $userId = auth()->id();
        $directory = $this->s3Service->currentDir($userId, $this->input('directory', ''));

        // Crear el directorio en S3
        if (!$this->s3Service->directoryExists($bucket, $directory)) {
            $this->s3Service->createDirectory($bucket, $directory);
            return response()->json([
                'message' => 'Directory created successfully.',
                'directory' => $directory,
            ]);
        } else {
            return response()->json(['message' => 'Directory already exists.'], 400);
        }
    }
}
