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
        $indices = $this->getIndices();
        $this->assertCount(1, $indices);
        $givenFields = $indices[0]->getFields();
        $expectedFields = [
            'uuid.id',
            'uuid.type',
            'metadata.array_of_arrays.id',
            'metadata.array_of_arrays.name',
            'metadata.field1',
            'indexed_metadata.brand.id',
            'indexed_metadata.brand.rank',
            'indexed_metadata.price',
            'searchable_metadata.editorial',
            'searchable_metadata.title',
            'suggest',
            'coordinate',
            'exact_matching_metadata',
        ];

        foreach ($expectedFields as $field) {
            $this->assertTrue(\array_key_exists($field, $givenFields));
        }
    }
}
