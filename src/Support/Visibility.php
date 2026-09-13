<?php

namespace Innoboxrr\AwsFileManager\Support;

use Aws\S3\Exception\S3Exception;
use Illuminate\Validation\ValidationException;

/**
 * La visibilidad de un archivo en los dos endpoints que la reciben.
 */
final class Visibility
{
    public const PRIVATE = 'private';

    public const PUBLIC = 'public';

    /**
     * La subida hablaba en ACL de S3 (`public-read`) y el cambio de visibilidad
     * en palabras (`public`). Los dos endpoints aceptan las dos formas.
     */
    public const ACCEPTED = ['private', 'public', 'public-read'];

    public static function normalize(?string $value): string
    {
        return in_array($value, ['public', 'public-read'], true) ? self::PUBLIC : self::PRIVATE;
    }

    public static function toAcl(?string $value): string
    {
        return self::normalize($value) === self::PUBLIC ? 'public-read' : 'private';
    }

    /**
     * El paquete tiene los ACL desactivados (`use_acl`), que es lo que funciona
     * en un bucket nuevo.
     */
    public static function aclsDisabled(): ValidationException
    {
        return ValidationException::withMessages([
            'visibility' => 'Object ACLs are disabled for the file manager (AWS_FILE_MANAGER_USE_ACL=false), so file visibility cannot be set from here. S3 buckets created since April 2023 block ACLs: make files public with a bucket policy or CloudFront, or enable ACLs on the bucket and set AWS_FILE_MANAGER_USE_ACL=true.',
        ]);
    }

    /**
     * El paquete usa ACL, pero el bucket no los admite.
     */
    public static function bucketRejectsAcls(): ValidationException
    {
        return ValidationException::withMessages([
            'visibility' => 'The S3 bucket rejected the ACL because its Object Ownership setting disables ACLs. Set AWS_FILE_MANAGER_USE_ACL=false, or enable ACLs on the bucket.',
        ]);
    }

    public static function isAclRejection(S3Exception $e): bool
    {
        return $e->getAwsErrorCode() === 'AccessControlListNotSupported';
    }
}
