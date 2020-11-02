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

namespace Apisearch\Server\Domain\CommandHandler;

use Apisearch\Server\Domain\Command\PostInteraction;
use Apisearch\Server\Domain\Repository\InteractionRepository\InteractionRepository;
use DateTime;
use React\Promise\PromiseInterface;

/**
 * Class PostInteractionHandler.
 */
class PostInteractionHandler
{
    private InteractionRepository $interactionRepository;

    /**
     * @param InteractionRepository $interactionRepository
     */
    public function __construct(InteractionRepository $interactionRepository)
    {
        $this->interactionRepository = $interactionRepository;
    }

    /**
     * @param PostInteraction $putInteraction
     *
     * @return PromiseInterface
     */
    public function handle(PostInteraction $putInteraction): PromiseInterface
    {
        return $this
            ->interactionRepository
            ->registerInteraction(
                $putInteraction->getRepositoryReference(),
                $putInteraction->getUserUUID(),
                $putInteraction->getItemUUID(),
                $putInteraction->getPosition(),
                $putInteraction->getOrigin(),
                $putInteraction->getInteractionType(),
                new DateTime()
            );
    }
}
