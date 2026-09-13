<?php

namespace Innoboxrr\AwsFileManager\Tests\Fakes;

final class FakeS3Error extends \RuntimeException
{
    public function __construct(public string $awsCode, string $message, public int $status)
    {
        parent::__construct($message);
    }
}
