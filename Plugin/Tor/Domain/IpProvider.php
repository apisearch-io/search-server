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

namespace Apisearch\Plugin\Tor\Domain;

use React\Promise\PromiseInterface;

/**
 * Interface IpProvider.
 */
interface IpProvider
{
    /**
     * @param string $source
     *
     * @return PromiseInterface
     */
    public function get(string $source): PromiseInterface;
}
