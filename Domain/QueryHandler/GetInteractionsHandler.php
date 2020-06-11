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

namespace Apisearch\Server\Domain\QueryHandler;

use Apisearch\Model\ItemUUID;
use Apisearch\Server\Domain\Query\GetInteractions;
use Apisearch\Server\Domain\Repository\InteractionRepository\InteractionFilter;
use Apisearch\Server\Domain\Repository\InteractionRepository\InteractionRepository;
use React\Promise\PromiseInterface;

/**
 * Class GetInteractionsHandler.
 */
class GetInteractionsHandler
{
    /**
     * @var InteractionRepository
     */
    private $interactionRepository;

    /**
     * @param InteractionRepository $interactionRepository
     */
    public function __construct(InteractionRepository $interactionRepository)
    {
        $this->interactionRepository = $interactionRepository;
    }

    /**
     * @param GetInteractions $getInteractions
     *
     * @return PromiseInterface
     */
    public function handle(GetInteractions $getInteractions): PromiseInterface
    {
        $itemUUID = $getInteractions->getItemId()
            ? ItemUUID::createByComposedUUID($getInteractions->getItemId())
            : null;

        return $this
            ->interactionRepository
            ->getRegisteredInteractions(
                InteractionFilter::create($getInteractions->getRepositoryReference())
                    ->perDay($getInteractions->isPerDay())
                    ->from($getInteractions->getFrom())
                    ->to($getInteractions->getTo())
                    ->byUser($getInteractions->getUser())
                    ->byPlatform($getInteractions->getPlatform())
                    ->byItem($itemUUID)
                    ->byType($getInteractions->getType())
                    ->count($getInteractions->getCount())
            );
    }
}
