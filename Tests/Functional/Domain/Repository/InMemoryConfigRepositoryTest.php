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

namespace Apisearch\Server\Tests\Functional\Domain\Token;

use Apisearch\Server\Tests\Functional\Domain\Repository\ConfigRepositoryTest;

/**
 * Class InMemoryIndexMetadataTest.
 */
class InMemoryConfigRepositoryTest extends ConfigRepositoryTest
{
    /**
     * Is distributed token respository.
     */
    public function isDistributedTokenRepository(): bool
    {
        return false;
    }
}
