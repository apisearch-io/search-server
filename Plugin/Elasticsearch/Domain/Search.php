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

namespace Apisearch\Plugin\Elasticsearch\Domain;

use Elastica\Query;

/**
 * Class Search.
 */
class Search
{
    /**
     * @var Query
     *
     * Elasticsearch Query
     */
    private $query;

    /**
     * @var int
     *
     * From
     */
    private $from;

    /**
     * @var int
     *
     * Size
     */
    private $size;

    /**
     * @var string
     *
     * Name
     */
    private $name;

    /**
     * Search constructor.
     *
     * @param Query  $query
     * @param int    $from
     * @param int    $size
     * @param string $name
     */
    public function __construct(
        Query $query,
        int $from,
        int $size,
        string $name = null
    ) {
        $this->query = $query;
        $this->from = $from;
        $this->size = $size;
        $this->name = $name;
    }

    /**
     * Get query.
     *
     * @return Query
     */
    public function getQuery(): Query
    {
        return $this->query;
    }

    /**
     * Get from.
     *
     * @return int
     */
    public function getFrom(): int
    {
        return $this->from;
    }

    /**
     * Get size.
     *
     * @return mixed
     */
    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
}
