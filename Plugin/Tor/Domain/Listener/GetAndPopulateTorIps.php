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

namespace Apisearch\Plugin\Tor\Domain\Listener;

use Apisearch\Plugin\Tor\Domain\ImperativeEvent\PopulateTorIps;
use Apisearch\Plugin\Tor\Domain\IpProvider;
use Apisearch\Plugin\Tor\Domain\Ips;
use Drift\HttpKernel\AsyncKernelEvents;
use function React\Promise\all;
use React\Promise\PromiseInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class GetAndPopulateTorIps.
 */
class GetAndPopulateTorIps implements EventSubscriberInterface
{
    private Ips $ips;
    private IpProvider $ipProvider;
    private array $sources;

    /**
     * @param Ips        $ips
     * @param IpProvider $ipProvider
     * @param array      $sources
     */
    public function __construct(Ips $ips, IpProvider $ipProvider, array $sources)
    {
        $this->ips = $ips;
        $this->ipProvider = $ipProvider;
        $this->sources = $sources;
    }

    /**
     * @return PromiseInterface
     */
    public function getAndPopulateTorIps(): PromiseInterface
    {
        return
            all(\array_map(function (string $source) {
                return $this
                    ->ipProvider
                    ->get($source)
                    ->then(function (string $content) {
                        return $this->plainToList($content);
                    });
            }, $this->sources))
            ->then(function (array $sourcesIps) {
                $ips = [];
                foreach ($sourcesIps as $sourceIps) {
                    $ips = \array_merge($ips, $sourceIps);
                }

                return \array_values(\array_unique($ips));
            })
            ->then(function (array $ips) {
                $this->ips->setIPS($ips);
            });
    }

    /**
     * @return string[]
     */
    public static function getSubscribedEvents()
    {
        return [
            PopulateTorIps::class => 'getAndPopulateTorIps',
            AsyncKernelEvents::PRELOAD => 'getAndPopulateTorIps',
        ];
    }

    /**
     * @param string $data
     *
     * @return string[]
     */
    private function plainToList(string $data): array
    {
        return \array_filter(\explode("\n", $data), function (string $possibleIp) {
            return \preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\z/', $possibleIp);
        });
    }
}
