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
use React\Http\Browser;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use function React\Promise\reject;
use React\Stream\ReadableStreamInterface;

/**
 * Class ResourceLoader.
 */
class ResourceLoader
{
    private Browser $browser;
    private Filesystem $filesystem;

    /**
     * @param Browser    $browser
     * @param Filesystem $filesystem
     */
    public function __construct(
        Browser $browser,
        Filesystem $filesystem
    ) {
        $this->browser = $browser;
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
        return $this
            ->browser
            ->requestStreaming('GET', $path)
            ->otherwise(function (\Throwable $throwable) {
                throw new InvalidFormatException('Invalid import file - '.$throwable->getMessage());
            });
    }
}
