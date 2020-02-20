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

namespace Apisearch\Server\Domain\Event;

use Apisearch\User\Interaction;

/**
 * Class InteractionWasAdded.
 */
final class InteractionWasAdded extends DomainEvent
{
    /**
     * @var Interaction
     *
     * Interaction
     */
    private $interaction;

    /**
     * ItemsWasIndexed constructor.
     */
    public function __construct(Interaction $interaction)
    {
        parent::__construct();
        $this->interaction = $interaction;
    }

    /**
     * @return Interaction
     */
    public function getInteraction(): Interaction
    {
        return $this->interaction;
    }

    /**
     * to array payload.
     *
     * @return array
     */
    public function toArrayPayload(): array
    {
        return [
            'interaction' => $this
                ->interaction
                ->toArray(),
        ];
    }
}
