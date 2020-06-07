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

use Apisearch\Model\ItemUUID;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Model\Origin;
use DateTime;
use React\Promise\PromiseInterface;

/**
 * Interface InteractionRepository.
 */
interface InteractionRepository
{
    /**
     * @param RepositoryReference $repositoryReference
     * @param string              $userUUID
     * @param ItemUUID            $itemUUID
     * @param Origin              $origin
     * @param string              $type
     * @param DateTime            $when
     *
     * @return PromiseInterface
     */
    public function registerInteraction(
        RepositoryReference $repositoryReference,
        string $userUUID,
        ItemUUID $itemUUID,
        Origin $origin,
        string $type,
        DateTime $when
    ): PromiseInterface;

    /**
     * @param InteractionFilter $filter
     *
     * @return PromiseInterface<int|int[]>
     */
    public function getRegisteredInteractions(InteractionFilter $filter): PromiseInterface;

    /**
     * @param  InteractionFilter $filter
     * @param int $n
     *
     * @return PromiseInterface
     */
    public function getTopInteractedItems(
        InteractionFilter $filter,
        int $n
    ): PromiseInterface;
}
