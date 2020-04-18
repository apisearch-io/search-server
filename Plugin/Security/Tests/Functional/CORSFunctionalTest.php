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
use Apisearch\Server\Tests\Functional\CurlFunctionalTest;

/**
 * Class CORSFunctionalTest.
 */
class CORSFunctionalTest extends CurlFunctionalTest
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
        $this->getCORSPermissions('Whatever.com');
        $this->assertEquals('Whatever.com', $this->getCORSPermissions('Whatever.com'));
    }

    /**
     * Test allowed index.
     */
    public function testAllowedIndex()
    {
        $this->configureIndex(Config::createEmpty()->addMetadataValue('allowed_domains', [
            'Whatever.com',
        ]));

        $this->assertEquals('Whatever.com', $this->getCORSPermissions('Whatever.com'));
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
        $this->getCORSPermissions('Whatever.com');
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
        ]), static::$appId, static::$anotherIndex);

        $this->expectException(ForbiddenException::class);
        $this->getCORSPermissions('Whatever.com', static::$appId, \implode(',', [
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
        ]), static::$appId, static::$anotherIndex);

        $this->assertEquals('Whatever.com', $this->getCORSPermissions('Whatever.com', static::$appId, \implode(',', [
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
        ]), static::$appId, static::$anotherIndex);

        $this->assertEquals('Whatever.com', $this->getCORSPermissions('Whatever.com', static::$appId, \implode(',', [
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

        $this->assertEquals('Whatever.com', $this->getCORSPermissions('Whatever.com', static::$appId, \implode(',', [
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
            $this->getCORSPermissions($origin)
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
            $origin, $this->getCORSPermissions($origin, static::$appId, '*')
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
}
