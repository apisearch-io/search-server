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

use Apisearch\Model\HttpTransportable;
use Apisearch\Query\Query;

/**
 * Class CampaignModifiers.
 */
class CampaignModifiers implements HttpTransportable
{
    private ?int $minScore;

    /**
     * @param Query $query
     */
    public function applyModifiersToQuery(Query $query)
    {
        if (\is_int($this->minScore)) {
            $query->setMinScore($this->minScore);
        }
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'min_score' => $this->minScore,
        ];
    }

    /**
     * @param array $array
     *
     * @return CampaignModifiers
     */
    public static function createFromArray(array $array)
    {
        $modifiers = new self();
        $modifiers->minScore = $array['min_score'] ?? null;

        return $modifiers;
    }
}
