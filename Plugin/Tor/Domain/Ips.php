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

/**
 * Class Ips.
 */
class Ips
{
    private array $ips = [];

    /**
     * @param array $ips
     */
    public function setIPS(array $ips): void
    {
        $this->ips = \array_flip($ips);
    }

    /**
     * @param string $ip
     *
     * @return bool
     */
    public function isATorIp(string $ip): bool
    {
        return \array_key_exists($ip, $this->ips);
    }
}
