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

namespace Apisearch\Plugin\Campaign\Domain\Model;

use Apisearch\Query\Filter;

/**
 * Class BoostingFilter.
 */
class CampaignBoostingFilter
{
    private Filter $filter;
    private float  $boostingFactor;
    private bool   $matchMainQuery;

    /**
     * @param Filter $filter
     * @param float  $boostingFactor
     * @param bool   $matchMainQuery
     */
    public function __construct(
        Filter $filter,
        float $boostingFactor,
        bool $matchMainQuery
    ) {
        $this->filter = $filter;
        $this->boostingFactor = $boostingFactor;
        $this->matchMainQuery = $matchMainQuery;
    }

    /**
     * @return Filter
     */
    public function getFilter(): Filter
    {
        return $this->filter;
    }

    /**
     * @param Filter $filter
     */
    public function setFilter(Filter $filter): void
    {
        $this->filter = $filter;
    }

    /**
     * @return float
     */
    public function getBoostingFactor(): float
    {
        return $this->boostingFactor;
    }

    /**
     * @return bool
     */
    public function isMatchMainQuery(): bool
    {
        return $this->matchMainQuery;
    }
}
