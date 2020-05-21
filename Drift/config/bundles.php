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

return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],

    Drift\CommandBus\CommandBusBundle::class => ['all' => true],
    Drift\Preload\PreloadBundle::class => ['all' => true],

    Apisearch\Server\ApisearchServerBundle::class => ['all' => true],
    Apisearch\ApisearchBundle::class => ['all' => true],
    Apisearch\Server\ApisearchPluginsBundle::class => ['all' => true],
];
