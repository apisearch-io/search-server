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

namespace Apisearch\Server\Domain\Repository\UsageRepository;

use DateTime;

/**
 * Class UseLine.
 */
class UseLine
{
    private string $event;
    private string $appUUID;
    private ?string $indexUUID;
    private DateTime $when;
    private int $n;

    /**
     * @param string      $event
     * @param string      $appUUID
     * @param string|null $indexUUID
     * @param DateTime    $when
     * @param int         $n
     */
    public function __construct(
        string $event,
        string $appUUID,
        ?string $indexUUID,
        DateTime $when,
        int $n = 1
    ) {
        $this->event = $event;
        $this->appUUID = $appUUID;
        $this->indexUUID = $indexUUID;
        $this->when = $when;
        $this->n = $n;
    }

    /**
     * @return string
     */
    public function getEvent(): string
    {
        return $this->event;
    }

    /**
     * @return string
     */
    public function getAppUUID(): string
    {
        return $this->appUUID;
    }

    /**
     * @return string|null
     */
    public function getIndexUUID(): ?string
    {
        return $this->indexUUID;
    }

    /**
     * @return DateTime
     */
    public function getWhen(): DateTime
    {
        return $this->when;
    }

    /**
     * Increase by.
     *
     * @param int $n
     */
    public function increaseBy(int $n)
    {
        $this->n += $n;
    }

    /**
     * @return int
     */
    public function getN(): int
    {
        return $this->n;
    }
}
