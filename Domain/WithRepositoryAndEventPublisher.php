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

use Apisearch\Server\Domain\EventPublisher\EventPublisher;
use Apisearch\Server\Domain\Repository\Repository\Repository;

/**
 * Class WithRepositoryAndEventPublisher.
 */
abstract class WithRepositoryAndEventPublisher extends WithEventPublisher
{
    /**
     * @var Repository
     *
     * Repository
     */
    protected $repository;

    /**
     * QueryHandler constructor.
     *
     * @param Repository     $repository
     * @param EventPublisher $eventPublisher
     */
    public function __construct(
        Repository $repository,
        EventPublisher $eventPublisher
    ) {
        $this->repository = $repository;
        parent::__construct($eventPublisher);
    }
}
