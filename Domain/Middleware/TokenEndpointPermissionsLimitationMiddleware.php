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

namespace Apisearch\Server\Domain\Middleware;

use Apisearch\Server\Domain\Command\PutToken;
use Apisearch\Server\Domain\Exception\InvalidTokenEndpointPermissionsException;
use Apisearch\Server\Domain\Model\EndpointNormalizer;
use Drift\CommandBus\Middleware\DiscriminableMiddleware;
use function React\Promise\reject;

/**
 * Class TokenEndpointPermissionsLimitationMiddleware.
 */
final class TokenEndpointPermissionsLimitationMiddleware implements DiscriminableMiddleware
{
    /**
     * @var string[]
     */
    private array $tokenEndpointPermissionsLimitation;
    private string $godToken;

    /**
     * @param string[] $tokenEndpointPermissionsLimitation
     * @param string   $godToken
     */
    public function __construct(array $tokenEndpointPermissionsLimitation, string $godToken)
    {
        $this->tokenEndpointPermissionsLimitation = $tokenEndpointPermissionsLimitation;
        $this->godToken = $godToken;
    }

    /**
     * @param object   $command
     * @param callable $next
     *
     * @return mixed
     */
    public function execute($command, callable $next)
    {
        $token = $command->getNewToken();
        $endpoints = EndpointNormalizer::normalizeEndpoints($token->getEndpoints());

        /*
         * This logic does not apply when the owner of the call is GOD. Only
         * limited to ordinary and earthly users
         */
        if ($command->getToken()->getTokenUUID()->composeUUID() === $this->godToken) {
            return $next($command);
        }

        if (
            !empty($this->tokenEndpointPermissionsLimitation) &&
            \count($endpoints) > \count(\array_intersect($endpoints, $this->tokenEndpointPermissionsLimitation))
        ) {
            return reject(InvalidTokenEndpointPermissionsException::createFromAvailableEndpoints(
                $this->tokenEndpointPermissionsLimitation
            ));
        }

        if (
            !empty($this->tokenEndpointPermissionsLimitation) &&
            empty($endpoints)
        ) {
            $token->setEndpoints($this->tokenEndpointPermissionsLimitation);
        }

        return $next($command);
    }

    /**
     * Only handle.
     *
     * @return string[]
     */
    public function onlyHandle(): array
    {
        return [
            PutToken::class,
        ];
    }
}
