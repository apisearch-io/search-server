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
use Apisearch\Server\Tests\Functional\HttpFunctionalTest;

/**
 * Class CampaignHttpFunctionalTest.
 */
class CampaignHttpFunctionalTest extends HttpFunctionalTest implements CampaignMethodsTest
{
    use CampaignFunctionalTest;
    use CampaignScenariosTest;

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
    ) {
        $this->request(
            'v1_campaigns_put_campaign',
            [
                'campaign_id' => $campaign->getUid()->composeUID(),
                'app_id' => $appId ?? static::$appId,
                'index_id' => $indexId ?? static::$index,
            ],
            $token,
            $campaign->toArray()
        );
    }

    /**
     * @param string|null $appId
     * @param string|null $indexId
     * @param Token|null  $token
     */
    public function deleteCampaigns(
        ?string $appId = null,
        ?string $indexId = null,
        ?Token $token = null
    ) {
        $this->request(
            'v1_campaigns_delete_campaigns',
            [
                'app_id' => $appId ?? static::$appId,
                'index_id' => $indexId ?? static::$index,
            ],
            $token
        );
    }

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
    ) {
        $this->request(
            'v1_campaigns_delete_campaign',
            [
                'campaign_id' => $campaignUID->composeUID(),
                'app_id' => $appId ?? static::$appId,
                'index_id' => $indexId ?? static::$index,
            ],
            $token
        );
    }

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
    ): Campaigns {
        $result = $this->request(
            'v1_campaigns_get_campaigns',
            [
                'app_id' => $appId ?? static::$appId,
                'index_id' => $indexId ?? static::$index,
            ],
            $token
        );

        return Campaigns::createFromArray($result['body']);
    }
}
