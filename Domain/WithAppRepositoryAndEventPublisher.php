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
use Apisearch\Server\Domain\Repository\AppRepository\Repository as AppRepository;

/**
 * Class WithAppRepositoryAndEventPublisher.
 */
abstract class WithAppRepositoryAndEventPublisher extends WithEventPublisher
{
    /**
     * @var AppRepository
     *
     * App Repository
     */
    protected $appRepository;

    /**
     * QueryHandler constructor.
     *
     * @param AppRepository  $appRepository
     * @param EventPublisher $eventPublisher
     */
    public function __construct(
        AppRepository $appRepository,
        EventPublisher $eventPublisher
    ) {
        $this->appRepository = $appRepository;
        parent::__construct($eventPublisher);
    }
}
