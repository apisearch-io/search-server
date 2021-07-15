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

class PurchaseItem
{
    private ItemUUID $itemUUID;

    /**
     * @param ItemUUID $itemUUID
     */
    public function __construct(ItemUUID $itemUUID)
    {
        $this->itemUUID = $itemUUID;
    }

    /**
     * @return ItemUUID
     */
    public function getItemUUID(): ItemUUID
    {
        return $this->itemUUID;
    }
}
