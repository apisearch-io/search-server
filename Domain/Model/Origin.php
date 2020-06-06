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
    /**
     * @var string
     */
    const TABLET = 'tablet';

    /**
     * @var string
     */
    const PHONE = 'phone';

    /**
     * @var string
     */
    const MOBILE = 'mobile';

    /**
     * @var string
     */
    const DESKTOP = 'desktop';

    /**
     * @var string
     */
    const ROBOT = 'robot';

    /**
     * @var string
     */
    const OTHERS = '';

    /**
     * @var string
     */
    private $host;

    /**
     * @var string
     */
    private $ip;

    /**
     * @var string
     */
    private $platform;

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
        return new Origin('', '', '');
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
