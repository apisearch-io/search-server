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

use Apisearch\Server\Domain\Command\DeleteTokens;
use Apisearch\Server\Domain\Event\TokensWereDeleted;
use Apisearch\Server\Domain\WithAppRepositoryAndEventPublisher;
use React\Promise\PromiseInterface;

/**
 * Class DeleteTokensHandler.
 */
class DeleteTokensHandler extends WithAppRepositoryAndEventPublisher
{
    /**
     * Delete token.
     *
     * @param DeleteTokens $deleteTokens
     *
     * @return PromiseInterface
     */
    public function handle(DeleteTokens $deleteTokens): PromiseInterface
    {
        $repositoryReference = $deleteTokens->getRepositoryReference();
        $ownerToken = $deleteTokens->getToken();

        return $this
            ->appRepository
            ->deleteTokens($repositoryReference)
            ->then(function () use ($repositoryReference, $ownerToken) {
                return $this
                    ->eventBus
                    ->dispatch(
                        (new TokensWereDeleted())
                            ->withRepositoryReference($repositoryReference)
                            ->dispatchedBy($ownerToken)
                    );
            });
    }
}
