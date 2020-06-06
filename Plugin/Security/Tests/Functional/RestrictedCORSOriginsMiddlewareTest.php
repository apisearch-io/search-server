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

use Apisearch\Config\Config;
use Apisearch\Exception\ForbiddenException;
use Apisearch\Server\Domain\Model\Origin;
use Apisearch\Server\Tests\Functional\CurlFunctionalTest;

/**
 * Class CORSFunctionalTest.
 */
class RestrictedCORSOriginsMiddlewareTest extends CurlFunctionalTest
{
    use SecurityFunctionalTestTrait;

    /**
     * Tested cases.
     *
     * - No security - Allowed
     * - Index secured - Allowed
     * - Index secured - Not Allowed
     * - Index1 secured, require Index1 & Index2 - Not Allowed
     * - Index1 and 2 secured, required Index1 & 2 - Allowed
     *
     * - Secure full domain
     * - Secure regexp
     */

    /**
     * Test no security.
     */
    public function testNoSecurity()
    {
        $this->getCORSPermissions(new Origin('Whatever.com'));
        $this->assertEquals('Whatever.com', $this->getCORSPermissions(new Origin('Whatever.com')));
    }

    /**
     * Test allowed index.
     */
    public function testAllowedIndex()
    {
        $this->configureIndex(Config::createEmpty()->addMetadataValue('allowed_domains', [
            'Whatever.com',
        ]));

        $this->assertEquals('Whatever.com', $this->getCORSPermissions(new Origin('Whatever.com')));
    }

    /**
     * Test not allowed index.
     */
    public function testNotAllowedIndex()
    {
        $this->configureIndex(Config::createEmpty()->addMetadataValue('allowed_domains', [
            'domain1.com',
        ]));

        $this->expectException(ForbiddenException::class);
        $this->getCORSPermissions(new Origin('Whatever.com'));
    }

    /**
     * Test allowed only one index.
     */
    public function testAllowedOnlyOneIndex()
    {
        $this->configureIndex(Config::createEmpty()->addMetadataValue('allowed_domains', [
            'Whatever.com',
        ]));

        $this->configureIndex(Config::createEmpty()->addMetadataValue('allowed_domains', [
            'Another.com',
        ]), false, static::$appId, static::$anotherIndex);

        $this->expectException(ForbiddenException::class);
        $this->getCORSPermissions(new Origin('Whatever.com'), static::$appId, \implode(',', [
            static::$index,
            static::$anotherIndex,
        ]));
    }

    /**
     * Test allowed in both indices.
     */
    public function testAllowedInBothIndices()
    {
        $this->configureIndex(Config::createEmpty()->addMetadataValue('allowed_domains', [
            'Whatever.com',
        ]));

        $this->configureIndex(Config::createEmpty()->addMetadataValue('allowed_domains', [
            'Whatever.com',
        ]), false, static::$appId, static::$anotherIndex);

        $this->assertEquals('Whatever.com', $this->getCORSPermissions(new Origin('Whatever.com'), static::$appId, \implode(',', [
            static::$index,
            static::$anotherIndex,
        ])));
    }

    /**
     * Test allowed in both indices and multiple domains allowed.
     */
    public function testAllowedInBothIndicesMultipleAllowed()
    {
        $this->configureIndex(Config::createEmpty()->addMetadataValue('allowed_domains', [
            'Whatever.com',
            'another.io',
        ]));

        $this->configureIndex(Config::createEmpty()->addMetadataValue('allowed_domains', [
            'Whatever.com',
            'another.net',
        ]), false, static::$appId, static::$anotherIndex);

        $this->assertEquals('Whatever.com', $this->getCORSPermissions(new Origin('Whatever.com'), static::$appId, \implode(',', [
            static::$index,
            static::$anotherIndex,
        ])));
    }

    /**
     * Test allowed in both indices and one empty.
     */
    public function testAllowedInBothIndicesOneEmpty()
    {
        $this->configureIndex(Config::createEmpty()->addMetadataValue('allowed_domains', [
            'Whatever.com',
            'another.io',
        ]));

        $this->assertEquals('Whatever.com', $this->getCORSPermissions(new Origin('Whatever.com'), static::$appId, \implode(',', [
            static::$index,
            static::$anotherIndex,
        ])));
    }

    /**
     * Test domain formats.
     *
     * @param string $origin
     * @param string $domain
     * @param bool   $allowed
     *
     * @dataProvider dataSecureDomainFormat
     */
    public function testSecureDomainFormat(
        string $origin,
        string $domain,
        bool $allowed
    ) {
        $this->configureIndex(Config::createEmpty()->addMetadataValue('allowed_domains', [
            $domain,
        ]));

        if (!$allowed) {
            $this->expectException(ForbiddenException::class);
        }

        $this->assertEquals(
            $origin,
            $this->getCORSPermissions(new Origin($origin))
        );
    }

    /**
     * Data for testSecureDomainFormat.
     *
     * @return array
     */
    public function dataSecureDomainFormat(): array
    {
        return [
            ['http://whatever.com', 'http://whatever.com', true],
            ['http://whatever.com', 'whatever.com', true],
            ['https://whatever.com', 'whatever.com', true],
            ['https://lol.whatever.com', '*.whatever.com', true],
            ['https://lol.whatever.com', 'https://*.whatever.com', true],
            ['http://lol.whatever.com', 'http://*.whatever.com', true],

            // Not allowed
            ['https://lol.whatever.com', 'https://cat.whatever.com', false],
            ['http://whatever.com', 'https://whatever.com', false],
            ['https://whatever.com', 'http://whatever.com', false],
            ['https://lol.whatever.com', 'whatever.com', false],
            ['http://lol.whatever.com', 'whatever.com', false],
        ];
    }

    /**
     * Test domain formats.
     *
     * @param string $origin
     * @param string $domain
     * @param bool   $allowed
     *
     * @dataProvider dataSecureDomainFormatAllIndices
     */
    public function testSecureDomainFormatAllIndices(
        string $origin,
        string $domain,
        bool $allowed
    ) {
        static::resetScenario();
        $this->configureIndex(Config::createEmpty()->addMetadataValue('allowed_domains', [
            $domain,
        ]));

        if (!$allowed) {
            $this->expectException(ForbiddenException::class);
        }

        $this->assertEquals(
            $origin, $this->getCORSPermissions(new Origin($origin), static::$appId, '*')
        );
    }

    /**
     * Data for testSecureDomainFormatAllIndices.
     *
     * @return array
     */
    public function dataSecureDomainFormatAllIndices(): array
    {
        return [
            ['http://whatever.com', 'http://whatever.com', true],
            ['http://whatever.com', 'whatever.com', true],
            ['https://whatever.com', 'whatever.com', true],
            ['https://lol.whatever.com', '*.whatever.com', true],
            ['https://lol.whatever.com', 'https://*.whatever.com', true],
            ['http://lol.whatever.com', 'http://*.whatever.com', true],

            // Not allowed
            ['https://lol.whatever.com', 'https://cat.whatever.com', false],
            ['http://whatever.com', 'https://whatever.com', false],
            ['https://whatever.com', 'http://whatever.com', false],
        ];
    }

    /**
     * Test ips unsecured.
     */
    public function testBlockedIPSUnsecured()
    {
        static::resetScenario();
        $this->configureIndex(Config::createEmpty()->addMetadataValue('blocked_ips', [
            '1.2.3.4',
        ]));

        $this->expectNotToPerformAssertions();
        $this->getCORSPermissions(new Origin('localhost'));
    }

    /**
     * Test secure by blocked ips.
     */
    public function testBlockedIPSSecured()
    {
        static::resetScenario();
        $this->configureIndex(Config::createEmpty()->addMetadataValue('blocked_ips', [
            '1.2.3.4',
            '5.6.7.8',
        ]));

        $this->getCORSPermissions(new Origin('localhost', '0.2.3.4'));
        $this->getCORSPermissions(new Origin('localhost', '0.0.0.0'));
        $this->getCORSPermissions(new Origin('localhost', '5.5.6.7'));
        $this->expectException(ForbiddenException::class);
        $this->getCORSPermissions(new Origin('localhost', '1.2.3.4'));
    }

    /**
     * Test mixed security.
     *
     * @param array $allowedOrigins
     * @param array $blockedIPs
     * @param bool  $allowed
     *
     * @dataProvider dataMixedSecurity
     */
    public function testMixedSecurity(
        array $allowedOrigins,
        array $blockedIPs,
        bool $allowed
    ) {
        $this->configureIndex(Config::createEmpty()
            ->addMetadataValue('allowed_domains', $allowedOrigins)
            ->addMetadataValue('blocked_ips', $blockedIPs)
        );

        if (!$allowed) {
            $this->expectException(ForbiddenException::class);
        } else {
            $this->expectNotToPerformAssertions();
        }

        $this->getCORSPermissions(new Origin('http://whatever.com', '1.1.1.1'), static::$appId, '*');
    }

    /**
     * Data for mixed security.
     *
     * Accessing always with whatever.com and 1.1.1.1
     *
     * @return array
     */
    public function dataMixedSecurity(): array
    {
        return [
            [['http://whatever.com'], ['1.1.1.2'], true],
            [['whatever.com'], ['1.1.1.2'], true],
            [['whatever.com'], [], true],
            [[], [], true],
            [[], ['1.1.1.2'], true],

            [[], ['1.1.1.1'], false],
            [['another.com'], ['1.1.1.1'], false],
            [['another.com'], [], false],
            [['another.com', 'yetanother.com'], ['1.1.1.1', '2.2.2.2'], false],
        ];
    }
}
