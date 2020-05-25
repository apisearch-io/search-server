<?php


namespace Apisearch\Plugin\Security\Domain\Middleware;

use Apisearch\Server\Domain\Plugin\PluginMiddleware;
use Apisearch\Server\Domain\Query\GetCORSPermissions;
use Closure;
use React\Promise\PromiseInterface;
use function React\Promise\resolve;

/**
 * Class RestrictedQueryOriginsMiddleware
 */
class RestrictedCORSOriginsMiddleware extends RestrictedOriginsMiddleware implements PluginMiddleware
{
    /**
     * {@inheritdoc}
     */
    public function onlyHandle(): array
    {
        return [
            GetCORSPermissions::class,
        ];
    }

    /**
     * @param Object $command
     * @param Closure $next
     * @param bool $isAllowed
     * @param string $origin
     *
     * @return PromiseInterface
     */
    protected function executeIfIsAllowed(
        $command,
        $next,
        bool $isAllowed,
        string $origin
    ) : PromiseInterface
    {
        return resolve($isAllowed
            ? $origin
            : false);
    }
}