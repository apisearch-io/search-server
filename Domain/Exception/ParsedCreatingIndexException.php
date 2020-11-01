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

namespace Apisearch\Server\Domain\Exception;

use Apisearch\Exception\ResourceExistsException;

/**
 * Class ParsedCreatingIndexException.
 */
class ParsedCreatingIndexException
{
    public static $status;

    /**
     * Index is not available.
     *
     * @param string $message
     *
     * @return ResourceExistsException
     */
    public static function parse(string $message): ResourceExistsException
    {
        if (1 === \preg_match(
                '#\[apisearch_item_(?P<app_id>.*?)_(?P<index_name>.*?)\/.*\] already exists#i',
                $message,
                $match)) {
            /*
             * Sample Response
             * index [apisearch_item_123456_test_products/dHJefTMIR4y1e2hSTee6MQ] already exists [index: apisearch_item_123456_test_products]
             */
            $parsedMessage = \sprintf('Error while creating index. "%s" index is already exists for "%s" app.',
                $match['index_name'],
                $match['app_id']
            );

            return new ResourceExistsException($parsedMessage);
        }

        return new ResourceExistsException($message);
    }
}
