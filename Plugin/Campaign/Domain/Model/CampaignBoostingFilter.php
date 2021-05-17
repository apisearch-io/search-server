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
    private array $filters = [];
    private float  $boostingFactor;
    private bool   $matchMainQuery;

    /**
     * @param Filter[] $filters
     * @param float    $boostingFactor
     * @param bool     $matchMainQuery
     */
    public function __construct(
        array $filters,
        float $boostingFactor,
        bool $matchMainQuery
    ) {
        $this->filters = $filters;
        $this->boostingFactor = $boostingFactor;
        $this->matchMainQuery = $matchMainQuery;
    }

    /**
     * @return Filter[]
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * @param Filter[] $filters
     */
    public function setFilters(array $filters): void
    {
        $this->filters = $filters;
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
