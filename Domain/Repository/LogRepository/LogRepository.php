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

use Apisearch\Repository\RepositoryReference;
use DateTime;
use React\Promise\PromiseInterface;

/**
 * Interface LogRepository.
 */
interface LogRepository
{
    /**
     * @param RepositoryReference $repositoryReference
     * @param DateTime            $when
     * @param int                 $n
     * @param string              $type
     * @param array               $params
     *
     * @return PromiseInterface
     */
    public function log(
        RepositoryReference $repositoryReference,
        DateTime $when,
        int $n,
        string $type,
        array $params
    ): PromiseInterface;

    /**
     * @param LogFilter $filter
     *
     * @return PromiseInterface<LogWithText[]>
     */
    public function getLogs(LogFilter $filter): PromiseInterface;
}
