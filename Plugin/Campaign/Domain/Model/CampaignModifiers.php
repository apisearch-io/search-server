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
    private ?bool $disableProgressiveExactMatchingMetadata;

    /**
     * @param Query $query
     *
     * @return void
     */
    public function applyModifiersToQuery(Query $query): void
    {
        if (\is_int($this->minScore)) {
            $query->setMinScore($this->minScore);
        }

        if (true === $this->disableProgressiveExactMatchingMetadata) {
            $query->setMetadataValue('progressive_exact_matching_metadata', false);
        }
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'min_score' => $this->minScore,
            'disable_progressive_exact_matching_metadata' => $this->disableProgressiveExactMatchingMetadata,
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
        $modifiers->minScore = \intval($array['min_score'] ?? null);
        $modifiers->disableProgressiveExactMatchingMetadata = \boolval($array['disable_progressive_exact_matching_metadata'] ?? false);

        return $modifiers;
    }
}
