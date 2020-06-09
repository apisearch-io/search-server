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

namespace Apisearch\Server\Domain\Repository\InteractionRepository;

use DateTime;

/**
 * Class Interaction.
 */
final class Interaction
{
    /**
     * @var string
     */
    private $user;

    /**
     * @var string
     */
    private $appUUID;

    /**
     * @var string
     */
    private $indexUUID;

    /**
     * @var string
     */
    private $itemUUID;

    /**
     * @var string
     */
    private $ip;

    /**
     * @var string
     */
    private $host;

    /**
     * @var string
     */
    private $platform;

    /**
     * @var string
     */
    private $type;

    /**
     * @var DateTime
     */
    private $when;

    /**
     * @param string   $user
     * @param string   $appUUID
     * @param string   $indexUUID
     * @param string   $itemUUID
     * @param string   $ip
     * @param string   $host
     * @param string   $platform
     * @param string   $type
     * @param DateTime $when
     */
    public function __construct(
        string $user,
        string $appUUID,
        string $indexUUID,
        string $itemUUID,
        string $ip,
        string $host,
        string $platform,
        string $type,
        DateTime $when
    ) {
        $this->user = $user;
        $this->appUUID = $appUUID;
        $this->indexUUID = $indexUUID;
        $this->itemUUID = $itemUUID;
        $this->ip = $ip;
        $this->host = $host;
        $this->platform = $platform;
        $this->type = $type;
        $this->when = $when;
    }

    /**
     * @return string
     */
    public function getUser(): string
    {
        return $this->user;
    }

    /**
     * @return string
     */
    public function getAppUUID(): string
    {
        return $this->appUUID;
    }

    /**
     * @return string
     */
    public function getIndexUUID(): string
    {
        return $this->indexUUID;
    }

    /**
     * @return string
     */
    public function getItemUUID(): string
    {
        return $this->itemUUID;
    }

    /**
     * @return string
     */
    public function getIp(): string
    {
        return $this->ip;
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @return string
     */
    public function getPlatform(): string
    {
        return $this->platform;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return DateTime
     */
    public function getWhen(): DateTime
    {
        return $this->when;
    }
}