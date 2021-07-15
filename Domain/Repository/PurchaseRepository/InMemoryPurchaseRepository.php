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

use Apisearch\Model\AppUUID;
use Apisearch\Model\ItemUUID;
use Apisearch\Repository\RepositoryReference;
use DateTime;
use React\Promise\PromiseInterface;
use function React\Promise\resolve;

/**
 * Class InMemoryPurchaseRepository.
 */
class InMemoryPurchaseRepository implements PurchaseRepository
{
    private array $purchases = [];

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
    ): PromiseInterface {
        $purchaseItems = new PurchaseItems();
        foreach ($itemsUUID as $itemUUID) {
            $purchaseItems->withItem(new PurchaseItem($itemUUID));
        }

        $this->purchases[] = new Purchase(
            $repositoryReference->getAppUUID()->composeUUID(),
            $repositoryReference->getIndexUUID()->composeUUID(),
            $user,
            $when,
            $purchaseItems
        );

        return resolve();
    }

    /**
     * @param PurchaseFilter $purchaseFilter
     *
     * @return PromiseInterface<Purchases>
     */
    public function getRegisteredPurchases(PurchaseFilter $purchaseFilter): PromiseInterface
    {
        $repositoryReference = $purchaseFilter->getRepositoryReference();
        $appUUID = $repositoryReference->getAppUUID();
        if (!$appUUID instanceof AppUUID) {
            return resolve([]);
        }

        $perDay = $purchaseFilter->isPerDay();
        $count = $purchaseFilter->getCount();
        $purchases = $perDay ? [] : (
            \is_null($count)
                ? []
                : 0
        );
        $uniqueUsers = [];

        foreach ($this->purchases as $purchase) {
            $whenFormatted = $purchase->getWhen()->format('Ymd');

            if (!$this->purchaseIsValidFromFilter($purchase, $purchaseFilter)) {
                continue;
            }

            if ($perDay) {
                if (!\array_key_exists($whenFormatted, $purchases)) {
                    $purchases[$whenFormatted] = 1;
                    $uniqueUsers[$whenFormatted] = [
                        $purchase->getUser() => true,
                    ];
                } else {
                    ++$purchases[$whenFormatted];
                    $uniqueUsers[$whenFormatted][$purchase->getUser()] = true;
                }
            } else {
                \is_null($count)
                    ? ($purchases[] = $purchase)
                    : ($purchases++);

                $uniqueUsers[$purchase->getUser()] = true;
            }
        }

        $uniqueUsers = $perDay
            ? \array_map(function (array $day) {
                return \count($day);
            }, $uniqueUsers)
            : \count($uniqueUsers);

        return resolve(PurchaseFilter::UNIQUE_USERS === $count
            ? $uniqueUsers
            : $purchases
        );
    }

    /**
     * Purchase is valid given a filter.
     *
     * @param Purchase       $purchase
     * @param PurchaseFilter $filter
     *
     * @return bool
     */
    private function purchaseIsValidFromFilter(
        Purchase $purchase,
        PurchaseFilter $filter
    ): bool {
        $whenFormatted = $purchase->getWhen()->format('Ymd');
        $repositoryReference = $filter->getRepositoryReference();
        $appUUID = $repositoryReference->getAppUUID();
        $indexUUID = $repositoryReference->getIndexUUID();

        if (!(
            (
                '*' === $appUUID->composeUUID() ||
                $appUUID->composeUUID() === $purchase->getAppUUID()
            ) &&
            (
                \is_null($indexUUID) ||
                '' === $indexUUID->composeUUID() ||
                '*' === $indexUUID->composeUUID() ||
                $indexUUID->composeUUID() === $purchase->getIndexUUID()
            )
        )) {
            return false;
        }

        if (
            !\is_null($filter->getUser()) &&
            $purchase->getUser() !== $filter->getUser()
        ) {
            return false;
        }

        if (
            !\is_null($filter->getItemUUID()) &&
            !$purchase->hasItem($filter->getItemUUID())
        ) {
            return false;
        }

        if (
            !\is_null($filter->getFrom()) &&
            $whenFormatted < $filter->getFrom()->format('Ymd')
        ) {
            return false;
        }

        if (
            !\is_null($filter->getTo()) &&
            $whenFormatted >= $filter->getTo()->format('Ymd')
        ) {
            return false;
        }

        return true;
    }
}
