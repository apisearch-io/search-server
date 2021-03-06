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

namespace Apisearch\Server\Http;

use Apisearch\Server\Domain\Model\Origin;
use Ramsey\Uuid\UuidFactory;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ControllerWithBus.
 */
abstract class BaseController
{
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
     * @param int $length
     *
     * @return string
     */
    protected function generateUUID4(int $length = 32): string
    {
        return \substr((new UuidFactory())->uuid4()->toString(), 0, $length);
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
        return RemoteAddr::getRemoteAddrFromHeaderBag($headers);
    }
}
