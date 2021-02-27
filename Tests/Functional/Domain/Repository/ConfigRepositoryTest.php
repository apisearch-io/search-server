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
use Apisearch\Model\AppUUID;
use Apisearch\Model\Index;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Query\GetIndices;
use Apisearch\Server\Tests\Functional\ServiceFunctionalTest;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class IndexMetadataTest.
 */
abstract class ConfigRepositoryTest extends ServiceFunctionalTest
{
    /**
     * Is distributed config respository.
     *
     * @return bool
     */
    abstract public function isDistributedConfigRepository(): bool;

    /**
     * Test initial configuration repository state.
     *
     * @return void
     */
    public function testInitialConfigRepositoryState(): void
    {
        $configs = $this->findAllConfigs();
        $this->assertCount(3, $configs);
    }

    /**
     * Test index metadata.
     *
     * @return void
     */
    public function testIndexMetadata(): void
    {
        $this->configureIndex(Config::createEmpty()
            ->addMetadataValue('key1', 'value1')
            ->addMetadataValue('key2', 'value2')
        );

        $index = $this->getPrincipalIndex();
        $this->assertEquals('value1', $index->getMetadataValue('key1'));
        $this->assertEquals('value2', $index->getMetadataValue('key2'));

        $configs = $this->findAllConfigs();
        $this->assertCount(3, $configs);
    }

    /**
     * Test new index.
     *
     * @return void
     */
    public function testNewIndex(): void
    {
        $configurableIndexId = 'configurable-index';
        try {
            $this->deleteIndex(static::$appId, $configurableIndexId);
        } catch (ResourceNotAvailableException $_) {
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
     * Test token persistence on new service creation.
     *
     * @return void
     */
    public function testNewServiceConfig()
    {
        if (!$this->isDistributedConfigRepository()) {
            $this->markTestSkipped('Skipped. Testing a non-distributed adapter');

            return;
        }

        $configurableIndexId = 'configurable-index';
        try {
            $this->deleteIndex(static::$appId, $configurableIndexId);
        } catch (ResourceNotAvailableException $_) {
        }

        $this->createIndex(
            static::$appId,
            $configurableIndexId,
            static::getGodToken(),
            Config::createEmpty()
                ->addMetadataValue('key3', 'value3')
                ->addMetadataValue('key4', 'value4')
        );

        $clusterKernel = static::createNewKernel();
        $index = $this->getIndexFromKernel($clusterKernel, static::$appId, $configurableIndexId);
        $this->assertEquals('value3', $index->getMetadataValue('key3'));
        $this->assertEquals('value4', $index->getMetadataValue('key4'));

        /**
         * Existing service.
         */
        $output = static::runCommand([
            'apisearch-server:print-indices',
            'app-id' => static::$appId,
            '--with-metadata' => true,
        ]);

        $this->assertStringContainsString('configurable-index', $output);
        $this->assertStringContainsString('value3', $output);
        $this->assertStringContainsString('value4', $output);

        /**
         * New service.
         */
        $process = static::runAsyncCommand([
            'apisearch-server:print-indices',
            static::$appId,
            '--with-metadata',
        ]);
        \sleep(2);
        $output = $process->getOutput();
        $this->assertStringContainsString('configurable-index', $output);
        $this->assertStringContainsString('value3', $output);
        $this->assertStringContainsString('value4', $output);
    }

    /**
     * Find all configs.
     *
     * @return array
     */
    private function findAllConfigs(): array
    {
        return static::await($this->get('apisearch_server.config_repository_test')->findAllConfigs());
    }

    /**
     * Get Index loaded.
     *
     * @param KernelInterface $kernel
     * @param string          $appId
     * @param string          $indexId
     *
     * @return Index|null
     */
    private function getIndexFromKernel(
        KernelInterface $kernel,
        string $appId,
        string $indexId
    ): ? Index {
        $appUUID = AppUUID::createById($appId);
        $container = $kernel->getContainer();

        $indices = static::await($container
            ->get('drift.query_bus.test')
            ->ask(new GetIndices(
                RepositoryReference::create($appUUID),
                $this->getGodToken($appId)
            )), $container->get('reactphp.event_loop'));

        foreach ($indices as $index) {
            if ($index->getUUID()->composeUUID() === $indexId) {
                return $index;
            }
        }

        return null;
    }
}
