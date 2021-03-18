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

namespace Apisearch\Plugin\Admin\Domain\Middleware;

use Apisearch\Plugin\Admin\Domain\Model\MetricsPreloadKeys;
use Apisearch\Server\Domain\Plugin\PluginMiddleware;
use Apisearch\Server\Domain\Query\GetUsage;
use Apisearch\Server\Domain\Repository\MetadataRepository\MetadataRepository;
use DateTime;
use React\Promise\PromiseInterface;
use function React\Promise\resolve;

/**
 * Class GetUsageOptimizerMiddleware.
 */
class GetUsageOptimizerMiddleware implements PluginMiddleware
{
    private MetadataRepository $metadataRepository;

    /**
     * @param MetadataRepository $metadataRepository
     */
    public function __construct(MetadataRepository $metadataRepository)
    {
        $this->metadataRepository = $metadataRepository;
    }

    /**
     * @param GetUsage $getUsage
     * @param callable $next
     *
     * @return PromiseInterface
     */
    public function execute($getUsage, $next): PromiseInterface
    {
        if (
            $getUsage->getFrom()->format('Ymd') === (new DateTime('first day of this month'))->format('Ymd') &&
            null === $getUsage->getTo() &&
            $getUsage->isPerDay() &&
            null === $getUsage->getEventName() &&
            $this->metadataRepository->has(
                $getUsage->getRepositoryReference(),
                MetricsPreloadKeys::CURRENT_MONTH_USAGES_ALL
            )
        ) {
            return resolve($this->metadataRepository->get(
                $getUsage->getRepositoryReference(),
                MetricsPreloadKeys::CURRENT_MONTH_USAGES_ALL
            ));
        }

        if (
            $getUsage->getFrom()->format('Ymd') === (new DateTime('14 days ago'))->format('Ymd') &&
            null !== $getUsage->getTo() &&
            $getUsage->getTo()->format('Ymd') === (new DateTime('tomorrow'))->format('Ymd') &&
            $getUsage->isPerDay() &&
            null === $getUsage->getEventName() &&
            $this->metadataRepository->has(
                $getUsage->getRepositoryReference(),
                MetricsPreloadKeys::LAST_15_DAYS_USAGES_ALL
            )
        ) {
            return resolve($this->metadataRepository->get(
                $getUsage->getRepositoryReference(),
                MetricsPreloadKeys::LAST_15_DAYS_USAGES_ALL
            ));
        }

        return $next($getUsage);
    }

    /**
     * @return array
     */
    public function onlyHandle(): array
    {
        return [
            GetUsage::class,
        ];
    }
}
