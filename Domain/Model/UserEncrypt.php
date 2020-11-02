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

namespace Apisearch\Server\Domain\Model;

/**
 * Class User.
 */
final class UserEncrypt
{
    /**
     * @param string|null $userId
     * @param Origin|null $origin
     *
     * @return string|null
     */
    public function getUUIDByInput(
        ?string $userId,
        Origin $origin = null
    ): ? string {
        if (
            empty($userId) &&
            (
                \is_null($origin) ||
                empty($origin->getIp())
            )
        ) {
            return null;
        }

        $userId = $userId ?? $origin->getIp();
        $userHash = \hash('sha1', $userId);

        return \substr($userHash, 7, 10);
    }
}
