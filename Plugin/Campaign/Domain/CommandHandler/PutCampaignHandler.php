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

namespace Apisearch\Plugin\Campaign\Domain\CommandHandler;

use Apisearch\Plugin\Campaign\Domain\Command\PutCampaign;
use Apisearch\Plugin\Campaign\Domain\Model\CampaignRepository;
use Apisearch\Server\Domain\ImperativeEvent\LoadMetadata;
use Drift\EventBus\Bus\EventBus;
use React\Promise\PromiseInterface;

/**
 * Class PutCampaignHandler.
 */
class PutCampaignHandler
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
     * @param PutCampaign $putCampaign
     *
     * @return PromiseInterface
     */
    public function handle(PutCampaign $putCampaign): PromiseInterface
    {
        return $this
            ->campaignRepository
            ->putCampaign(
                $putCampaign->getRepositoryReference(),
                $putCampaign->getCampaign()
            )
            ->then(function () use ($putCampaign) {
                return $this
                    ->eventBus
                    ->dispatch(new LoadMetadata($putCampaign->getRepositoryReference()));
            });
    }
}
