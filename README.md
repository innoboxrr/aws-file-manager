# AWS File Manager

`innoboxrr/aws-file-manager` is a Laravel package for managing files in AWS S3.

## Installation

You can install the package via Composer:

```bash
composer require innoboxrr/aws-file-manager
```

## Configuration

You need to publish the configuration file:

```bash
php artisan vendor:publish --provider="Innoboxrr\\AWSFileManager\\AWSFileManagerServiceProvider"
```

Then, configure your AWS credentials in the `.env` file:

```
AWS_ACCESS_KEY_ID=your-access-key-id
AWS_SECRET_ACCESS_KEY=your-secret-access-key
AWS_DEFAULT_REGION=your-default-region
AWS_BUCKET=your-bucket-name
```

## Usage

### Upload a File

```php
use Innoboxrr\AWSFileManager\Facades\AWSFileManager;

AWSFileManager::upload($filePath, $destinationPath);
```

### Download a File

```php
use Innoboxrr\AWSFileManager\Facades\AWSFileManager;

$fileContent = AWSFileManager::download($filePath);
```

### Delete a File

```php
use Innoboxrr\AWSFileManager\Facades\AWSFileManager;

AWSFileManager::delete($filePath);
```

## Testing

```bash
composer test
```

## License

The MIT License (MIT). Please see [License File](LICENSE.txt) for more information.