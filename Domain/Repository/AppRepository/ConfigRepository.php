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

namespace Apisearch\Server\Domain\Repository\AppRepository;

use Apisearch\Config\Config;
use Apisearch\Model\AppUUID;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Event\IndexWasConfigured;
use Apisearch\Server\Domain\Event\IndexWasCreated;
use Apisearch\Server\Domain\Event\IndexWasDeleted;
use Apisearch\Server\Domain\ImperativeEvent\LoadConfigs;
use Drift\HttpKernel\AsyncKernelEvents;
use React\Promise\PromiseInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ConfigRepository.
 */
abstract class ConfigRepository implements EventSubscriberInterface
{
    /**
     * @var array
     */
    private $configs = [];

    /**
     * Put config.
     *
     * @param RepositoryReference $repositoryReference
     * @param Config              $config
     *
     * @return PromiseInterface
     */
    abstract public function putConfig(
        RepositoryReference $repositoryReference,
        Config $config
    ): PromiseInterface;

    /**
     * Delete config.
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
                $this->configs = [];
                foreach ($configs as $repositoryReferenceComposed => $config) {
                    $repositoryReference = RepositoryReference::createFromComposed($repositoryReferenceComposed);
                    $appUUIDComposed = $repositoryReference->getAppUUID()->composeUUID();
                    $indexUUIDComposed = $repositoryReference->getIndexUUID()->composeUUID();

                    if (!isset($this->configs[$appUUIDComposed])) {
                        $this->configs[$appUUIDComposed] = [];
                    }

                    $this->configs[$appUUIDComposed][$indexUUIDComposed] = $config;
                }
            });
    }

    /**
     * Get config.
     *
     * @param RepositoryReference $repositoryReference
     *
     * @return Config|null
     */
    public function getConfig(RepositoryReference $repositoryReference): ? Config
    {
        $appUUIDComposed = $repositoryReference->getAppUUID()->composeUUID();
        $indexUUIDComposed = $repositoryReference->getIndexUUID()->composeUUID();

        if (
            !\array_key_exists($appUUIDComposed, $this->configs) ||
            !\array_key_exists($indexUUIDComposed, $this->configs[$appUUIDComposed])
        ) {
            return null;
        }

        return $this->configs[$appUUIDComposed][$indexUUIDComposed];
    }

    /**
     * Get apps configs.
     *
     * @param AppUUID $appUUID
     *
     * @return Config[]
     */
    public function getAppConfigs(AppUUID $appUUID): array
    {
        return $this->configs[$appUUID->composeUUID()] ?? [];
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
            AsyncKernelEvents::PRELOAD => [
                ['forceLoadAllConfigs', 0],
            ],
            LoadConfigs::class => [
                ['forceLoadAllConfigs', 0],
            ],
        ];
    }
}
