# AWS File Manager

`innoboxrr/aws-file-manager` gives every authenticated user a private folder in an S3 bucket, with a JSON API to browse it, upload files, create folders, delete files and change their visibility. It is the backend of the file manager in the admin UI that `innoboxrr/laravel-setup` installs.

The package has no facade. Use it through its HTTP endpoints, or resolve `S3Service` from the container for low-level access.

## Requirements

- PHP 8.3 or newer.
- Laravel 13.
- Laravel Sanctum. It is installed as a dependency, and the routes use the `auth:sanctum` guard.
- An S3 bucket.

## Installation

```bash
composer require innoboxrr/aws-file-manager
```

The service providers are registered through package discovery.

## Configuration

The configuration is loaded without publishing it. To copy it to `config/aws-file-manager.php`:

```bash
php artisan vendor:publish --provider="Innoboxrr\AwsFileManager\Providers\AppServiceProvider" --tag=config
```

Set the bucket in `.env`:

```dotenv
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=my-bucket
AWS_URL=https://my-bucket.s3.amazonaws.com
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_FILE_MANAGER_USE_ACL=false
```

| Key | Env | Default | Purpose |
|---|---|---|---|
| `root` | — | `file-manager` | Prefix that holds one folder per user. |
| `bucket` | `AWS_BUCKET` | — | Required. |
| `region` | `AWS_DEFAULT_REGION` | — | Required. |
| `url` | `AWS_URL` | — | Base of the `source` URL in the index. |
| `credentials.key` / `credentials.secret` | `AWS_ACCESS_KEY_ID` / `AWS_SECRET_ACCESS_KEY` | — | Optional. When empty, the AWS SDK default credential chain is used (environment, profile, instance role). |
| `use_acl` | `AWS_FILE_MANAGER_USE_ACL` | `false` | Whether visibility is managed with object ACLs. See below. |

Until `AWS_BUCKET` and `AWS_DEFAULT_REGION` are set, every endpoint answers `503` with `{"message": "The file manager is not configured. Set AWS_BUCKET and AWS_DEFAULT_REGION."}`.

### Object ACLs and visibility

S3 buckets created since April 2023 have ACLs disabled (Object Ownership: *Bucket owner enforced*). They reject any request that carries an `ACL`, even `private`. That is why `use_acl` is off by default:

| | `use_acl = false` (default, new buckets) | `use_acl = true` (buckets with ACLs enabled) |
|---|---|---|
| Upload | Stored without an ACL, so the file is private. Asking for `public` answers `422`. | Stored with `private` or `public-read`. |
| Change visibility | `422` explaining that ACLs are disabled. | Sets the object ACL. |
| `information.visibility` in the index | Always `private`: public access is up to the bucket policy. | Read from the object ACL. |

With ACLs disabled, make files public with a bucket policy or CloudFront. If `use_acl` is on and the bucket still rejects ACLs, uploads and visibility changes answer `422` saying so, instead of an AWS error.

Visibility accepts `private`, `public` and `public-read` on both endpoints; `public` and `public-read` mean the same.

## Authentication and CSRF

The routes are in the `web` middleware group, behind `auth:sanctum`, for a single-page app on the same domain that uses the session cookie:

1. The SPA requests `GET /sanctum/csrf-cookie` (or any page in the `web` group) and receives the `XSRF-TOKEN` cookie.
2. axios sends it back in the `X-XSRF-TOKEN` header on every `POST`.
3. Without it, a `POST` answers `419`.

A guest asking for JSON gets `401` with `{"message": "Unauthenticated."}`.

## User folders

Each user works inside `file-manager/{user id}/` and can't reach anything else:

- `directory` (index, upload, create-directory) is relative to the user's folder: `docs` means `file-manager/1/docs/`.
- `file` (delete, change-visibility) is either the full key, as returned by the upload (`file`) and the index (`key`), or a path relative to the user's folder: `file-manager/1/docs/a.pdf` and `docs/a.pdf` are the same file.
- Anything outside the user's folder answers `403` with `{"message": "Access to this path is denied."}`. That includes another user's key, `file-manager/10/…` for user 1, and paths with `..` segments.

## Endpoints

| Method | URI | Name | Parameters | Response |
|---|---|---|---|---|
| GET | `/afm/file_manager/index` | `afm.file_manager.index` | `directory` (optional) | `{"files": [...], "currentFile": {...} or null}` |
| POST | `/afm/file_manager/upload` | `afm.file_manager.upload` | `files[]` (required), `directory`, `visibility` | `[{"message": "File uploaded successfully.", "file": "file-manager/1/docs/a.pdf"}]` |
| POST | `/afm/file_manager/create-directory` | `afm.file_manager.create-directory` | `directory` (required) | `{"message": "Directory created successfully.", "directory": "file-manager/1/docs/"}`; `400` if it already exists |
| POST | `/afm/file/delete` | `afm.file.delete` | `file` (required) | `{"message": "File deleted successfully."}` |
| POST | `/afm/file/change-visibility` | `afm.file.change-visibility` | `file`, `visibility` (required) | `{"message": "File visibility changed successfully."}`; `404` if the file does not exist; `422` when ACLs are off |

Validation errors answer `422` with Laravel's usual `message` and `errors`.

The index creates the requested folder if it doesn't exist yet. Folders come first, then files; `currentFile` is the first file, marked `"current": true`.

```json
{
    "files": [
        {
            "name": "docs",
            "key": "file-manager/1/docs/",
            "size": "N/A",
            "source": null,
            "current": false,
            "information": { "type": "directory", "created_at": "N/A", "updated_at": "N/A" }
        },
        {
            "name": "report.pdf",
            "key": "file-manager/1/report.pdf",
            "size": "1.20 MB",
            "source": "https://my-bucket.s3.amazonaws.com/file-manager/1/report.pdf",
            "signedUrl": "https://my-bucket.s3.amazonaws.com/file-manager/1/report.pdf?X-Amz-...",
            "url": "https://my-bucket.s3.amazonaws.com/file-manager/1/report.pdf",
            "current": false,
            "information": {
                "type": "pdf",
                "created_at": "2026-09-13 10:00:00",
                "updated_at": "2026-09-13 10:00:00",
                "visibility": "private"
            }
        }
    ],
    "currentFile": { "name": "report.pdf", "current": true, "...": "..." }
}
```

`signedUrl` is valid for 20 minutes. Uploads store the file's `Content-Type`, so browsers display images and PDFs instead of downloading them.

## Using `S3Service` directly

```php
use Innoboxrr\AwsFileManager\Services\S3Service;

$s3 = app(S3Service::class);
$bucket = config('aws-file-manager.bucket');

$s3->putObject($bucket, 'file-manager/1/report.pdf', fopen($path, 'r'), 'private', 'application/pdf');
$url = $s3->getSignedUrl($bucket, 'file-manager/1/report.pdf');
$s3->deleteObject($bucket, 'file-manager/1/report.pdf');
```

`S3Service` is a singleton. To use your own client, bind an instance:

```php
$this->app->instance(S3Service::class, new S3Service($s3Client));
```

## Testing

```bash
composer install
vendor/bin/phpunit
```

The feature tests bind an `S3Service` whose client is the real AWS SDK client with an in-memory bucket as its handler. The SDK builds, validates and signs every command as in production, and nothing leaves the machine.

## License

The MIT License (MIT). Please see [License File](LICENSE.txt) for more information.
