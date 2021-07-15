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

namespace Apisearch\Server\Domain\QueryHandler;

use Apisearch\Model\ItemUUID;
use Apisearch\Server\Domain\Query\GetPurchases;
use Apisearch\Server\Domain\Repository\PurchaseRepository\PurchaseFilter;
use Apisearch\Server\Domain\Repository\PurchaseRepository\PurchaseRepository;
use React\Promise\PromiseInterface;

/**
 * Class GetPurchasesHandler.
 */
class GetPurchasesHandler
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
     * @param GetPurchases $getPurchases
     *
     * @return PromiseInterface
     */
    public function handle(GetPurchases $getPurchases): PromiseInterface
    {
        $itemUUID = $getPurchases->getItemId()
            ? ItemUUID::createByComposedUUID($getPurchases->getItemId())
            : null;

        return $this
            ->purchaseRepository
            ->getRegisteredPurchases(
                PurchaseFilter::create($getPurchases->getRepositoryReference())
                    ->perDay($getPurchases->isPerDay())
                    ->from($getPurchases->getFrom())
                    ->to($getPurchases->getTo())
                    ->byUser($getPurchases->getUser())
                    ->byItem($itemUUID)
                    ->count($getPurchases->getCount())
            );
    }
}
