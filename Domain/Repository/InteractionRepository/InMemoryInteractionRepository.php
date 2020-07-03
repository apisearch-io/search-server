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

use Apisearch\Model\AppUUID;
use Apisearch\Model\ItemUUID;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Model\Origin;
use Apisearch\Server\Domain\Repository\ResetableRepository;
use DateTime;
use React\Promise\PromiseInterface;
use function React\Promise\resolve;

/**
 * Class InMemoryInteractionRepository.
 */
class InMemoryInteractionRepository implements TemporaryInteractionRepository, ResetableRepository
{
    /**
     * @var Interaction[]
     */
    private $interactions = [];

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
    ): PromiseInterface {
        $this->interactions[] = new Interaction(
            $userUUID,
            $repositoryReference->getAppUUID()->composeUUID(),
            $repositoryReference->getIndexUUID()->composeUUID(),
            $itemUUID->composeUUID(),
            $origin->getIp(),
            $origin->getHost(),
            $origin->getPlatform(),
            $type,
            $when
        );

        return resolve();
    }

    /**
     * @param InteractionFilter $filter
     *
     * @return PromiseInterface<int|int[]>
     */
    public function getRegisteredInteractions(InteractionFilter $filter): PromiseInterface
    {
        $repositoryReference = $filter->getRepositoryReference();
        $appUUID = $repositoryReference->getAppUUID();
        if (!$appUUID instanceof AppUUID) {
            return resolve(0);
        }

        $perDay = $filter->isPerDay();
        $count = $filter->getCount();
        $interactions = $perDay ? [] : 0;
        $uniqueUsers = [];

        foreach ($this->interactions as $interaction) {
            $whenFormatted = $interaction->getWhen()->format('Ymd');

            if (!$this->interactionIsValidFromFilter($interaction, $filter)) {
                continue;
            }

            if ($perDay) {
                if (!\array_key_exists($whenFormatted, $interactions)) {
                    $interactions[$whenFormatted] = 1;
                    $uniqueUsers[$whenFormatted] = [
                        $interaction->getUser() => true,
                    ];
                } else {
                    ++$interactions[$whenFormatted];
                    $uniqueUsers[$whenFormatted][$interaction->getUser()] = true;
                }
            } else {
                ++$interactions;
                $uniqueUsers[$interaction->getUser()] = true;
            }
        }

        $uniqueUsers = $perDay
            ? \array_map(function (array $day) {
                return \count($day);
            }, $uniqueUsers)
            : \count($uniqueUsers);

        return resolve(InteractionFilter::UNIQUE_USERS === $count
            ? $uniqueUsers
            : $interactions
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
        $repositoryReference = $filter->getRepositoryReference();
        $appUUID = $repositoryReference->getAppUUID();
        if (!$appUUID instanceof AppUUID) {
            return resolve(0);
        }

        $interactions = \array_filter($this->interactions, function (Interaction $interaction) use ($filter) {
            return $this->interactionIsValidFromFilter($interaction, $filter);
        });

        $itemsMap = [];
        foreach ($interactions as $interaction) {
            $itemUUID = $interaction->getItemUUID();
            if (!\array_key_exists($interaction->getItemUUID(), $itemsMap)) {
                $itemsMap[$itemUUID] = 1;
            } else {
                ++$itemsMap[$itemUUID];
            }
        }

        \arsort($itemsMap);

        return resolve(\array_slice($itemsMap, 0, $n));
    }

    /**
     * Interaction is valid given a filter.
     *
     * @param Interaction       $interaction
     * @param InteractionFilter $filter
     *
     * @return bool
     */
    private function interactionIsValidFromFilter(
        Interaction $interaction,
        InteractionFilter $filter
    ): bool {
        $whenFormatted = $interaction->getWhen()->format('Ymd');
        $repositoryReference = $filter->getRepositoryReference();
        $appUUID = $repositoryReference->getAppUUID();
        $indexUUID = $repositoryReference->getIndexUUID();

        if (!(
            (
                '*' === $appUUID->composeUUID() ||
                $appUUID->composeUUID() === $interaction->getAppUUID()
            ) &&
            (
                \is_null($indexUUID) ||
                '' === $indexUUID->composeUUID() ||
                '*' === $indexUUID->composeUUID() ||
                $indexUUID->composeUUID() === $interaction->getIndexUUID()
            )
        )) {
            return false;
        }

        if (
            !\is_null($filter->getUser()) &&
            $interaction->getUser() !== $filter->getUser()
        ) {
            return false;
        }

        if (
            !\is_null($filter->getItemUUID()) &&
            $interaction->getItemUUID() !== $filter->getItemUUID()->composeUUID()
        ) {
            return false;
        }

        if (
            !\is_null($filter->getPlatform()) &&
            $interaction->getPlatform() !== $filter->getPlatform() &&
            (
                Origin::MOBILE !== $filter->getPlatform() ||
                !\in_array($interaction->getPlatform(), [
                    Origin::TABLET,
                    Origin::PHONE,
                ])
            )
        ) {
            return false;
        }

        if (
            !\is_null($filter->getType()) &&
            $interaction->getType() !== $filter->getType()
        ) {
            return false;
        }

        if (
            !\is_null($filter->getFrom()) &&
            $whenFormatted < $filter->getFrom()->format('Ymd')
        ) {
            return false;
        }
        if (
            !\is_null($filter->getTo()) &&
            $whenFormatted >= $filter->getTo()->format('Ymd')
        ) {
            return false;
        }

        return true;
    }

    /**
     * @return array
     */
    public function getAndResetInteractions(): array
    {
        $interactions = $this->interactions;
        $this->reset();

        return $interactions;
    }

    /**
     * @return PromiseInterface
     */
    public function reset(): PromiseInterface
    {
        $this->interactions = [];

        return resolve();
    }
}
