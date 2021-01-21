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
 * Class ExactMatchingMetadataTest.
 */
trait ExactMatchingMetadataTest
{
    /**
     * Test metadata.
     *
     * @return void
     */
    public function testSpecialWords(): void
    {
        $item = $this->query(Query::create('Vinci'))->getItems()[0];
        $this->assertSame(
            '5',
            $item->getUUID()->getId()
        );

        $item = $this->query(Query::create('vinci'))->getItems()[0];
        $this->assertSame(
            '5',
            $item->getUUID()->getId()
        );

        $item = $this->query(Query::create('vinc'))->getItems()[0];
        $this->assertSame(
            '3',
            $item->getUUID()->getId()
        );

        $item = $this->query(Query::create('engonga'))->getItems()[0];
        $this->assertSame(
            '3',
            $item->getUUID()->getId()
        );
    }
}
