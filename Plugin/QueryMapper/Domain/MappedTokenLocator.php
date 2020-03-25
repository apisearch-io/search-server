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

namespace Apisearch\Plugin\QueryMapper\Domain;

use Apisearch\Model\AppUUID;
use Apisearch\Model\Token;
use Apisearch\Model\TokenUUID;
use Apisearch\Server\Domain\Token\TokenLocator;
use function React\Promise\resolve;
use React\Promise\PromiseInterface;

/**
 * Class MappedTokenLocator.
 */
class MappedTokenLocator implements TokenLocator
{
    /**
     * @var QueryMapperLoader
     *
     * Query mapper loader
     */
    private $queryMapperLoader;

    /**
     * MappedTokenLocator constructor.
     *
     * @param QueryMapperLoader $queryMapperLoader
     */
    public function __construct(QueryMapperLoader $queryMapperLoader)
    {
        $this->queryMapperLoader = $queryMapperLoader;
    }

    /**
     * Locator is enabled.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return true;
    }

    /**
     * Get token by uuid.
     *
     * @param AppUUID   $appUUID
     * @param TokenUUID $tokenUUID
     *
     * @return PromiseInterface<Token|null>
     */
    public function getTokenByUUID(
        AppUUID $appUUID,
        TokenUUID $tokenUUID
    ): PromiseInterface {
        $queryMapper = $this
            ->queryMapperLoader
            ->getQueryMappers()
            ->findQueryMapperByToken($tokenUUID->composeUUID());

        if (
            (!$queryMapper instanceof QueryMapper) ||
            $queryMapper->getRepositoryReference()->getAppUUID()->composeUUID() !== $appUUID->composeUUID()
        ) {
            return resolve(null);
        }

        return resolve(
            new Token(
                $tokenUUID,
                $appUUID
            )
        );
    }
}
