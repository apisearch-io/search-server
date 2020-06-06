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

use Apisearch\Server\Domain\Query\GetTopInteractions;
use Apisearch\Server\Domain\Repository\InteractionRepository\InteractionFilter;
use Apisearch\Server\Domain\Repository\InteractionRepository\InteractionRepository;
use React\Promise\PromiseInterface;

/**
 * Class GetTopInteractionsHandler.
 */
class GetTopInteractionsHandler
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
     * @param GetTopInteractions $getTopInteractions
     *
     * @return PromiseInterface
     */
    public function handle(GetTopInteractions $getTopInteractions): PromiseInterface
    {
        return $this
            ->interactionRepository
            ->getTopInteractedItems(
                InteractionFilter::create($getTopInteractions->getRepositoryReference())
                    ->from($getTopInteractions->getFrom())
                    ->to($getTopInteractions->getTo())
                    ->byUser($getTopInteractions->getUser())
                    ->byPlatform($getTopInteractions->getPlatform())
                    ->byType($getTopInteractions->getType()),
                $getTopInteractions->getN() ?? 10
            );
    }
}
