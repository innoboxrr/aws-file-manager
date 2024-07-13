<?php

namespace Innoboxrr\AwsFileManager\Services;

use Aws\S3\S3Client;

class S3Service
{
    private $s3Client;

    public function __construct()
    {
        $this->initializeS3Client();
    }

    private function initializeS3Client()
    {
        $this->s3Client = new S3Client([
            'version' => 'latest',
            'region' => config('aws-file-manager.region'),
            'credentials' => [
                'key' => config('aws-file-manager.credentials.key'),
                'secret' => config('aws-file-manager.credentials.secret'),
            ],
        ]);
    }

    public function listObjects($bucket, $directory)
    {
        return $this->s3Client->listObjectsV2([
            'Bucket' => $bucket,
            'Prefix' => $directory,
            'Delimiter' => '/',
        ]);
    }

    public function getObjectMetadata($bucket, $key)
    {
        return $this->s3Client->headObject([
            'Bucket' => $bucket,
            'Key' => $key,
        ]);
    }

    public function getObjectAcl($bucket, $key)
    {
        return $this->s3Client->getObjectAcl([
            'Bucket' => $bucket,
            'Key' => $key,
        ]);
    }

    public function determineVisibility($acl)
    {
        foreach ($acl['Grants'] as $grant) {
            if (isset($grant['Grantee']['URI']) &&
                $grant['Grantee']['URI'] === 'http://acs.amazonaws.com/groups/global/AllUsers' &&
                $grant['Permission'] === 'READ') {
                return 'public';
            }
        }
        return 'private';
    }

    public function formatSizeUnits($bytes)
    {
        if ($bytes >= 1073741824) {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        } elseif ($bytes > 1) {
            $bytes = $bytes . ' bytes';
        } elseif ($bytes == 1) {
            $bytes = $bytes . ' byte';
        } else {
            $bytes = '0 bytes';
        }

        return $bytes;
    }

    public function currentDir($userId, $inputDirectory = '')
    {
        $root = rtrim(config('aws-file-manager.root'), '/');
        $directory = trim($inputDirectory, '/');
        return $root . '/' . $userId . '/' . $directory;
    }

    public function directoryExists($bucket, $directory)
    {
        $results = $this->listObjects($bucket, $directory);
        return isset($results['Contents']) && count($results['Contents']) > 0;
    }

    public function createDirectory($bucket, $directory)
    {
        // S3 no tiene una operación de "crear directorio" explícita, pero puedes simularla subiendo un archivo vacío
        $this->s3Client->putObject([
            'Bucket' => $bucket,
            'Key' => rtrim($directory, '/') . '/',
            'Body' => '',
        ]);
    }

    public function validateUserPath($root, $userId, $path)
    {
        $userRoot = $root . '/' . $userId;
        if (strpos($path, $userRoot) !== 0) {
            throw new \Exception("Access to this path is denied.");
        }
    }

    public function getSignedUrl($bucket, $key)
    {
        try {
            $cmd = $this->s3Client->getCommand('GetObject', [
                'Bucket' => $bucket,
                'Key' => $key
            ]);

            $request = $this->s3Client->createPresignedRequest($cmd, '+20 minutes');

            return (string)$request->getUri();
        } catch (AwsException $e) {
            // Manejar la excepción si es necesario
            return null;
        }
    }
}
