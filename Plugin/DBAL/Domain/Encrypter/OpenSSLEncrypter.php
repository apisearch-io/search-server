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

namespace Apisearch\Plugin\DBAL\Domain\Encrypter;

/**
 * Class OpenSSLEncrypter.
 */
class OpenSSLEncrypter implements Encrypter
{
    private string $encryptPrivateKey;
    private string $encryptMethod;
    private string $encryptIV;

    /**
     * @param string $encryptPrivateKey
     * @param string $encryptMethod
     * @param string $encryptIV
     */
    public function __construct(
        string $encryptPrivateKey,
        string $encryptMethod,
        string $encryptIV
    ) {
        $this->encryptPrivateKey = $encryptPrivateKey;
        $this->encryptMethod = $encryptMethod;
        $this->encryptIV = $encryptIV;
    }

    /**
     * @param string|null $content
     *
     * @return string|null
     */
    public function encrypt(?string $content): ?string
    {
        return \is_null($content)
            ? null
            : \utf8_encode(
                \openssl_encrypt($content, $this->encryptMethod, $this->encryptPrivateKey, OPENSSL_RAW_DATA, $this->encryptIV)
            );
    }

    /**
     * @param string|null $content
     *
     * @return string|null
     */
    public function decrypt(?string $content): ?string
    {
        if (\is_null($content)) {
            return null;
        }

        $content = \utf8_decode($content);
        $decrypted = \openssl_decrypt($content, $this->encryptMethod, $this->encryptPrivateKey, OPENSSL_RAW_DATA, $this->encryptIV);

        if (false === $decrypted) {
            $decrypted = $content;
        }

        return $decrypted;
    }
}
