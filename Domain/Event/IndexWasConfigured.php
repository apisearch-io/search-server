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

namespace Apisearch\Server\Domain\Event;

use Apisearch\Config\Config;
use Apisearch\Model\IndexUUID;

/**
 * Class IndexWasConfigured.
 */
final class IndexWasConfigured extends DomainEvent
{
    /**
     * @var IndexUUID
     *
     * Index UUID
     */
    private $indexUUID;

    /**
     * @var Config
     *
     * Config
     */
    private $config;

    /**
     * @var bool
     */
    private $indexWasReindexed;

    /**
     * IndexWasConfigured constructor.
     *
     * @param IndexUUID $indexUUID
     * @param Config    $config
     * @param bool      $indexWasReindexed
     */
    public function __construct(
        IndexUUID $indexUUID,
        Config $config,
        bool $indexWasReindexed
    ) {
        parent::__construct();
        $this->config = $config;
        $this->indexUUID = $indexUUID;
        $this->indexWasReindexed = $indexWasReindexed;
    }

    /**
     * @return IndexUUID
     */
    public function getIndexUUID(): IndexUUID
    {
        return $this->indexUUID;
    }

    /**
     * @return Config
     */
    public function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * @return bool
     */
    public function indexWasReindexed(): bool
    {
        return $this->indexWasReindexed;
    }

    /**
     * to array payload.
     *
     * @return array
     */
    public function toArrayPayload(): array
    {
        return [
            'index_uuid' => $this
                ->indexUUID
                ->composeUUID(),
            'index_was_reindexed' => $this->indexWasReindexed,
            'config' => \json_encode($this
                ->config
                ->toArray()),
        ];
    }
}
