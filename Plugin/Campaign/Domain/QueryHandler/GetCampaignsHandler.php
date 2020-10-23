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

namespace Apisearch\Plugin\Campaign\Domain\QueryHandler;

use Apisearch\Plugin\Campaign\Domain\Model\CampaignRepository;
use Apisearch\Plugin\Campaign\Domain\Model\Campaigns;
use Apisearch\Plugin\Campaign\Domain\Query\GetCampaigns;
use Drift\EventBus\Bus\EventBus;

/**
 * Class GetCampaignsHandler.
 */
class GetCampaignsHandler
{
    private CampaignRepository $campaignRepository;
    private EventBus $eventBus;

    /**
     * @param CampaignRepository $campaignRepository
     * @param EventBus           $eventBus
     */
    public function __construct(
        CampaignRepository $campaignRepository,
        EventBus $eventBus
    ) {
        $this->campaignRepository = $campaignRepository;
        $this->eventBus = $eventBus;
    }

    /**
     * @param GetCampaigns $getCampaigns
     *
     * @return Campaigns
     */
    public function handle(GetCampaigns $getCampaigns): Campaigns
    {
        return $this
            ->campaignRepository
            ->getCampaigns($getCampaigns->getRepositoryReference());
    }
}
