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

namespace Apisearch\Plugin\Campaign\Domain\Middleware;

use Apisearch\Plugin\Campaign\Domain\CampaignApplicator;
use Apisearch\Server\Domain\Plugin\PluginMiddleware;
use Apisearch\Server\Domain\Query\Query;
use DateTime;
use React\Promise\PromiseInterface;

/**
 * Class AddCampaignsToQueryMiddleware.
 */
class AddCampaignsToQueryMiddleware implements PluginMiddleware
{
    private CampaignApplicator $campaignApplicator;

    /**
     * @param CampaignApplicator $campaignApplicator
     */
    public function __construct(CampaignApplicator $campaignApplicator)
    {
        $this->campaignApplicator = $campaignApplicator;
    }

    /**
     * @param Query    $command
     * @param callable $next
     *
     * @return PromiseInterface
     */
    public function execute($command, $next): PromiseInterface
    {
        $this
            ->campaignApplicator
            ->applyCampaigns(
                $command->getRepositoryReference(),
                $command->getQuery(),
                (new DateTime())
            );

        return $next($command);
    }

    /**
     * @return array
     */
    public function onlyHandle(): array
    {
        return [
            Query::class,
        ];
    }
}
