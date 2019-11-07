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

namespace Apisearch\Plugin\Security\Domain\Token;

use Apisearch\Exception\InvalidTokenException;
use Apisearch\Model\AppUUID;
use Apisearch\Model\IndexUUID;
use Apisearch\Model\Token;
use Apisearch\Plugin\Redis\Domain\RedisWrapper;
use Apisearch\Server\Domain\Token\TokenValidator;
use DateTime;
use React\Promise\FulfilledPromise;
use React\Promise\PromiseInterface;

/**
 * Class RequestsLimitTokenValidator.
 *
 * This validator uses a specific format, having two parts of how request limits
 * are defined. The number and the time margin
 * - 10/s => 10 requests per second
 * - 10000/i => 10000 requests per minute
 * - 10K/h => 10000 requests per hour
 * - 2M/m => 2 millions requests per month
 * - 2MM/y => 2 billions requests per month
 */
class RequestsLimitTokenValidator implements TokenValidator
{
    /**
     * @var RedisWrapper
     *
     * Redis wrapper
     */
    private $redisWrapper;

    /**
     * HttpReferrersTokenValidator constructor.
     *
     * @param RedisWrapper $redisWrapper
     */
    public function __construct(RedisWrapper $redisWrapper)
    {
        $this->redisWrapper = $redisWrapper;
    }

    /**
     * Validate token given basic fields.
     *
     * If is valid, return valid Token
     *
     * @param AppUUID   $appUUID
     * @param IndexUUID $indexUUID
     * @param Token     $token
     * @param string    $referrer
     * @param string    $routeName
     *
     * @return PromiseInterface<bool>
     */
    public function isTokenValid(
        Token $token,
        AppUUID $appUUID,
        IndexUUID $indexUUID,
        string $referrer,
        string $routeName
    ): PromiseInterface {
        $requestsLimit = $token->getMetadataValue('requests_limit', []);
        $now = new DateTime();
        $promise = new FulfilledPromise(true);
        $client = $this
            ->redisWrapper
            ->getClient();

        foreach ($requestsLimit as $element) {
            $parts = $this->getHitsAndTimePositionByData($element, $now);
            if (empty($parts)) {
                continue;
            }

            $key = sprintf(
                'token_requests_limit_%s_%s',
                $token
                    ->getTokenUUID()
                    ->composeUUID(),
                $parts[1]
            );

            $promise = $promise
                ->then(function () use ($client, $key) {
                    return $client->get($key);
                })
                ->then(function ($accesses) use ($token, $parts) {
                    $accesses = (int) $accesses;
                    if ($accesses >= $parts[0]) {
                        throw InvalidTokenException::createInvalidTokenPermissions($token->getTokenUUID()->composeUUID());
                    }
                })
                ->then(function () use ($client) {
                    $client->multi();
                })
                ->then(function () use ($client, $key) {
                    $client->incr($key);
                })
                ->then(function () use ($client, $key, $parts) {
                    if ($parts[2] > 0) {
                        $client->expire($key, $parts[2]);
                    }
                })
                ->then(function () use ($client) {
                    $client->exec();

                    return true;
                });
        }

        return $promise;
    }

    /**
     * Given a request limit definition, return an array of two positions
     * - The number as integer
     * - The redis position related to the time position.
     *
     * Return empty array when invalid definition
     *
     * @param string   $data
     * @param DateTime $dateTime
     *
     * @return array
     */
    public function getHitsAndTimePositionByData(
        string $data,
        DateTime $dateTime
    ) {
        $parts = explode('/', $data);
        if (1 === count($parts)) {
            $parts[1] = '';
        }

        list($number, $timeMargin) = $parts;
        preg_match('~(\d+)(K|MM|M)?~', $number, $match);
        $number = $match[1] ?? null;
        if (\is_null($number)) {
            return [];
        }

        if (isset($match[2])) {
            switch ($match[2]) {
                case 'K':
                    $number *= 1000;
                    break;
                case 'M':
                    $number *= 1000000;
                    break;
                case 'MM':
                    $number *= 1000000000;
                    break;
            }
        }

        $timekey = '';
        $secondsForExpire = -1;
        $dateInTimestamp = $dateTime->getTimestamp();
        switch ($timeMargin) {
            case 's':
                $timekey = $dateTime->format('Y-m-d\TH:i:s');
                $secondsForExpire = 1;
                break;
            case 'i':
                $timekey = $dateTime->format('Y-m-d\TH:i');
                $secondsForExpire = (new DateTime(sprintf(
                        '%s-%s-%s %s:%s:00',
                        $dateTime->format('Y'),
                        $dateTime->format('m'),
                        $dateTime->format('d'),
                        $dateTime->format('H'),
                        $dateTime->format('i')
                    )))
                        ->modify('+1 minute')
                        ->getTimestamp() - $dateInTimestamp;
                break;
            case 'h':
                $timekey = $dateTime->format('Y-m-d\TH');
                $secondsForExpire = (new DateTime(sprintf(
                        '%s-%s-%s %s:00:00',
                        $dateTime->format('Y'),
                        $dateTime->format('m'),
                        $dateTime->format('d'),
                        $dateTime->format('H')
                    )))
                        ->modify('+1 hour')
                        ->getTimestamp() - $dateInTimestamp;
                break;
            case 'd':
                $timekey = $dateTime->format('Y-m-d');
                $secondsForExpire = (clone $dateTime)
                        ->setTime(0, 0, 0)
                        ->modify('+1 day')
                        ->getTimestamp() - $dateInTimestamp;
                break;
            case 'm':
                $timekey = $dateTime->format('Y-m');
                $secondsForExpire = (clone $dateTime)
                        ->setTime(0, 0, 0)
                        ->modify('first day of next month')
                        ->getTimestamp() - $dateInTimestamp;
                break;
            case 'y':
                $timekey = $dateTime->format('Y');
                $secondsForExpire = (new DateTime(sprintf(
                        '%s-01-01 00:00:00',
                        $dateTime->format('Y')
                    )))
                        ->modify('+1 year')
                        ->getTimestamp() - $dateInTimestamp;
                break;
        }

        return [
            (int) $number,
            $timekey,
            ((int) $secondsForExpire) + 1,
        ];
    }
}
