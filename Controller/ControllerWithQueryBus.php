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

use Drift\CommandBus\Bus\QueryBus;
use React\Promise\PromiseInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ControllerWithQueryBus.
 */
abstract class ControllerWithQueryBus extends BaseController
{
    /**
     * @var QueryBus
     *
     * Query bus
     */
    protected $queryBus;

    /**
     * Controller constructor.
     *
     * @param QueryBus $queryBus
     */
    public function __construct(QueryBus $queryBus)
    {
        $this->queryBus = $queryBus;
    }

    /**
     * @param object $query
     *
     * @return PromiseInterface
     */
    protected function ask($query)
    {
        return $this
            ->queryBus
            ->ask($query);
    }

    /**
     * @param Request $request
     *
     * @return string
     */
    protected function getOrigin(Request $request): string
    {
        $headers = $request->headers;

        return $headers->get('Origin', '');
    }

    /**
     * @param Request $request
     *
     * @return string
     */
    protected function getRemoteAddr(Request $request): string
    {
        $headers = $request->headers;

        return $headers->get('HTTP_X_FORWARDED_FOR', $headers->get('REMOTE_ADDR', $headers->get('HTTP_CLIENT_IP', '')));
    }
}
