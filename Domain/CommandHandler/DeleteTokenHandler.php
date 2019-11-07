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

use Apisearch\Server\Domain\Command\DeleteToken;
use Apisearch\Server\Domain\Event\DomainEventWithRepositoryReference;
use Apisearch\Server\Domain\Event\TokenWasDeleted;
use Apisearch\Server\Domain\WithAppRepositoryAndEventPublisher;
use React\Promise\PromiseInterface;

/**
 * Class DeleteTokenHandler.
 */
class DeleteTokenHandler extends WithAppRepositoryAndEventPublisher
{
    /**
     * Delete token.
     *
     * @param DeleteToken $deleteToken
     *
     * @return PromiseInterface
     */
    public function handle(DeleteToken $deleteToken): PromiseInterface
    {
        $repositoryReference = $deleteToken->getRepositoryReference();
        $tokenUUID = $deleteToken->getTokenUUIDToDelete();

        return $this
            ->appRepository
            ->deleteToken(
                $repositoryReference,
                $tokenUUID
            )
            ->then(function () use ($repositoryReference, $tokenUUID) {
                return $this
                    ->eventPublisher
                    ->publish(new DomainEventWithRepositoryReference(
                        $repositoryReference,
                        new TokenWasDeleted($tokenUUID)
                    ));
            });
    }
}
