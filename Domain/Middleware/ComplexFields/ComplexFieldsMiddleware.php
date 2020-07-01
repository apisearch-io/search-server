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

use Apisearch\Model\Item;
use Apisearch\Server\Domain\Repository\MetadataRepository\MetadataRepository;
use Drift\EventBus\Bus\EventBus;
use React\EventLoop\LoopInterface;

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
     * @var LoopInterface
     */
    protected $loop;

    /**
     * @var string
     */
    const COMPLEX_FIELDS_METADATA = 'complex_fields';

    /**
     * @param MetadataRepository $metadataRepository
     * @param EventBus           $eventBus
     * @param LoopInterface      $loop
     */
    public function __construct(
        MetadataRepository $metadataRepository,
        EventBus $eventBus,
        LoopInterface $loop
    ) {
        $this->metadataRepository = $metadataRepository;
        $this->eventBus = $eventBus;
        $this->loop = $loop;
    }

    /**
     * @param Item  $item
     * @param array $complexFields
     */
    protected function exportComplexFieldsItem(
        Item $item,
        array $complexFields
    ) {
        $metadata = $item->getMetadata();
        $indexedMetadata = $item->getIndexedMetadata();

        foreach ($complexFields as $complexField) {
            if (\array_key_exists($complexField, $metadata)) {
                $indexedMetadata[$complexField] = \json_decode($metadata[$complexField], true);
                unset($metadata[$complexField]);
            }

            unset($indexedMetadata[$complexField.'_id']);
            unset($indexedMetadata[$complexField.'_data']);
        }

        $item->setMetadata($metadata);
        $item->setIndexedMetadata($indexedMetadata);
    }
}
