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

namespace Apisearch\Server\Tests\Unit\Domain\Plugin;

use Apisearch\Server\Domain\Model\Origin;
use Apisearch\Server\Domain\Model\UserEncrypt;
use PHPUnit\Framework\TestCase;

/**
 * Class UserTest.
 */
class UserEncryptTest extends TestCase
{
    public function testUser()
    {
        $userEncrypt = new UserEncrypt('xxxx');
        $this->assertNull($userEncrypt->getUUIDByInput(null, new Origin()));
        $this->assertNull($userEncrypt->getUUIDByInput('', new Origin('', '')));
        $this->assertNull($userEncrypt->getUUIDByInput(''));
        $this->assertNull($userEncrypt->getUUIDByInput(null));

        $this->assertNotEmpty($userEncrypt->getUUIDByInput(null, Origin::buildByUserAgent('', '0.0.0.0')));
        $this->assertNotEmpty($userEncrypt->getUUIDByInput('123', Origin::buildByUserAgent('', '')));
        $this->assertNotEmpty($userEncrypt->getUUIDByInput('123', Origin::buildByUserAgent('', '0.0.0.0')));
        $this->assertNotEmpty($userEncrypt->getUUIDByInput('123'));

        /*
         * Test idempotency
         */
        $this->assertSame(
            $userEncrypt->getUUIDByInput('123', Origin::buildByUserAgent('', '0.0.0.0')),
            $userEncrypt->getUUIDByInput('123', Origin::buildByUserAgent('', '0.0.0.0'))
        );
    }
}
