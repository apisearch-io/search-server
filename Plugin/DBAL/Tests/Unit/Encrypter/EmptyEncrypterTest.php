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

namespace Apisearch\Plugin\DBAL\Tests\Unit;

use Apisearch\Plugin\DBAL\Domain\Encrypter\EmptyEncrypter;
use PHPUnit\Framework\TestCase;

/**
 * Class EmptyEncrypterTest.
 */
class EmptyEncrypterTest extends TestCase
{
    /**
     * Test encrypt.
     *
     * @return void
     */
    public function testEncrypt(): void
    {
        $encrypter = new EmptyEncrypter();
        $value = 'value';
        $this->assertEquals($value, $encrypter->encrypt($value));

        $value = null;
        $this->assertNull($encrypter->encrypt($value));
    }

    /**
     * Test encrypt.
     *
     * @return void
     */
    public function testDecrypt(): void
    {
        $encrypter = new EmptyEncrypter();
        $value = 'value';
        $this->assertEquals($value, $encrypter->decrypt($value));

        $value = null;
        $this->assertNull($encrypter->decrypt($value));
    }
}
