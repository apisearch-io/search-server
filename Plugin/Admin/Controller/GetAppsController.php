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

namespace Apisearch\Plugin\Admin\Controller;

use Apisearch\Model\AppUUID;
use Apisearch\Model\Index;
use Apisearch\Model\IndexUUID;
use Apisearch\Model\Token;
use Apisearch\Model\TokenUUID;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Query\GetIndices;
use React\Promise\PromiseInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class GetAppsController.
 */
class GetAppsController extends ControllerWithQueryBusAsGod
{
    /**
     * Get all apps.
     *
     * @return PromiseInterface
     */
    public function __invoke(): PromiseInterface
    {
        $appUUID = AppUUID::createById('*');

        return $this
            ->ask(new GetIndices(
                RepositoryReference::create(
                    $appUUID,
                    IndexUUID::createById('*')
                ),
                new Token(
                    TokenUUID::createById($this->godToken),
                    $appUUID
                )
            ))
            ->then(function (array $indices) {
                $apps = [];
                foreach ($indices as $index) {
                    /**
                     * @var Index
                     */
                    $appUUIDComposed = $index->getAppUUID()->composeUUID();
                    $indexUUIDComposed = $index->getUUID()->composeUUID();
                    if (!\array_key_exists($appUUIDComposed, $apps)) {
                        $apps[$appUUIDComposed] = [];
                    }

                    $apps[$appUUIDComposed][$indexUUIDComposed] = [
                        'ok' => $index->isOk(),
                        'items' => $index->getDocCount(),
                        'size' => $index->getSize(),
                    ];
                }

                return new JsonResponse($apps);
            });
    }
}
