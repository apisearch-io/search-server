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

namespace Apisearch\Server\Domain\Repository\PurchaseRepository;

use Apisearch\Model\ItemUUID;
use Apisearch\Repository\RepositoryReference;
use DateTime;
use React\Promise\PromiseInterface;

/**
 * Interface PurchaseRepository.
 */
interface PurchaseRepository
{
    /**
     * @param RepositoryReference $repositoryReference
     * @param string              $user
     * @param DateTime            $when
     * @param ItemUUID[]          $itemsUUID
     *
     * @return PromiseInterface
     */
    public function registerPurchase(
        RepositoryReference $repositoryReference,
        string $user,
        DateTime $when,
        array $itemsUUID
    ): PromiseInterface;

    /**
     * @param PurchaseFilter $purchaseFilter
     *
     * @return PromiseInterface<Purchases>
     */
    public function getRegisteredPurchases(PurchaseFilter $purchaseFilter): PromiseInterface;
}
