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
 * Class ItemsWereDeleted.
 */
final class ItemsWereDeleted extends DomainEvent
{
    /**
     * @var ItemUUID[]
     */
    private array $itemsUUID;

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
        ];
    }
}
