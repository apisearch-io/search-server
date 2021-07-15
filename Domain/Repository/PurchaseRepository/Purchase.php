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
use DateTime;

class Purchase
{
    private string $appUUID;
    private string $indexUUID;
    private string $user;
    private DateTime $when;
    private PurchaseItems $purchaseItems;

    /**
     * @param string        $appUUID
     * @param string        $indexUUID
     * @param string        $user
     * @param DateTime      $when
     * @param PurchaseItems $purchaseItems
     */
    public function __construct(
        string $appUUID,
        string $indexUUID,
        string $user,
        DateTime $when,
        PurchaseItems $purchaseItems
    ) {
        $this->appUUID = $appUUID;
        $this->indexUUID = $indexUUID;
        $this->user = $user;
        $this->when = $when;
        $this->purchaseItems = $purchaseItems;
    }

    /**
     * @return string
     */
    public function getAppUUID(): string
    {
        return $this->appUUID;
    }

    /**
     * @return string
     */
    public function getIndexUUID(): string
    {
        return $this->indexUUID;
    }

    /**
     * @return string
     */
    public function getUser(): string
    {
        return $this->user;
    }

    /**
     * @return DateTime
     */
    public function getWhen(): DateTime
    {
        return $this->when;
    }

    /**
     * @return PurchaseItems
     */
    public function getPurchaseItems(): PurchaseItems
    {
        return $this->purchaseItems;
    }

    /**
     * @param ItemUUID $itemUUID
     *
     * @return bool
     */
    public function hasItem(ItemUUID $itemUUID): bool
    {
        return $this
            ->getPurchaseItems()
            ->hasItem($itemUUID);
    }
}
