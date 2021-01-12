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
use Apisearch\Server\Domain\Repository\ResetableRepository;
use DateTime;
use React\Promise\PromiseInterface;
use function React\Promise\resolve;

/**
 * Class EmptyInteractionRepository.
 */
class EmptyInteractionRepository implements TemporaryInteractionRepository, TestableInteractionRepository, ResetableRepository
{
    /**
     * @param RepositoryReference $repositoryReference
     * @param string              $userUUID
     * @param ItemUUID            $itemUUID
     * @param int                 $position
     * @param string|null         $context
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
        int $position,
        ?string $context,
        Origin $origin,
        string $type,
        DateTime $when
    ): PromiseInterface {
        return resolve();
    }

    /**
     * @param InteractionFilter $filter
     *
     * @return PromiseInterface<int|int[]>
     */
    public function getRegisteredInteractions(InteractionFilter $filter): PromiseInterface
    {
        $count = $filter->getCount();

        return resolve(InteractionFilter::UNIQUE_USERS === $count
            ? 0
            : []
        );
    }

    /**
     * @param InteractionFilter $filter
     * @param int               $n
     *
     * @return PromiseInterface
     */
    public function getTopInteractedItems(
        InteractionFilter $filter,
        int $n
    ): PromiseInterface {
        return resolve([]);
    }

    /**
     * @return array
     */
    public function getAndResetInteractions(): array
    {
        return [];
    }

    /**
     * @return array
     */
    public function getInteractions(): array
    {
        return [];
    }

    /**
     * @return PromiseInterface
     */
    public function reset(): PromiseInterface
    {
        return resolve();
    }
}
