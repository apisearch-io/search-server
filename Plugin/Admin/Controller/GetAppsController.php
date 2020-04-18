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
use Apisearch\Server\Controller\ControllerWithQueryBus;
use Apisearch\Server\Domain\Query\GetIndices;
use Drift\CommandBus\Bus\QueryBus;
use React\Promise\PromiseInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class GetAppsController.
 */
class GetAppsController extends ControllerWithQueryBus
{
    /**
     * @var string
     */
    private $godToken;

    /**
     * @param QueryBus $queryBus
     * @param string   $godToken
     */
    public function __construct(
        QueryBus $queryBus,
        string $godToken
    ) {
        parent::__construct($queryBus);
        $this->godToken = $godToken;
    }

    /**
     * Get all apps.
     *
     * @return PromiseInterface
     */
    public function __invoke()
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
