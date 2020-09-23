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

namespace Apisearch\Server\Tests\Unit\Domain\Format;

use Apisearch\Server\Domain\Format\FormatTransformer;
use Apisearch\Server\Domain\Format\SourceFormatTransformer;

/**
 * Class SourceFormatTransformerTest.
 */
class SourceFormatTransformerTest extends FormatTransformerTest
{
    /**
     * @return FormatTransformer
     */
    protected function getFormatTransformer(): FormatTransformer
    {
        return new SourceFormatTransformer();
    }

    /**
     * @return string
     */
    protected function getLine(): string
    {
        return \implode('|', [
            '123~test',
            '{"name":"n1","title":"t1","description":"d1","extra_description":"ed1 && and & another \\\\| % %% chars","another_extra_description":"aed1","link":"l1","brand":"b1","s_field2":"sv2ã","structure1":["1","2","3"],"ampersand":"One & Two && Three","chars":"One % Two %% \\\\| Three"}',
            '{"categories":[{"id":"1","name":"cat1","level":"1"},{"id":"2","level":"2"},{"name":"cat2","level":"2"},{"id":"invalid"}],"alternative_categories":[{"id":"3","name":"cat3 && what","level":"3"},{"id":"4","level":"4"},{"name":"cat4","level":"4"},{"id":"invalid"}],"reference":"rf1","alternative_reference":"arf1","price":10,"reduced_price":8,"reduced_price_percent":0,"stock":100,"on_offer":true,"s_field1":"sv1","s_field2":"sv2ã","an_array":["val1","val2"],"a_complex_array":[{"val":1,"price":2,"char":"ã"}],"a_multi_complex_array":[{"val":1,"price":2},{"val":3,"price":4}]}',
            '{"name":"n1","title":"t1","description":"d1","extra_description":"ed1 && and & another \\\\| % %% chars","another_extra_description":"aed1","brand":"b1","s_field1":"sv1","s_field2":"sv2ã"}',
            '["123","rf1","arf1","sv2ã"]',
            '["sug1","sug2"]',
            '',
        ]);
    }

    /**
     * @return string
     */
    protected function getAlternativeLine(): string
    {
        return \implode('|', [
            '123~test',
            '[]',
            '[]',
            '[]',
            '[]',
            '[]',
            '{"lat":45.6,"lon":123}',
        ]);
    }

    /**
     * @return bool
     */
    protected function optimizes(): bool
    {
        return false;
    }
}
