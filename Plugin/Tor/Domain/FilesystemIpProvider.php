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

use React\Filesystem\Filesystem;
use React\Promise\PromiseInterface;

/**
 * Class FilesystemIpProvider.
 */
class FilesystemIpProvider implements IpProvider
{
    private Filesystem $filesystem;

    /**
     * @param Filesystem $filesystem
     */
    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * @param string $source
     *
     * @return PromiseInterface
     */
    public function get(string $source): PromiseInterface
    {
        return $this
            ->filesystem
            ->getContents($source)
            ->otherwise(function (\Exception $exception) {
                return '';
            });
    }
}
