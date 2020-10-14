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

namespace Apisearch\Server\Tests\Functional\Domain\Repository;

use Apisearch\Query\Query;

/**
 * Class SuggestTest.
 */
trait SuggestTest
{
    /**
     * Test basic suggest.
     */
    public function testBasicSuggest()
    {
        $results = $this->query(
            Query::create('barc')
                ->enableSuggestions()
                ->disableAggregations()
        );

        $this->assertArraysEquals(
            ['Barcelona', 'barce'],
            $results->getSuggests()
        );
    }

    /**
     * Test basic suggest same text.
     */
    public function testSuggestSameThanQuery()
    {
        $results = $this->query(
            Query::create('Barce')
                ->enableSuggestions()
                ->disableAggregations()
        );

        $this->assertArraysEquals(
            ['Barcelona'],
            $results->getSuggests()
        );
    }

    /**
     * Test basic suggest same text.
     */
    public function testNumberOfSuggestions()
    {
        $results = $this->query(
            Query::create('ba')
                ->enableSuggestions()
                ->disableAggregations()
                ->setMetadataValue('number_of_suggestions', 2)
        );

        $this->assertArraysEquals(
            ['Badalona', 'bar'],
            $results->getSuggests()
        );

        $results = $this->query(
            Query::create('ba')
                ->enableSuggestions()
                ->disableAggregations()
                ->setMetadataValue('number_of_suggestions', 3)
        );

        $this->assertArraysEquals(
            ['Badalona', 'bar', 'barce'],
            $results->getSuggests()
        );

        $results = $this->query(
            Query::create('bar')
                ->enableSuggestions()
                ->disableAggregations()
                ->setMetadataValue('number_of_suggestions', 2)
        );

        $this->assertArraysEquals(
            ['Barcelona', 'barce'],
            $results->getSuggests()
        );
    }
}
