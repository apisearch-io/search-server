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
use Ramsey\Uuid\Uuid;

/**
 * Class BasicSecurityTest.
 */
class BasicSecurityTest extends SecurityFunctionalTest
{
    /**
     * Test seconds available.
     */
    public function testSecondsAvailableFailing()
    {
        $token = new Token(
            TokenUUID::createById('12345'),
            AppUUID::createById(self::$appId)
        );
        $token->setMetadataValue('seconds_valid', 1);
        $this->putToken($token, self::$appId);
        sleep(2);

        try {
            $this->query(
                Query::createMatchAll(),
                self::$appId,
                self::$index,
                $token
            );
            $this->fail(sprintf('%s exception expected', InvalidTokenException::class));
        } catch (InvalidTokenException $e) {
            // Silent pass
            $this->assertTrue(true);
        }
    }

    /**
     * Test seconds available.
     */
    public function testSecondsAvailableAccepted()
    {
        $token = new Token(
            TokenUUID::createById('12345'),
            AppUUID::createById(self::$appId)
        );
        $token->setMetadataValue('seconds_valid', 2);
        $this->putToken($token, self::$appId);
        sleep(1);
        $this->query(
            Query::createMatchAll(),
            self::$appId,
            self::$index,
            $token
        );
        $this->assertTrue(true);
    }

    /**
     * Test bad referrers.
     *
     * @param array $referrers
     *
     * @dataProvider dataBadReferrers
     */
    public function testBadReferrers(array $referrers)
    {
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
                $token
            );
            $this->fail(sprintf('%s exception expected', InvalidTokenException::class));
        } catch (InvalidTokenException $e) {
            // Silent pass
            $this->assertTrue(true);
        }
    }

    /**
     * Test bad referrers.
     */
    public function dataBadReferrers()
    {
        return [
            [['google.es']],
        ];
    }

    /**
     * Test bad referrers.
     *
     * @param array $referrers
     *
     * @dataProvider dataGoodReferrers
     */
    public function testGoodReferrers(array $referrers)
    {
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
            $token
        );
        $this->assertTrue(true);
    }

    /**
     * Test good referrers.
     */
    public function dataGoodReferrers()
    {
        $currentReferrer = 'localhost';

        return [
            [[$currentReferrer]],
            [['google.es', $currentReferrer]],
            [[$currentReferrer, $currentReferrer]],
        ];
    }

    /**
     * Test requests limit.
     */
    public function testRequestsLimit()
    {
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
            $this->fail(sprintf('%s should be thrown', InvalidTokenException::class));
        } catch (InvalidTokenException $e) {
            // Silent pass
            $this->assertTrue(true);
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
            $this->fail(sprintf('%s should be thrown', InvalidTokenException::class));
        } catch (InvalidTokenException $e) {
            // Silent pass
            $this->assertTrue(true);
        }
    }

    /**
     * Test restricted fields.
     */
    public function testRestrictedFields()
    {
        $token = new Token(
            TokenUUID::createById('12345'),
            AppUUID::createById(self::$appId)
        );
        $token->setMetadataValue('restricted_fields', [
            'metadata.stored_field_boolean_false',
        ]);
        $this->putToken($token, self::$appId);
        $item = $this->query(Query::createMatchAll(), self::$appId, self::$index)->getFirstItem();
        $this->assertTrue(isset($item->getMetadata()['stored_field_boolean_false']));
        $item = $this->query(Query::createMatchAll(), self::$appId, self::$index, $token)->getFirstItem();
        $this->assertFalse(isset($item->getMetadata()['stored_field_boolean_false']));
    }

    /**
     * Test restricted fields.
     */
    public function testAllowedFields()
    {
        $token = new Token(
            TokenUUID::createById('12345'),
            AppUUID::createById(self::$appId)
        );
        $token->setMetadataValue('allowed_fields', [
            'metadata.stored_field_boolean_false',
            '!metadata.stored_field_boolean_false',
        ]);
        $this->putToken($token, self::$appId);
        $item = $this->query(Query::createMatchAll(), self::$appId, self::$index, $token)->getFirstItem();
        $this->assertCount(0, $item->getAllMetadata());
    }

    /**
     * Test plugin is working with subqueries as well.
     */
    public function testRestrictedFieldsInSubqueries()
    {
        $token = new Token(
            TokenUUID::createById('12345'),
            AppUUID::createById(self::$appId)
        );
        $token->setMetadataValue('restricted_fields', [
            'metadata.stored_field_boolean_false',
        ]);
        $this->putToken($token, self::$appId);

        $item = $this->query(Query::createMultiquery([
            'q1' => Query::createMatchAll(),
            'q2' => Query::createMatchAll(),
        ]), self::$appId, self::$index, $token)->getSubresults()['q1']->getFirstItem();
        $this->assertFalse(isset($item->getMetadata()['stored_field_boolean_false']));
    }
}
