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

use Apisearch\Query\Query;

/**
 * Trait ImportTest.
 */
trait ImportTest
{
    /**
     * Test import.
     */
    public function testImport()
    {
        $this->createImportFile(300);
        $this->importIndexByFeed('file:///tmp/dump.300.apisearch');
        $result = $this->query(Query::createMatchAll());
        $this->assertEquals(305, $result->getTotalHits());
        $result = $this->query(Query::createMatchAll()->filterBy('year', 'year', ['1989']));
        $this->assertEquals(300, $result->getTotalHits());
    }
}
