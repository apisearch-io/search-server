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

namespace Apisearch\Server\Domain;

use Apisearch\Server\Domain\Repository\Repository\Repository;
use Drift\EventBus\Bus\EventBus;

/**
 * Class WithRepositoryAndEventPublisher.
 */
abstract class WithRepositoryAndEventPublisher extends WithEventBus
{
    protected Repository $repository;

    /**
     * QueryHandler constructor.
     *
     * @param Repository $repository
     * @param EventBus   $eventBus
     */
    public function __construct(
        Repository $repository,
        EventBus $eventBus
    ) {
        $this->repository = $repository;
        parent::__construct($eventBus);
    }
}
