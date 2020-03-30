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

namespace Apisearch\Server\Tests\Unit;

use Exception;
use function Clue\React\Block\await;
use function Clue\React\Block\awaitAll;
use PHPUnit\Framework\TestCase;
use React\EventLoop\Factory;
use React\Promise\PromiseInterface;

/**
 * Class BaseUnitTest.
 */
abstract class BaseUnitTest extends TestCase
{
    /**
     * Await.
     *
     * @param PromiseInterface $promise
     *
     * @return mixed
     *
     * @throws Exception
     */
    protected function await(PromiseInterface $promise)
    {
        $loop = Factory::create();

        return await($promise, $loop);
    }

    /**
     * Await all.
     *
     * @param PromiseInterface[] $promises
     *
     * @return mixed
     *
     * @throws Exception
     */
    protected function awaitAll(array $promises)
    {
        $loop = Factory::create();

        return awaitAll($promises, $loop);
    }
}
