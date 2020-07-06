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

namespace Apisearch\Server\Domain\CommandHandler;

use Apisearch\Server\Domain\Command\PostClick;
use Apisearch\Server\Domain\Model\InteractionType;
use Apisearch\Server\Domain\Repository\InteractionRepository\InteractionRepository;
use Apisearch\Server\Exception\InvalidClickException;
use DateTime;
use React\Promise\PromiseInterface;

/**
 * Class PostClickHandler.
 */
class PostClickHandler
{
    /**
     * @var InteractionRepository
     */
    private $interactionRepository;

    /**
     * @param InteractionRepository $interactionRepository
     */
    public function __construct(InteractionRepository $interactionRepository)
    {
        $this->interactionRepository = $interactionRepository;
    }

    /**
     * @param PostClick $putClick
     *
     * @return PromiseInterface
     */
    public function handle(PostClick $putClick): PromiseInterface
    {
        $origin = $putClick->getOrigin();
        $userUUID = $putClick->getUserUUID() ?? $origin->getIp();

        if (empty($userUUID)) {
            throw InvalidClickException::create();
        }

        return $this
            ->interactionRepository
            ->registerInteraction(
                $putClick->getRepositoryReference(),
                $userUUID,
                $putClick->getItemUUID(),
                $putClick->getPosition(),
                $origin,
                InteractionType::CLICK,
                new DateTime()
            );
    }
}
