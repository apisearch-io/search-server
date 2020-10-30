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

namespace Apisearch\Plugin\JWT\Tests\Unit\Domain;

use Apisearch\Plugin\JWT\Domain\JWTQueryFilter;
use Apisearch\Query\Query;
use PHPUnit\Framework\TestCase;

/**
 * Class JWTQueryFilterTest.
 */
class JWTQueryFilterTest extends TestCase
{
    /**
     * Test empty.
     */
    public function testEmptyConfiguration()
    {
        $jwtQueryFilter = new JWTQueryFilter([]);

        $query = Query::createMatchAll();
        $jwtQueryFilter->configureQueryByArrayAndJWTPayload($query, []);
        $this->assertEmpty($query->getUniverseFilters());

        $query = Query::create('Hola');
        $jwtQueryFilter->configureQueryByArrayAndJWTPayload($query, []);
        $this->assertEmpty($query->getUniverseFilters());

        $query = Query::create('Hola');
        $jwtQueryFilter->configureQueryByArrayAndJWTPayload($query, ['id' => 3]);
        $this->assertEmpty($query->getUniverseFilters());
    }

    /**
     * Test fields not found.
     */
    public function testFieldNotFound()
    {
        $jwtQueryFilter = new JWTQueryFilter([
            'role' => [
                'admin' => [
                    'role' => 'admin',
                ],
            ],
        ]);

        $query = Query::createMatchAll();
        $jwtQueryFilter->configureQueryByArrayAndJWTPayload($query, ['id']);
        $this->assertEmpty($query->getUniverseFilters());
    }

    /**
     * Test field found and value not found.
     */
    public function testFieldFoundFieldNotFound()
    {
        $jwtQueryFilter = new JWTQueryFilter([
            'role' => [
                'admin' => [
                    'role' => 'admin',
                ],
            ],
        ]);

        $query = Query::createMatchAll();
        $jwtQueryFilter->configureQueryByArrayAndJWTPayload($query, ['role' => 'user']);
        $this->assertEmpty($query->getUniverseFilters());
    }

    /**
     * Test field and value match.
     */
    public function testFieldAndValueMatch()
    {
        $jwtQueryFilter = new JWTQueryFilter([
            'role' => [
                'admin' => [
                    'role' => 'admin',
                ],
            ],
        ]);

        $query = Query::createMatchAll();
        $jwtQueryFilter->configureQueryByArrayAndJWTPayload($query, ['role' => 'admin']);
        $universeFilters = $query->getUniverseFilters();
        $this->assertCount(1, $universeFilters);
        $universeFilter = \reset($universeFilters);
        $this->assertEquals('indexed_metadata.role', $universeFilter->getField());
        $this->assertEquals(['admin'], $universeFilter->getValues());
    }

    /**
     * Test field and value match.
     */
    public function testFieldAndValueMatchMultipleFields()
    {
        $jwtQueryFilter = new JWTQueryFilter([
            'role' => [
                'admin' => [
                    'role' => ['admin'],
                    'another' => ['value1', 'value2'],
                ],
            ],
            'category' => [
                'cat1' => [
                    'cat' => 'cat1',
                ],
            ],
        ]);

        $query = Query::createMatchAll();
        $jwtQueryFilter->configureQueryByArrayAndJWTPayload($query, ['role' => 'admin', 'category' => 'cat1']);
        $universeFilters = $query->getUniverseFilters();
        $this->assertCount(3, $universeFilters);

        $roleFilter = $universeFilters['role'];
        $this->assertEquals('indexed_metadata.role', $roleFilter->getField());
        $this->assertEquals(['admin'], $roleFilter->getValues());

        $anotherFilter = $universeFilters['another'];
        $this->assertEquals('indexed_metadata.another', $anotherFilter->getField());
        $this->assertEquals(['value1', 'value2'], $anotherFilter->getValues());

        $catFilter = $universeFilters['cat'];
        $this->assertEquals('indexed_metadata.cat', $catFilter->getField());
        $this->assertEquals(['cat1'], $catFilter->getValues());
    }

    /**
     * Test placeholder.
     */
    public function testPlaceholder()
    {
        $jwtQueryFilter = new JWTQueryFilter([
            'role' => [
                '*' => [
                    'role' => ['$1'],
                ],
            ],
            'id' => [
                '*' => [
                    'id' => ['$1', '{{role}}', 'another'],
                ],
                '1' => [
                    'id' => '_1',
                ],
                '2' => [
                    'id' => '_222',
                ],
            ],
        ]);

        $query = Query::createMatchAll();
        $jwtQueryFilter->configureQueryByArrayAndJWTPayload($query, ['role' => 'admin', 'id' => '2']);
        $universeFilters = $query->getUniverseFilters();
        $this->assertCount(2, $universeFilters);

        $roleFilter = $universeFilters['role'];
        $this->assertEquals('indexed_metadata.role', $roleFilter->getField());
        $this->assertEquals(['admin'], $roleFilter->getValues());

        $roleFilter = $universeFilters['id'];
        $this->assertEquals('uuid.id', $roleFilter->getField());
        $this->assertEquals(['2', 'admin', 'another'], $roleFilter->getValues());
    }
}
