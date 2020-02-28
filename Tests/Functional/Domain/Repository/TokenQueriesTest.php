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

use Apisearch\Model\AppUUID;
use Apisearch\Model\Token;
use Apisearch\Model\TokenUUID;
use Apisearch\Query\Filter;
use Apisearch\Query\Query;

/**
 * Class TokenQueriesTest.
 */
trait TokenQueriesTest
{
    /**
     * Test token queries.
     *
     * @group lele
     */
    public function testTokenQueries()
    {
        /*
         * Base query
         */
        $token = new Token(
            TokenUUID::createById('12345'),
            AppUUID::createById(static::$appId),
            [], [], [],
            60,
            [
                'base_query' => ['q' => 'codigo'],
            ]
        );

        $this->addToken($token);
        $this->assertCount(1, $this->query(
            Query::createMatchAll(),
            static::$appId,
            static::$index,
            $token
        )->getItems());

        /*
         * Merge query
         */
        $token = new Token(
            TokenUUID::createById('12345'),
            AppUUID::createById(static::$appId),
            [], [], [],
            60,
            [
                'merge_query' => Query::createMatchAll()
                    ->filterBy('color', 'color', ['yellow'], Filter::AT_LEAST_ONE)
                    ->toArray(),
            ]
        );

        $this->addToken($token);
        $this->assertCount(2, $this->query(
            Query::createMatchAll(),
            static::$appId,
            static::$index,
            $token
        )->getItems());

        /*
         * Force query
         */
        $token = new Token(
            TokenUUID::createById('12345'),
            AppUUID::createById(static::$appId),
            [], [], [],
            60,
            [
                'force_query' => Query::createMatchAll()
                    ->filterBy('color', 'color', ['yellow'], Filter::AT_LEAST_ONE)
                    ->toArray(),
            ]
        );

        $this->addToken($token);
        $this->assertCount(2, $this->query(
            Query::createMatchAll()->filterBy('color', 'color', ['green'], Filter::AT_LEAST_ONE),
            static::$appId,
            static::$index,
            $token
        )->getItems());

        /*
         * Test with parameters
         */
        $token = new Token(
            TokenUUID::createById('12345'),
            AppUUID::createById(static::$appId),
            [], [], [],
            60,
            [
                'force_query' => [
                    'q' => '{{q}}',
                    'page' => '{{page}}',
                ],
            ]
        );

        $this->addToken($token);
        $this->assertCount(1, $this->query(
            Query::createMatchAll(),
            static::$appId,
            static::$index,
            $token,
            [
                'q' => 'codigo',
                'page' => '1',
            ]
        )->getItems());
    }
}
