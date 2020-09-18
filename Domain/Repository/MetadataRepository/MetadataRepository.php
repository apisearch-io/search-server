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
use Apisearch\Server\Domain\ImperativeEvent\LoadMetadata;
use Drift\HttpKernel\AsyncKernelEvents;
use Drift\HttpKernel\Event\DomainEventEnvelope;
use React\Promise\PromiseInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class MetadataRepository.
 */
abstract class MetadataRepository implements EventSubscriberInterface
{
    /**
     * @var array
     */
    private $metadata = [];

    /**
     * Set metadata value.
     *
     * @param RepositoryReference $repositoryReference
     * @param string              $key
     * @param mixed               $value
     *
     * @return PromiseInterface<mixed>
     */
    abstract public function set(
        RepositoryReference $repositoryReference,
        string $key,
        $value
    ): PromiseInterface;

    /**
     * Get metadata value.
     *
     * @param RepositoryReference $repositoryReference
     * @param string              $key
     *
     * @return mixed
     */
    public function get(
        RepositoryReference $repositoryReference,
        string $key
    ) {
        $composedRepositoryReference = $repositoryReference->compose();

        if (
            !\array_key_exists($composedRepositoryReference, $this->metadata) ||
            !\array_key_exists($key, $this->metadata[$composedRepositoryReference])
        ) {
            return null;
        }

        return $this->metadata[$composedRepositoryReference][$key];
    }

    /**
     * Delete token.
     *
     * @param RepositoryReference $repositoryReference
     * @param string              $key
     *
     * @return PromiseInterface
     */
    abstract public function delete(
        RepositoryReference $repositoryReference,
        string $key
    ): PromiseInterface;

    /**
     * Force load metadata reference.
     *
     * @param RepositoryReference $repositoryReference
     *
     * @return PromiseInterface
     */
    public function forceLoadMetadata(RepositoryReference $repositoryReference): PromiseInterface
    {
        return $this
            ->findMetadata($repositoryReference)
            ->then(function (array $metadata) use ($repositoryReference) {
                if (empty($metadata)) {
                    unset($this->metadata[$repositoryReference->compose()]);
                } else {
                    $this->metadata[$repositoryReference->compose()] = $metadata;
                }
            });
    }

    /**
     * Force load all metadata reference.
     *
     * @param RepositoryReference $repositoryReference
     *
     * @return PromiseInterface
     */
    public function forceLoadAllMetadata(): PromiseInterface
    {
        return $this
            ->findAllMetadata()
            ->then(function (array $allMetadata) {
                $this->metadata = $allMetadata;
            });
    }

    /**
     * Find metadata from repository reference.
     *
     * @param RepositoryReference $repositoryReference
     *
     * @return PromiseInterface
     */
    abstract public function findMetadata(RepositoryReference $repositoryReference): PromiseInterface;

    /**
     * Find all metadata from repository reference.
     *
     * @return PromiseInterface
     */
    abstract public function findAllMetadata(): PromiseInterface;

    /**
     * Get all metadata from repository reference.
     *
     * @param RepositoryReference $repositoryReference
     *
     * @return array
     */
    public function all(RepositoryReference $repositoryReference): array
    {
        $composedRepositoryReference = $repositoryReference->compose();

        return $this->metadata[$composedRepositoryReference] ?? [];
    }

    /**
     * On Load metadata event.
     *
     * @param DomainEventEnvelope $eventEnvelope
     */
    public function onLoadMetadataEvent(DomainEventEnvelope $eventEnvelope)
    {
        $domainEvent = $eventEnvelope->getDomainEvent();

        return $this->forceLoadMetadata($domainEvent->getRepositoryReference());
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            AsyncKernelEvents::PRELOAD => [
                ['forceLoadAllMetadata', 0],
            ],
            LoadMetadata::class => [
                ['onLoadMetadataEvent', 0],
            ],
        ];
    }
}
