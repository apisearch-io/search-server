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

namespace Apisearch\Plugin\Admin\Tests;

use Apisearch\Plugin\Admin\Domain\Command\PreloadAllMetrics;
use Apisearch\Query\Query;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\ImperativeEvent\LoadMetadata;
use Apisearch\Server\Tests\Functional\ServiceFunctionalTest;
use DateTime;

/**
 * Class PreloadAllMetricsTest.
 */
class PreloadAllMetricsTest extends ServiceFunctionalTest
{
    use AdminPluginFunctionalTest;

    /**
     * @param DateTime|null $from
     * @param DateTime|null $to
     *
     * @dataProvider dataThisMonthPreloadedMetrics
     */
    public function testThisMonthPreloadedMetrics(
        ?DateTime $from,
        ?DateTime $to
    ) {
        $this->get('apisearch_server.usage_lines_repository_test')->reset();
        $this->get('apisearch_server.metadata_repository_test')->reset();

        $today = (new DateTime())->format('Ymd');
        $this->query(Query::createMatchAll());
        $this->query(Query::createMatchAll());
        $this->query(Query::createMatchAll());
        static::indexTestingItems();

        $usage = $this->getUsage(self::$appId, null, self::$index, $from, null, null, true);
        $this->assertEquals([$today => [
            'query' => 3,
            'admin' => 1,
        ]], $usage);

        self::executeCommand(new PreloadAllMetrics());
        $this->dispatchImperative(new LoadMetadata(RepositoryReference::createFromComposed(self::$appId.'_'.self::$index)));

        $usage = $this->getUsage(self::$appId, null, self::$index, $from, $to, null, true);
        $this->assertEquals([$today => [
            'query' => 3,
            'admin' => 1,
        ]], $usage);
        $this->query(Query::createMatchAll());
        static::indexTestingItems();
        $usage = $this->getUsage(self::$appId, null, self::$index, $from, $to, null, true);
        $this->assertEquals([$today => [
            'query' => 3,
            'admin' => 1,
        ]], $usage);

        self::executeCommand(new PreloadAllMetrics());

        $usage = $this->getUsage(self::$appId, null, self::$index, $from, $to, null, true);
        $this->assertEquals([$today => [
            'query' => 3,
            'admin' => 1,
        ]], $usage);

        $this->dispatchImperative(new LoadMetadata(RepositoryReference::createFromComposed(self::$appId.'_'.self::$index)));
        $usage = $this->getUsage(self::$appId, null, self::$index, $from, $to, null, true);
        $this->assertEquals([$today => [
            'query' => 4,
            'admin' => 2,
        ]], $usage);
    }

    /**
     * @return array
     */
    public function dataThisMonthPreloadedMetrics(): array
    {
        return [
            [null, null], // This month
            [new DateTime('15 days ago'), new DateTime('tomorrow')], // Last 15 days
        ];
    }
}
