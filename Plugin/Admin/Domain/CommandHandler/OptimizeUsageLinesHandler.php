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

use Apisearch\Plugin\Admin\Domain\Command\OptimizeUsageLines;
use Apisearch\Server\Domain\Repository\UsageRepository\UsageRepository;
use React\Promise\PromiseInterface;

/**
 * Class OptimizeUsageLinesHandler.
 */
final class OptimizeUsageLinesHandler
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
     * @param OptimizeUsageLines $optimizeUsageLines
     *
     * @return PromiseInterface
     */
    public function handle(OptimizeUsageLines $optimizeUsageLines): PromiseInterface
    {
        return $this
            ->usageRepository
            ->optimize(
                $optimizeUsageLines->getFrom(),
                $optimizeUsageLines->getTo()
            );
    }
}
