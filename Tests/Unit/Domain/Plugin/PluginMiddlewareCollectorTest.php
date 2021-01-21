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

namespace Apisearch\Server\Tests\Unit\Domain\Plugin;

use Apisearch\Server\Domain\Plugin\PluginMiddleware;
use Apisearch\Server\Domain\Plugin\PluginMiddlewareCollector;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * Class PluginMiddlewareCollectorTest.
 */
class PluginMiddlewareCollectorTest extends TestCase
{
    /**
     * Test subscribed to all.
     *
     * @return void
     */
    public function testSubscribedToAll(): void
    {
        $pluginMiddlewareCollector = new PluginMiddlewareCollector();
        $middleware = $this->prophesize(PluginMiddleware::class);
        $middleware->onlyHandle()->willReturn([]);
        $middleware->execute(Argument::cetera())->shouldBeCalledTimes(2);
        $middlewareInstance = $middleware->reveal();
        $this->assertTrue(0 === \strpos(\get_class($middlewareInstance), 'Double'));
        $pluginMiddlewareCollector->addPluginMiddleware($middlewareInstance);
        $pluginMiddlewareCollector->execute(new FakeCommand(), function () {});
        $pluginMiddlewareCollector->execute(new AbstractFakeCommand(), function () {});
    }

    /**
     * Test subscribed  to one.
     *
     * @return void
     */
    public function testSubscribedToSpecific(): void
    {
        $pluginMiddlewareCollector = new PluginMiddlewareCollector();
        $middleware = $this->prophesize(PluginMiddleware::class);
        $middleware->onlyHandle()->willReturn([FakeCommand::class, AnotherFakeCommand::class]);
        $middleware->execute(Argument::cetera())->shouldBeCalledTimes(1);
        $pluginMiddlewareCollector->addPluginMiddleware($middleware->reveal());
        $pluginMiddlewareCollector->execute(new FakeCommand(), function () {});
        $pluginMiddlewareCollector->execute(new AbstractFakeCommand(), function () {});
    }

    /**
     * Test subscribed  to one.
     *
     * @return void
     */
    public function testNotSubscribed(): void
    {
        $pluginMiddlewareCollector = new PluginMiddlewareCollector();
        $middleware = $this->prophesize(PluginMiddleware::class);
        $middleware->onlyHandle()->willReturn([FakeCommand::class]);
        $middleware->execute(Argument::cetera())->shouldNotBeCalled();
        $pluginMiddlewareCollector->addPluginMiddleware($middleware->reveal());
        $pluginMiddlewareCollector->execute(new AbstractFakeCommand(), function () {});
    }

    /**
     * Test subscribed to abstract.
     *
     * @return void
     */
    public function testSubscribedToAbstract(): void
    {
        $pluginMiddlewareCollector = new PluginMiddlewareCollector();
        $middleware = $this->prophesize(PluginMiddleware::class);
        $middleware->onlyHandle()->willReturn([AbstractFakeCommand::class]);
        $middleware->execute(Argument::cetera())->shouldBeCalled();
        $pluginMiddlewareCollector->addPluginMiddleware($middleware->reveal());
        $pluginMiddlewareCollector->execute(new FakeCommand(), function () {});
    }

    /**
     * Test subscribed to multiple.
     *
     * @return void
     */
    public function testSubscribedToMultiple(): void
    {
        $pluginMiddlewareCollector = new PluginMiddlewareCollector();
        $middleware = $this->prophesize(PluginMiddleware::class);
        $middleware->onlyHandle()->willReturn([AnotherFakeCommand::class, FakeCommand::class]);
        $middleware->execute(Argument::cetera())->shouldBeCalledTimes(2);
        $pluginMiddlewareCollector->addPluginMiddleware($middleware->reveal());
        $pluginMiddlewareCollector->execute(new FakeCommand(), function () {});
        $pluginMiddlewareCollector->execute(new AnotherFakeCommand(), function () {});
    }

    /**
     * Test subscribed to interface.
     *
     * @return void
     */
    public function testSubscribedToInterface(): void
    {
        $pluginMiddlewareCollector = new PluginMiddlewareCollector();
        $middleware = $this->prophesize(PluginMiddleware::class);
        $middleware->onlyHandle()->willReturn([FakeInterface::class]);
        $middleware->execute(Argument::cetera())->shouldBeCalledTimes(1);
        $pluginMiddlewareCollector->addPluginMiddleware($middleware->reveal());
        $pluginMiddlewareCollector->execute(new FakeCommand(), function () {});
        $pluginMiddlewareCollector->execute(new AnotherFakeCommand(), function () {});
    }
}
