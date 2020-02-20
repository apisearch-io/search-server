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

use Apisearch\Server\Domain\Command\AddToken;
use Apisearch\Server\Domain\Event\TokenWasAdded;
use Apisearch\Server\Domain\WithAppRepositoryAndEventPublisher;
use React\Promise\PromiseInterface;

/**
 * Class AddTokenHandler.
 */
class AddTokenHandler extends WithAppRepositoryAndEventPublisher
{
    /**
     * Add token.
     *
     * @param AddToken $addToken
     *
     * @return PromiseInterface
     */
    public function handle(AddToken $addToken): PromiseInterface
    {
        $repositoryReference = $addToken->getRepositoryReference();
        $token = $addToken->getNewToken();

        return $this
            ->appRepository
            ->addToken(
                $repositoryReference,
                $token
            )
            ->then(function () use ($repositoryReference, $token) {
                return $this
                    ->eventBus
                    ->dispatch(
                        (new TokenWasAdded($token))
                            ->withRepositoryReference($repositoryReference)
                    );
            });
    }
}
