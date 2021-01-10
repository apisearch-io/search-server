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

use Apisearch\Model\Changes;
use Apisearch\Query\Filter;

/**
 * Class ItemsWereUpdated.
 */
final class ItemsWereUpdated extends DomainEvent
{
    /**
     * @var Filter[]
     */
    private array $appliedFilters;
    private Changes $changes;

    /**
     * ItemsWasIndexed constructor.
     *
     * @param Filter[] $appliedFilters
     * @param Changes  $changes
     */
    public function __construct(
        array $appliedFilters,
        Changes $changes
    ) {
        parent::__construct();
        $this->appliedFilters = $appliedFilters;
        $this->changes = $changes;
    }

    /**
     * @return Filter[]
     */
    public function getAppliedFilters(): array
    {
        return $this->appliedFilters;
    }

    /**
     * @return Changes
     */
    public function getChanges(): Changes
    {
        return $this->changes;
    }

    /**
     * to array payload.
     *
     * @return array
     */
    public function toArrayPayload(): array
    {
        return [
            'filters' => \json_encode(\array_map(function (Filter $filter) {
                return $filter->toArray();
            }, $this->appliedFilters)),
            'changes' => \json_encode($this->changes->toArray()),
        ];
    }
}
