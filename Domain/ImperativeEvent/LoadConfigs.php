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

namespace Apisearch\Server\Domain\ImperativeEvent;

use Apisearch\Model\AppUUID;

/**
 * Class LoadConfigs.
 */
final class LoadConfigs
{
    /**
     * @var AppUUID
     */
    private $appUUID;

    /**
     * @param AppUUID $appUUID
     */
    public function __construct(AppUUID $appUUID)
    {
        $this->appUUID = $appUUID;
    }

    /**
     * @return AppUUID
     */
    public function getAppUUID(): AppUUID
    {
        return $this->appUUID;
    }
}
