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

use Apisearch\Server\Domain\Command\AddInteraction;
use Apisearch\Server\Domain\Event\DomainEventWithRepositoryReference;
use Apisearch\Server\Domain\Event\InteractionWasAdded;
use Apisearch\Server\Domain\WithEventPublisher;
use React\Promise\FulfilledPromise;
use React\Promise\PromiseInterface;

/**
 * Class AddInteractionHandler.
 */
class AddInteractionHandler extends WithEventPublisher
{
    /**
     * Add interaction.
     *
     * @param AddInteraction $addInteraction
     *
     * @return PromiseInterface
     */
    public function handle(AddInteraction $addInteraction): PromiseInterface
    {
        $repositoryReference = $addInteraction->getRepositoryReference();
        $interaction = $addInteraction->getInteraction();

        $this
            ->eventPublisher
            ->publish(new DomainEventWithRepositoryReference(
                $repositoryReference,
                new InteractionWasAdded($interaction)
            ));

        return new FulfilledPromise();
    }
}
