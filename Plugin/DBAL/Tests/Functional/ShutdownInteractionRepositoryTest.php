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

namespace Apisearch\Plugin\DBAL\Tests\Functional;

use Apisearch\Model\ItemUUID;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Command\PostClick;
use Apisearch\Server\Domain\Model\Origin;
use Apisearch\Server\Tests\Functional\ServiceFunctionalTest;

/**
 * Class ShutdownInteractionRepositoryTest.
 */
class ShutdownInteractionRepositoryTest extends ServiceFunctionalTest
{
    use DBALFunctionalTestTrait;

    /**
     * Test shutdown event.
     */
    public function testShutdownEvent()
    {
        $interactions = $this->getInteractions(false);
        $this->assertEquals(0, $interactions);
        $this->clickWithoutFlush('u1');
        $this->clickWithoutFlush('u2');
        $this->clickWithoutFlush('u3');
        $this->clickWithoutFlush('u4');

        $interactions = $this->getInteractions(false);
        $this->assertEquals(0, $interactions);
        $this->await(self::$kernel->shutdown());
        self::usleep(100000);
        $interactions = $this->getInteractions(false);
        $this->assertEquals(4, $interactions);
    }

    /**
     * @param string $userId
     */
    public function clickWithoutFlush(string $userId)
    {
        self::executeCommand(new PostClick(
            RepositoryReference::createFromComposed(static::$appId.'_'.static::$index),
            static::getGodToken(),
            $userId,
            ItemUUID::createByComposedUUID('1~it'),
            1,
            new Origin('d.com', '0.0.0.0', Origin::PHONE)
        ));
    }
}
