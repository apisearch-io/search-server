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

namespace Apisearch\Plugin\StaticTokens\Tests\Functional;

use Apisearch\Model\AppUUID;
use Apisearch\Model\Token;
use Apisearch\Model\TokenUUID;
use Apisearch\Query\Query;

/**
 * Class FilteredQueriesTest.
 */
class FilteredQueriesTest extends StaticTokensFunctionalTest
{
    /**
     * Test simple workflow.
     *
     * @return void
     */
    public function testSimpleWorkflow(): void
    {
        $token = new Token(TokenUUID::createById('base_filtered_token'), AppUUID::createById(self::$appId));
        $this->assertCount(1, $this->query(
            Query::createMatchAll(),
            null,
            null,
            $token
        )->getItems());
    }
}
