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

use Apisearch\Config\Config;
use Apisearch\Exception\InvalidFormatException;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Command\ConfigureIndex;
use React\Promise\PromiseInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ConfigureIndexController.
 */
final class ConfigureIndexController extends ControllerWithCommandBus
{
    /**
     * Config the index.
     *
     * @param Request $request
     *
     * @return PromiseInterface
     */
    public function __invoke(Request $request): PromiseInterface
    {
        $requestQuery = $request->query;
        $indexUUID = RequestAccessor::getIndexUUIDFromRequest($request);
        $configAsArray = RequestAccessor::extractRequestContentObject(
            $request,
            '',
            InvalidFormatException::configFormatNotValid($request->getContent())
        );

        return $this
            ->execute(new ConfigureIndex(
                RepositoryReference::create(
                    RequestAccessor::getAppUUIDFromRequest($request),
                    $indexUUID
                ),
                RequestAccessor::getTokenFromRequest($request),
                $indexUUID,
                Config::createFromArray($configAsArray),
                \boolval($requestQuery->get('force_reindex', false))
            ))
            ->then(function () {
                return new JsonResponse(
                    'Index configured with given configuration',
                    200
                );
            });
    }
}
