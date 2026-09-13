<?php

namespace Innoboxrr\AwsFileManager\Http\Requests\FileManager;

use Aws\S3\Exception\S3Exception;
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

        // Validar que el usuario no suba nada fuera de su directorio
        $this->s3Service->validateUserPath(config('aws-file-manager.root'), $userId, $directory);

        $visibility = Visibility::normalize($this->input('visibility'));

        // Sin ACL no hay forma de hacer publico un objeto desde aqui. Se dice
        // antes de subir nada, en lugar de guardarlo privado en silencio.
        if ($visibility === Visibility::PUBLIC && ! $this->s3Service->usesAcl()) {
            throw Visibility::aclsDisabled();
        }

        $files = $this->file('files');

        $responses = [];

        foreach ($files as $file) {

            $filePath = $directory . $file->getClientOriginalName();
            $body = fopen($file->getRealPath(), 'r');

            try {
                $this->s3Service->putObject($bucket, $filePath, $body, Visibility::toAcl($visibility), $file->getMimeType());
            } catch (S3Exception $e) {
                throw Visibility::isAclRejection($e) ? Visibility::bucketRejectsAcls() : $e;
            }

            $responses[] = ['message' => 'File uploaded successfully.', 'file' => $filePath];
        }

        return response()->json($responses);
    }
}
