<?php

/*
 * This file is part of the Apisearch Server
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Feel free to edit as you please, and have fun.
 *
 * @author Marc Morera <yuhu@mmoreram.com>
 */

declare(strict_types=1);

namespace Apisearch\Server\Domain\Resource;

use Apisearch\Exception\InvalidFormatException;
use React\Filesystem\Filesystem;
use React\HttpClient\Client as HTTPClient;
use React\HttpClient\Response as HTTPResponse;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use function React\Promise\reject;
use React\Stream\ReadableStreamInterface;

/**
 * Class ResourceLoader.
 */
class ResourceLoader
{
    private HTTPClient $client;
    private Filesystem $filesystem;

    /**
     * @param HTTPClient $client
     * @param Filesystem $filesystem
     */
    public function __construct(
        HTTPClient $client,
        Filesystem $filesystem
    ) {
        $this->client = $client;
        $this->filesystem = $filesystem;
    }

    /**
     * @param string $path
     *
     * @return PromiseInterface<ReadableStreamInterface|null>
     *
     * @throws InvalidFormatException
     */
    public function getByPath(string $path): PromiseInterface
    {
        if (
            0 === \strpos($path, 'http://') ||
            0 === \strpos($path, 'https://')
        ) {
            return $this->processHTTPResource($path);
        }

        if (0 === \strpos($path, 'file:///')) {
            return $this->processLocalResource(\str_replace('file:///', '/', $path));
        }

        return reject(new InvalidFormatException('Only http[s]:// or file:// is allowed'));
    }

    /**
     * @param string $path
     *
     * @return PromiseInterface<ReadableStreamInterface>
     */
    private function processLocalResource(string $path): PromiseInterface
    {
        $deferred = new Deferred();
        $file = $this
            ->filesystem
            ->file($path);

        $file
            ->exists()
            ->then(function () use ($file, $deferred) {
                return $file
                    ->open('r')
                    ->then(function (ReadableStreamInterface $fileStream) use ($deferred) {
                        $deferred->resolve($fileStream);
                    });
            })
            ->otherwise(function (\Throwable $throwable) use ($deferred) {
                $deferred->reject(new InvalidFormatException('Invalid import file - '.$throwable->getMessage()));
            });

        return $deferred->promise();
    }

    /**
     * @param string $path
     *
     * @return PromiseInterface<ReadableStreamInterface>
     */
    private function processHTTPResource(string $path): PromiseInterface
    {
        $deferred = new Deferred();
        $request = $this->client->request('GET', $path);
        $request->on('response', function (HTTPResponse $response) use ($deferred) {
            $deferred->resolve($response);
        });

        $request->on('error', function (\Throwable $throwable) use ($deferred) {
            $deferred->reject(new InvalidFormatException('Invalid import file - '.$throwable->getMessage()));
        });

        $request->end();

        return $deferred->promise();
    }
}
