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

use Apisearch\Model\IndexUUID;

/**
 * Class IndexWasDeleted.
 */
final class IndexWasDeleted extends DomainEvent
{
    private IndexUUID $indexUUID;

    /**
     * IndexWasConfigured constructor.
     *
     * @param IndexUUID $indexUUID
     */
    public function __construct(IndexUUID $indexUUID)
    {
        parent::__construct();
        $this->indexUUID = $indexUUID;
    }

    /**
     * to array payload.
     *
     * @return array
     */
    public function toArrayPayload(): array
    {
        return [
            'index_uuid' => $this
                ->indexUUID
                ->composeUUID(),
        ];
    }
}
