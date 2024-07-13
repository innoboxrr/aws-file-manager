<?php

namespace Innoboxrr\AwsFileManager\Http\Requests\FileManager;

use Illuminate\Foundation\Http\FormRequest;
use Innoboxrr\AwsFileManager\Services\S3Service;

class UploadRequest extends FormRequest
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
            'file' => 'required|file',
            'directory' => 'required|string',
        ];
    }

    public function messages()
    {
        return [
            'file.required' => 'Please provide a file to upload.',
            'directory.required' => 'Please provide a directory.',
        ];
    }

    public function handle()
    {
        $bucket = config('aws-file-manager.bucket');
        $userId = auth()->id();
        $directory = $this->s3Service->currentDir($userId, $this->input('directory', ''));
        $file = $this->file('file');
        $filePath = $directory . '/' . $file->getClientOriginalName();

        // Subir el archivo a S3
        $this->s3Service->s3Client->putObject([
            'Bucket' => $bucket,
            'Key' => $filePath,
            'Body' => fopen($file->getRealPath(), 'r'),
            'ACL' => 'private', // O 'public-read' si deseas que el archivo sea público
        ]);

        return response()->json(['message' => 'File uploaded successfully.']);
    }
}
