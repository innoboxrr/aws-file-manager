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
            'files' => 'required|array',
            'files.*' => 'required|file',
            'directory' => 'nullable|string',
            'visibility' => 'nullable|string|in:private,public-read',
        ];
    }

    public function messages()
    {
        return [
            'files.required' => 'Please provide files to upload.',
            'files.*.required' => 'Please provide a file to upload.',
            'directory.required' => 'Please provide a directory.',
        ];
    }

    public function handle()
    {
        $bucket = config('aws-file-manager.bucket');
        $userId = auth()->id();
        $directory = $this->s3Service->currentDir($userId, $this->input('directory', ''));
        $files = $this->file('files');

        $responses = [];

        foreach ($files as $file) {

            $filePath = $directory . $file->getClientOriginalName();
            $body = fopen($file->getRealPath(), 'r');
            $acl = $this->input('visibility', 'private');

            $this->s3Service->putObject($bucket, $filePath, $body, $acl);

            $responses[] = ['message' => 'File uploaded successfully.', 'file' => $filePath];
        }

        return response()->json($responses);
    }
}
