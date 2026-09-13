<?php

namespace Innoboxrr\AwsFileManager\Http\Requests\File;

use Aws\S3\Exception\S3Exception;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Innoboxrr\AwsFileManager\Services\S3Service;
use Innoboxrr\AwsFileManager\Support\Visibility;

class ChangeVisibilityRequest extends FormRequest
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
            'visibility' => ['required', 'string', Rule::in(Visibility::ACCEPTED)],
        ];
    }

    public function messages()
    {
        return [
            'file.required' => 'Please provide a file key.',
            'visibility.required' => 'Please provide a visibility setting.',
            'visibility.in' => 'The visibility must be private, public or public-read.',
        ];
    }

    public function handle()
    {
        $bucket = config('aws-file-manager.bucket');
        // Eliminar la primera barra diagonal solo si existe
        $fileKey = ltrim($this->input('file'), '/');

        // Sin ACL la visibilidad no se cambia desde aqui: un 422 que lo explica
        // en lugar del error de AWS.
        if (! $this->s3Service->usesAcl()) {
            throw Visibility::aclsDisabled();
        }

        try {
            $this->s3Service->putObjectAcl($bucket, $fileKey, Visibility::toAcl($this->input('visibility')));
        } catch (S3Exception $e) {
            throw Visibility::isAclRejection($e) ? Visibility::bucketRejectsAcls() : $e;
        }

        return response()->json(['message' => 'File visibility changed successfully.']);
    }
}
