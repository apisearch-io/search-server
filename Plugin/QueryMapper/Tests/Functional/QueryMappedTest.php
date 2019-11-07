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

namespace Apisearch\Plugin\QueryMapper\Tests\Functional;

use Apisearch\Exception\TransportableException;
use Apisearch\Model\AppUUID;
use Apisearch\Model\Token;
use Apisearch\Model\TokenUUID;
use Apisearch\Query\Query;

/**
 * Class QueryMappedTest.
 */
class QueryMappedTest extends QueryMapperFunctionalTest
{
    /**
     * Basic usage.
     */
    public function testWithMappedQuery()
    {
        $result = $this->query(
            Query::create(''),
            static::$appId,
            static::$index,
            new Token(
                TokenUUID::createById('query-mapped-simple'),
                AppUUID::createById(static::$appId)
            )
        );

        $this->assertEquals(2, $result->getTotalHits());
        $this->assertEquals('2~product', $result->getItems()[0]->composeUUID());
        $this->assertEquals('4~bike', $result->getItems()[1]->composeUUID());
    }

    /**
     * Test without mapped query.
     */
    public function testWithoutMappedQuery()
    {
        try {
            $this->query(
                Query::create(''),
                static::$appId,
                static::$index,
                new Token(
                    TokenUUID::createById('non-existing'),
                    AppUUID::createById(static::$appId)
                )
            );

            $this->fail('An exception with code 401 should be thrown here');
        } catch (TransportableException $exception) {
            $this->assertEquals(401, $exception->getTransportableHTTPError());
        }
    }
}
