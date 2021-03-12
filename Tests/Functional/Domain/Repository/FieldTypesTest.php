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
 * Trait FieldTypesTest.
 */
trait FieldTypesTest
{
    /**
     * Test field types.
     *
     * @return void
     */
    public function testFieldTypes(): void
    {
        $index = $this->getPrincipalIndex();
        $fields = $index->getFields();

        $this->assertEquals('long', $fields['indexed_metadata.field_integer']);
        $this->assertEquals('keyword', $fields['indexed_metadata.field_keyword']);
        $this->assertEquals('keyword', $fields['indexed_metadata.field_text']);
        $this->assertEquals('long', $fields['metadata.array_of_arrays.id']);
        $this->assertEquals('text', $fields['metadata.array_of_arrays.name']);
        $this->assertEquals('boolean', $fields['indexed_metadata.field_boolean_false']);
        $this->assertEquals('object', $fields['indexed_metadata.author']);
        $this->assertEquals('object', $fields['indexed_metadata.category']);
        $this->assertEquals('geo_point', $fields['coordinate']);
        $this->assertEquals('completion', $fields['suggest']);
        $this->assertEquals('keyword', $fields['uuid.id']);
        $this->assertEquals('keyword', $fields['uuid.type']);
        $this->assertEquals('text', $fields['exact_matching_metadata']);
    }
}
