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

namespace Apisearch\Server\Domain\Repository\MetadataRepository;

use Apisearch\Repository\RepositoryReference;
use React\Promise\PromiseInterface;
use function React\Promise\resolve;

/**
 * Class DiskMetadataRepository.
 */
class DiskMetadataRepository extends InMemoryMetadataRepository
{
    /**
     * @var string
     */
    private $file;

    /**
     * @param string $file
     */
    public function __construct(string $file)
    {
        $this->file = $file;
    }

    /**
     * @param RepositoryReference $repositoryReference
     * @param string              $key
     * @param mixed               $value
     *
     * @return PromiseInterface
     */
    public function set(RepositoryReference $repositoryReference, string $key, $value): PromiseInterface
    {
        return $this
            ->loadFromDisk()
            ->then(function () use ($repositoryReference, $key, $value) {
                return parent::set($repositoryReference, $key, $value)
                    ->then(function () {
                        return $this->saveToDisk();
                    });
            });
    }

    /**
     * @param RepositoryReference $repositoryReference
     * @param string              $key
     *
     * @return PromiseInterface
     */
    public function delete(RepositoryReference $repositoryReference, string $key): PromiseInterface
    {
        return $this
            ->loadFromDisk()
            ->then(function () use ($repositoryReference, $key) {
                return parent::delete($repositoryReference, $key)
                    ->then(function () {
                        return $this->saveToDisk();
                    });
            });
    }

    /**
     * @param RepositoryReference $repositoryReference
     *
     * @return PromiseInterface
     */
    public function findMetadata(RepositoryReference $repositoryReference): PromiseInterface
    {
        return $this
            ->loadFromDisk()
            ->then(function () use ($repositoryReference) {
                return parent::findMetadata($repositoryReference);
            });
    }

    /**
     * @return PromiseInterface
     */
    public function findAllMetadata(): PromiseInterface
    {
        return $this
            ->loadFromDisk()
            ->then(function () {
                return parent::findAllMetadata();
            });
    }

    /**
     * Save to disk.
     *
     * @return PromiseInterface
     */
    private function saveToDisk(): PromiseInterface
    {
        @\unlink($this->file);
        \touch($this->file);
        \file_put_contents($this->file, \serialize($this->storedMetadata));

        return resolve();
    }

    /**
     * Load from disk.
     *
     * @return PromiseInterface
     */
    private function loadFromDisk(): PromiseInterface
    {
        $content = @\file_get_contents($this->file);
        if (!\is_string($content)) {
            $this->storedMetadata = [];

            return resolve();
        }

        $content = \unserialize($content);
        $this->storedMetadata = \is_array($content)
            ? $content
            : [];

        return resolve();
    }
}
