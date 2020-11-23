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

use React\Http\Message\Response;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class TeapotController.
 */
final class TeapotController extends ControllerWithQueryBus
{
    /**
     * @param Request $request
     *
     * @return Response
     */
    public function __invoke(Request $request): Response
    {
        return new Response(418, [], 'I\'m a teagod');
    }
}
