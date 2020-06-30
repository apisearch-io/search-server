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

namespace Apisearch\Server\Tests\Functional\Console;

use Apisearch\Exception\InvalidTokenException;
use Apisearch\Model\AppUUID;
use Apisearch\Model\Token;
use Apisearch\Model\TokenUUID;
use Apisearch\Query\Query;

/**
 * Trait GenerateBasicTokensCommandTest.
 */
trait GenerateBasicTokensCommandTest
{
    /**
     * Test token creation.
     */
    public function testTokenCreation()
    {
        static::runCommand([
            'command' => 'apisearch-server:create-index',
            'app-id' => self::$appId,
            'index' => self::$index,
        ]);

        $output = static::runCommand([
            'command' => 'apisearch-server:generate-basic-tokens',
            'app-id' => static::$appId,
        ]);

        $appUUID = AppUUID::createById(self::$appId);
        \preg_match('~UUID\s*(.*?)\s*generated for admin~', $output, $matches);
        $uuidAdmin = $matches[1];
        \preg_match('~UUID\s*(.*?)\s*generated for query~', $output, $matches);
        $uuidQuery = $matches[1];
        \preg_match('~UUID\s*(.*?)\s*generated for interaction~', $output, $matches);
        $uuidInteractions = $matches[1];

        $adminToken = new Token(TokenUUID::createById($uuidAdmin), $appUUID);
        $queryToken = new Token(TokenUUID::createById($uuidQuery), $appUUID);
        $interactionsToken = new Token(TokenUUID::createById($uuidInteractions), $appUUID);

        $this->query(Query::createMatchAll(), null, null, $adminToken);
        $this->query(Query::createMatchAll(), null, null, $queryToken);

        $this->expectException(InvalidTokenException::class);
        $this->query(Query::createMatchAll(), null, null, $interactionsToken);
    }
}
