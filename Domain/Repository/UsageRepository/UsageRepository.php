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

use Apisearch\Repository\RepositoryReference;
use DateTime;
use React\Promise\PromiseInterface;

/**
 * Interface UsageRepository.
 */
interface UsageRepository
{
    /**
     * @param RepositoryReference $repositoryReference
     * @param string              $eventName
     * @param DateTime            $when
     * @param int                 $n
     *
     * @return PromiseInterface
     */
    public function registerEvent(
        RepositoryReference $repositoryReference,
        string $eventName,
        DateTime $when,
        int $n = 1
    ): PromiseInterface;

    /**
     * @param RepositoryReference $repositoryReference
     * @param string|null         $eventType
     * @param DateTime            $from
     * @param DateTime|null       $to
     * @param bool                $perDay
     *
     * @return PromiseInterface
     */
    public function getRegisteredEvents(
        RepositoryReference $repositoryReference,
        ?string $eventType,
        DateTime $from,
        ?DateTime $to = null,
        bool $perDay = false
    ): PromiseInterface;
}
