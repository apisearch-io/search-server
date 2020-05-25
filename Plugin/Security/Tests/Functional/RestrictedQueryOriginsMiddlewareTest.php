<?php


namespace Apisearch\Plugin\Security\Tests\Functional;

use Apisearch\Config\Config;
use Apisearch\Exception\ForbiddenException;
use Apisearch\Query\Query;
use Apisearch\Server\Tests\Functional\CurlFunctionalTest;

/**
 * Class QueryRestrictionsFunctionalTest
 */
class RestrictedQueryOriginsMiddlewareTest extends CurlFunctionalTest
{
    use SecurityFunctionalTestTrait;

    /**
     * Test mixed security
     *
     * @param array $allowedOrigins
     * @param array $blockedIPs
     * @param bool $allowed
     *
     * @dataProvider dataMixedSecurity
     */
    public function testMixedSecurity(
        array $allowedOrigins,
        array $blockedIPs,
        bool $allowed
    )
    {
        $this->configureIndex(Config::createEmpty()
            ->addMetadataValue('allowed_domains', $allowedOrigins)
            ->addMetadataValue('blocked_ips', $blockedIPs)
        );

        if (!$allowed) {
            $this->expectException(ForbiddenException::class);
        } else {
            $this->expectNotToPerformAssertions();
        }

        $this->query(
            Query::createMatchAll(),
            static::$appId,
            '*',
            null,
            [],
            [
                'Origin: http://whatever.com',
                'REMOTE_ADDR: 1.1.1.1'
            ]
        );
    }

    /**
     * Data for mixed security.
     *
     * Accessing always with whatever.com and 1.1.1.1
     *
     * @return array
     */
    public function dataMixedSecurity() : array
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