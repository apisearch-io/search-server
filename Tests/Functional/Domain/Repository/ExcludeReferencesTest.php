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

use Apisearch\Model\ItemUUID;
use Apisearch\Query\Query;

/**
 * Class ExcludeReferencesTest.
 */
trait ExcludeReferencesTest
{
    /**
     * Test family filter.
     *
     * @return void
     */
    public function testExcludeProducts(): void
    {
        $this->assertResults(
            $this->query(Query::createMatchAll()->excludeUUID(new ItemUUID('2', 'product'))),
            ['?1', '!2', '?3', '?4', '?5']
        );

        $this->assertResults(
            $this->query(Query::createMatchAll()->excludeUUIDs([
                new ItemUUID('2', 'product'),
                new ItemUUID('3', 'book'),
                new ItemUUID('4', 'superbike'),
                new ItemUUID('6', 'boke'),
            ])),
            ['?1', '!2', '!3', '?4', '?5']
        );

        $this->assertEmpty(
            $this->query(Query::create('engonga')->excludeUUID(new ItemUUID('3', 'book')))->getItems()
        );
    }
}
