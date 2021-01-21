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

namespace Apisearch\Server\Tests\Functional\Http;

use Apisearch\Query\Query;
use Apisearch\Server\Tests\Functional\CurlFunctionalTest;

/**
 * Class CompressionTest.
 */
class CompressionTest extends CurlFunctionalTest
{
    /**
     * Test gzip compression.
     *
     * @return void
     */
    public function testGzipCompression(): void
    {
        $this->query(Query::createMatchAll());
        $length1 = self::$lastResponse['length'];
        $this->query(Query::createMatchAll());
        $length2 = self::$lastResponse['length'];
        $this->assertEquals($length1, $length2);
        $result = $this->query(
            Query::createMatchAll(),
            null, null, null, [], null, [
                'Accept-Encoding: gzip',
            ]
        );
        $this->assertCount(5, $result->getItems());
        $lengthReduced = self::$lastResponse['length'];
        $this->assertTrue($length1 > $lengthReduced);
    }

    /**
     * Test deflate compression.
     *
     * @return void
     */
    public function testDeflateCompression(): void
    {
        $this->query(Query::createMatchAll());
        $length1 = self::$lastResponse['length'];
        $result = $this->query(
            Query::createMatchAll(),
            null, null, null, [], null, [
                'Accept-Encoding: deflate',
            ]
        );
        $this->assertCount(5, $result->getItems());
        $lengthReduced = self::$lastResponse['length'];
        $this->assertTrue($length1 > $lengthReduced);
    }
}
