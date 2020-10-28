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
 * Interface Encrypter.
 */
interface Encrypter
{
    /**
     * @param string|null $content
     *
     * @return string|null
     */
    public function encrypt(?string $content): ?string;

    /**
     * @param string|null $content
     *
     * @return string|null
     */
    public function decrypt(?string $content): ?string;
}
