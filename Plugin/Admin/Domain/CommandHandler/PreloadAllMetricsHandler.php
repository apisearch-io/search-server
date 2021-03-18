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

namespace Apisearch\Plugin\Admin\Domain\CommandHandler;

use Apisearch\Plugin\Admin\Domain\Command\PreloadAllMetrics;
use Apisearch\Plugin\Admin\Domain\Model\MetricsPreloadKeys;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Repository\AppRepository\ConfigRepository;
use Apisearch\Server\Domain\Repository\MetadataRepository\MetadataRepository;
use Apisearch\Server\Domain\Repository\UsageRepository\UsageRepository;
use DateTime;
use function React\Promise\all;
use React\Promise\PromiseInterface;

/**
 * Class PreloadAllMetricsHandler.
 */
class PreloadAllMetricsHandler
{
    private UsageRepository $usageRepository;
    private MetadataRepository $metadataRepository;
    private ConfigRepository $configRepository;

    /**
     * @param UsageRepository    $usageRepository
     * @param MetadataRepository $metadataRepository
     * @param ConfigRepository   $configRepository
     */
    public function __construct(
        UsageRepository $usageRepository,
        MetadataRepository $metadataRepository,
        ConfigRepository $configRepository
    ) {
        $this->usageRepository = $usageRepository;
        $this->metadataRepository = $metadataRepository;
        $this->configRepository = $configRepository;
    }

    /**
     * @param PreloadAllMetrics $preloadAllMetrics
     *
     * @return PromiseInterface
     */
    public function handle(PreloadAllMetrics $preloadAllMetrics): PromiseInterface
    {
        return all([
            $this->preloadLastNDaysUsages(14, MetricsPreloadKeys::LAST_15_DAYS_USAGES_ALL),
            $this->preloadCurrentMonth(),
        ]);
    }

    /**
     * @param int    $days
     * @param string $key
     *
     * @return PromiseInterface
     */
    private function preloadLastNDaysUsages(
        int $days,
        string $key
    ): PromiseInterface {
        $from = (new DateTime('today'))->modify('-'.$days.' days');
        $to = (new DateTime('tomorrow'));

        return all(\array_map(function (RepositoryReference $repositoryReference) use ($from, $to, $key) {
            return $this->preloadPerIntervalKeyAndRepositoryReference(
                $repositoryReference,
                $from,
                $to,
                $key
            );
        }, $this->getAllAvailableIndices()));
    }

    /**
     * @return PromiseInterface
     */
    private function preloadCurrentMonth(): PromiseInterface
    {
        $from = (new DateTime('first day of this month'));
        $to = (new DateTime('first day of next month'));

        return all(\array_map(function (RepositoryReference $repositoryReference) use ($from, $to) {
            return $this->preloadPerIntervalKeyAndRepositoryReference(
                $repositoryReference,
                $from,
                $to,
                MetricsPreloadKeys::CURRENT_MONTH_USAGES_ALL
            );
        }, $this->getAllAvailableIndices()));
    }

    /**
     * @param RepositoryReference $repositoryReference
     * @param DateTime            $from
     * @param DateTime            $to
     * @param string              $key
     *
     * @return PromiseInterface
     */
    private function preloadPerIntervalKeyAndRepositoryReference(
        RepositoryReference $repositoryReference,
        DateTime $from,
        DateTime $to,
        string $key
    ): PromiseInterface {
        return $this
            ->usageRepository
            ->getRegisteredEvents(
                $repositoryReference,
                null,
                $from,
                $to,
                true
            )
            ->then(function (array $usages) use ($repositoryReference, $key, $from, $to) {
                return $this
                    ->metadataRepository
                    ->set($repositoryReference, $key, [
                        'data' => $usages,
                        'from' => $from->format('Ymd'),
                        'to' => $to->format('Ymd'),
                        'days' => ($to->diff($from)->days),
                    ]);
            });
    }

    /**
     * @return RepositoryReference[]
     */
    private function getAllAvailableIndices(): array
    {
        return $this->configRepository->allRepositoryReferences();
    }
}
