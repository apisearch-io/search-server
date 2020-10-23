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

namespace Apisearch\Plugin\Campaign\Domain;

use Apisearch\Model\Token;
use Apisearch\Plugin\Campaign\Domain\Model\CampaignUID;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\CommandWithRepositoryReferenceAndToken;

/**
 * Class WithCampaignIdCommand.
 */
abstract class WithCampaignUidCommand extends CommandWithRepositoryReferenceAndToken
{
    private CampaignUID $campaignUid;

    /**
     * ResetCommand constructor.
     *
     * @param RepositoryReference $repositoryReference
     * @param Token               $token
     * @param CampaignUID         $campaignUid
     */
    public function __construct(
        RepositoryReference $repositoryReference,
        Token $token,
        CampaignUID $campaignUid
    ) {
        parent::__construct($repositoryReference, $token);
        $this->campaignUid = $campaignUid;
    }

    /**
     * @return CampaignUID
     */
    public function getCampaignUid(): CampaignUID
    {
        return $this->campaignUid;
    }
}
