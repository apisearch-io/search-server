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

namespace Apisearch\Server\Domain\Command;

use Apisearch\Model\Item;
use Apisearch\Model\Token;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Repository\WithRepositoryReference;
use Apisearch\Server\Domain\AsynchronousableCommand;
use Apisearch\Server\Domain\CommandWithRepositoryReferenceAndToken;
use Apisearch\Server\Domain\IndexRequiredCommand;
use Apisearch\Server\Domain\LoggableCommand;
use Apisearch\Server\Domain\WriteCommand;

/**
 * Class IndexItems.
 */
class IndexItems extends CommandWithRepositoryReferenceAndToken implements WithRepositoryReference, WriteCommand, LoggableCommand, AsynchronousableCommand, IndexRequiredCommand
{
    /**
     * @var Item[]
     *
     * Items
     */
    private $items;

    /**
     * IndexCommand constructor.
     *
     * @param RepositoryReference $repositoryReference
     * @param Token               $token
     * @param Item[]              $items
     */
    public function __construct(
        RepositoryReference $repositoryReference,
        Token $token,
        array $items
    ) {
        parent::__construct(
            $repositoryReference,
            $token
        );

        $this->items = $items;
    }

    /**
     * Get Items.
     *
     * @return Item[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * To array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'repository_reference' => $this
                ->getRepositoryReference()
                ->compose(),
            'token' => $this
                ->getToken()
                ->toArray(),
            'items' => \array_map(function (Item $item) {
                return $item->toArray();
            }, $this->items),
        ];
    }

    /**
     * Create command from array.
     *
     * @param array $data
     *
     * @return self
     */
    public static function fromArray(array $data)
    {
        return new self(
            RepositoryReference::createFromComposed($data['repository_reference']),
            Token::createFromArray($data['token']),
            \array_map(function (array $item) {
                return Item::createFromArray($item);
            }, $data['items'])
        );
    }
}
