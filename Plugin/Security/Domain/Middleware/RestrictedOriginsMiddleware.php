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
use Apisearch\Server\Domain\Plugin\PluginMiddleware;
use Apisearch\Server\Domain\Query\GetCORSPermissions;
use Apisearch\Server\Domain\Repository\AppRepository\ConfigRepository;
use function React\Promise\resolve;
use React\Promise\PromiseInterface;

/**
 * Class RestrictedOriginsMiddleware.
 */
class RestrictedOriginsMiddleware implements PluginMiddleware
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
     * {@inheritdoc}
     */
    public function onlyHandle(): array
    {
        return [
            GetCORSPermissions::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function execute($command, $next): PromiseInterface
    {
        if (!$command instanceof GetCORSPermissions) {
            return $next($command);
        }

        $origin = $command->getOrigin();
        $ip = $command->getIP();
        if (empty($origin)) {
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
                        $origin,
                        $allowedDomains
                    );

                    $blockedIps = $metadata['blocked_ips'] ?? [];
                    $isPartialBlocked = $this->IPIsAllowed(
                        $ip,
                        $blockedIps
                    );

                    $isAllowed &= $isPartialAllowed & $isPartialBlocked;
                }
            }
        }

        return resolve($isAllowed
            ? $origin
            : false);
    }
}
