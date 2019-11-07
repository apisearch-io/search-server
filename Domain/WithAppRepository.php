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

use Apisearch\Server\Domain\Repository\AppRepository\Repository as AppRepository;

/**
 * Class WithAppRepository.
 */
abstract class WithAppRepository
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
     * @param AppRepository $appRepository
     */
    public function __construct(AppRepository $appRepository)
    {
        $this->appRepository = $appRepository;
    }
}
