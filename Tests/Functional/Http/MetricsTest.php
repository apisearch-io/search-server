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
use Apisearch\Server\Tests\Functional\HttpFunctionalTest;

/**
 * Class MetricsTest.
 */
class MetricsTest extends HttpFunctionalTest
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

        $this->query(Query::create('Alfaguarra')->byUser(new User('u1')), null, null, null, [], new Origin('localhost', '0.0.0.0', Origin::PHONE));
        $this->query(Query::create('Alfaguarra')->byUser(new User('u1')), null, null, null, [], new Origin('localhost', '0.0.0.0', Origin::DESKTOP));
        $this->query(Query::create('Stylestep')->byUser(new User('u1')), null, null, null, [], new Origin('localhost', '0.0.0.0', Origin::DESKTOP));
        $this->query(Query::create('Alfaguarra'), null, null, null, [], new Origin('localhost', '0.0.0.0', Origin::TABLET));
        $this->query(Query::create('Da Vinci Code')->byUser(new User('u2')), null, null, null, [], new Origin('localhost', '0.0.0.0', Origin::PHONE));

        $this->click('u1', '3~it', 1, new Origin('d.com', '0.0.0.0', Origin::PHONE));
        $this->click('u1', '1~it', 1, new Origin('d.com', '0.0.0.0', origin::PHONE));
        $this->click('u3', '1~it', 1, new Origin('d.com', '0.0.0.0', origin::PHONE));
        $this->click('u3', '1~it', 1, new Origin('d.com', '0.0.0.1', origin::PHONE));
        $this->click('u1', '4~it', 1, new Origin('a.com', '0.0.0.1', origin::DESKTOP));
        $this->click('u2', '2~it', 1, new Origin('b.com', '0.0.0.1', origin::DESKTOP));
        $this->click('u1', '1~it', 1, new Origin('d.com', '0.0.0.0', origin::PHONE));
        $this->click('u1', '1~it', 1, new Origin('d.com', '0.0.0.0', origin::PHONE));
        $this->click('u3', '1~it', 1, new Origin('d.com', '0.0.0.0', origin::PHONE));
        $this->click('u3', '3~it', 1, new Origin('d.com', '0.0.0.1', origin::DESKTOP));
        $this->click('u1', '4~it', 1, new Origin('a.com', '0.0.0.1', origin::TABLET));
        $this->click('u1', '3~it', 1, new Origin('a.com', '0.0.0.1', origin::TABLET));

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
        $this->assertEquals(
            $this->getAllMetricsArray($metrics),
            $metrics
        );

        $metrics = $this->getMetrics(1);
        $clicks = $metrics['clicks'];
        $timeKey = \key($clicks);
        $this->assertEquals([
            'clicks' => [
                $timeKey => 12,
            ],
            'total_clicks' => 12,
            'top_clicks' => [
                '1~it' => 6,
            ],
            'unique_users_clicks' => [
                $timeKey => 3,
            ],
            'total_unique_users_clicks' => 3,
            'searches_with_results' => [
                $timeKey => 4,
            ],
            'total_searches_with_results' => 4,
            'searches_without_results' => [
                $timeKey => 1,
            ],
            'total_searches_without_results' => 1,
            'unique_users_searching' => [
                $timeKey => 3,
            ],
            'total_unique_users_searching' => 3,
            'top_searches_with_results' => [
                'Alfaguarra' => 3,
            ],
            'top_searches_without_results' => [
                'Da Vinci Code' => 1,
            ],
        ], $metrics);

        $metrics = $this->getMetrics(null, null, null, 'u1');
        $clicks = $metrics['clicks'];
        $timeKey = \key($clicks);
        $this->assertEquals([
            'clicks' => [
                $timeKey => 7,
            ],
            'total_clicks' => 7,
            'top_clicks' => [
                '1~it' => 3,
                '3~it' => 2,
                '4~it' => 2,
            ],
            'unique_users_clicks' => [
                $timeKey => 1,
            ],
            'total_unique_users_clicks' => 1,
            'searches_with_results' => [
                $timeKey => 3,
            ],
            'total_searches_with_results' => 3,
            'searches_without_results' => [],
            'total_searches_without_results' => 0,
            'unique_users_searching' => [
                $timeKey => 1,
            ],
            'total_unique_users_searching' => 1,
            'top_searches_with_results' => [
                'Alfaguarra' => 2,
                'Stylestep' => 1,
            ],
            'top_searches_without_results' => [],
        ], $metrics);

        $metrics = $this->getMetrics(null, null, null, null, Origin::MOBILE);
        $clicks = $metrics['clicks'];
        $timeKey = \key($clicks);
        $this->assertEquals([
            'clicks' => [
                $timeKey => 9,
            ],
            'total_clicks' => 9,
            'top_clicks' => [
                '1~it' => 6,
                '3~it' => 2,
                '4~it' => 1,
            ],
            'unique_users_clicks' => [
                $timeKey => 2,
            ],
            'total_unique_users_clicks' => 2,
            'searches_with_results' => [
                $timeKey => 2,
            ],
            'total_searches_with_results' => 2,
            'searches_without_results' => [
                $timeKey => 1,
            ],
            'total_searches_without_results' => 1,
            'unique_users_searching' => [
                $timeKey => 3,
            ],
            'total_unique_users_searching' => 3,
            'top_searches_with_results' => [
                'Alfaguarra' => 2,
            ],
            'top_searches_without_results' => [
                'Da Vinci Code' => 1,
            ],
        ], $metrics);

        $metrics = $this->getMetrics(null, null, null, null, 'non-existing');
        $this->assertEquals(
            $this->getEmptyMetricsArray(),
            $metrics
        );

        $metrics = $this->getMetrics(null, (new \DateTime())->modify('+1 day'));
        $this->assertEquals(
            $this->getEmptyMetricsArray(),
            $metrics
        );

        $metrics = $this->getMetrics(null, null, (new \DateTime())->modify('-1 day'));
        $this->assertEquals(
            $this->getEmptyMetricsArray(),
            $metrics
        );

        $metrics = $this->getMetrics(null, (new \DateTime())->modify('-1 day'));
        $this->assertEquals(
            $this->getAllMetricsArray($metrics),
            $metrics
        );

        $metrics = $this->getMetrics(null, null, (new \DateTime())->modify('+1 day'));
        $this->assertEquals(
            $this->getAllMetricsArray($metrics),
            $metrics
        );

        $metrics = $this->getMetrics(null, (new \DateTime())->modify('-1 day'), (new \DateTime())->modify('+1 day'));
        $this->assertEquals(
            $this->getAllMetricsArray($metrics),
            $metrics
        );
    }

    /**
     * @return array
     */
    private function getEmptyMetricsArray(): array
    {
        return [
            'clicks' => [],
            'total_clicks' => 0,
            'top_clicks' => [],
            'unique_users_clicks' => [],
            'total_unique_users_clicks' => 0,
            'searches_with_results' => [],
            'total_searches_with_results' => 0,
            'searches_without_results' => [],
            'total_searches_without_results' => 0,
            'unique_users_searching' => [],
            'total_unique_users_searching' => 0,
            'top_searches_with_results' => [],
            'top_searches_without_results' => [],
        ];
    }

    /**
     * @param array $metrics
     *
     * @return array
     */
    private function getAllMetricsArray(array $metrics): array
    {
        $clicks = $metrics['clicks'];
        $timeKey = \key($clicks);

        return [
            'clicks' => [
                $timeKey => 12,
            ],
            'total_clicks' => 12,
            'top_clicks' => [
                '1~it' => 6,
                '3~it' => 3,
                '4~it' => 2,
                '2~it' => 1,
            ],
            'unique_users_clicks' => [
                $timeKey => 3,
            ],
            'total_unique_users_clicks' => 3,
            'searches_with_results' => [
                $timeKey => 4,
            ],
            'total_searches_with_results' => 4,
            'searches_without_results' => [
                $timeKey => 1,
            ],
            'total_searches_without_results' => 1,
            'unique_users_searching' => [
                $timeKey => 3,
            ],
            'total_unique_users_searching' => 3,
            'top_searches_with_results' => [
                'Alfaguarra' => 3,
                'Stylestep' => 1,
            ],
            'top_searches_without_results' => [
                'Da Vinci Code' => 1,
            ],
        ];
    }
}
