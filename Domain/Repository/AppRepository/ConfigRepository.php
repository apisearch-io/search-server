<?php


namespace Apisearch\Server\Domain\Repository\AppRepository;

use Apisearch\Config\Config;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Event\IndexWasConfigured;
use Apisearch\Server\Domain\Event\IndexWasCreated;
use Apisearch\Server\Domain\Event\IndexWasDeleted;
use React\Promise\PromiseInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ConfigRepository
 */
abstract class ConfigRepository implements EventSubscriberInterface
{
    /**
     * @var Config[]
     */
    private $configs;

    /**
     * Put config
     *
     * @param RepositoryReference $repositoryReference
     * @param Config $config
     *
     * @return PromiseInterface
     */
    abstract public function putConfig(
        RepositoryReference $repositoryReference,
        Config $config
    ): PromiseInterface;

    /**
     * Delete config
     *
     * @param RepositoryReference $repositoryReference
     *
     * @return PromiseInterface
     */
    abstract public function deleteConfig(RepositoryReference $repositoryReference): PromiseInterface;

    /**
     * Find all configs.
     *
     * @return PromiseInterface<array>
     */
    abstract public function findAllConfigs(): PromiseInterface;

    /**
     * Force load all configs.
     *
     * @return PromiseInterface
     */
    public function forceLoadAllConfigs(): PromiseInterface
    {
        return $this
            ->findAllConfigs()
            ->then(function (array $configs) {
                $this->configs = array_map(function(Config $config) {
                    return Config::createFromArray($config->toArray());
                }, $configs);
            });
    }

    /**
     * Get config
     *
     * @param RepositoryReference $repositoryReference
     *
     * @return Config|null
     */
    public function getConfig(RepositoryReference $repositoryReference) : ? Config
    {
        $appUUIDComposed = $repositoryReference->compose();

        return $this->configs[$appUUIDComposed] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            IndexWasCreated::class => [
                ['forceLoadAllConfigs', 0],
            ],
            IndexWasConfigured::class => [
                ['forceLoadAllConfigs', 0],
            ],
            IndexWasDeleted::class => [
                ['forceLoadAllConfigs', 0],
            ],
        ];
    }
}