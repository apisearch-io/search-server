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

namespace Apisearch\Server\Tests\Functional\SpecialScenario;

use Apisearch\Model\Item;
use Apisearch\Server\Domain\ImperativeEvent\LoadMetadata;
use Apisearch\Server\Tests\Functional\EventCollector;
use Apisearch\Server\Tests\Functional\ServiceFunctionalTest;

/**
 * Class LoadMetadatIsOnlyDispatchedOnceTest.
 */
class LoadMetadatIsOnlyDispatchedOnceTest extends ServiceFunctionalTest
{
    /**
     * Decorate configuration.
     *
     * @param array $configuration
     *
     * @return array
     */
    protected static function decorateConfiguration(array $configuration): array
    {
        $configuration = parent::decorateConfiguration($configuration);
        $configuration['services'][EventCollector::class] = [
            'public' => true,
            'tags' => [
                ['name' => 'kernel.event_listener', 'event' => LoadMetadata::class, 'method' => 'registerEvent'],
            ],
        ];

        return $configuration;
    }

    /**
     * Test scenario.
     *
     * @return void
     */
    public function testScenario(): void
    {
        $this->indexItems([
            Item::createFromArray([
                'uuid' => [
                    'id' => 6,
                    'type' => 'lol',
                ],
            ]),
        ]);

        $this->assertEquals(1, $this->get(EventCollector::class)->count(LoadMetadata::class));

        $this->indexItems([
            Item::createFromArray([
                'uuid' => [
                    'id' => 7,
                    'type' => 'lol',
                ],
                'indexed_metadata' => [
                    'lolazo' => [[
                        'id' => '1',
                        'name' => 'lolamen',
                    ]],
                ],
            ]),
        ]);

        $this->assertEquals(2, $this->get(EventCollector::class)->count(LoadMetadata::class));

        $this->indexItems([
            Item::createFromArray([
                'uuid' => [
                    'id' => 8,
                    'type' => 'lol',
                ],
                'indexed_metadata' => [
                    'lolazo' => [[
                        'id' => '2',
                        'name' => 'lolamen',
                    ]],
                ],
            ]),
        ]);

        $this->assertEquals(2, $this->get(EventCollector::class)->count(LoadMetadata::class));

        $this->indexItems([
            Item::createFromArray([
                'uuid' => [
                    'id' => 8,
                    'type' => 'lol',
                ],
                'indexed_metadata' => [
                    'lolazo' => [[
                        'id' => '3',
                        'name' => 'lolamen',
                    ]],
                    'lolazo_2' => [[
                        'id' => '2',
                        'name' => 'lolamen',
                    ]],
                ],
            ]),
        ]);

        $this->assertEquals(3, $this->get(EventCollector::class)->count(LoadMetadata::class));
    }
}
