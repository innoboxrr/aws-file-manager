<?php

namespace Innoboxrr\AwsFileManager\Http\Requests\FileManager;

use Illuminate\Foundation\Http\FormRequest;
use Innoboxrr\AwsFileManager\Services\S3Service;

class ChangeVisibilityRequest extends FormRequest
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
            'visibility' => 'required|string|in:private,public',
        ];
    }

    public function messages()
    {
        return [
            'file.required' => 'Please provide a file key.',
            'visibility.required' => 'Please provide a visibility setting.',
            'visibility.in' => 'The visibility must be either private or public.',
        ];
    }

    public function handle()
    {
        $bucket = config('aws-file-manager.bucket');
        $userId = auth()->id();
        $fileKey = $this->s3Service->currentDir($userId, $this->input('file'));
        $visibility = $this->input('visibility') === 'public' ? 'public-read' : 'private';

        // Cambiar la visibilidad del archivo en S3
        $this->s3Service->s3Client->putObjectAcl([
            'Bucket' => $bucket,
            'Key' => $fileKey,
            'ACL' => $visibility,
        ]);

        return response()->json(['message' => 'File visibility changed successfully.']);
    }
}
