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

namespace Apisearch\Plugin\ELK\Tests\Functional;

use Apisearch\Exception\ResourceNotAvailableException;
use Apisearch\Query\Query;

/**
 * Class BasicUsageTest.
 */
class BasicUsageTest extends ELKFunctionalTest
{
    /**
     * Basic usage.
     */
    public function testBasicUsage()
    {
        $redis = new \Redis();
        $redis->connect('apisearch.redis');
        $redis->del('apisearch_test.elk');

        $this->query(Query::createMatchAll());
        $this->assertEquals(
            1,
            $redis->lLen('apisearch_test.elk')
        );

        $redis->del('apisearch_test.elk');
        $this->deleteIndex();
        $this->createIndex();
        $this->indexTestingItems();
        $this->query(Query::createMatchAll());
        $this->assertEquals(
            2,
            $redis->lLen('apisearch_test.elk')
        );
        $body = json_decode($redis->lPop('apisearch_test.elk'), true);
        $message = json_decode($body['@message'], true);

        $this->assertEquals(200, $body['@fields']['level']);
        $this->assertEquals('dev', $message['environment']);
        $this->assertEquals('apisearch', $message['service']);
        $this->assertEquals('26178621test_default', $message['repository_reference']);
        $this->assertEquals('ItemsWereIndexed', $message['type']);
        $body = json_decode($redis->lPop('apisearch_test.elk'), true);
        $message = json_decode($body['@message'], true);
        $this->assertEquals(200, $body['@fields']['level']);
        $this->assertEquals('QueryWasMade', $message['type']);

        try {
            $this->deleteIndex('non-existing', 'non-existing');
        } catch (ResourceNotAvailableException $e) {
            // Ignoring exception
        }

        $body = json_decode($redis->lPop('apisearch_test.elk'), true);
        $this->assertEquals(400, $body['@fields']['level']);
    }

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
        $configuration['apisearch_plugin_elk'] = [
            'host' => 'apisearch.redis',
            'port' => 6380,
            'key' => 'apisearch_test.elk',
        ];

        return $configuration;
    }
}
