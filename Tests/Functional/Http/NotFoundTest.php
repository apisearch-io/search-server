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

use Apisearch\Exception\TransportableException;
use Apisearch\Server\Tests\Functional\CurlFunctionalTest;

/**
 * Class NotFoundTest.
 */
class NotFoundTest extends CurlFunctionalTest
{
    /**
     * Test not found on some non existing path.
     */
    public function testNotFoundResponse()
    {
        $this->expectNotToPerformAssertions();
        try {
            static::makeCurl(
                'v2', [], null
            );

            $this->fail('Route v2 should throw exception');
        } catch (TransportableException $exception) {
        }
    }
}
