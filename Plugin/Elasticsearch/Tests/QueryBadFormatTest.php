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

namespace Apisearch\Plugin\Elasticsearch\Tests;

use Apisearch\Http\HttpResponsesToException;
use Apisearch\Server\Tests\Functional\HttpFunctionalTest;

/**
 * Class QueryBadFormatTest.
 */
class QueryBadFormatTest extends HttpFunctionalTest
{
    use HttpResponsesToException;

    /**
     * Test query with bad format.
     *
     * @expectedException \Apisearch\Exception\InvalidFormatException
     */
    public function testQueryBadFormat()
    {
        $context = stream_context_create([
            'http' => ['ignore_errors' => true],
        ]);

        $result = file_get_contents(sprintf('http://localhost:%d/v1/%s/indices/%s?token=%s&query=%s',
            self::HTTP_TEST_SERVICE_PORT,
            self::$appId,
            self::$index,
            self::$godToken,
            '{"n":"""}'
        ), false, $context);

        $result = [
            'code' => explode(' ', $http_response_header[0], 3)[1],
            'body' => json_decode($result, true),
        ];

        self::throwTransportableExceptionIfNeeded($result);
    }
}
