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

use Apisearch\Model\ItemUUID;
use Apisearch\Server\Domain\Model\Origin;

/**
 * Class QueryWasMade.
 */
final class QueryWasMade extends DomainEvent
{
    private string $queryText;
    private int $size;
    private array $itemsUUID;
    private ?string $userId;
    private array $parameters;
    private Origin $origin;
    private string $querySerialized;
    private int $cost;

    /**
     * QueryWasMade constructor.
     *
     * @param string      $queryText
     * @param int         $size
     * @param ItemUUID[]  $itemsUUID
     * @param string|null $userId
     * @param string      $querySerialized
     * @param array       $parameters
     * @param Origin      $origin
     * @param int         $cost
     */
    public function __construct(
        string $queryText,
        int $size,
        array $itemsUUID,
        ? string $userId,
        string $querySerialized,
        Origin $origin,
        array $parameters = [],
        int $cost = -1
    ) {
        parent::__construct();
        $this->queryText = $queryText;
        $this->size = $size;
        $this->itemsUUID = $itemsUUID;
        $this->userId = $userId;
        $this->querySerialized = $querySerialized;
        $this->parameters = $parameters;
        $this->origin = $origin;
        $this->cost = $cost;
    }

    /**
     * @return string
     */
    public function getQueryText(): string
    {
        return $this->queryText;
    }

    /**
     * @return int
     */
    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * @return ItemUUID[]
     */
    public function getItemsUUID(): array
    {
        return $this->itemsUUID;
    }

    /**
     * @return string|null
     */
    public function getUserId(): ?string
    {
        return $this->userId;
    }

    /**
     * @return string
     */
    public function getQuerySerialized(): string
    {
        return $this->querySerialized;
    }

    /**
     * Get parameters.
     *
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @return Origin
     */
    public function getOrigin(): Origin
    {
        return $this->origin;
    }

    /**
     * @return int
     */
    public function getCost(): int
    {
        return $this->cost;
    }

    /**
     * to array payload.
     *
     * @return array
     */
    public function toArrayPayload(): array
    {
        return [
            'q' => $this->queryText,
            'q_empty' => empty($this->queryText),
            'q_length' => \strlen($this->queryText),
            'size' => $this->size,
            'item_uuid' => \array_values(
                \array_map(function (ItemUUID $itemUUID) {
                    return $itemUUID->composeUUID();
                }, $this->itemsUUID)
            ),
            'result_length' => \count($this->itemsUUID),
            'user_id' => $this->userId,
            'query_serialized' => $this->querySerialized,
            'cost' => $this->cost,
        ];
    }
}
