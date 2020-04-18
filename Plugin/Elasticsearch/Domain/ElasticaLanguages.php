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

namespace Apisearch\Plugin\Elasticsearch\Domain;

/**
 * Class ElasticaLanguages.
 */
class ElasticaLanguages
{
    /**
     * Get stopwords language by language iso.
     *
     * @param string|null $iso
     *
     * @return string
     */
    public static function getStopwordsLanguageByIso(? string $iso): ? string
    {
        return (string) ([
            '_' => '_arabic_',
            '_' => '_armenian_',
            'ba' => '_basque_',
            'br' => '_brazilian_',
            '_' => '_bulgarian_',
            'ca' => '_catalan_',
            '_' => '_czech_',
            '_' => '_danish_',
            '_' => '_dutch_',
            'en' => '_english_',
            '_' => '_finnish_',
            'fr' => '_french_',
            'ga' => '_galician_',
            '_' => '_german_',
            'gr' => '_greek_',
            '_' => '_hindi_',
            '_' => '_hungarian_',
            '_' => '_indonesian_',
            '_' => '_irish_',
            'it' => '_italian_',
            '_' => '_latvian_',
            '_' => '_norwegian_',
            '_' => '_persian_',
            '_' => '_portuguese_',
            '_' => '_romanian_',
            'ru' => '_russian_',
            '_' => '_sorani_',
            'es' => '_spanish_',
            '_' => '_swedish_',
            '_' => '_thai_',
            '_' => '_turkish_',
        ][$iso] ?? null);
    }

    /**
     * Get stemmer language by language iso.
     *
     * @param string|null $iso
     *
     * @return string|null
     */
    public static function getStemmerLanguageByIso(? string $iso): ? string
    {
        $value = [
            '_' => 'arabic',
            '_' => 'armenian',
            'ba' => 'basque',
            'br' => 'brazilian',
            '_' => 'bulgarian',
            'ca' => 'catalan',
            '_' => 'czech',
            '_' => 'danish',
            '_' => 'dutch',
            'en' => 'english',
            '_' => 'finnish',
            'fr' => 'light_french',
            'ga' => 'galician',
            '_' => 'light_german',
            'gr' => 'greek',
            '_' => 'hindi',
            '_' => 'hungarian',
            '_' => 'indonesian',
            '_' => 'irish',
            'it' => 'light_italian',
            '_' => 'sorani',
            '_' => 'latvian',
            '_' => 'lithuanian',
            '_' => 'norwegian',
            '_' => 'light_nynorsk',
            '_' => 'portuguese',
            '_' => 'romanian',
            'ru' => 'russian',
            'es' => 'light_spanish',
            '_' => 'swedish',
            '_' => 'turkish',
        ][$iso] ?? null;

        return \is_null($value)
            ? $value
            : (string) $value;
    }

    /**
     * Get languages.
     */
    public static function getLanguages()
    {
        return [
            'ba',
            'br',
            'ca',
            'en',
            'fr',
            'ga',
            'gr',
            'it',
            'ru',
            'es',
        ];
    }
}
