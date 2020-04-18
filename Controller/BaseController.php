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

namespace Apisearch\Server\Controller;

use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Class ControllerWithBus.
 */
abstract class BaseController
{
    /**
     * Get query value and cast to int of not null.
     *
     * @param ParameterBag $parameters
     * @param string       $paramName
     *
     * @return int|null
     */
    protected function castToIntIfNotNull(
        ParameterBag $parameters,
        string $paramName
    ): ? int {
        $param = $parameters->get($paramName, null);
        if (!\is_null($param)) {
            $param = \intval($param);
        }

        return $param;
    }
}
