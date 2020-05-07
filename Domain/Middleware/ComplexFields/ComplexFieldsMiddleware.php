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

namespace Apisearch\Server\Domain\Middleware\ComplexFields;

use Apisearch\Server\Domain\Repository\MetadataRepository\MetadataRepository;
use Drift\EventBus\Bus\EventBus;

/**
 * Class ComplexFieldsMiddleware.
 */
abstract class ComplexFieldsMiddleware
{
    /**
     * @var MetadataRepository
     */
    protected $metadataRepository;

    /**
     * @var EventBus
     */
    protected $eventBus;

    /**
     * @var string
     */
    const COMPLEX_FIELDS_METADATA = 'complex_fields';

    /**
     * @param MetadataRepository $metadataRepository
     * @param EventBus           $eventBus
     */
    public function __construct(
        MetadataRepository $metadataRepository,
        EventBus $eventBus
    ) {
        $this->metadataRepository = $metadataRepository;
        $this->eventBus = $eventBus;
    }
}
