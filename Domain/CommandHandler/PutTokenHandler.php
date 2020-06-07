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

use Apisearch\Server\Domain\Command\PutToken;
use Apisearch\Server\Domain\Event\TokenWasPut;
use Apisearch\Server\Domain\WithAppRepositoryAndEventPublisher;
use React\Promise\PromiseInterface;

/**
 * Class PutTokenHandler.
 */
class PutTokenHandler extends WithAppRepositoryAndEventPublisher
{
    /**
     * @param PutToken $putToken
     *
     * @return PromiseInterface
     */
    public function handle(PutToken $putToken): PromiseInterface
    {
        $repositoryReference = $putToken->getRepositoryReference();
        $token = $putToken->getNewToken();

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
                        (new TokenWasPut($token))
                            ->withRepositoryReference($repositoryReference)
                    );
            });
    }
}
