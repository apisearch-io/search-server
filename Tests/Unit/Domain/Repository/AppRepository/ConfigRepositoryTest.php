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

namespace Apisearch\Server\Tests\Unit\Domain\Repository\AppRepository;

use Apisearch\Config\Config;
use Apisearch\Model\AppUUID;
use Apisearch\Model\IndexUUID;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Repository\AppRepository\ConfigRepository;
use Apisearch\Server\Tests\Unit\BaseUnitTest;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;

/**
 * Class ConfigRepositoryTest.
 */
abstract class ConfigRepositoryTest extends BaseUnitTest
{
    /**
     * @param LoopInterface $loop
     *
     * @return ConfigRepository
     */
    abstract public function buildEmptyRepository(LoopInterface $loop): ConfigRepository;

    /**
     * Test add and remove token.
     *
     * @return void
     */
    public function testAddRemoveConfig(): void
    {
        $loop = Factory::create();
        $repository = $this->buildEmptyRepository($loop);
        $appUUID = AppUUID::createById('app');
        $app2UUID = AppUUID::createById('app2');
        $app3UUID = AppUUID::createById('app3');
        $indexUUID = IndexUUID::createById('index');
        $index2UUID = IndexUUID::createById('index2');
        $index3UUID = IndexUUID::createById('index3');

        $repositoryReference = RepositoryReference::create(
            $appUUID,
            $indexUUID
        );

        $config1 = Config::createEmpty()->addMetadataValue('key1', 'value1');
        $promise1 = $repository
            ->putConfig($repositoryReference, $config1)
            ->then(function () use ($repository) {
                return $repository->forceLoadAllConfigs();
            })
            ->then(function () use ($repository, $repositoryReference) {
                $this->assertEquals(
                    'value1',
                    $repository->getConfig($repositoryReference)->getMetadata()['key1']
                );
            });

        $repositoryReference2 = RepositoryReference::create(
            $app2UUID,
            $index2UUID
        );
        $config2 = Config::createEmpty()->addMetadataValue('key2', 'value2');
        $promise2 = $repository
            ->putConfig($repositoryReference2, $config2)
            ->then(function () use ($repository) {
                return $repository->forceLoadAllConfigs();
            })
            ->then(function () use ($repository, $repositoryReference2) {
                $this->assertEquals(
                    'value2',
                    $repository->getConfig($repositoryReference2)->getMetadata()['key2']
                );
            });

        $repositoryReference3 = RepositoryReference::create(
            $appUUID,
            $index2UUID
        );

        $config3 = Config::createEmpty()->addMetadataValue('key3', 'value3');
        $promise3 = $repository
            ->putConfig($repositoryReference3, $config3)
            ->then(function () use ($repository) {
                return $repository->forceLoadAllConfigs();
            })
            ->then(function () use ($repository, $repositoryReference3) {
                $this->assertEquals(
                    'value3',
                    $repository->getConfig($repositoryReference3)->getMetadata()['key3']
                );
            });

        $this->assertNull(
            $repository->getConfig(RepositoryReference::create(
                $appUUID,
                $index3UUID
            ))
        );

        $this->assertNull(
            $repository->getConfig(RepositoryReference::create(
                $app3UUID,
                $indexUUID
            ))
        );

        $this->assertNull(
            $repository->getConfig(RepositoryReference::create(
                $app3UUID,
                $index3UUID
            ))
        );

        $this->awaitAll([
            $promise1,
            $promise2,
            $promise3,
        ], $loop);

        $this->assertCount(2, $repository->getAppConfigs($appUUID));
        $this->assertCount(1, $repository->getAppConfigs($app2UUID));
        $this->assertCount(0, $repository->getAppConfigs($app3UUID));

        $this->await($repository
            ->deleteConfig($repositoryReference)
            ->then(function () use ($repository) {
                return $repository->forceLoadAllConfigs();
            }), $loop);

        $this->assertCount(1, $repository->getAppConfigs($appUUID));
        $this->assertCount(1, $repository->getAppConfigs($app2UUID));
        $this->assertCount(0, $repository->getAppConfigs($app3UUID));

        $this->await($repository
            ->deleteConfig($repositoryReference2)
            ->then(function () use ($repository) {
                return $repository->forceLoadAllConfigs();
            }), $loop);

        $this->assertCount(1, $repository->getAppConfigs($appUUID));
        $this->assertCount(0, $repository->getAppConfigs($app2UUID));

        $this->await($repository
            ->deleteConfig($repositoryReference3)
            ->then(function () use ($repository) {
                return $repository->forceLoadAllConfigs();
            }), $loop);

        $this->assertCount(0, $repository->getAppConfigs($appUUID));
        $this->assertCount(0, $repository->getAppConfigs($app2UUID));
    }
}
