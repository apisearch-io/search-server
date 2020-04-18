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

namespace Apisearch\Server\Domain\Event;

use Apisearch\Model\ItemUUID;

/**
 * Class ItemsWereIndexed.
 */
final class ItemsWereIndexed extends DomainEvent
{
    /**
     * @var ItemUUID[]
     *
     * Items UUID
     */
    private $itemsUUID;

    /**
     * ItemsWasIndexed constructor.
     *
     * @param ItemUUID[] $itemsUUID
     */
    public function __construct(array $itemsUUID)
    {
        parent::__construct();
        $this->itemsUUID = $itemsUUID;
    }

    /**
     * @return ItemUUID[]
     */
    public function getItemsUUID(): array
    {
        return $this->itemsUUID;
    }

    /**
     * to array payload.
     *
     * @return array
     */
    public function toArrayPayload(): array
    {
        return [
            'nb_items' => \count($this->itemsUUID),
            'item_uuid' => \array_values(
                \array_map(function (ItemUUID $itemUUID) {
                    return $itemUUID->composeUUID();
                }, $this->itemsUUID)
            ),
        ];
    }
}
