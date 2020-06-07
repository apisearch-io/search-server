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
        return $this
            ->interactionRepository
            ->registerInteraction(
                $putClick->getRepositoryReference(),
                $putClick->getUserUUID(),
                $putClick->getItemUUID(),
                $putClick->getOrigin(),
                InteractionType::CLICK,
                new DateTime()
            );
    }
}
