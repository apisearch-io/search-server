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

use Apisearch\Server\Domain\Model\Origin;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

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

    /**
     * @param Request $request
     *
     * @return Origin
     */
    protected function createOriginByRequest(Request $request): Origin
    {
        $headers = $request->headers;

        return Origin::buildByUserAgent(
            $this->getOriginFromHeaderBag($headers),
            $this->getRemoteAddrFromHeaderBag($headers),
            $headers->get('USER_AGENT', '')
        );
    }

    /**
     * @param HeaderBag $headers
     *
     * @return string
     */
    private function getOriginFromHeaderBag(HeaderBag $headers): string
    {
        return $headers->get('Origin', '');
    }

    /**
     * @param HeaderBag $headers
     *
     * @return string
     */
    private function getRemoteAddrFromHeaderBag(HeaderBag $headers): string
    {
        return $headers->get('HTTP_X_FORWARDED_FOR', $headers->get('REMOTE_ADDR', $headers->get('HTTP_CLIENT_IP', '')));
    }
}
