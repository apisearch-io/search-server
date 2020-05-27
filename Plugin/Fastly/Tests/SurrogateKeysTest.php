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

namespace Apisearch\Plugin\Fastly\Tests;

use Apisearch\Query\Query;

/**
 * Class SurrogateKeysTest.
 */
class SurrogateKeysTest extends FastlyPluginFunctionalTest
{
    /**
     * Test surrogate keys on query.
     */
    public function testSurrogateKeysOnQuery()
    {
        $this->query(Query::createMatchAll());
        $surrogateKey = static::$lastResponse['headers']['surrogate-key'];

        $this->assertEquals(\sprintf('%s %s %s',
            'token-'.self::$godToken,
            'app-'.self::$appId,
            'index-'.self::$index
        ), $surrogateKey);

        $this->query(Query::createMatchAll(), static::$appId, '');
        $surrogateKey = static::$lastResponse['headers']['surrogate-key'];

        $this->assertEquals(\sprintf('%s %s %s',
            'token-'.self::$godToken,
            'app-'.self::$appId,
            'all-indices-'.self::$appId
        ), $surrogateKey);

        $this->query(Query::createMatchAll(), static::$appId, '*');
        $surrogateKey = static::$lastResponse['headers']['surrogate-key'];

        $this->assertEquals(\sprintf('%s %s %s',
            'token-'.self::$godToken,
            'app-'.self::$appId,
            'all-indices-'.self::$appId
        ), $surrogateKey);

        $this->query(Query::createMatchAll(), static::$appId, static::$index.','.static::$anotherIndex);
        $surrogateKey = static::$lastResponse['headers']['surrogate-key'];

        $this->assertEquals(\sprintf('%s %s %s %s',
            'token-'.self::$godToken,
            'app-'.self::$appId,
            'index-'.self::$index,
            'index-'.self::$anotherIndex
        ), $surrogateKey);

        $this->query(Query::createMatchAll(), static::$appId, static::$index.','.static::$anotherIndex.','.static::$yetAnotherIndex);
        $surrogateKey = static::$lastResponse['headers']['surrogate-key'];

        $this->assertEquals(\sprintf('%s %s %s %s %s',
            'token-'.self::$godToken,
            'app-'.self::$appId,
            'index-'.self::$index,
            'index-'.self::$anotherIndex,
            'index-'.self::$yetAnotherIndex
        ), $surrogateKey);
    }

    /**
     * Test other endpoints.
     */
    public function testOtherEndpoints()
    {
        $this->resetIndex();
        $this->assertFalse(\array_key_exists('surrogate-key', static::$lastResponse['headers']));
    }
}
