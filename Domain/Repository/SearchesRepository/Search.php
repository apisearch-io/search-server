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

namespace Apisearch\Server\Domain\Repository\SearchesRepository;

use DateTime;

/**
 * Class Search.
 */
class Search
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
    private $text;

    /**
     * @var int
     */
    private $numberOfResults;

    /**
     * @var bool
     */
    private $withResults;

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
     * @var DateTime
     */
    private $when;

    /**
     * @param string   $user
     * @param string   $appUUID
     * @param string   $indexUUID
     * @param string   $text
     * @param int      $numberOfResults
     * @param bool     $withResults
     * @param string   $ip
     * @param string   $host
     * @param string   $platform
     * @param DateTime $when
     */
    public function __construct(
        string $user,
        string $appUUID,
        string $indexUUID,
        string $text,
        int $numberOfResults,
        bool $withResults,
        string $ip,
        string $host,
        string $platform,
        DateTime $when
    ) {
        $this->user = $user;
        $this->appUUID = $appUUID;
        $this->indexUUID = $indexUUID;
        $this->text = $text;
        $this->numberOfResults = $numberOfResults;
        $this->withResults = $withResults;
        $this->ip = $ip;
        $this->host = $host;
        $this->platform = $platform;
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
    public function getText(): string
    {
        return $this->text;
    }

    /**
     * @return int
     */
    public function getNumberOfResults(): int
    {
        return $this->numberOfResults;
    }

    /**
     * @return bool
     */
    public function isWithResults(): bool
    {
        return $this->withResults;
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
     * @return DateTime
     */
    public function getWhen(): DateTime
    {
        return $this->when;
    }
}
