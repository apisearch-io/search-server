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

use Apisearch\Model\IndexUUID;

/**
 * Class IndexWasExported.
 */
final class IndexWasExported extends DomainEvent
{
    private IndexUUID $indexUUID;
    private int $cost;
    private int $numberOfItems;

    /**
     * @param IndexUUID $indexUUID
     * @param int       $cost
     * @param int       $numberOfItems
     */
    public function __construct(IndexUUID $indexUUID, int $cost, int $numberOfItems)
    {
        parent::__construct();
        $this->indexUUID = $indexUUID;
        $this->cost = $cost;
        $this->numberOfItems = $numberOfItems;
    }

    /**
     * @return IndexUUID
     */
    public function getIndexUUID(): IndexUUID
    {
        return $this->indexUUID;
    }

    /**
     * @return int
     */
    public function getCost(): int
    {
        return $this->cost;
    }

    /**
     * @return int
     */
    public function getNumberOfItems(): int
    {
        return $this->numberOfItems;
    }

    /**
     * to array payload.
     *
     * @return array
     */
    public function toArrayPayload(): array
    {
        return [
            'cost' => $this->cost,
            'index_uuid' => $this
                ->indexUUID
                ->composeUUID(),
            'number_of_items' => $this->numberOfItems,
        ];
    }
}
