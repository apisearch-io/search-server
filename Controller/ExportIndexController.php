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

use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Query\ExportIndex;
use Apisearch\Server\Domain\Stream\ItemToArrayTransformerStream;
use Clue\React\NDJson\Encoder as NDJsonEncoder;
use Drift\CommandBus\Bus\QueryBus;
use function Drift\React\wait_for_stream_listeners;
use React\EventLoop\LoopInterface;
use React\Http\Response;
use React\Promise\PromiseInterface;
use React\Stream\ReadableStreamInterface;
use React\Stream\ThroughStream;
use React\Stream\Util;
use React\Stream\WritableStreamInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ExportIndexController.
 */
class ExportIndexController extends ControllerWithQueryBus
{
    /**
     * @var LoopInterface
     */
    private $loop;

    /**
     * Controller constructor.
     *
     * @param QueryBus      $queryBus
     * @param LoopInterface $loop
     */
    public function __construct(
        QueryBus $queryBus,
        LoopInterface $loop
    ) {
        parent::__construct($queryBus);
        $this->loop = $loop;
    }

    /**
     * Get tokens.
     *
     * @param Request $request
     *
     * @return PromiseInterface
     */
    public function __invoke(Request $request): PromiseInterface
    {
        $indexUUID = RequestAccessor::getIndexUUIDFromRequest($request);
        $repositoryReference = RepositoryReference::create(
            RequestAccessor::getAppUUIDFromRequest($request),
            $indexUUID
        );

        return $this
            ->ask(new ExportIndex($repositoryReference))
            ->then(function (ReadableStreamInterface $stream) {
                $responseStream = new ThroughStream();

                wait_for_stream_listeners($responseStream, $this->loop, 1, 1)
                    ->then(function (WritableStreamInterface $responseStream) use ($stream) {
                        $encoder = new NDJsonEncoder($responseStream);
                        $itemToArrayTransformer = new ItemToArrayTransformerStream($encoder);

                        $stream->pipe($itemToArrayTransformer);
                        Util::forwardEvents($itemToArrayTransformer, $stream, ['close']);
                    });

                return new Response(200, [
                    'Content-Type' => 'text/plain',
                ], $responseStream);
            });
    }
}
