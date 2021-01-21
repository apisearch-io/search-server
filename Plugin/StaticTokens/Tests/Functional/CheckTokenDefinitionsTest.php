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

namespace Apisearch\Plugin\StaticTokens\Tests\Functional;

use Apisearch\Model\AppUUID;
use Apisearch\Model\Item;
use Apisearch\Model\ItemUUID;
use Apisearch\Model\Token;
use Apisearch\Model\TokenUUID;
use Apisearch\Query\Query;

/**
 * Class CheckTokenDefinitionsTest.
 */
class CheckTokenDefinitionsTest extends StaticTokensFunctionalTest
{
    /**
     * Test simple workflow.
     *
     * @return void
     */
    public function testSimpleWorkflow(): void
    {
        $this->expectNotToPerformAssertions();
        $token = new Token(TokenUUID::createById('blablabla'), AppUUID::createById(self::$appId));
        $this->query(
            Query::createMatchAll(),
            null,
            null,
            $token
        );

        try {
            $this->query(
                Query::createMatchAll(),
                self::$anotherAppId,
                null,
                $token
            );

            $this->fail('Non accepted app should throw exception');
        } catch (\Exception $e) {
            // Silence Pass
        }
    }

    /**
     * Test simple workflow.
     *
     * @return void
     */
    public function testSpecificIndex(): void
    {
        $this->expectNotToPerformAssertions();
        $token = new Token(TokenUUID::createById('onlyindex'), AppUUID::createById(self::$appId));
        $this->query(
            Query::createMatchAll(),
            null,
            self::$index,
            $token
        );

        try {
            $this->query(
                Query::createMatchAll(),
                null,
                self::$anotherIndex,
                $token
            );

            $this->fail('Non accepted index should throw exception');
        } catch (\Exception $e) {
            // Silence Pass
        }
    }

    /**
     * Test simple workflow.
     *
     * @return void
     */
    public function testSpecificEndpoint(): void
    {
        $this->expectNotToPerformAssertions();
        $token = new Token(TokenUUID::createById('onlyaddtoken'), AppUUID::createById(self::$appId));
        $this->query(
            Query::createMatchAll(),
            null,
            null,
            $token
        )->getItems();

        try {
            $this->indexItems(
                [Item::create(new ItemUUID('1', 'lala'))],
                null,
                null,
                $token
            );

            $this->fail('Non accepted endpoints should throw exception');
        } catch (\Exception $e) {
            // Silence Pass
        }
    }

    /**
     * Test wrong format.
     *
     * @return void
     */
    public function testLongTokenIdFormat(): void
    {
        $this->expectNotToPerformAssertions();
        $token = new Token(TokenUUID::createById('bla-bla-blah'), AppUUID::createById(self::$appId));
        $this->query(
            Query::createMatchAll(),
            null,
            null,
            $token
        );
    }

    /**
     * Test readonly flag.
     *
     * @return void
     */
    public function testReadOnlyFlag(): void
    {
        $tokens = $this->getTokensById();
        $this->assertTrue($tokens['bla-bla-blah']->getMetadataValue('read_only'));
        $this->assertTrue($tokens['onlyaddtoken']->getMetadataValue('read_only'));
        $this->assertTrue($tokens['base_filtered_token']->getMetadataValue('read_only'));
    }
}
