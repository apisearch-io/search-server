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

use Apisearch\Server\Domain\Repository\AppRepository\TokenRepository;

/**
 * Class InMemoryTokenTest.
 */
class InMemoryTokenTest extends TokenTest
{
    /**
     * Is distributed token respository.
     */
    public function isDistributedTokenRepository(): bool
    {
        return false;
    }

    /**
     * Truncate the table.
     */
    protected function setUp()
    {
        $this->get(TokenRepository::class)->truncate();

        parent::setUp();
    }

    /**
     * Decorate configuration.
     *
     * @param array $configuration
     *
     * @return array
     */
    protected static function decorateConfiguration(array $configuration): array
    {
        $configuration = parent::decorateConfiguration($configuration);

        return $configuration;
    }
}
