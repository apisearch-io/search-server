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

use Apisearch\Server\Domain\Repository\AppRepository\IndexRepository;
use Apisearch\Server\Domain\Repository\NoItemsInMemoryRepository;
use Apisearch\Server\Domain\Repository\Repository\ItemsRepository;
use Apisearch\Server\Domain\Repository\Repository\QueryRepository;
use Apisearch\Server\Tests\Functional\CurlFunctionalTest;

/**
 * Class ServerMemoryControlTestEmptyRepository.
 */
class ServerMemoryControlTestEmptyRepository extends CurlFunctionalTest
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
        $configuration['services'][IndexRepository::class] = [
            'alias' => NoItemsInMemoryRepository::class,
        ];

        $configuration['services'][ItemsRepository::class] = [
            'alias' => NoItemsInMemoryRepository::class,
        ];

        $configuration['services'][QueryRepository::class] = [
            'alias' => NoItemsInMemoryRepository::class,
        ];

        return $configuration;
    }

    /**
     * Test import exhaustive.
     */
    public function testImportExhaustive()
    {
        $this->resetIndex();
        $this->createImportFile(10000);
        $this->importIndexByFeed('file:///tmp/dump.5000.apisearch', true);
        $lastMemory = 0;
        $numberOfGrows = 0;
        $numberOfIterations = 0;
        $initialMemory = $memory = $this->checkHealth()['process']['memory_used'];
        $initialMemoryMargin = \intval($initialMemory * 1.1);
        $maxMemory = \intval($initialMemory * 1.5);
        $secondsStart = \time();

        while (true) {
            $memory = $this->checkHealth()['process']['memory_used'];

            if ($memory > $maxMemory) {
                $this->fail('Memory increased too much.');

                return;
            }
            ++$numberOfIterations;
            if ($memory > $lastMemory) {
                ++$numberOfGrows;
            } else {
                if ($initialMemoryMargin > $memory) {
                    $numberOfGrows = 0;
                } else {
                    --$numberOfGrows;
                }
            }

            $lastMemory = $memory;
            \ob_flush();
            $secondsEnd = \time();

            // Only 10 seconds
            if ($secondsEnd - $secondsStart > 10) {
                break;
            }

            static::usleep(100000);
        }

        $this->assertTrue(0.2 > $numberOfGrows / $numberOfIterations);

        static::resetScenario();
    }
}
