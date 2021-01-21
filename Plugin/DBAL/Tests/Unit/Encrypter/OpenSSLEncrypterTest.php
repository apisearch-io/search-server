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

use Apisearch\Plugin\DBAL\Domain\Encrypter\OpenSSLEncrypter;
use PHPUnit\Framework\TestCase;

/**
 * Class OpenSSLEncrypterTest.
 */
class OpenSSLEncrypterTest extends TestCase
{
    /**
     * Test encrypt.
     *
     * @return void
     */
    public function testEncrypt(): void
    {
        $encrypter = new OpenSSLEncrypter('123', 'aes128', '1234567890123456');
        $value = 'value';
        $encryptedValue = $encrypter->encrypt($value);
        $this->assertNotEquals($value, $encryptedValue);
        $this->assertEquals($value, $encrypter->decrypt($encryptedValue));

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
        $encrypter = new OpenSSLEncrypter('123', 'aes128', '1234567890123456');
        $value = 'value';
        $this->assertEquals($value, $encrypter->decrypt($value));

        $array = ['key' => 'value1'];
        $string = \json_encode(['key' => 'value1']);
        $this->assertEquals($array, \json_decode($encrypter->decrypt($string), true));

        $value = null;
        $this->assertNull($encrypter->decrypt($value));
    }
}
