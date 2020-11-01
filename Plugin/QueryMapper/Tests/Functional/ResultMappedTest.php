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

/**
 * Class ResultMappedTest.
 */
class ResultMappedTest extends QueryMapperFunctionalTest
{
    /**
     * Basic usage.
     */
    public function testBasicUsage()
    {
        $result = $this->request('v1_query_all_indices', [
            'app_id' => self::$appId,
        ], $this->createTokenByIdAndAppId(self::$readonlyToken));
        $this->assertEquals([
            'item_nb' => 5,
            'item_ids' => [
                '1~product',
                '2~product',
                '3~book',
                '4~bike',
                '5~gum',
            ],
        ], $result['body']);
    }
}
