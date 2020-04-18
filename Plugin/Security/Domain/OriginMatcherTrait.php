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

namespace Apisearch\Plugin\Security\Domain;

/**
 * Trait OriginMatcherTrait.
 */
trait OriginMatcherTrait
{
    /**
     * Origin is allowed.
     *
     * @param string   $origin
     * @param string[] $allowedDomains
     *
     * @return bool
     */
    private function originIsAllowed(
        string $origin,
        array $allowedDomains
    ): bool {
        if (empty($allowedDomains)) {
            return true;
        }

        foreach ($allowedDomains as $allowedDomain) {
            if ($this->domainMatchesOrigin($origin, $allowedDomain)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Domain matches origin.
     *
     * @param string $origin
     * @param string $domain
     *
     * @return bool
     */
    private function domainMatchesOrigin(
        string $origin,
        string $domain
    ): bool {
        if (
            0 !== \strpos($domain, 'https://') &&
            0 !== \strpos($domain, 'http://')
        ) {
            $origin = \preg_replace('~^https?://~', '', $origin);
        }

        return \fnmatch($domain, $origin);
    }
}
