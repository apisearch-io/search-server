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

namespace Apisearch\Plugin\QueryMapper\Tests\Functional;

use Apisearch\Http\Http;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ResultMappedTest.
 */
class ResultMappedTest extends QueryMapperFunctionalTest
{
    /**
     * Basic usage.
     */
    public function testBasicUsage()
    {
        $request = new Request();
        $request->setMethod('GET');
        $request->server->set('REQUEST_URI', '/v1/'.static::$appId);
        $request->headers->set(Http::TOKEN_ID_HEADER, static::$readonlyToken);
        $promise = $this
            ->get('kernel')
            ->handleAsync($request)
            ->then(function (Response $response) {
                $this->assertEquals([
                    'item_nb' => 5,
                    'item_ids' => [
                        '1~product',
                        '2~product',
                        '3~book',
                        '4~bike',
                        '5~gum',
                    ],
                ], \json_decode($response->getContent(), true));
            });

        $this->await($promise);
    }
}
