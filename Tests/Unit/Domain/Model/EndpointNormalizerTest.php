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

namespace Apisearch\Server\Tests\Unit\Domain\Plugin;

use Apisearch\Server\Domain\Model\EndpointNormalizer;
use PHPUnit\Framework\TestCase;

/**
 * Class EndpointNormalizerTest.
 */
class EndpointNormalizerTest extends TestCase
{
    public function testNormalizeEndpoint()
    {
        $this->assertEquals('apisearch_v1_endpoint', EndpointNormalizer::normalizeEndpoint('apisearch_v1_endpoint'));
        $this->assertEquals('apisearch_v1_endpoint', EndpointNormalizer::normalizeEndpoint('v1_endpoint'));
        $this->assertEquals(['apisearch_v1_endpoint'], EndpointNormalizer::normalizeEndpoints(['v1_endpoint']));
        $this->assertEquals([], EndpointNormalizer::normalizeEndpoints([]));
        $this->assertEquals(['apisearch_v1_endpoint1', 'apisearch_v1_endpoint2', 'apisearch_v1_endpoint3'], EndpointNormalizer::normalizeEndpoints([
            'apisearch_v1_endpoint1',
            'v1_endpoint2',
            'apisearch_v1_endpoint3',
        ]));
    }
}
