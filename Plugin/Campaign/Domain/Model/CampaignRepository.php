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

use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Repository\MetadataRepository\MetadataRepository;
use React\Promise\PromiseInterface;

/**
 * Interface CampaignRepository.
 */
final class CampaignRepository
{
    private MetadataRepository $metadataRepository;

    /**
     * @param MetadataRepository $metadataRepository
     */
    public function __construct(MetadataRepository $metadataRepository)
    {
        $this->metadataRepository = $metadataRepository;
    }

    /**
     * @param RepositoryReference $repositoryReference
     *
     * @return Campaigns
     */
    public function getCampaigns(RepositoryReference $repositoryReference): Campaigns
    {
        return new Campaigns($this->getRawCampaigns($repositoryReference));
    }

    /**
     * @param RepositoryReference $repositoryReference
     *
     * @return Campaign[]
     */
    public function getRawCampaigns(RepositoryReference $repositoryReference): array
    {
        return \array_map(function (array $campaignAsArray) {
            return Campaign::createFromArray($campaignAsArray);
        }, $this
            ->metadataRepository
            ->get(
                $repositoryReference,
                'campaigns'
            ) ?? []);
    }

    /**
     * @param RepositoryReference $repositoryReference
     * @param CampaignUID         $campaignUID
     *
     * @return Campaign|null
     */
    public function getCampaign(
        RepositoryReference $repositoryReference,
        CampaignUID $campaignUID
    ): ? Campaign {
        $campaigns = $this->getCampaigns($repositoryReference);

        return $campaigns[$campaignUID->composeUID()]
            ? Campaign::createFromArray($campaignUID[$campaignUID->composeUID()])
            : null;
    }

    /**
     * @param RepositoryReference $repositoryReference
     * @param Campaign            $campaign
     *
     * @return PromiseInterface
     */
    public function putCampaign(
        RepositoryReference $repositoryReference,
        Campaign $campaign
    ): PromiseInterface {
        $campaigns = $this->getRawCampaigns($repositoryReference);
        $campaigns[$campaign->getUid()->composeUID()] = $campaign;
        $campaignsAsArray = \array_map(function (Campaign $campaign) {
            return $campaign->toArray();
        }, $campaigns);

        return $this
            ->metadataRepository
            ->set($repositoryReference, 'campaigns', $campaignsAsArray);
    }

    /**
     * @param RepositoryReference $repositoryReference
     * @param CampaignUID         $campaignUID
     *
     * @return PromiseInterface
     */
    public function deleteCampaign(
        RepositoryReference $repositoryReference,
        CampaignUID $campaignUID
    ): PromiseInterface {
        $campaigns = $this->getRawCampaigns($repositoryReference);
        unset($campaigns[$campaignUID->composeUID()]);
        $campaignsAsArray = \array_map(function (Campaign $campaign) {
            return $campaign->toArray();
        }, $campaigns);

        return $this
            ->metadataRepository
            ->set($repositoryReference, 'campaigns', $campaignsAsArray);
    }

    /**
     * @param RepositoryReference $repositoryReference
     *
     * @return PromiseInterface
     */
    public function deleteCampaigns(RepositoryReference $repositoryReference): PromiseInterface
    {
        return $this
            ->metadataRepository
            ->set(
                $repositoryReference,
                'campaigns', null
            );
    }
}
