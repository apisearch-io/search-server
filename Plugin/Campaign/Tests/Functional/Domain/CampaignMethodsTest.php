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

namespace Apisearch\Plugin\Campaign\Tests\Functional\Domain;

use Apisearch\Model\Token;
use Apisearch\Plugin\Campaign\Domain\Model\Campaign;
use Apisearch\Plugin\Campaign\Domain\Model\Campaigns;
use Apisearch\Plugin\Campaign\Domain\Model\CampaignUID;

/**
 * Interface CampaignMethodsTest.
 */
interface CampaignMethodsTest
{
    /**
     * @param Campaign    $campaign
     * @param string|null $appId
     * @param string|null $indexId
     * @param Token|null  $token
     */
    public function putCampaign(
        Campaign $campaign,
        ?string $appId = null,
        ?string $indexId = null,
        ?Token $token = null
    );

    /**
     * @param string|null $appId
     * @param string|null $indexId
     * @param Token|null  $token
     */
    public function deleteCampaigns(
        ?string $appId = null,
        ?string $indexId = null,
        ?Token $token = null
    );

    /**
     * @param CampaignUID $campaignUID
     * @param string|null $appId
     * @param string|null $indexId
     * @param Token|null  $token
     */
    public function deleteCampaign(
        CampaignUID $campaignUID,
        ?string $appId = null,
        ?string $indexId = null,
        ?Token $token = null
    );

    /**
     * @param string|null $appId
     * @param string|null $indexId
     * @param Token|null  $token
     *
     * @return Campaigns
     */
    public function getCampaigns(
        ?string $appId = null,
        ?string $indexId = null,
        ?Token $token = null
    ): Campaigns;
}
