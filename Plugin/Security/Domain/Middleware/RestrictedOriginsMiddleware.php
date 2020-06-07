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

namespace Apisearch\Plugin\Security\Domain\Middleware;

use Apisearch\Config\Config;
use Apisearch\Model\IndexUUID;
use Apisearch\Plugin\Security\Domain\OriginMatcherTrait;
use Apisearch\Server\Domain\Query\GetCORSPermissions;
use Apisearch\Server\Domain\Repository\AppRepository\ConfigRepository;
use Closure;
use React\Promise\PromiseInterface;

/**
 * Class RestrictedOriginsMiddleware.
 */
abstract class RestrictedOriginsMiddleware
{
    use OriginMatcherTrait;

    /**
     * @var ConfigRepository
     */
    private $configRepository;

    /**
     * @param ConfigRepository $configRepository
     */
    public function __construct(ConfigRepository $configRepository)
    {
        $this->configRepository = $configRepository;
    }

    /**
     * @param object  $command
     * @param Closure $next
     *
     * @return PromiseInterface
     */
    public function execute($command, $next): PromiseInterface
    {
        /**
         * @var GetCORSPermissions
         */
        $origin = $command->getOrigin();
        $ip = $origin->getIp();
        $host = $origin->getHost();

        if (
            empty($origin) &&
            empty($ip)
        ) {
            return $next($command);
        }

        $repositoryReference = $command->getRepositoryReference();
        $indexUUID = $repositoryReference->getIndexUUID()->composeUUID();
        $indicesIds = \explode(',', $indexUUID);
        $isAllowed = true;

        foreach ($indicesIds as $indicesId) {
            $configs = ('*' === $indicesId || empty($indicesId))
                ? $this
                    ->configRepository
                    ->getAppConfigs($repositoryReference->getAppUUID())
                : [$this
                    ->configRepository
                    ->getConfig($repositoryReference->changeIndex(IndexUUID::createById($indicesId))), ];

            foreach ($configs as $config) {
                if ($config instanceof Config) {
                    $metadata = $config->getMetadata();
                    $allowedDomains = $metadata['allowed_domains'] ?? [];
                    $isPartialAllowed = $this->originIsAllowed(
                        $host,
                        $allowedDomains
                    );

                    $blockedIps = $metadata['blocked_ips'] ?? [];
                    $isPartialBlocked = $this->IPIsAllowed(
                        $ip,
                        $blockedIps
                    );

                    $isAllowed = $isAllowed && $isPartialAllowed && $isPartialBlocked;
                }
            }
        }

        return $this->executeIfIsAllowed(
            $command,
            $next,
            $isAllowed,
            $host
        );
    }

    /**
     * @param object  $command
     * @param Closure $next
     * @param bool    $isAllowed
     * @param string  $origin
     *
     * @return PromiseInterface
     */
    abstract protected function executeIfIsAllowed(
        $command,
        $next,
        bool $isAllowed,
        string $origin
    ): PromiseInterface;
}
