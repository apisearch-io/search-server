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

namespace Apisearch\Plugin\Campaign\Domain\Command;

use Apisearch\Model\Token;
use Apisearch\Plugin\Campaign\Domain\Model\Campaign;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\CommandWithRepositoryReferenceAndToken;

/**
 * Class PutCampaign.
 */
class PutCampaign extends CommandWithRepositoryReferenceAndToken
{
    private Campaign $campaign;

    /**
     * ResetCommand constructor.
     *
     * @param RepositoryReference $repositoryReference
     * @param Token               $token
     * @param Campaign            $campaign
     */
    public function __construct(
        RepositoryReference $repositoryReference,
        Token $token,
        Campaign $campaign
    ) {
        parent::__construct($repositoryReference, $token);
        $this->campaign = $campaign;
    }

    /**
     * @return Campaign
     */
    public function getCampaign(): Campaign
    {
        return $this->campaign;
    }
}
