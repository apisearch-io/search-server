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

use Apisearch\Server\Tests\Functional\CurlFunctionalTest;

/**
 * Class HttpParametersTest.
 */
class HttpParametersTest extends CurlFunctionalTest
{
    /**
     * Test mandatory app_id parameter.
     */
    public function testMandatoryAppId()
    {
        $this->expectNotToPerformAssertions();
        static::makeCurl(
            'v1_query_all_indices',
            [
                'app_id' => '1234',
            ]
        );
    }
}
