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

namespace Apisearch\Server\Tests\Unit\Domain\Command;

use Apisearch\Model\AppUUID;
use Apisearch\Model\IndexUUID;
use Apisearch\Model\Token;
use Apisearch\Model\TokenUUID;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Command\PutToken;
use PHPUnit\Framework\TestCase;

/**
 * Class AddTokenTest.
 */
class AddTokenTest extends TestCase
{
    /**
     * Test interaction asynchronous implementation.
     */
    public function testAsynchronous()
    {
        $appUUID = AppUUID::createById('main');
        $repositoryReference = RepositoryReference::create(
            $appUUID,
            IndexUUID::createById('default')
        );
        $token = new Token(TokenUUID::createById('9999'), $appUUID);
        $newToken = new Token(TokenUUID::createById('aaaa'), $appUUID);

        $addToken = new PutToken(
            $repositoryReference,
            $token,
            $newToken
        );

        $builtAddToken = PutToken::fromArray($addToken->toArray());
        $this->assertEquals(
            $addToken,
            $builtAddToken
        );

        $this->assertEquals(
            $newToken,
            $builtAddToken->getNewToken()
        );

        $this->assertEquals(
            $repositoryReference,
            $builtAddToken->getRepositoryReference()
        );

        $this->assertEquals(
            $token,
            $builtAddToken->getToken()
        );
    }
}
