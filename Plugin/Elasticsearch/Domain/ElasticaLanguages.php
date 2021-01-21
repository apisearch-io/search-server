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
     * Get stop words language by language iso.
     *
     * @param string|null $iso
     *
     * @return string|null
     */
    public static function getStopWordsLanguageByIso(? string $iso): ? string
    {
        if (\is_null($iso)) {
            return null;
        }

        $value = ([
            'ar' => '_arabic_',
            'hy' => '_armenian_',
            'ba' => '_basque_',
            'br' => '_brazilian_',
            'bg' => '_bulgarian_',
            'ca' => '_catalan_',
            'cs' => '_czech_',
            'da' => '_danish_',
            'nl' => '_dutch_',
            'en' => '_english_',
            'fi' => '_finnish_',
            'fr' => '_french_',
            'ga' => '_galician_',
            'de' => '_german_',
            'gr' => '_greek_',
            'hi' => '_hindi_',
            'hu' => '_hungarian_',
            'id' => '_indonesian_',
            'ie' => '_irish_',
            'it' => '_italian_',
            'lv' => '_latvian_',
            'lt' => '_lithuanian_',
            'nb' => '_norwegian_',
            'pt' => '_portuguese_',
            'ro' => '_romanian_',
            'ru' => '_russian_',
            'ckb' => '_sorani_',
            'es' => '_spanish_',
            'sv' => '_swedish_',
            'th' => '_thai_',
            'tr' => '_turkish_',
        ][$iso] ?? null);

        return \is_null($value)
            ? $value
            : (string) $value;
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
        if (\is_null($iso)) {
            return null;
        }

        $value = [
            'ar' => 'arabic',
            'hy' => 'armenian',
            'ba' => 'basque',
            'br' => 'brazilian',
            'bg' => 'bulgarian',
            'ca' => 'catalan',
            'cs' => 'czech',
            'da' => 'danish',
            'nl' => 'dutch',
            'en' => 'english',
            'fi' => 'finnish',
            'fr' => 'light_french',
            'ga' => 'galician',
            'de' => 'light_german',
            'gr' => 'greek',
            'hi' => 'hindi',
            'hu' => 'hungarian',
            'id' => 'indonesian',
            'ie' => 'irish',
            'it' => 'light_italian',
            'ckb' => 'sorani',
            'lv' => 'latvian',
            'lt' => 'lithuanian',
            'nb' => 'norwegian',
            'pt' => 'portuguese',
            'ro' => 'romanian',
            'ru' => 'russian',
            'es' => 'light_spanish',
            'sv' => 'swedish',
            'tr' => 'turkish',
        ][$iso] ?? null;

        return \is_null($value)
            ? $value
            : (string) $value;
    }
}
