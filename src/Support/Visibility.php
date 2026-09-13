<?php

namespace Innoboxrr\AwsFileManager\Support;

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
}
