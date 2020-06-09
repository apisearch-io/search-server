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

namespace Apisearch\Server\Tests\Functional\Http;

use Apisearch\Model\User;
use Apisearch\Query\Query;
use Apisearch\Server\Domain\ImperativeEvent\FlushSearches;
use Apisearch\Server\Domain\Model\Origin;
use Apisearch\Server\Domain\Repository\SearchesRepository\InMemorySearchesRepository;
use Apisearch\Server\Domain\Repository\SearchesRepository\SearchesRepository;
use Apisearch\Server\Tests\Functional\CurlFunctionalTest;

/**
 * Class MetricsTest.
 */
class MetricsTest extends CurlFunctionalTest
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
        $configuration['services'][SearchesRepository::class] = [
            'alias' => InMemorySearchesRepository::class,
        ];

        return $configuration;
    }

    /**
     * Test load data.
     */
    public function testLoadData()
    {
        $this->expectNotToPerformAssertions();

        $this->query(Query::create('Alfaguarra')->byUser(new User('u1')), null, null, null, [], new Origin('', '', Origin::PHONE));
        $this->query(Query::create('Alfaguarra')->byUser(new User('u1')), null, null, null, [], new Origin('', '', Origin::DESKTOP));
        $this->query(Query::create('Stylestep')->byUser(new User('u1')), null, null, null, [], new Origin('', '', Origin::DESKTOP));
        $this->query(Query::create('Alfaguarra'), null, null, null, [], new Origin('', '', Origin::TABLET));
        $this->query(Query::create('Da Vinci Code')->byUser(new User('u2')), null, null, null, [], new Origin('', '', Origin::PHONE));

        $this->click('u1', '3~it', new Origin('d.com', '0.0.0.0', Origin::PHONE));
        $this->click('u1', '1~it', new Origin('d.com', '0.0.0.0', origin::PHONE));
        $this->click('u3', '1~it', new Origin('d.com', '0.0.0.0', origin::PHONE));
        $this->click('u3', '1~it', new Origin('d.com', '0.0.0.1', origin::PHONE));
        $this->click('u1', '4~it', new Origin('a.com', '0.0.0.1', origin::DESKTOP));
        $this->click('u2', '2~it', new Origin('b.com', '0.0.0.1', origin::DESKTOP));
        $this->click('u1', '1~it', new Origin('d.com', '0.0.0.0', origin::PHONE));
        $this->click('u1', '1~it', new Origin('d.com', '0.0.0.0', origin::PHONE));
        $this->click('u3', '1~it', new Origin('d.com', '0.0.0.0', origin::PHONE));
        $this->click('u3', '3~it', new Origin('d.com', '0.0.0.1', origin::DESKTOP));
        $this->click('u1', '4~it', new Origin('a.com', '0.0.0.1', origin::TABLET));
        $this->click('u1', '3~it', new Origin('a.com', '0.0.0.1', origin::TABLET));

        self::usleep(100000);
        $this->dispatchImperative(new FlushSearches());
        self::usleep(100000);
    }

    /**
     * test basics.
     */
    public function testBasics()
    {
        $metrics = $this->getMetrics();
        $interactions = $metrics['interactions'];
        $timeKey = \key($interactions);

        $this->assertEquals([
            'interactions' => [
                $timeKey => 12,
            ],
            'top_clicks' => [
                '1~it' => 6,
                '3~it' => 3,
                '4~it' => 2,
                '2~it' => 1,
            ],
            'searches_with_results' => [
                $timeKey => 4,
            ],
            'searches_without_results' => [
                $timeKey => 1,
            ],
            'top_searches_with_results' => [
                'Alfaguarra' => 3,
                'Stylestep' => 1,
            ],
            'top_searches_without_results' => [
                'Da Vinci Code' => 1,
            ],
        ], $metrics);

        $metrics = $this->getMetrics(1);
        $interactions = $metrics['interactions'];
        $timeKey = \key($interactions);
        $this->assertEquals([
            'interactions' => [
                $timeKey => 12,
            ],
            'top_clicks' => [
                '1~it' => 6,
            ],
            'searches_with_results' => [
                $timeKey => 4,
            ],
            'searches_without_results' => [
                $timeKey => 1,
            ],
            'top_searches_with_results' => [
                'Alfaguarra' => 3,
            ],
            'top_searches_without_results' => [
                'Da Vinci Code' => 1,
            ],
        ], $metrics);

        $metrics = $this->getMetrics(null, null, null, 'u1');
        $interactions = $metrics['interactions'];
        $timeKey = \key($interactions);
        $this->assertEquals([
            'interactions' => [
                $timeKey => 7,
            ],
            'top_clicks' => [
                '1~it' => 3,
                '3~it' => 2,
                '4~it' => 2,
            ],
            'searches_with_results' => [
                $timeKey => 3,
            ],
            'searches_without_results' => [],
            'top_searches_with_results' => [
                'Alfaguarra' => 2,
                'Stylestep' => 1,
            ],
            'top_searches_without_results' => [],
        ], $metrics);

        $metrics = $this->getMetrics(null, null, null, null, Origin::MOBILE);
        $interactions = $metrics['interactions'];
        $timeKey = \key($interactions);
        $this->assertEquals([
            'interactions' => [
                $timeKey => 9,
            ],
            'top_clicks' => [
                '1~it' => 6,
                '3~it' => 2,
                '4~it' => 1,
            ],
            'searches_with_results' => [
                $timeKey => 2,
            ],
            'searches_without_results' => [
                $timeKey => 1,
            ],
            'top_searches_with_results' => [
                'Alfaguarra' => 2,
            ],
            'top_searches_without_results' => [
                'Da Vinci Code' => 1,
            ],
        ], $metrics);
    }
}
