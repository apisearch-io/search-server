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
     *
     * @return void
     */
    public function testMassiveUsage(): void
    {
        $this->loadMassiveIndexItems(10000);

        $actions = [
            fn () => $this->exportIndex('source'),
            fn () => $this->exportIndex('standard'),
            fn () => $this->checkIndex(),
            fn () => $this->checkHealth($this->createTokenByIdAndAppId(self::$healthCheckToken)),
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
        }, 200, 150);
    }

    /**
     * Test given callable $calls times, and asserts if a minimum of
     * $acceptableWithoutIncrementing times the action consumed equals or less
     * memory than the previous one.
     *
     * @param callable $callable
     * @param int      $calls
     * @param int      $acceptableWithoutIncrementing
     *
     * @return void
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
            $health = $this->checkHealth($this->createTokenByIdAndAppId(self::$healthCheckToken));
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
     * Run server.
     *
     * @param string $serverPath
     * @param string $port
     * @param array  $arguments
     *
     * @return Process
     */
    protected static function runInsaneServer(
        string $serverPath,
        string $port,
        array $arguments = []
    ): Process {
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
