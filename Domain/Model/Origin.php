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

use Jenssegers\Agent\Agent;

/**
 * Class Origin.
 */
final class Origin
{
    const TABLET = 'tablet';
    const PHONE = 'phone';
    const MOBILE = 'mobile';
    const DESKTOP = 'desktop';
    const ROBOT = 'robot';
    const OTHERS = '';

    private string $host;
    private string $ip;
    private string $platform;

    /**
     * @param string $host
     * @param string $ip
     * @param string $platform
     */
    public function __construct(
        string $host = '',
        string $ip = '',
        string $platform = ''
    ) {
        $this->host = $host;
        $this->ip = $ip;
        $this->platform = $platform;
    }

    /**
     * @param string $host
     * @param string $ip
     * @param string $userAgent
     */
    public static function buildByUserAgent(
        string $host = '',
        string $ip = '',
        string $userAgent = ''
    ) {
        if (empty($userAgent)) {
            return new Origin(
                $host,
                $ip,
                self::OTHERS
            );
        }

        $agent = new Agent();
        $platform = self::OTHERS;
        if ($agent->isTablet($userAgent)) {
            $platform = self::TABLET;
        } elseif ($agent->isDesktop($userAgent)) {
            $platform = self::DESKTOP;
        } elseif ($agent->isPhone($userAgent)) {
            $platform = self::PHONE;
        } elseif ($agent->isRobot($userAgent)) {
            $platform = self::ROBOT;
        }

        return new Origin(
            $host,
            $ip,
            $platform
        );
    }

    /**
     * @return Origin
     */
    public static function createEmpty(): Origin
    {
        return new Origin('localhost', '0.0.0.0', '');
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @return string
     */
    public function getIp(): string
    {
        return $this->ip;
    }

    /**
     * @return string
     */
    public function getPlatform(): string
    {
        return $this->platform;
    }
}
