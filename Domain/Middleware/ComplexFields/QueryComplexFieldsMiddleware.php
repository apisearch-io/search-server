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

namespace Apisearch\Server\Domain\Middleware\ComplexFields;

use Apisearch\Query\Query as QueryModel;
use Apisearch\Result\Result;
use Apisearch\Server\Domain\Query\Query;
use Drift\CommandBus\Middleware\DiscriminableMiddleware;

/**
 * Class QueryComplexFieldsMiddleware.
 */
class QueryComplexFieldsMiddleware extends ComplexFieldsMiddleware implements DiscriminableMiddleware
{
    /**
     * @param object   $command
     * @param callable $next
     *
     * @return mixed
     */
    public function execute($command, callable $next)
    {
        /**
         * @var Query
         */
        $query = $command->getQuery();
        $repositoryReference = $command->getRepositoryReference();
        $complexFields = $this->metadataRepository->get($repositoryReference, static::COMPLEX_FIELDS_METADATA);

        if (empty($complexFields)) {
            return $next($command);
        }

        $this->checkComplexFieldsInFields($query, $complexFields);
        $this->checkComplexFieldsInFilters($query, $complexFields);
        $this->checkComplexFieldsInAggregations($query, $complexFields);

        return $next($command)
            ->then(function (Result $result) use ($complexFields) {
                foreach ($result->getItems() as $item) {
                    $metadata = $item->getMetadata();
                    $indexedMetadata = $item->getIndexedMetadata();

                    foreach ($complexFields as $complexField) {
                        if (\array_key_exists($complexField, $metadata)) {
                            $indexedMetadata[$complexField] = \json_decode($metadata[$complexField], true);
                            unset($metadata[$complexField]);
                        }

                        unset($indexedMetadata[$complexField.'_id']);
                        unset($indexedMetadata[$complexField.'_data']);
                    }

                    $item->setMetadata($metadata);
                    $item->setIndexedMetadata($indexedMetadata);
                }

                return $result;
            });
    }

    /**
     * Only handle.
     *
     * @return string[]
     */
    public function onlyHandle(): array
    {
        return [
            Query::class,
        ];
    }

    /**
     * @param QueryModel $query
     * @param            $complexFields
     */
    private function checkComplexFieldsInFields(QueryModel $query, $complexFields)
    {
        $fields = $query->getFields();
        $fixedFields = [];

        foreach ($fields as $field) {
            $excludes = false;

            if ('metadata.*' === $field) {
                $fixedFields[] = $field;
                $fixedFields = \array_merge(
                    $fixedFields,
                    \array_map(function (string $field) {
                        return '!metadata.'.$field;
                    }, $complexFields)
                );

                continue;
            }

            if (0 === \strpos($field, '!')) {
                $excludes = true;
                $field = \substr($field, 1, -1);
            }

            $fieldParts = \explode('.', $field, 2);
            $fieldName = 1 === \count($fieldParts)
                ? $fieldParts[0]
                : $fieldParts[1];

            $fixedFields[] = \in_array($fieldName, $complexFields)
                ? ($excludes ? '!' : '').'metadata.'.$fieldName
                : $field;
        }

        $query->setFields($fixedFields);
    }

    /**
     * @param QueryModel $query
     * @param            $complexFields
     */
    private function checkComplexFieldsInFilters(
        QueryModel $query,
        $complexFields
    ) {
        foreach ($query->getFilters() as $filterName => $filter) {
            $field = \substr($filter->getField(), 17);
            if (\in_array($field, $complexFields)) {
                $query->filterBy(
                    $filterName,
                    $field.'_id',
                    $filter->getValues(),
                    $filter->getApplicationType(),
                    false
                );
            }
        }
    }

    /**
     * @param QueryModel $query
     * @param            $complexFields
     */
    private function checkComplexFieldsInAggregations(
        QueryModel $query,
        $complexFields
    ) {
        foreach ($query->getAggregations() as $filterName => $aggregation) {
            $field = \substr($aggregation->getField(), 17);
            if (\in_array($field, $complexFields)) {
                $query->aggregateBy(
                    $filterName,
                    $field.'_data',
                    $aggregation->getApplicationType(),
                    $aggregation->getSort(),
                    $aggregation->getLimit()
                );
            }
        }
    }
}
