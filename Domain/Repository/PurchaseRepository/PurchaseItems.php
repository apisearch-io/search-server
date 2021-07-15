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

/**
 * Class PurchaseItems.
 */
class PurchaseItems
{
    /**
     * @var PurchaseItem[]
     */
    private array $items = [];

    /**
     * @param PurchaseItem[] $items
     */
    public function __construct(array $items = [])
    {
        foreach ($items as $item) {
            $this->withItem($item);
        }
    }

    /**
     * @param PurchaseItem $itemUUID
     *
     * @return $this
     */
    public function withItem(PurchaseItem $itemUUID): self
    {
        $this->items[] = $itemUUID;

        return $this;
    }

    /**
     * @return PurchaseItem[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @param ItemUUID $itemUUID
     *
     * @return bool
     */
    public function hasItem(ItemUUID $itemUUID): bool
    {
        foreach ($this->items as $item) {
            if ($item->getItemUUID()->composeUUID() === $itemUUID->composeUUID()) {
                return true;
            }
        }

        return false;
    }
}
