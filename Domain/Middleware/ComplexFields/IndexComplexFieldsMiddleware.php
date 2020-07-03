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

use Apisearch\Model\Item;
use Apisearch\Model\Metadata;
use Apisearch\Server\Domain\Command\IndexItems;
use Apisearch\Server\Domain\ImperativeEvent\LoadMetadata;
use Drift\CommandBus\Middleware\DiscriminableMiddleware;

/**
 * Class IndexComplexStructuresMiddleware.
 */
class IndexComplexFieldsMiddleware extends ComplexFieldsMiddleware implements DiscriminableMiddleware
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
         * @var IndexItems
         */
        $items = $command->getItems();
        $repositoryReference = $command->getRepositoryReference();
        $complexFields = [];
        $currentComplexFields = $this
            ->metadataRepository
            ->get($repositoryReference, 'complex_fields');

        $currentComplexFields = $currentComplexFields ?? [];

        \array_walk($items, function (Item $item) use (&$complexFields) {
            $metadata = $item->getMetadata();
            $indexedMetadata = $item->getIndexedMetadata();

            foreach ($item->getIndexedMetadata() as $field => $values) {
                if (
                    !\is_array($values) ||
                    empty($values) ||
                    !(
                        \array_key_exists('id', $values) ||
                        (
                            \is_array($values[\array_key_first($values)]) &&
                            \array_key_exists('id', $values[\array_key_first($values)])
                        )
                    )
                ) {
                    continue;
                }

                // Value can be an element or an array of elements.
                // We turn everything an array of elements
                $originalValues = $values;
                if (\array_key_exists('id', $values)) {
                    $values = [$values];
                }

                $dataForAggregate = [];
                $dataForFilter = [];
                foreach ($values as $value) {
                    $dataForAggregate[] = Metadata::toMetadata($value);
                    $dataForFilter[] = $value['id'];
                }

                unset($indexedMetadata[$field]);
                $indexedMetadata["{$field}_data"] = $dataForAggregate;
                $indexedMetadata["{$field}_id"] = $dataForFilter;
                $metadata[$field] = \json_encode($originalValues);
                $complexFields[$field] = true;
            }

            $item->setMetadata($metadata);
            $item->setIndexedMetadata($indexedMetadata);
        });

        $complexFields = \array_keys($complexFields);
        $mergedComplexFields = \array_merge($currentComplexFields, $complexFields);
        $mergedComplexFields = \array_unique($mergedComplexFields);
        $mergedComplexFields = \array_values($mergedComplexFields);

        if ($currentComplexFields === $mergedComplexFields) {
            return $next($command);
        }

        return $this
            ->metadataRepository
            ->set($repositoryReference, static::COMPLEX_FIELDS_METADATA, $mergedComplexFields)
            ->then(function () use ($repositoryReference) {
                return $this
                    ->eventBus
                    ->dispatch(new LoadMetadata($repositoryReference));
            })
            ->then(function () use ($next, $command, $repositoryReference) {
                return $next($command);
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
            IndexItems::class,
        ];
    }
}
