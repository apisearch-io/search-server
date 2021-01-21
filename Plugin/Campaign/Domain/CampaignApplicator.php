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

use Apisearch\Plugin\Campaign\Domain\Model\Campaign;
use Apisearch\Plugin\Campaign\Domain\Model\CampaignRepository;
use Apisearch\Query\Query;
use Apisearch\Query\ScoreStrategy;
use Apisearch\Repository\RepositoryReference;
use DateTime;

/**
 * Class CampaignApplicator.
 */
class CampaignApplicator
{
    private Matcher $matcher;
    private CampaignRepository $campaignRepository;

    /**
     * @param Matcher            $matcher
     * @param CampaignRepository $campaignRepository
     */
    public function __construct(
        Matcher $matcher,
        CampaignRepository $campaignRepository
    ) {
        $this->matcher = $matcher;
        $this->campaignRepository = $campaignRepository;
    }

    /**
     * @param RepositoryReference $repositoryReference
     * @param Query               $query
     * @param DateTime            $dateTime
     *
     * @return void
     */
    public function applyCampaigns(
        RepositoryReference $repositoryReference,
        Query $query,
        DateTime $dateTime
    ): void {
        $campaigns = $this
            ->campaignRepository
            ->getCampaigns($repositoryReference);

        $campaigns = \array_filter($campaigns->getCampaigns(), function (Campaign $campaign) use ($repositoryReference, $query, $dateTime) {
            $matcher = $this->matcher;

            return
                $matcher->repositoryReferenceMatchesCampaign($repositoryReference, $campaign) &&
                $matcher->queryMatchesCampaign($query, $campaign, $dateTime);
        });

        /*
         * @var Campaign[]
         */
        foreach ($campaigns as $campaign) {
            $campaign->getCampaignModifiers()->applyModifiersToQuery($query);
            foreach ($campaign->getBoostingFilters() as $boostingFilter) {
                $query->addScoreStrategy(ScoreStrategy::createWeightFunction(
                    $boostingFilter->getBoostingFactor(),
                    $boostingFilter->getFilter(),
                    $boostingFilter->isMatchMainQuery()
                ));
            }
        }
    }
}
