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

namespace Apisearch\Server\Tests\Unit\Domain\Repository\MetadataRepository;

use Apisearch\Server\Domain\Repository\MetadataRepository\DiskMetadataRepository;
use Apisearch\Server\Domain\Repository\MetadataRepository\MetadataRepository;
use React\EventLoop\LoopInterface;

/**
 * Class DiskMetadataRepositoryTest.
 */
class DiskMetadataRepositoryTest extends MetadataRepositoryTest
{
    /**
     * @param LoopInterface $loop
     *
     * @return MetadataRepository
     */
    public function buildEmptyRepository(LoopInterface $loop): MetadataRepository
    {
        $path = '/tmp/apisearch.metadata.repository';
        if (\file_exists($path)) {
            \unlink($path);
        }

        return new DiskMetadataRepository($path);
    }
}
