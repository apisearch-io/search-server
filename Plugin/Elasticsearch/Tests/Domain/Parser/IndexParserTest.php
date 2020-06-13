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

namespace Apisearch\Plugin\Elasticsearch\Tests\Domain\Parser;

use Apisearch\Plugin\Elasticsearch\Domain\Parser\IndexParser;
use Apisearch\Server\Tests\Unit\BaseUnitTest;

/**
 * Class IndexParserTest
 */
class IndexParserTest extends BaseUnitTest
{
    /**
     * Test parser
     */
    public function testParser()
    {
        $this->assertEquals(
            [
                'app_uuid' => '26178621test',
                'index_uuid' => 'default'
            ],
            IndexParser::parseIndexName('apisearch_478464003358_item_26178621test_default')
        );
    }
}