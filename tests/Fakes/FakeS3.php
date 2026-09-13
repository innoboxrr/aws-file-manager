<?php

namespace Innoboxrr\AwsFileManager\Tests\Fakes;

use Aws\Api\DateTimeResult;
use Aws\CommandInterface;
use Aws\Result;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;

/**
 * Un bucket en memoria detras del cliente real del SDK.
 *
 * Se conecta como `handler` del S3Client: el SDK construye, valida y firma cada
 * comando igual que en produccion, y en el ultimo paso, en lugar de mandarlo a
 * la red, lo entrega aqui. Asi los tests ejercen el S3Service de verdad, con
 * los parametros que AWS recibiria, y sin red.
 *
 * Con `$aclsEnabled = false` se comporta como un bucket creado desde abril de
 * 2023 (Object Ownership "Bucket owner enforced"): rechaza cualquier ACL con
 * AccessControlListNotSupported.
 */
final class FakeS3
{
    /** @var array<string, array{body: string, acl: string, content_type: string}> */
    public array $objects = [];

    /** @var array<int, array{name: string, params: array<string, mixed>}> */
    public array $commands = [];

    public function __construct(public bool $aclsEnabled = true) {}

    public function client(): S3Client
    {
        return new S3Client([
            'version' => 'latest',
            'region' => 'us-east-1',
            'credentials' => ['key' => 'test-key', 'secret' => 'test-secret'],
            'handler' => $this,
        ]);
    }

    public function put(string $key, string $body = '', string $acl = 'private', string $contentType = 'binary/octet-stream'): void
    {
        $this->objects[$key] = ['body' => $body, 'acl' => $acl, 'content_type' => $contentType];
    }

    public function has(string $key): bool
    {
        return isset($this->objects[$key]);
    }

    /**
     * Los parametros de cada comando con ese nombre que llego al bucket.
     *
     * @return array<int, array<string, mixed>>
     */
    public function sent(string $name): array
    {
        return array_values(array_map(
            fn (array $command): array => $command['params'],
            array_filter($this->commands, fn (array $command): bool => $command['name'] === $name)
        ));
    }

    public function __invoke(CommandInterface $command, ?RequestInterface $request = null)
    {
        $params = $command->toArray();

        $this->commands[] = ['name' => $command->getName(), 'params' => $params];

        try {
            $method = lcfirst($command->getName());

            return Create::promiseFor(new Result($this->{$method}($params, $request)));
        } catch (FakeS3Error $error) {
            return Create::rejectionFor(new S3Exception($error->getMessage(), $command, [
                'code' => $error->awsCode,
                'response' => new Response($error->status),
            ]));
        }
    }

    private function listObjectsV2(array $params): array
    {
        $prefix = $params['Prefix'] ?? '';
        $delimiter = $params['Delimiter'] ?? null;
        $contents = [];
        $prefixes = [];

        ksort($this->objects);

        foreach ($this->objects as $key => $object) {
            if (! str_starts_with($key, $prefix)) {
                continue;
            }

            $rest = substr($key, strlen($prefix));

            if ($delimiter !== null && ($position = strpos($rest, $delimiter)) !== false) {
                $prefixes[$prefix . substr($rest, 0, $position + 1)] = true;

                continue;
            }

            $contents[] = ['Key' => $key, 'Size' => strlen($object['body'])];
        }

        // Como AWS: las claves vacias no vienen en la respuesta.
        $result = ['KeyCount' => count($contents) + count($prefixes)];

        if ($contents !== []) {
            $result['Contents'] = $contents;
        }

        if ($prefixes !== []) {
            $result['CommonPrefixes'] = array_map(fn (string $p): array => ['Prefix' => $p], array_keys($prefixes));
        }

        return $result;
    }

    private function putObject(array $params, ?RequestInterface $request): array
    {
        if (isset($params['ACL'])) {
            $this->rejectAclsWhenDisabled();
        }

        $this->objects[$params['Key']] = [
            'body' => $request !== null ? (string) $request->getBody() : (string) $params['Body'],
            'acl' => $params['ACL'] ?? 'private',
            'content_type' => $params['ContentType'] ?? 'binary/octet-stream',
        ];

        return ['ETag' => '"fake"'];
    }

    private function headObject(array $params): array
    {
        $object = $this->find($params['Key'], 'NotFound');

        return [
            'ContentLength' => strlen($object['body']),
            'ContentType' => $object['content_type'],
            'LastModified' => new DateTimeResult('2026-09-01T10:00:00Z'),
        ];
    }

    private function getObjectAcl(array $params): array
    {
        $object = $this->find($params['Key']);

        $grants = [['Grantee' => ['Type' => 'CanonicalUser', 'ID' => 'owner'], 'Permission' => 'FULL_CONTROL']];

        if ($object['acl'] === 'public-read') {
            $grants[] = ['Grantee' => ['Type' => 'Group', 'URI' => 'http://acs.amazonaws.com/groups/global/AllUsers'], 'Permission' => 'READ'];
        }

        return ['Grants' => $grants];
    }

    private function putObjectAcl(array $params): array
    {
        $this->rejectAclsWhenDisabled();
        $this->find($params['Key']);

        $this->objects[$params['Key']]['acl'] = $params['ACL'];

        return [];
    }

    private function deleteObject(array $params): array
    {
        // S3 no distingue: borrar lo que no existe tambien es un 204.
        unset($this->objects[$params['Key']]);

        return [];
    }

    /**
     * @return array{body: string, acl: string, content_type: string}
     */
    private function find(string $key, string $code = 'NoSuchKey'): array
    {
        return $this->objects[$key] ?? throw new FakeS3Error($code, 'The specified key does not exist.', 404);
    }

    private function rejectAclsWhenDisabled(): void
    {
        if (! $this->aclsEnabled) {
            throw new FakeS3Error('AccessControlListNotSupported', 'The bucket does not allow ACLs', 400);
        }
    }
}
