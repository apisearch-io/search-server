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

namespace Apisearch\Server\Domain\Repository\LogRepository;

use DateTime;

/**
 * Class Log.
 */
class Log
{
    private string $appUUID;
    private ?string $indexUUID;
    private DateTime $when;
    private int $n;
    private string $type;
    private array $params;

    /**
     * @param string      $appUUID
     * @param string|null $indexUUID
     * @param DateTime    $when
     * @param int         $n
     * @param string      $type
     * @param array       $params
     */
    public function __construct(
        string $appUUID,
        ?string $indexUUID,
        DateTime $when,
        int $n,
        string $type,
        array $params
    ) {
        $this->appUUID = $appUUID;
        $this->indexUUID = $indexUUID;
        $this->when = $when;
        $this->n = $n;
        $this->type = $type;
        $this->params = $params;
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
    public function getIndexUUID(): ? string
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
     * @return int
     */
    public function getN(): int
    {
        return $this->n;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * @param string
     *
     * @return mixed
     */
    public function getParam(string $key)
    {
        return $this->params[$key] ?? null;
    }
}
