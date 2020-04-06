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

namespace Apisearch\Server\Domain;

use Apisearch\Config\Config;
use Apisearch\Server\Domain\Repository\AppRepository\ConfigRepository;
use Apisearch\Server\Domain\Repository\AppRepository\Repository as AppRepository;
use Drift\EventBus\Bus\EventBus;

/**
 * Class WithIndexConfigHandler.
 */
abstract class WithConfigRepositoryAppRepositoryAndEventPublisher extends WithAppRepositoryAndEventPublisher
{
    /**
     * @var ConfigRepository
     */
    protected $configRepository;

    /**
     * QueryHandler constructor.
     *
     * @param AppRepository    $appRepository
     * @param EventBus         $eventBus
     * @param ConfigRepository $configRepository
     */
    public function __construct(
        AppRepository $appRepository,
        EventBus $eventBus,
        ConfigRepository $configRepository
    ) {
        parent::__construct($appRepository, $eventBus);
        $this->configRepository = $configRepository;
    }

    /**
     * Config hashes are equal.
     *
     * @param Config $config1
     * @param Config $config2
     *
     * @return bool
     */
    protected function configHashesAreEqual(
        ?Config $config1,
        ?Config $config2
    ): bool {
        return
            !is_null($config1) &&
            !is_null($config2) &&
            $this->getConfigReindexationHash($config1) === $this->getConfigReindexationHash($config2);
    }

    /**
     * Get Config re-indexation hash.
     *
     * @param Config $config
     *
     * @return string
     */
    protected function getConfigReindexationHash(Config $config): string
    {
        $synonyms = $config->getSynonyms();
        $plainSynonyms = [];
        foreach ($synonyms as $synonym) {
            $plainSynonyms[] = $synonym->expand();
        }
        sort($plainSynonyms);

        return md5(json_encode([
            $config->getShards(),
            $config->getReplicas(),
            $config->getLanguage(),
            $plainSynonyms,
            $config->shouldSearchableMetadataBeStored(),
        ]));
    }
}
