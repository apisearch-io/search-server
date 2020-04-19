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

use Apisearch\Server\Domain\Query\GetUsage;
use Apisearch\Server\Domain\Repository\UsageRepository\UsageRepository;
use DateTime;
use React\Promise\PromiseInterface;

/**
 * Class GetUsageHandler.
 */
class GetUsageHandler
{
    /**
     * @var UsageRepository
     */
    private $usageRepository;

    /**
     * @param UsageRepository $usageRepository
     */
    public function __construct(UsageRepository $usageRepository)
    {
        $this->usageRepository = $usageRepository;
    }

    /**
     * @param GetUsage $getUsage
     *
     * @return PromiseInterface
     */
    public function handle(GetUsage $getUsage): PromiseInterface
    {
        return $this
            ->usageRepository
            ->getRegisteredEvents(
                $getUsage->getRepositoryReference(),
                null,
                new DateTime('first day of this month')
            );
    }
}
