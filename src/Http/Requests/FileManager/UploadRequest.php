<?php

namespace Innoboxrr\AwsFileManager\Http\Requests\FileManager;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Innoboxrr\AwsFileManager\Services\S3Service;
use Innoboxrr\AwsFileManager\Support\Visibility;

class UploadRequest extends FormRequest
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
            'files' => 'required|array',
            'files.*' => 'required|file',
            'directory' => 'nullable|string',
            'visibility' => ['nullable', 'string', Rule::in(Visibility::ACCEPTED)],
        ];
    }

    public function messages()
    {
        return [
            'files.required' => 'Please provide files to upload.',
            'files.*.required' => 'Please provide a file to upload.',
            'directory.required' => 'Please provide a directory.',
            'visibility.in' => 'The visibility must be private, public or public-read.',
        ];
    }

    public function handle()
    {
        $bucket = config('aws-file-manager.bucket');
        $userId = auth()->id();
        $directory = $this->s3Service->currentDir($userId, $this->input('directory', ''));
        $files = $this->file('files');
        $acl = Visibility::toAcl($this->input('visibility'));

        $responses = [];

        foreach ($files as $file) {

            $filePath = $directory . $file->getClientOriginalName();
            $body = fopen($file->getRealPath(), 'r');

            $this->s3Service->putObject($bucket, $filePath, $body, $acl, $file->getMimeType());

            $responses[] = ['message' => 'File uploaded successfully.', 'file' => $filePath];
        }

        return response()->json($responses);
    }
}
