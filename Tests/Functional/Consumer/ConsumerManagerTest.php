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

namespace Apisearch\Server\Tests\Functional\Consumer;

use Apisearch\Model\Item;
use Apisearch\Model\ItemUUID;
use Apisearch\Server\Domain\Consumer\ConsumerManager;
use Apisearch\Server\Tests\Functional\AsynchronousFunctionalTest;
use Clue\React\Block;

/**
 * Class ConsumerManagerTest.
 */
abstract class ConsumerManagerTest extends AsynchronousFunctionalTest
{
    /**
     * Test pause and resume consumers.
     */
    public function testPauseAndResumeConsumers()
    {
        $consumerManager = $this->get('apisearch_server.consumer_manager');
        $loop = $this->get('reactphp.event_loop');

        $commandsInQueue = Block\await($consumerManager->getQueueSize(ConsumerManager::COMMAND_CONSUMER_TYPE), $loop);
        $eventsInQueue = Block\await($consumerManager->getQueueSize(ConsumerManager::DOMAIN_EVENT_CONSUMER_TYPE), $loop);
        $this->assertEquals(0, $commandsInQueue);
        $this->assertEquals(0, $eventsInQueue);

        $this->pauseConsumers([ConsumerManager::COMMAND_CONSUMER_TYPE]);
        $this->pauseConsumers([ConsumerManager::DOMAIN_EVENT_CONSUMER_TYPE]);

        sleep(1);

        self::indexItems([
            Item::create(ItemUUID::createByComposedUUID('1~lala')),
            Item::create(ItemUUID::createByComposedUUID('2~lala')),
            Item::create(ItemUUID::createByComposedUUID('3~lala')),
        ]);

        self::indexItems([Item::create(ItemUUID::createByComposedUUID('1~lala'))]);
        self::indexItems([Item::create(ItemUUID::createByComposedUUID('1~lala'))]);

        sleep(1);

        $commandsInQueue = Block\await($consumerManager->getQueueSize(ConsumerManager::COMMAND_CONSUMER_TYPE), $loop);
        $eventsInQueue = Block\await($consumerManager->getQueueSize(ConsumerManager::DOMAIN_EVENT_CONSUMER_TYPE), $loop);
        $this->assertTrue(in_array($commandsInQueue, [2, 3]));
        $this->assertEquals(0, $eventsInQueue);

        $this->resumeConsumers([ConsumerManager::COMMAND_CONSUMER_TYPE]);

        sleep(2);

        $commandsInQueue = Block\await($consumerManager->getQueueSize(ConsumerManager::COMMAND_CONSUMER_TYPE), $loop);
        $eventsInQueue = Block\await($consumerManager->getQueueSize(ConsumerManager::DOMAIN_EVENT_CONSUMER_TYPE), $loop);
        $this->assertEquals(0, $commandsInQueue);
        $this->assertTrue(in_array($eventsInQueue, [2, 3]));

        $this->resumeConsumers([
            ConsumerManager::DOMAIN_EVENT_CONSUMER_TYPE,
        ]);

        sleep(2);

        $commandsInQueue = Block\await($consumerManager->getQueueSize(ConsumerManager::COMMAND_CONSUMER_TYPE), $loop);
        $eventsInQueue = Block\await($consumerManager->getQueueSize(ConsumerManager::DOMAIN_EVENT_CONSUMER_TYPE), $loop);
        $this->assertEquals(0, $commandsInQueue);
        $this->assertEquals(0, $eventsInQueue);
    }
}
