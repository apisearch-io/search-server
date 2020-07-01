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
use Apisearch\Server\Domain\Format\StandardFormatTransformer;

/**
 * Class StandardFormatTransformerTest.
 */
class StandardFormatTransformerTest extends FormatTransformerTest
{
    /**
     * @return FormatTransformer
     */
    protected function getFormatTransformer(): FormatTransformer
    {
        return new StandardFormatTransformer();
    }

    /**
     * @return string
     */
    protected function getLine(): string
    {
        return \implode('|', [
            '123',
            'test',
            't1',
            'd1',
            'ed1 \&& and & another \| % \%% chars',
            'l1',
            '',
            'b1',
            '123 && rf1 && arf1 && sv2',
            'sug1 && sug2',
            'id##1~~name##cat1~~level##1 && id##2~~level##2~~name##2',
            'id##3~~name##cat3 \&& what~~level##3 && id##4~~level##4~~name##4',
            'rf1',
            'arf1',
            '10',
            '8',
            '',
            '100',
            '1',
            '',
            \implode(' %% ', [
                '[ms]name=n1',
                '[ms]another_extra_description=aed1',
                '[mis]s_field2=sv2',
                '[mj]structure1=["1","2","3"]',
                'ampersand=One & Two \&& Three',
                'chars=One % Two \%% \| Three',
                '[is]s_field1=sv1',
                '[ij]an_array=["val1","val2"]',
                '[ij]a_complex_array=[{"val":1,"price":2}]',
                '[ij]a_multi_complex_array=[{"val":1,"price":2},{"val":3,"price":4}]',
            ]),
        ]);
    }

    /**
     * @return string
     */
    protected function getAlternativeLine(): string
    {
        return \implode('|', [
            '123',
            'test',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '45.6,123',
            '',
        ]);
    }

    /**
     * @return bool
     */
    protected function optimizes(): bool
    {
        return true;
    }
}
