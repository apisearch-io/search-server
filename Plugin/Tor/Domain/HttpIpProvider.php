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

namespace Apisearch\Plugin\Tor\Domain;

use React\Http\Browser;
use React\Promise\PromiseInterface;
use RingCentral\Psr7\Response;

class HttpIpProvider implements IpProvider
{
    private Browser $browser;

    /**
     * @param Browser $browser
     */
    public function __construct(Browser $browser)
    {
        $this->browser = $browser;
    }

    public function get(string $source): PromiseInterface
    {
        return $this
            ->browser
            ->get($source)
            ->then(function (Response $response) {
                return $response->getBody()->getContents();
            })
            ->otherwise(function (\Exception $exception) {
                return '';
            });
    }
}
