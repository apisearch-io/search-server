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

use Apisearch\Model\Token;
use Apisearch\Server\Domain\Query\GetTokens;
use Apisearch\Server\Domain\WithAppRepositoryAndEventPublisher;
use React\Promise\PromiseInterface;

/**
 * Class GetTokensHandler.
 */
class GetTokensHandler extends WithAppRepositoryAndEventPublisher
{
    /**
     * @param GetTokens $getTokens
     *
     * @return PromiseInterface<Token[]>
     */
    public function handle(GetTokens $getTokens): PromiseInterface
    {
        return $this
            ->appRepository
            ->getTokens($getTokens->getRepositoryReference());
    }
}
