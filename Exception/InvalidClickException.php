<?php


namespace Apisearch\Server\Exception;

use Apisearch\Exception\InvalidFormatException;

/**
 * Class InvalidClickException
 */
class InvalidClickException extends InvalidFormatException
{
    /**
     * Create exception
     */
    public static function create() : InvalidClickException
    {
        return new self('Invalid click. You should pass a valid user_id or a valid IP instead');
    }
}