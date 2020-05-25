<?php


namespace Apisearch\Plugin\Security\Domain\Middleware;

use Apisearch\Exception\ForbiddenException;
use Apisearch\Server\Domain\Plugin\PluginMiddleware;
use Apisearch\Server\Domain\Query\Query;
use Closure;
use React\Promise\PromiseInterface;
use function React\Promise\reject;

/**
 * Class RestrictedQueryOriginsMiddleware
 */
class RestrictedQueryOriginsMiddleware extends RestrictedOriginsMiddleware implements PluginMiddleware
{
    /**
     * {@inheritdoc}
     */
    public function onlyHandle(): array
    {
        return [
            Query::class,
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
        return $isAllowed
            ? $next($command)
            : reject(new ForbiddenException());
    }
}