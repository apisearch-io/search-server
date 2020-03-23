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
use Apisearch\Server\Domain\Event\InteractionWasAdded;
use Apisearch\Server\Domain\WithEventBus;
use function React\Promise\resolve;
use React\Promise\PromiseInterface;

/**
 * Class AddInteractionHandler.
 */
class AddInteractionHandler extends WithEventBus
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
            ->eventBus
            ->dispatch(
                (new InteractionWasAdded($interaction))
                    ->withRepositoryReference($repositoryReference)
            );

        return resolve();
    }
}
