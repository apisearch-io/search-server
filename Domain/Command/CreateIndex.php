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

namespace Apisearch\Server\Domain\Command;

use Apisearch\Config\Config;
use Apisearch\Model\IndexUUID;
use Apisearch\Model\Token;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\CommandWithRepositoryReferenceAndToken;
use Apisearch\Server\Domain\IndexRequiredCommand;

/**
 * Class CreateIndex.
 */
class CreateIndex extends CommandWithRepositoryReferenceAndToken implements IndexRequiredCommand
{
    /**
     * @var IndexUUID
     *
     * Index uuid
     */
    private $indexUUID;

    /**
     * @var Config
     *
     * Config
     */
    private $config;

    /**
     * ResetCommand constructor.
     *
     * @param RepositoryReference $repositoryReference
     * @param Token               $token
     * @param IndexUUID           $indexUUID
     * @param Config              $config
     */
    public function __construct(
        RepositoryReference $repositoryReference,
        Token $token,
        IndexUUID $indexUUID,
        Config $config
    ) {
        parent::__construct(
            $repositoryReference,
            $token
        );

        $this->indexUUID = $indexUUID;
        $this->config = $config;
    }

    /**
     * Get IndexUUID.
     *
     * @return IndexUUID
     */
    public function getIndexUUID(): IndexUUID
    {
        return $this->indexUUID;
    }

    /**
     * Get config.
     *
     * @return Config
     */
    public function getConfig(): Config
    {
        return $this->config;
    }
}
