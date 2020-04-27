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
    /**
     * @var IndexUUID
     *
     * Index UUID
     */
    private $indexUUID;

    /**
     * @var int
     */
    private $cost;

    /**
     * IndexWasConfigured constructor.
     *
     * @param IndexUUID $indexUUID
     * @param int       $cost
     */
    public function __construct(
        IndexUUID $indexUUID,
        int $cost
    ) {
        parent::__construct();
        $this->indexUUID = $indexUUID;
        $this->cost = $cost;
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
        ];
    }
}
