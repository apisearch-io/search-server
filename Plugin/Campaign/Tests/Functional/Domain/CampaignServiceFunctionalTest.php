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

use Apisearch\Model\AppUUID;
use Apisearch\Model\IndexUUID;
use Apisearch\Model\Token;
use Apisearch\Model\TokenUUID;
use Apisearch\Plugin\Campaign\Domain\Command\DeleteCampaign;
use Apisearch\Plugin\Campaign\Domain\Command\DeleteCampaigns;
use Apisearch\Plugin\Campaign\Domain\Command\PutCampaign;
use Apisearch\Plugin\Campaign\Domain\Model\Campaign;
use Apisearch\Plugin\Campaign\Domain\Model\Campaigns;
use Apisearch\Plugin\Campaign\Domain\Model\CampaignUID;
use Apisearch\Plugin\Campaign\Domain\Query\GetCampaigns;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Tests\Functional\ServiceFunctionalTest;

/**
 * Class CampaignServiceFunctionalTest.
 */
class CampaignServiceFunctionalTest extends ServiceFunctionalTest implements CampaignMethodsTest
{
    use CampaignFunctionalTest;
    use CampaignScenariosTest;

    /**
     * @param Campaign $campaign
     * @param string   $appId
     * @param string   $indexId
     * @param Token    $token
     *
     * @return void
     */
    public function putCampaign(
        Campaign $campaign,
        ?string $appId = null,
        ?string $indexId = null,
        ?Token $token = null
    ) {
        $appUUID = AppUUID::createById($appId ?? self::$appId);

        self::executeCommand(new PutCampaign(
            RepositoryReference::create(
                $appUUID,
                IndexUUID::createById($index ?? self::$index)
            ),
            $token ??
            new Token(
                TokenUUID::createById(self::getParameterStatic('apisearch_server.god_token')),
                $appUUID
            ),
            $campaign
        ));
    }

    /**
     * @param string $appId
     * @param string $indexId
     * @param Token  $token
     *
     * @return void
     */
    public function deleteCampaigns(
        ?string $appId = null,
        ?string $indexId = null,
        ?Token $token = null
    ) {
        $appUUID = AppUUID::createById($appId ?? self::$appId);

        self::executeCommand(new DeleteCampaigns(
            RepositoryReference::create(
                $appUUID,
                IndexUUID::createById($index ?? self::$index)
            ),
            $token ??
            new Token(
                TokenUUID::createById(self::getParameterStatic('apisearch_server.god_token')),
                $appUUID
            ),
        ));
    }

    /**
     * @param CampaignUID $campaignUID
     * @param string      $appId
     * @param string      $indexId
     * @param Token       $token
     *
     * @return void
     */
    public function deleteCampaign(
        CampaignUID $campaignUID,
        ?string $appId = null,
        ?string $indexId = null,
        ?Token $token = null
    ) {
        $appUUID = AppUUID::createById($appId ?? self::$appId);

        self::executeCommand(new DeleteCampaign(
            RepositoryReference::create(
                $appUUID,
                IndexUUID::createById($index ?? self::$index)
            ),
            $token ??
            new Token(
                TokenUUID::createById(self::getParameterStatic('apisearch_server.god_token')),
                $appUUID
            ),
            $campaignUID
        ));
    }

    /**
     * @param string $appId
     * @param string $indexId
     * @param Token  $token
     *
     * @return Campaigns
     */
    public function getCampaigns(
        ?string $appId = null,
        ?string $indexId = null,
        ?Token $token = null
    ): Campaigns {
        $appUUID = AppUUID::createById($appId ?? self::$appId);

        return self::askQuery(new GetCampaigns(
            RepositoryReference::create(
                $appUUID,
                IndexUUID::createById($index ?? self::$index)
            ),
            $token ??
            new Token(
                TokenUUID::createById(self::getParameterStatic('apisearch_server.god_token')),
                $appUUID
            ),
        ));
    }
}
