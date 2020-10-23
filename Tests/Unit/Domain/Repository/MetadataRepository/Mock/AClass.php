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

namespace Apisearch\Server\Tests\Unit\Domain\Repository\MetadataRepository\Mock;

use Apisearch\Model\HttpTransportable;

/**
 * Class AClass.
 */
class AClass implements HttpTransportable
{
    private string $value;

    /**
     * @param string $value
     */
    public function __construct(string $value)
    {
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return ['value' => $this->getValue()];
    }

    /**
     * @param array $array
     *
     * @return HttpTransportable
     */
    public static function createFromArray(array $array)
    {
        return new self($array['value']);
    }
}
