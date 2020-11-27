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
    private string $user;
    private string $appUUID;
    private string $indexUUID;
    private string $itemUUID;
    private int $position;
    private string $ip;
    private string $host;
    private string $platform;
    private string $type;
    private DateTime $when;

    /**
     * @param string   $user
     * @param string   $appUUID
     * @param string   $indexUUID
     * @param string   $itemUUID
     * @param int      $position
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
        int $position,
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
        $this->position = $position;
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
     * @return int
     */
    public function getPosition(): int
    {
        return $this->position;
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
