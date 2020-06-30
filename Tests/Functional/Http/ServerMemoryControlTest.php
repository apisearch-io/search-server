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

use Apisearch\Model\Item;
use Apisearch\Model\ItemUUID;
use Apisearch\Query\Query;
use Apisearch\Server\Tests\Functional\CurlFunctionalTest;
use React\ChildProcess\Process;

/**
 * Class ServerMemoryControlTest.
 */
class ServerMemoryControlTest extends CurlFunctionalTest
{
    /**
     * Test insane massive usage.
     */
    public function testMassiveUsage()
    {
        $this->loadMassiveIndexItems(10000);
        $actions = [
            fn () => $this->exportIndex('source'),
            fn () => $this->exportIndex('standard'),
            fn () => $this->checkIndex(),
            fn () => $this->checkHealth(),
            fn () => $this->checkHealth(),
            fn () => $this->deleteItems([
                ItemUUID::createFromArray(['type' => 'type1', 'id' => \rand(10, 1000)]),
                ItemUUID::createFromArray(['type' => 'type1', 'id' => \rand(10, 1000)]),
                ItemUUID::createFromArray(['type' => 'type1', 'id' => \rand(10, 1000)]),
                ItemUUID::createFromArray(['type' => 'type1', 'id' => \rand(10, 1000)]),
                ItemUUID::createFromArray(['type' => 'type1', 'id' => \rand(10, 1000)]),
            ]),
            fn () => $this->query(Query::create('', \rand(1, 100), \rand(1, 100)), self::$anotherInexistentAppId),
            function () {
                $result = $this->query(Query::create('', \rand(1, 100), \rand(1, 100)));
                $this->assertEquals(90005, $result->getTotalItems());
            },
        ];

        $this->assertActionMemoryNTimes(function () use ($actions) {
            try {
                $actions[\rand(0, \count($actions) - 1)]();
            } catch (\Exception $e) {
                // Pass through
            }
        }, 1000, 800);
    }

    /**
     * Test given callable $calls times, and asserts if a minimum of
     * $acceptableWithoutIncrementing times the action consumed equals or less
     * memory than the previous one.
     *
     * @param callable $callable
     * @param int      $calls
     * @param int      $acceptableWithoutIncrementing
     */
    private function assertActionMemoryNTimes(
        callable $callable,
        int $calls,
        int $acceptableWithoutIncrementing
    ) {
        $lastMemory = null;
        $iterationsOk = 0;

        for ($i = 0; $i <= $calls; ++$i) {
            $callable();
            $health = $this->checkHealth();
            $partialMemory = $health['process']['memory_used'];

            if (\is_null($lastMemory)) {
                $lastMemory = $partialMemory;
                continue;
            }

            if ($partialMemory <= $lastMemory) {
                ++$iterationsOk;
            }

            if ($iterationsOk >= $acceptableWithoutIncrementing) {
                $this->assertTrue(true);

                return;
            }

            $lastMemory = $partialMemory;
        }

        $this->fail(\sprintf(
            'Expected %d calls with equal memory usage than the previous. Only %d seen',
            $acceptableWithoutIncrementing,
            $iterationsOk
        ));
    }

    /**
     * Load massive index items.
     *
     * @param int $n
     */
    private function loadMassiveIndexItems(int $n)
    {
        $ri = $rj = \intval(\sqrt($n));

        for ($i = 0; $i < $ri; ++$i) {
            $items = [];
            for ($j = 0; $j < $rj; ++$j) {
                $id = $i.'a'.$j;
                $items[] = Item::createFromArray([
                    'uuid' => [
                        'id' => $id,
                        'type' => 'type1',
                    ],
                    'metadata' => [
                        'title' => 'value',
                        'title2' => 'value2',
                        'title3' => 'value3',
                        'title4' => 'value4',
                        'title5' => 'value5',
                        'title6' => 'value6',
                        'title7' => 'value7',
                    ],
                ]);
            }

            static::indexItems($items);
        }
    }

    /**
     * Run server.
     *
     * @param string $serverPath
     * @param string $port
     * @param array  $arguments
     */
    protected static function runInsaneServer(
        string $serverPath,
        string $port,
        array $arguments = []
    ) {
        $serverPath = \rtrim($serverPath, '/');
        $serverPath = \realpath($serverPath);
        $serverFile = "$serverPath/server";

        if (!\is_file($serverFile)) {
            throw new \Exception("Server not found in $serverPath");
        }

        $kernel = self::$kernel;
        $jsonSerializedKernel = \json_encode($kernel->toArray());
        $jsonSerializedKernelHash = '/kernel'.\rand(1, 99999999999999).'.kernel.json';
        $jsonSerializedKernelPath = $kernel->getProjectDir().$jsonSerializedKernelHash;

        \file_put_contents(
            $jsonSerializedKernelPath,
            $jsonSerializedKernel
        );

        $command = [
            'php', $serverFile,
            'run', "0.0.0.0:$port",
            '--adapter=Drift\\\\PHPUnit\\\\TestAdapter',
        ];
        $command += $arguments;

        $environmentVars = $_ENV;
        $environmentVars['KERNEL_SERIALIZED_PATH'] = $jsonSerializedKernelPath;
        $environmentVars['APP_DEBUG'] = '0';

        $commandLine = '';

        foreach ($command as $part) {
            $commandLine .= " $part";
        }

        $process = new Process($commandLine, null, $environmentVars);
        $process->start(self::getLoop());
        $process->stdout->on('data', function ($data) {
            echo $data;
            @\ob_flush();
        });

        return $process;
    }
}
