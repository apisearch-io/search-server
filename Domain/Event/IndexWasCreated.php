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
 * Class IndexWasCreated.
 */
final class IndexWasCreated extends DomainEvent
{
    private IndexUUID $indexUUID;
    private Config $config;

    /**
     * IndexWasConfigured constructor.
     *
     * @param IndexUUID $indexUUID
     * @param Config    $config
     */
    public function __construct(
        IndexUUID $indexUUID,
        Config $config
    ) {
        parent::__construct();
        $this->config = $config;
        $this->indexUUID = $indexUUID;
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
            'config' => \json_encode($this
                ->config
                ->toArray()),
        ];
    }
}
