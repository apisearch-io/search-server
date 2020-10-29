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

namespace Apisearch\Plugin\JWT\Domain;

use Apisearch\Exception\ForbiddenException;
use DomainException;
use Firebase\JWT\JWT;
use UnexpectedValueException;

/**
 * Class JWTBearerChecker.
 */
class JWTBearerChecker
{
    private string $privateKey;
    private array $allowedAlgorithms;

    /**
     * @param string $privateKey
     * @param array  $allowedAlgorithms
     * @param int    $ttl
     */
    public function __construct(
        string $privateKey,
        array $allowedAlgorithms,
        int $ttl
    ) {
        $this->privateKey = $privateKey;
        $this->allowedAlgorithms = $allowedAlgorithms;
        JWT::$leeway = $ttl;
    }

    /**
     * @param string $authorization
     *
     * @return array $payload
     *
     * @throws ForbiddenException
     */
    public function checkBearer(string $authorization)
    {
        $bearer = \trim(\str_replace('Bearer ', '', $authorization));

        try {
            $payload = JWT::decode($bearer, $this->privateKey, $this->allowedAlgorithms);
        } catch (UnexpectedValueException $exception) {
            throw new ForbiddenException();
        } catch (DomainException $exception) {
            throw new ForbiddenException();
        }

        return \json_decode(\json_encode($payload), true);
    }
}
