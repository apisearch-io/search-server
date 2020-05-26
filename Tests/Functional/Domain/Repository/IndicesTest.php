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

namespace Apisearch\Server\Tests\Functional\Domain\Repository;

use Apisearch\Model\Index;

/**
 * Class IndicesTest.
 */
trait IndicesTest
{
    /**
     * Test indices fields.
     */
    public function testIndicesFields()
    {
        $indices = $this->getIndices(self::$appId);
        $this->assertCount(2, $indices);
        $index = $this->getPrincipalIndex();

        $givenFields = $index->getFields();
        $expectedFields = [
            'uuid.id',
            'uuid.type',
            'metadata.array_of_arrays.id',
            'metadata.array_of_arrays.name',
            'metadata.field1',
            'indexed_metadata.brand',
            'indexed_metadata.category',
            'indexed_metadata.author',
            'indexed_metadata.editorial',
            'indexed_metadata.price',
            'searchable_metadata.editorial',
            'searchable_metadata.title',
            'suggest',
            'coordinate',
            'exact_matching_metadata',
        ];

        $this->assertCount(
            \count($expectedFields),
            \array_intersect(
                $expectedFields,
                \array_keys($givenFields)
            )
        );
    }
}
