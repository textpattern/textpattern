<?php

/*
 * Textpattern Content Management System
 * http://textpattern.com
 *
 * Copyright (C) 2016 The Textpattern Development Team
 *
 * This file is part of Textpattern.
 *
 * Textpattern is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation, version 2.
 *
 * Textpattern is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Textpattern. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Handles locales.
 *
 * <code>
 * echo Txp::get('Textpattern\L10n\Locale')->setLocale(LC_ALL, 'da-dk')->getLocale();
 * </code>
 *
 * @since   4.6.0
 * @package L10n
 */

namespace Textpattern\L10n;

class Locale
{
    /**
     * An array of locale identifiers.
     *
     * @var array
     */

    protected $locales = array(
        'ar-dz' => array('ar_DZ.UTF-8', 'ar_DZ', 'ara', 'ar', 'arabic', 'ar_DZ.ISO_8859-6', 'Arabic_Saudi Arabia.1256'),
        'bg-bg' => array('bg_BG.UTF-8', 'bg_BG', 'bg', 'bul', 'bulgarian', 'bg_BG.ISO_8859-5', 'Bulgarian_Bulgaria.1251'),
        'bs-ba' => array('bs_BA.UTF-8', 'bs_BA', 'bs', 'bos', 'bosnian'),
        'ca-es' => array('ca_ES.UTF-8', 'ca_ES', 'cat', 'ca', 'catalan', 'ca_ES.ISO_8859-1', 'Catalan_Spain.1252'),
        'cs-cz' => array('cs_CZ.UTF-8', 'cs_CZ', 'ces', 'cze', 'cs', 'csy', 'czech', 'cs_CZ.ISO_8859-2', 'Czech_Czech Republic.1250'),
        'da-dk' => array('da_DK.UTF-8', 'da_DK', 'da', 'dan', 'danish', 'da_DK.ISO_8859-1', 'Danish_Denmark.1252'),
        'de-de' => array('de_DE.UTF-8', 'de_DE', 'de', 'deu', 'german', 'de_DE.ISO_8859-1', 'de_DE.ISO_8859-16', 'German_Germany.1252'),
        'el-gr' => array('el_GR.UTF-8', 'el_GR', 'el', 'gre', 'greek', 'el_GR.ISO_8859-7', 'Greek_Greece.1253'),
        'en-gb' => array('en_GB.UTF-8', 'en_GB', 'en_UK', 'eng', 'en', 'english-uk', 'english', 'en_GB.ISO_8859-1', 'C', 'English_UK.1252'),
        'en-us' => array('en_US.UTF-8', 'en_US', 'english-us', 'eng', 'en', 'english', 'en_US.ISO_8859-1', 'English_USA.1252'),
        'es-es' => array('es_ES.UTF-8', 'es_ES', 'esp', 'spanish', 'es_ES.ISO_8859-1', 'Spanish_Spain.1252'),
        'et-ee' => array('et_EE.UTF-8', 'et_EE', 'et', 'est', 'estonian', 'et_EE.ISO_8859-1', 'et_EE.ISO_8859-15', 'Estonian_Estonia.1257'),
        'fa-ir' => array('fa_IR.UTF-8', 'fa_IR', 'fa', 'persian', 'per', 'fas', 'farsi', 'Farsi_Iran.1256'),
        'fi-fi' => array('fi_FI.UTF-8', 'fi_FI', 'fin', 'fi', 'finnish', 'fi_FI.ISO_8859-1', 'fi_FI.ISO-8859-15', 'fi_FI.ISO_8859-16', 'Finnish_Finland.1252'),
        'fr-fr' => array('fr_FR.UTF-8', 'fr_FR', 'fra', 'fre', 'fr', 'french', 'fr_FR.ISO_8859-1', 'fr_FR.ISO-8859-15', 'fr_FR.ISO_8859-16', 'French_France.1252'),
        'gl-gz' => array('gl_GZ.UTF-8', 'gl_GZ', 'glg', 'gl'),
        'gl-es' => array('gl_ES.UTF-8', 'gl_ES', 'gle', 'gl', 'galician', 'galleco', 'gl_ES.ISO_8859-1', 'Galician_Spain.1252'),
        'he-il' => array('he_IL.UTF-8', 'he_IL', 'heb', 'he', 'hebrew', 'he_IL.ISO_8859-8', 'Hebrew_Israel.1255'),
        'hr-hr' => array('hr_HR.UTF-8', 'hr_HR', 'hr', 'hrv', 'croatian', 'hr_HR.ISO_8859-2', 'hr_HR.ISO_8859-16', 'Croatian_Croatia.1250'),
        'hu-hu' => array('hu_HU.UTF-8', 'hu_HU', 'hun', 'hu', 'hungarian', 'hu_HU.ISO_8859-2', 'hu_HU.ISO_8859-16', 'Hungarian_Hungary.1250'),
        'id-id' => array('id_ID.UTF-8', 'id_ID', 'id', 'ind', 'indonesian', 'id_ID.ISO_8859-1', 'Indonesian_indonesia.1252'),
        'is-is' => array('is_IS.UTF-8', 'is_IS', 'is', 'ice', 'isl', 'icelandic', 'is_IS.ISO_8859-1', 'Icelandic_Iceland.1252'),
        'it-it' => array('it_IT.UTF-8', 'it_IT', 'it', 'ita', 'italian', 'it_IT.ISO_8859-1', 'it_IT.ISO_8859-16', 'Italian_Italy.1252'),
        'ja-jp' => array('ja_JP.UTF-8', 'ja_JP', 'ja', 'jpn', 'japanese', 'Japanese_Japan.932'),
        'ko-kr' => array('ko_KR.UTF-8', 'ko_KR', 'ko', 'kor', 'korean', 'Korean_Korea.949'),
        'lt-lt' => array('lt_LT.UTF-8', 'lt_LT', 'lt', 'lit', 'lithuanian', 'lt_LT.ISO_8859-4', 'Lithuanian_Lithuania.1257'),
        'lv-lv' => array('lv_LV.UTF-8', 'lv_LV', 'lv', 'lav', 'latvian', 'lv_LV.ISO_8859-4', 'Latvian_Latvia.1257'),
        'nl-nl' => array('nl_NL.UTF-8', 'nl_NL', 'dut', 'nla', 'nl', 'nld', 'dutch', 'nl_NL.ISO_8859-1', 'Dutch_Netherlands.1252'),
        'nn-no' => array('nn_NO.UTF-8', 'nn_NO', 'no', 'nn', 'nor', 'nno', 'nb', 'nob', 'norwegian', 'no_NO.ISO_8859-1', 'Norwegian_Norway.1252'),
        'no-no' => array('no_NO.UTF-8', 'no_NO', 'no', 'nor', 'norwegian', 'no_NO.ISO_8859-1', 'Norwegian_Norway.1252'),
        'pl-pl' => array('pl_PL.UTF-8', 'pl_PL', 'pl', 'pol', 'polish', 'po_PO.ISO_8859-2', 'Polish_Poland.1250'),
        'pt-br' => array('pt_BR.UTF-8', 'pt_BR', 'pt', 'ptb', 'portuguese-brazil', 'Portuguese_Brazil.1252'),
        'pt-pt' => array('pt_PT.UTF-8', 'pt_PT', 'por', 'portuguese', 'pt_PT.ISO_8859-1', 'Portuguese_Portugal.1252'),
        'ro-ro' => array('ro_RO.UTF-8', 'ro_RO', 'ron', 'rum', 'ro', 'romanian', 'ro_RO.ISO_8859-2', 'ro_RO.ISO_8859-16', 'Romanian_Romania.1250'),
        'ru-ru' => array('ru_RU.UTF-8', 'ru_RU', 'ru', 'rus', 'russian', 'ru_RU.ISO_8859-5', 'Russian_Russia.1251'),
        'sk-sk' => array('sk_SK.UTF-8', 'sk_SK', 'sk', 'slo', 'slk', 'slovak', 'sk_SK.ISO_8859-1', 'Slovak_Slovakia.1250'),
        'sr-rs' => array('sr-RS.UTF-8', 'sr_RS', 'sr', 'sp', 'srb', 'serbian', 'Serbian (Cyrillic)_Serbia and Montenegro.1251'),
        'sr-sp' => array('sr-SP.UTF-8', 'sr_SP', 'sr', 'sp', 'srb', 'serbian', 'Serbian (Cyrillic)_Serbia and Montenegro.1251'),
        'sv-se' => array('sv_SE.UTF-8', 'sv_SE', 'sv', 'swe', 'sve', 'swedish', 'sv_SE.ISO_8859-1', 'Swedish_Sweden.1252'),
        'th-th' => array('th_TH.UTF-8', 'th_TH', 'th', 'tha', 'thai', 'th_TH.ISO_8859-11', 'Thai_Thailand.874'),
        'tr-tr' => array('tr_TR.UTF-8', 'tr_TR', 'tr', 'tur', 'turkish', 'tr_TR.ISO_8859-3', 'tr_TR.ISO_8859-9', 'Turkish_Turkey.1254'),
        'uk-ua' => array('uk_UA.UTF-8', 'uk_UA', 'uk', 'ukr', 'ukrainian', 'uk_UA.ISO_8859-5', 'Ukrainian_Ukraine.1251'),
        'ur-in' => array('ur_IN.UTF-8', 'ur_IN', 'ur', 'urd', 'urdu'),
        'vi-vn' => array('vi_VN.UTF-8', 'vi_VN', 'vi', 'vie', 'vietnamese', 'Vietnamese_Viet Nam.1258'),
        'zh-cn' => array('zh_CN.UTF-8', 'zh_CN', 'chinese-simplified', 'Chinese_China.936'),
        'zh-tw' => array('zh_TW.UTF-8', 'zh_TW', 'chinese-traditional', 'Chinese_Taiwan.950'),
    );

    /**
     * Sets the locale.
     *
     * This method wraps around system setlocale. It takes an IETF language code
     * and sets the locale accordingly.
     *
     * The following would set the locale to English:
     *
     * <code>
     * Txp::get('Textpattern\L10n\Locale')->setLocale(LC_ALL, 'en-GB');
     * </code>
     *
     * This would format currencies according to the French localisation:
     *
     * <code>
     * Txp::get('Textpattern\L10n\Locale')->setLocale(LC_MONETARY, 'fr-FR');
     * echo money_format('%i', 51.99);
     * </code>
     *
     * The '51.99' would be returned as '51,99 EUR' if you have up to date
     * French locale installed on your system.
     *
     * If an array of locales is provided, the first one that works is used.
     *
     * @param  int          $category The localisation category to change
     * @param  string|array $locale   The language code
     * @return Locale
     * @throws \Exception
     * @see    setlocale()
     */

    public function setLocale($category, $locale)
    {
        foreach ((array)$locale as $name) {
            $code = strtolower($name);

            if (isset($this->locales[$code])) {
                if (@setlocale($category, $this->locales[$code])) {
                    return $this;
                }
            }
        }

        if (@setlocale($category, $name)) {
            return $this;
        }

        throw new \Exception(gTxt('invalid_argument', array('{name}' => 'locale')));
    }

    /**
     * Gets the current locale.
     *
     * <code>
     * echo Txp::get('Textpattern\L10n\Locale')->getLocale(LC_ALL);
     * </code>
     *
     * @param  int $category The localisation category
     * @return mixed
     */

    public function getLocale($category = LC_ALL)
    {
        return @setlocale($category, '0');
    }

    /**
     * Gets a locale identifier for the given language code.
     *
     * This method takes an IETF language code and returns a locale for it that
     * works on the current system.
     *
     * The following returns 'en_GB.UTF-8':
     *
     * <code>
     * echo Txp::get('Textpattern\L10n\Locale')->getLanguageLocale('en-GB');
     * </code>
     *
     * Returns the current locale name if the system doesn't have anything
     * more appropriate.
     *
     * @param  string $language The language
     * @return string|bool Locale code, or FALSE on error
     */

    public function getLanguageLocale($language)
    {
        $locale = false;

        if ($original = $this->getLocale(LC_TIME)) {
            $locale = $original;

            try {
                $locale = $this->setLocale(LC_TIME, $language)->getLocale(LC_TIME);
                $this->setLocale(LC_TIME, $original);
            } catch (\Exception $e) {
            }
        }

        return $locale;
    }

    /**
     * Gets a language code for the given locale identifier.
     *
     * This method supports various different formats used by different host
     * platform. These formats include IETF language tag, POSIX locale name and
     * language name in English.
     *
     * All these will return 'en-GB':
     *
     * <code>
     * echo Txp::get('Textpattern\L10n\Locale')->getLocaleLanguage('en_GB.UTF-8');
     * echo Txp::get('Textpattern\L10n\Locale')->getLocaleLanguage('en-gb');
     * echo Txp::get('Textpattern\L10n\Locale')->getLocaleLanguage('english');
     * echo Txp::get('Textpattern\L10n\Locale')->getLocaleLanguage('c');
     * echo Txp::get('Textpattern\L10n\Locale')->getLocaleLanguage('English_UK.1252');
     * </code>
     *
     * If the specified locale isn't supported, FALSE will be returned.
     *
     * @param  string $locale The locale identifier
     * @return string|bool The language code, or FALSE on failure
     */

    public function getLocaleLanguage($locale)
    {
        $code = strtolower($locale);

        foreach ($this->locales as $lang => $data) {
            if ($lang === $code || in_array($code, array_map('strtolower', $data), true)) {
                return $lang;
            }
        }

        if (strpos($locale, '.')) {
            return join('.', array_slice(explode('.', $locale), 0, 1));
        }

        return false;
    }

    /**
     * Gets the character set from the current locale.
     *
     * This method exports the character set from the current locale string as
     * returned by the OS.
     *
     * <code>
     * echo Txp::get('Textpattern\L10n\Locale')->getCharset();
     * </code>
     *
     * @param  int $category The localisation category
     * @return string|bool The character set, or FALSE on failure
     */

    public function getCharset($category = LC_ALL)
    {
        if (($locale = $this->getLocale($category)) && strpos($locale, '.')) {
            list($language, $charset) = explode('.', $locale);

            if (IS_WIN && is_numeric($charset)) {
                return 'Windows-'.$charset;
            }

            return $charset;
        }

        return false;
    }

    /**
     * Gets the language from the current locale.
     *
     * <code>
     * echo Txp::get('Textpattern\L10n\Locale')->getLanguage();
     * </code>
     *
     * @param  int $category The localisation category
     * @return string|bool The language code, or FALSE on failure
     */

    public function getLanguage($category = LC_ALL)
    {
        if ($locale = $this->getLocale($category)) {
            if (($lang = $this->getLocaleLanguage($locale)) !== false) {
                return $lang;
            }
        }

        return false;
    }

    /**
     * Gets locale identifiers mapped to the given language.
     *
     * Returns all locale identifiers that match the given language or locale
     * code. For instance providing 'en', will return both en-US and en-GB
     * locale identifiers.
     *
     * <code>
     * print_r(Txp::get('Textpattern\L10n\Locale')->getLocaleIdentifiers('english'));
     * print_r(Txp::get('Textpattern\L10n\Locale')->getLocaleIdentifiers('en'));
     * print_r(Txp::get('Textpattern\L10n\Locale')->getLocaleIdentifiers('en-gb'));
     * </code>
     *
     * @param  string $locale The locale or language code
     * @return array|bool An array of identifiers, or FALSE if not supported
     */

    public function getLocaleIdentifiers($locale)
    {
        if (isset($this->locales[strtolower($locale)])) {
            return array_merge($this->locales[$locale], array($locale));
        }

        $code = strtolower($locale);
        $matches = array();

        foreach ($this->locales as $lang => $data) {
            if ($lang === $code || in_array($code, array_map('strtolower', $data), true)) {
                $matches = array_merge($matches, $data, array($lang, $locale));
            }
        }

        if ($matches) {
            return array_unique($matches);
        }

        return false;
    }
}
