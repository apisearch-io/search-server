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

namespace Apisearch\Plugin\Campaign\Domain\Model;

use Apisearch\Model\HttpTransportable;

/**
 * Class Campaigns.
 */
class Campaigns implements HttpTransportable
{
    /**
     * @var Campaign[]
     */
    private array $campaigns = [];

    /**
     * @param Campaign[] $campaigns
     */
    public function __construct(array $campaigns)
    {
        foreach ($campaigns as $campaign) {
            $this->putCampaign($campaign);
        }
    }

    /**
     * @param Campaign $campaign
     */
    public function putCampaign(Campaign $campaign)
    {
        $this->campaigns[$campaign->getUid()->getUid()] = $campaign;
    }

    /**
     * @return Campaign[]
     */
    public function getCampaigns(): array
    {
        return $this->campaigns;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return \array_map(function (Campaign $campaign) {
            return $campaign->toArray();
        }, $this->campaigns);
    }

    /**
     * @param array $array
     *
     * @return HttpTransportable|void
     */
    public static function createFromArray(array $array)
    {
        return new self(\array_map(function (array $campaignAsArray) {
            return Campaign::createFromArray($campaignAsArray);
        }, $array));
    }
}
