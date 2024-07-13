<?php

namespace Innoboxrr\AwsFileManager\Http\Requests\FileManager;

use Illuminate\Foundation\Http\FormRequest;
use Innoboxrr\AwsFileManager\Services\S3Service;

class DeleteFileRequest extends FormRequest
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
        $fileKey = $this->s3Service->currentDir($userId, $this->input('file'));

        // Eliminar el archivo en S3
        $this->s3Service->s3Client->deleteObject([
            'Bucket' => $bucket,
            'Key' => $fileKey,
        ]);

        return response()->json(['message' => 'File deleted successfully.']);
    }
}
