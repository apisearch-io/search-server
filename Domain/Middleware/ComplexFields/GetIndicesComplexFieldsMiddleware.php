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

use Apisearch\Model\Index;
use Apisearch\Server\Domain\Query\GetIndices;
use Drift\CommandBus\Middleware\DiscriminableMiddleware;

/**
 * Class GetIndexComplexFieldsMiddleware.
 */
class GetIndicesComplexFieldsMiddleware extends ComplexFieldsMiddleware implements DiscriminableMiddleware
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
         * @var GetIndices
         */
        $repositoryReference = $command->getRepositoryReference();

        return $next($command)
            ->then(function (array $indices) use ($repositoryReference) {
                /**
                 * @var Index
                 */
                $newIndices = [];

                foreach ($indices as $index) {
                    $indexRepositoryReference = $repositoryReference->changeIndex($index->getUUID());
                    $complexFields = $this
                        ->metadataRepository
                        ->get($indexRepositoryReference, static::COMPLEX_FIELDS_METADATA);

                    if (empty($complexFields)) {
                        $newIndices[] = $index;
                        continue;
                    }

                    $indexAsArray = $index->toArray();

                    foreach ($complexFields as $complexField) {
                        $completeComplexField = 'indexed_metadata.'.$complexField;
                        unset($indexAsArray['fields'][$completeComplexField.'_id']);
                        unset($indexAsArray['fields'][$completeComplexField.'_data']);

                        if (\array_key_exists('metadata.'.$complexField, $indexAsArray['fields'])) {
                            $indexAsArray['fields'][$completeComplexField] = 'object';
                            unset($indexAsArray['fields']['metadata.'.$complexField]);
                        }
                    }

                    $newIndices[] = Index::createFromArray($indexAsArray);
                }

                return $newIndices;
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
            GetIndices::class,
        ];
    }
}
