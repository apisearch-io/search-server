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

use Apisearch\Model\Changes;
use Apisearch\Model\Coordinate;
use Apisearch\Model\Item;
use Apisearch\Model\ItemUUID;
use Apisearch\Plugin\Elasticsearch\Domain\Builder\QueryBuilder;
use Apisearch\Plugin\Elasticsearch\Domain\ElasticaWrapper;
use Apisearch\Plugin\Elasticsearch\Domain\WithElasticaWrapper;
use Apisearch\Query\Query;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Repository\Repository\ItemsRepository as ItemRepositoryInterface;
use Elastica\Document as ElasticaDocument;
use Elastica\Query as ElasticaQuery;
use Elastica\Script\Script;
use Elasticsearch\Endpoints\UpdateByQuery;
use React\Promise\PromiseInterface;
use function React\Promise\resolve;

/**
 * Class ItemRepository.
 */
class ItemRepository extends WithElasticaWrapper implements ItemRepositoryInterface
{
    use Transformers;

    private QueryBuilder $queryBuilder;

    /**
     * ElasticaSearchRepository constructor.
     *
     * @param ElasticaWrapper $elasticaWrapper
     * @param bool            $refreshOnWrite
     * @param QueryBuilder    $queryBuilder
     */
    public function __construct(
        ElasticaWrapper $elasticaWrapper,
        bool $refreshOnWrite,
        QueryBuilder $queryBuilder
    ) {
        parent::__construct(
            $elasticaWrapper,
            $refreshOnWrite
        );

        $this->queryBuilder = $queryBuilder;
    }

    /**
     * Generate items documents.
     *
     * @param RepositoryReference $repositoryReference
     * @param Item[]              $items
     *
     * @return PromiseInterface
     */
    public function addItems(
        RepositoryReference $repositoryReference,
        array $items
    ): PromiseInterface {
        $documents = [];
        foreach ($items as $item) {
            $documents[] = $this->createItemDocument($item);
        }

        if (empty($documents)) {
            return resolve(null);
        }

        return $this
            ->elasticaWrapper
            ->addDocuments(
                $repositoryReference,
                $documents,
                $this->refreshOnWrite
            );
    }

    /**
     * Delete items.
     *
     * @param RepositoryReference $repositoryReference
     * @param ItemUUID[]          $itemUUIDs
     *
     * @return PromiseInterface
     */
    public function deleteItems(
        RepositoryReference $repositoryReference,
        array $itemUUIDs
    ): PromiseInterface {
        return $this
            ->elasticaWrapper
            ->deleteDocumentsByIds(
                $repositoryReference,
                \array_map(function (ItemUUID $itemUUID) {
                    return $itemUUID->composeUUID();
                }, $itemUUIDs),
                $this->refreshOnWrite
            );
    }

    /**
     * @param RepositoryReference $repositoryReference
     * @param Query               $query
     *
     * @return PromiseInterface
     */
    public function deleteItemsByQuery(
        RepositoryReference $repositoryReference,
        Query $query
    ): PromiseInterface {
        return $this
            ->elasticaWrapper
            ->deleteDocumentsByQuery(
                $repositoryReference,
                $this->createElasticaQueryByModelQuery($query),
                $this->refreshOnWrite
            );
    }

    /**
     * Update items.
     *
     * @param RepositoryReference $repositoryReference
     * @param Query               $query
     * @param Changes             $changes
     *
     * @return PromiseInterface
     */
    public function updateItems(
        RepositoryReference $repositoryReference,
        Query $query,
        Changes $changes
    ): PromiseInterface {
        $mainQuery = new ElasticaQuery();
        $boolQuery = new ElasticaQuery\BoolQuery();
        $this
            ->queryBuilder
            ->buildQuery(
                $query,
                $mainQuery,
                $boolQuery
            );

        $query = ElasticaQuery::create($mainQuery)->getQuery();
        $endpoint = new UpdateByQuery();
        $body = ['query' => \is_array($query)
            ? $query
            : $query->toArray(),
        ];

        $body['script'] = $this->createUpdateScriptByChanges($changes)->toArray()['script'];
        $endpoint->setBody($body);
        $endpoint->setParams([
            'conflicts' => 'proceed',
            'refresh' => $this->refreshOnWrite,
        ]);

        return $this
            ->elasticaWrapper
            ->requestAsyncEndpoint(
                $endpoint,
                $this
                    ->elasticaWrapper
                    ->getIndexAliasName($repositoryReference)
            );
    }

    /**
     * Create item document.
     *
     * @param Item $item
     *
     * @return ElasticaDocument
     */
    private function createItemDocument(Item $item): ElasticaDocument
    {
        $uuid = $item->getUUID();
        $indexedMetadata = $item->getIndexedMetadata();

        $itemDocument = [
            'uuid' => [
                'id' => $uuid->getId(),
                'type' => $uuid->getType(),
            ],
            'coordinate' => $item->getCoordinate() instanceof Coordinate
                ? $item
                    ->getCoordinate()
                    ->toArray()
                : null,
            'metadata' => $this->filterElementRecursively(
                $item->getMetadata()
            ),
            'indexed_metadata' => $this->filterElementRecursively($indexedMetadata),
            'searchable_metadata' => $this->filterSearchableElementRecursively(
                $item->getSearchableMetadata(),
                false
            ),
            'exact_matching_metadata' => $this->filterSearchableElementRecursively(
                $item->getExactMatchingMetadata(),
                true
            ),
            'suggest' => \array_values(
                $this->filterSearchableElementRecursively(
                    $item->getSuggest(),
                    false
                )
            ),
        ];

        return new ElasticaDocument($uuid->composeUUID(), $itemDocument);
    }

    /**
     * Filter recursively element for index and data.
     *
     * @param mixed $elements
     *
     * @return mixed $element
     */
    private function filterElementRecursively(array $elements)
    {
        foreach ($elements as $key => $element) {
            if (\is_array($element)) {
                $elements[$key] = $this->filterElementRecursively($element);
            }
        }

        $elements = \array_filter(
            $elements,
            [$this, 'filterElement']
        );

        return $elements;
    }

    /**
     * Filter element for index and data.
     *
     * @param mixed $element
     *
     * @return mixed $element
     */
    private function filterElement($element)
    {
        return !(
            \is_null($element) ||
            (\is_array($element) && empty($element))
        );
    }

    /**
     * Filter element for search.
     *
     * @param array $elements
     * @param bool  $asList
     *
     * @return mixed $element
     */
    private function filterSearchableElementRecursively(
        array $elements,
        bool $asList
    ) {
        foreach ($elements as $key => $element) {
            if (\is_array($element)) {
                $elements[$key] = $this->filterSearchableElementRecursively($element, true);
            }
        }

        $elements = \array_filter(
            $elements,
            [$this, 'filterSearchableElement']
        );

        if ($asList) {
            $elements = \array_values($elements);
        }

        return $elements;
    }

    /**
     * Filter element for search.
     *
     * @param mixed $element
     *
     * @return mixed $element
     */
    private function filterSearchableElement($element)
    {
        return !(
            \is_null($element) ||
            \is_bool($element) ||
            (\is_string($element) && empty($element)) ||
            (\is_array($element) && empty($element))
        );
    }

    /**
     * Build update script by Changes.
     *
     * @param Changes $changes
     *
     * @return Script|null
     */
    private function createUpdateScriptByChanges(Changes $changes): ? Script
    {
        if (empty($changes->getChanges())) {
            return null;
        }

        $bucleScripts = [];
        $singleScripts = [];
        $params = [];
        foreach ($changes->getChanges() as $change) {
            $field = $change['field'];
            $internalField = $this->parseExpressionToInternal($field);
            $currentScript = null;
            $currentValue = null;
            $type = $change['type'];

            if ($type & Changes::TYPE_VALUE) {
                $fieldName = 'param_'.\str_replace('.', '_', $field).'_'.\rand(0, 99999999999);
                $currentValue = "params.$fieldName";
                $currentScript = "$internalField = $currentValue;";
                $params[$fieldName] = $change['value'];
            }

            if ($type & Changes::TYPE_LITERAL) {
                $currentValue = $this->parseExpressionToInternal($change['value']);
                $currentScript = "$internalField = $currentValue;";
            }

            if ($type & Changes::TYPE_ARRAY) {
                if (
                    ($type & Changes::TYPE_ARRAY_EXPECTS_ELEMENT) &&
                    empty($currentValue)
                ) {
                    continue;
                }

                $condition = isset($change['condition']) && !empty($change['condition'])
                    ? $this->parseExpressionToInternal($change['condition'])
                    : null;

                $assignmentLine = null;

                if ($type & Changes::TYPE_ARRAY_ELEMENT_ADD) {
                    $singleScripts[] = "{$internalField}.add($currentValue);";
                    continue;
                } elseif ($type & Changes::TYPE_ARRAY_ELEMENT_DELETE) {
                    $assignmentLine = "{$internalField}.remove(i);";
                } elseif ($type & Changes::TYPE_ARRAY_ELEMENT_UPDATE) {
                    $assignmentLine = "{$internalField}.set(i, $currentValue);";
                }

                if (\is_null($assignmentLine)) {
                    continue;
                }

                if (!\is_null($condition)) {
                    $assignmentLine = "    if ($condition) {
        $assignmentLine
    }";
                }

                if (!isset($bucleScripts[$internalField])) {
                    $bucleScripts[$internalField] = [];
                }

                $bucleScripts[$internalField][] = $assignmentLine;

                continue;
            }

            $singleScripts[] = $currentScript;
        }

        $finalScript = 'def item = ctx._source;
def element;'.PHP_EOL;

        $finalScript .= \implode(PHP_EOL, $singleScripts).PHP_EOL;
        foreach ($bucleScripts as $bucleInternalField => $bucleScriptElements) {
            $rand = \rand(0, 100000000000000);
            $finalScript .= "def field_{$rand} = $bucleInternalField;
if (field_$rand != null && field_$rand instanceof Collection) {
    for (int i = 0; i < field_$rand.length; i++) {
        element = field_{$rand}[i];".PHP_EOL;

            foreach ($bucleScriptElements as $bucleScriptElement) {
                $finalScript .= $bucleScriptElement.PHP_EOL;
            }

            $finalScript .= '}}'.PHP_EOL;
        }

        $finalScript = \trim($finalScript);

        return empty($finalScript)
            ? null
            : new Script(
                $finalScript,
                $params
            );
    }

    /**
     * Parse expression with internal format.
     *
     * @param string $expression
     *
     * @return string
     */
    private function parseExpressionToInternal(string $expression): string
    {
        return \preg_replace(
            '~((?:(?:indexed|searchable|exact_matching)_)?metadata.(?:[\w\d\.\-]+))~',
            'ctx._source.$1',
            $expression
        );
    }
}
