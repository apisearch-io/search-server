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

namespace Apisearch\Plugin\Tor\Tests\Functional;

use Apisearch\Exception\ForbiddenException;
use Apisearch\Plugin\Tor\Domain\ImperativeEvent\PopulateTorIps;
use Apisearch\Query\Query;
use Apisearch\Server\Domain\Model\Origin;

class BasicTest extends TorFunctionalTest
{
    public function testRegularCall()
    {
        $this->dispatchImperative(new PopulateTorIps());
        $this->assertCount(5, $this->query(Query::createMatchAll())->getItems());
    }

    public function testWithGoodIP()
    {
        $this->dispatchImperative(new PopulateTorIps());
        $this->assertCount(5, $this->query(Query::createMatchAll(), null, null, null, [], new Origin(
            'localhost',
            '0.0.0.0',
            'mobile'
        ))->getItems());
    }

    public function testWithTrashIp()
    {
        $this->dispatchImperative(new PopulateTorIps());
        $this->assertCount(5, $this->query(Query::createMatchAll(), null, null, null, [], new Origin(
            'localhost',
            'Trash',
            'mobile'
        ))->getItems());
    }

    public function testWithTorIP()
    {
        $this->dispatchImperative(new PopulateTorIps());
        $this->expectException(ForbiddenException::class);
        $this->query(Query::createMatchAll(), null, null, null, [], new Origin(
            'localhost',
            '101.100.160.122',
            'mobile'
        ));
    }

    public function testWithTorIP2()
    {
        $this->dispatchImperative(new PopulateTorIps());
        $this->expectException(ForbiddenException::class);
        $this->query(Query::createMatchAll(), null, null, null, [], new Origin(
            'localhost',
            '100.36.131.172',
            'mobile'
        ));
    }
}
