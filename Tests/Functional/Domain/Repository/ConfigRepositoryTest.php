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

use Apisearch\Config\Config;
use Apisearch\Exception\ResourceNotAvailableException;
use Apisearch\Server\Tests\Functional\ServiceFunctionalTest;

/**
 * Class IndexMetadataTest.
 */
abstract class ConfigRepositoryTest extends ServiceFunctionalTest
{
    /**
     * Test initial configuration repository state
     */
    public function testInitialConfigRepositoryState()
    {
        $configs = $this->findAllConfigs();
        $this->assertCount(3, $configs);
    }

    /**
     * Test index metadata
     */
    public function testIndexMetadata()
    {
        $this->configureIndex(Config::createEmpty()
            ->addMetadataValue('key1', 'value1')
            ->addMetadataValue('key2', 'value2')
        );

        $index = $this->getIndices()[0];
        $this->assertEquals('value1', $index->getMetadataValue('key1'));
        $this->assertEquals('value2', $index->getMetadataValue('key2'));

        $configs = $this->findAllConfigs();
        $this->assertCount(3, $configs);
    }

    /**
     * Test new index
     */
    public function testNewIndex()
    {
        $configurableIndexId = 'configurable-index';
        try {
            $this->deleteIndex(static::$appId, $configurableIndexId);
        } catch (ResourceNotAvailableException $_) {
            //
        }

        $configs = $this->findAllConfigs();
        $this->assertCount(3, $configs);

        $this->createIndex(
            static::$appId,
            $configurableIndexId,
            static::getGodToken(),
            Config::createEmpty()
                ->addMetadataValue('key3', 'value3')
                ->addMetadataValue('key4', 'value4')
        );

        $configs = $this->findAllConfigs();
        $this->assertCount(4, $configs);

        $index = $this->getIndices()[2];
        $this->assertEquals('value3', $index->getMetadataValue('key3'));
        $this->assertEquals('value4', $index->getMetadataValue('key4'));

        $this->deleteIndex(static::$appId, $configurableIndexId);

        $configs = $this->findAllConfigs();
        $this->assertCount(3, $configs);
    }

    /**
     * Find all configs
     *
     * @return array
     */
    private function findAllConfigs() : array {
        return static::await($this->get('apisearch_server.config_repository_test')->findAllConfigs());
    }
}
