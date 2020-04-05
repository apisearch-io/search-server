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

use Apisearch\Model\TokenUUID;

/**
 * Class TokenWasDeleted.
 */
final class TokenWasDeleted extends DomainEvent
{
    /**
     * @var TokenUUID
     *
     * Token UUID
     */
    private $tokenUUID;

    /**
     * ItemsWasIndexed constructor.
     */
    public function __construct(TokenUUID $tokenUUID)
    {
        parent::__construct();
        $this->tokenUUID = $tokenUUID;
    }

    /**
     * @return TokenUUID
     */
    public function getTokenUUID(): TokenUUID
    {
        return $this->tokenUUID;
    }

    /**
     * to array payload.
     *
     * @return array
     */
    public function toArrayPayload(): array
    {
        return [
            'token' => $this
                ->tokenUUID
                ->toArray(),
        ];
    }
}
