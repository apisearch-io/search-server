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

namespace Apisearch\Server\Domain\CommandHandler;

use Apisearch\Server\Domain\Command\PostPurchase;
use Apisearch\Server\Domain\Repository\PurchaseRepository\PurchaseRepository;
use DateTime;
use React\Promise\PromiseInterface;

/**
 * Class PostPurchaseHandler.
 */
class PostPurchaseHandler
{
    private PurchaseRepository $purchaseRepository;

    /**
     * @param PurchaseRepository $purchaseRepository
     */
    public function __construct(PurchaseRepository $purchaseRepository)
    {
        $this->purchaseRepository = $purchaseRepository;
    }

    /**
     * @param PostPurchase $putPurchase
     *
     * @return PromiseInterface
     */
    public function handle(PostPurchase $putPurchase): PromiseInterface
    {
        return $this
            ->purchaseRepository
            ->registerPurchase(
                $putPurchase->getRepositoryReference(),
                $putPurchase->getUserUUID(),
                new DateTime(),
                $putPurchase->getItemsUUID()
            );
    }
}
