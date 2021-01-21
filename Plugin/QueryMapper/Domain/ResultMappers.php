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

namespace Apisearch\Plugin\QueryMapper\Domain;

/**
 * Class ResultMappers.
 */
class ResultMappers
{
    /**
     * @var ResultMapper[]
     */
    private array $resultMappers = [];

    /**
     * Add result mapper.
     *
     * @param ResultMapper $resultMapper
     *
     * @return void
     */
    public function addResultMapper(ResultMapper $resultMapper): void
    {
        $this->resultMappers[] = $resultMapper;
    }

    /**
     * Find result mapper by token.
     *
     * @param string $token
     *
     * @return ResultMapper|null
     */
    public function findResultMapperByToken(string $token): ? ResultMapper
    {
        foreach ($this->resultMappers as $resultMapper) {
            if (\in_array($token, $resultMapper->getTokens())) {
                return $resultMapper;
            }
        }

        return null;
    }
}
