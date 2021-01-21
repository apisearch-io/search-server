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

namespace Apisearch\Server\Domain\Model;

use function React\Promise\all;
use React\Promise\PromiseInterface;

/**
 * Class HealthCheckData.
 */
final class HealthCheckData
{
    private array $promises = [];
    private bool $healthy = true;
    private array $data;

    /**
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * @param PromiseInterface $promise
     */
    public function addPromise(PromiseInterface $promise): void
    {
        $this->promises[] = $promise;
    }

    /**
     * @param bool $partialHealthy
     *
     * @return void
     */
    public function setPartialHealth(bool $partialHealthy): void
    {
        $this->healthy = $this->healthy && $partialHealthy;
    }

    /**
     * @param array $data
     *
     * @return void
     */
    public function mergeData(array $data): void
    {
        $this->data = \array_merge_recursive($data, $this->data);
    }

    /**
     * @return PromiseInterface<array>
     */
    public function getData(): PromiseInterface
    {
        return all($this->promises)
            ->then(function () {
                $data = $this->data;
                $data['healthy'] = $this->healthy;

                return $data;
            });
    }
}
