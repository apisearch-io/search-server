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

namespace Apisearch\Server\Domain\Helper;

/**
 * Class StringTo.
 */
final class Str
{
    /**
     * @param string $string
     *
     * @return string
     */
    public static function toAscii(string $string): string
    {
        return \strtr($string, [
            'ä' => 'a', 'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'å' => 'a', 'æ' => 'a',
            'Ä' => 'a', 'À' => 'a', 'Á' => 'a', 'Â' => 'a', 'Ã' => 'a', 'Å' => 'a', 'Æ' => 'a',
            'ç' => 'c', 'ć' => 'c', 'ĉ' => 'c', 'č' => 'c',
            'Ç' => 'c', 'Ć' => 'c', 'Ĉ' => 'c', 'Č' => 'c',
            'ö' => 'o', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ø' => 'o',
            'Ö' => 'o', 'Ò' => 'o', 'Ó' => 'o', 'Ô' => 'o', 'Ő' => 'o', 'Ø' => 'o',
            'ü' => 'u', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ű' => 'u',
            'Ü' => 'u', 'Ù' => 'u', 'Ú' => 'u', 'Û' => 'u', 'Ű' => 'u',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'É' => 'e', 'È' => 'e', 'Ê' => 'e', 'Ë' => 'e',
            'ý' => 'y',
            'Ý' => 'y',
            'ñ' => 'n',
            'Ñ' => 'n',
            'î' => 'i', 'ì' => 'i', 'í' => 'i', 'ï' => 'i',
            'Î' => 'i', 'Ì' => 'i', 'Í' => 'i', 'Ï' => 'i',
        ]);
    }
}
