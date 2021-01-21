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

namespace Apisearch\Plugin\Security\Tests\Functional;

use Apisearch\Exception\InvalidTokenException;
use Apisearch\Model\AppUUID;
use Apisearch\Model\Token;
use Apisearch\Model\TokenUUID;
use Apisearch\Query\Query;
use Apisearch\Server\Domain\Model\Origin;
use Apisearch\Server\Tests\Functional\HttpFunctionalTest;
use Ramsey\Uuid\Uuid;

/**
 * Class BasicSecurityTest.
 */
class BasicSecurityTest extends HttpFunctionalTest
{
    use SecurityFunctionalTestTrait;

    /**
     * @var string
     */
    const CURL_REFERER = 'http://local.host';

    /**
     * Test seconds available.
     *
     * @return void
     */
    public function testSecondsAvailableFailing(): void
    {
        $this->expectNotToPerformAssertions();
        $token = new Token(
            TokenUUID::createById('12345'),
            AppUUID::createById(self::$appId)
        );
        $token->setMetadataValue('seconds_valid', 1);
        $this->putToken($token, self::$appId);
        \sleep(2);

        try {
            $this->query(
                Query::createMatchAll(),
                self::$appId,
                self::$index,
                $token
            );
            $this->fail(\sprintf('%s exception expected', InvalidTokenException::class));
        } catch (InvalidTokenException $e) {
            // Silent pass
        }
    }

    /**
     * Test seconds available.
     *
     * @return void
     */
    public function testSecondsAvailableAccepted(): void
    {
        $this->expectNotToPerformAssertions();
        $token = new Token(
            TokenUUID::createById('12345'),
            AppUUID::createById(self::$appId)
        );
        $token->setMetadataValue('seconds_valid', 2);
        $this->putToken($token, self::$appId);
        \sleep(1);

        $this->query(
            Query::createMatchAll(),
            self::$appId,
            self::$index,
            $token
        );
    }

    /**
     * Test bad referrers.
     *
     * @param array $referrers
     *
     * @dataProvider dataBadReferrers
     *
     * @return void
     */
    public function testBadReferrers(array $referrers): void
    {
        $this->expectNotToPerformAssertions();
        $token = new Token(
            TokenUUID::createById('12345'),
            AppUUID::createById(self::$appId)
        );
        $token->setMetadataValue('http_referrers', $referrers);
        $this->putToken($token, self::$appId);
        try {
            $this->query(
                Query::createMatchAll(),
                self::$appId,
                self::$index,
                $token, [], Origin::createEmpty(), [
                    'Referer' => static::CURL_REFERER,
                ]
            );
            $this->fail(\sprintf('%s exception expected', InvalidTokenException::class));
        } catch (InvalidTokenException $e) {
            // Silent pass
        }
    }

    /**
     * Test bad referrers.
     *
     * @return array
     */
    public function dataBadReferrers(): array
    {
        return [
            [['google.es']],
            [['another.host']],
            [['https://local.host']],
        ];
    }

    /**
     * Test bad referrers.
     *
     * @param array $referrers
     *
     * @dataProvider dataGoodReferrers
     *
     * @return void
     */
    public function testGoodReferrers(array $referrers): void
    {
        $this->expectNotToPerformAssertions();
        $token = new Token(
            TokenUUID::createById('12345'),
            AppUUID::createById(self::$appId)
        );
        $token->setMetadataValue('http_referrers', $referrers);
        $this->putToken($token, self::$appId);
        $this->query(
            Query::createMatchAll(),
            self::$appId,
            self::$index,
            $token, [], Origin::createEmpty(), [
                'Referer' => static::CURL_REFERER,
            ]
        );
    }

    /**
     * Test good referrers.
     *
     * @return array
     */
    public function dataGoodReferrers(): array
    {
        return [
            [[static::CURL_REFERER]],
            [['google.es', static::CURL_REFERER]],
            [[static::CURL_REFERER, static::CURL_REFERER]],
            [['local.host']],
            [['*.host']],
        ];
    }

    /**
     * Test requests limit.
     *
     * @return void
     */
    public function testRequestsLimit(): void
    {
        $this->expectNotToPerformAssertions();
        $token = new Token(
            TokenUUID::createById('12345'),
            AppUUID::createById(self::$appId)
        );
        $token->setMetadataValue('requests_limit', [
            '2/s',
        ]);
        $this->putToken($token, self::$appId);
        $this->query(Query::createMatchAll(), self::$appId, self::$index, $token);
        $this->query(Query::createMatchAll(), self::$appId, self::$index, $token);
        try {
            $this->query(Query::createMatchAll(), self::$appId, self::$index, $token);
            $this->fail(\sprintf('%s should be thrown', InvalidTokenException::class));
        } catch (InvalidTokenException $e) {
            // Silent pass
        }

        $newToken = new Token(
            TokenUUID::createById((string) Uuid::uuid4()),
            AppUUID::createById(self::$appId)
        );
        $newToken->setMetadataValue('requests_limit', [
            '5',
        ]);
        $this->putToken($newToken, self::$appId);
        $this->query(Query::createMatchAll(), self::$appId, self::$index, $newToken);
        $this->query(Query::createMatchAll(), self::$appId, self::$index, $newToken);
        $this->query(Query::createMatchAll(), self::$appId, self::$index, $newToken);
        $this->query(Query::createMatchAll(), self::$appId, self::$index, $newToken);
        $this->query(Query::createMatchAll(), self::$appId, self::$index, $newToken);
        try {
            $this->query(Query::createMatchAll(), self::$appId, self::$index, $newToken);
            $this->fail(\sprintf('%s should be thrown', InvalidTokenException::class));
        } catch (InvalidTokenException $e) {
            // Silent pass
        }
    }

    /**
     * Test restricted fields.
     *
     * @return void
     */
    public function testRestrictedFields(): void
    {
        $token = new Token(
            TokenUUID::createById('12345'),
            AppUUID::createById(self::$appId)
        );
        $token->setMetadataValue('restricted_fields', [
            'metadata.field',
        ]);
        $this->putToken($token, self::$appId);

        $item = $this->query(Query::createMatchAll(), self::$appId, self::$index)->getFirstItem();
        $this->assertTrue(isset($item->getMetadata()['field']));
        $item = $this->query(Query::createMatchAll(), self::$appId, self::$index, $token)->getFirstItem();
        $this->assertFalse(isset($item->getMetadata()['field']));
    }

    /**
     * Test restricted fields.
     *
     * @return void
     */
    public function testAllowedFields(): void
    {
        $token = new Token(
            TokenUUID::createById('12345'),
            AppUUID::createById(self::$appId)
        );
        $token->setMetadataValue('allowed_fields', [
            'metadata.field',
            '!metadata.field',
        ]);
        $this->putToken($token, self::$appId);
        $item = $this->query(Query::createMatchAll(), self::$appId, self::$index, $token)->getFirstItem();
        $this->assertCount(0, $item->getMetadata());
    }

    /**
     * Test plugin is working with subqueries as well.
     *
     * @return void
     */
    public function testRestrictedFieldsInSubqueries(): void
    {
        $token = new Token(
            TokenUUID::createById('12345'),
            AppUUID::createById(self::$appId)
        );
        $token->setMetadataValue('restricted_fields', [
            'metadata.field',
        ]);
        $this->putToken($token, self::$appId);

        $item = $this->query(Query::createMultiquery([
            'q1' => Query::createMatchAll(),
            'q2' => Query::createMatchAll(),
        ]), self::$appId, self::$index, $token)->getSubresults()['q1']->getFirstItem();
        $this->assertFalse(isset($item->getMetadata()['field']));
    }
}
