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

use Apisearch\Query\Query;

/**
 * Class ItemsWereDeletedByQuery.
 */
final class ItemsWereDeletedByQuery extends DomainEvent
{
    /**
     * @var Query
     */
    private $query;

    /**
     * @param Query $query
     */
    public function __construct(Query $query)
    {
        parent::__construct();
        $this->query = $query;
    }

    /**
     * @return Query
     */
    public function getQuery(): array
    {
        return $this->query;
    }

    /**
     * to array payload.
     *
     * @return array
     */
    public function toArrayPayload(): array
    {
        return [];
    }
}
