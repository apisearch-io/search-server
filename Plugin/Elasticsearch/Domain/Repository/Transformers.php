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

namespace Apisearch\Plugin\Elasticsearch\Domain\Repository;

use Apisearch\Model\ItemUUID;
use Apisearch\Query\Query;
use Elastica\Query as ElasticaQuery;
use Elastica\Suggest;

/**
 * Trait Transformers.
 */
trait Transformers
{
    /**
     * Create Elasticsearch query by model query.
     *
     * @param Query $query
     *
     * @return ElasticaQuery
     */
    private function createElasticaQueryByModelQuery(Query $query): ElasticaQuery
    {
        $mainQuery = new ElasticaQuery();
        $boolQuery = new ElasticaQuery\BoolQuery();
        $this
            ->queryBuilder
            ->buildQuery(
                $query,
                $mainQuery,
                $boolQuery
            );

        $this->promoteUUIDs(
            $boolQuery,
            $query->getItemsPromoted()
        );

        if ($query->areHighlightEnabled()) {
            $this->addHighlights($mainQuery);
        }

        $this->addSuggest(
            $mainQuery,
            $query
        );

        $mainQuery->setExplain(false);

        return $mainQuery;
    }

    /**
     * Add suggest into an Elastica Query.
     *
     * @param ElasticaQuery $mainQuery
     * @param Query         $query
     *
     * @return void
     */
    private function addSuggest($mainQuery, $query): void
    {
        if ($query->areSuggestionsEnabled()) {
            $completitionText = new Suggest\Completion(
                'completion',
                'suggest'
            );
            $completitionText->setText($query->getQueryText());
            $size = $query->getMetadata()['number_of_suggestions'] + 1;
            $completitionText->setSize($size);

            $mainQuery->setSuggest(
                new Suggest($completitionText)
            );
        }
    }

    /**
     * Promote UUID.
     *
     * The boosting values go from 1 (not included) to 3 (not included)
     *
     * @param ElasticaQuery\BoolQuery $boolQuery
     * @param ItemUUID[]              $itemsPriorized
     *
     * @return void
     */
    private function promoteUUIDs(
        ElasticaQuery\BoolQuery $boolQuery,
        array $itemsPriorized
    ) {
        if (empty($itemsPriorized)) {
            return;
        }

        $it = \count($itemsPriorized);
        foreach ($itemsPriorized as $position => $itemUUID) {
            $boolQuery->addShould(new ElasticaQuery\Term([
                '_id' => [
                    'value' => $itemUUID->composeUUID(),
                    'boost' => 10 + ($it-- / (\count($itemsPriorized) + 1)),
                ],
            ]));
        }
    }

    /**
     * Highlight.
     *
     * @param ElasticaQuery $query
     *
     * @return void
     */
    private function addHighlights(ElasticaQuery $query): void
    {
        $query->setHighlight([
            'fields' => [
                '*' => [
                    'fragment_size' => 100,
                    'number_of_fragments' => 3,
                ],
            ],
        ]);
    }
}
