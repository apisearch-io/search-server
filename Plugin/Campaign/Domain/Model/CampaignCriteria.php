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
 * Class CampaignCriteria.
 */
class CampaignCriteria
{
    const MATCH_TYPE_NONE = '';
    const MATCH_TYPE_EXACT = 'exact';
    const MATCH_TYPE_SIMILAR = 'similar';
    const MATCH_TYPE_INCLUDES_EXACT = 'includes_exact';
    const MATCH_TYPE_INCLUDES_SIMILAR = 'includes_similar';

    private string $matchType;
    private ?string $queryText;
    private array $filters;

    /**
     * @param string      $matchType
     * @param string|null $queryText
     * @param Filter[]    $filters
     */
    public function __construct(
        string $matchType,
        ?string $queryText,
        array $filters = []
    ) {
        $this->matchType = $matchType;
        $this->queryText = $queryText;
        $this->filters = $filters;
    }

    /**
     * @return string
     */
    public function getMatchType(): string
    {
        return $this->matchType;
    }

    /**
     * @return string|null
     */
    public function getQueryText(): ?string
    {
        return $this->queryText;
    }

    /**
     * @return array
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
}
