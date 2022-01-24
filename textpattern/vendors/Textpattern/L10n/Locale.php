<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2022 The Textpattern Development Team
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
 * along with Textpattern. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * Handles locales.
 *
 * <code>
 * echo Txp::get('\Textpattern\L10n\Locale')->setLocale(LC_ALL, 'da-dk')->getLocale();
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
        'ar'    => array('ar_SA.UTF-8', 'ar_SA.ISO_8859-6', 'Arabic_Saudi Arabia.1256', 'ar_SA', 'ara', 'ar', 'arabic'),
        'bg'    => array('bg_BG.UTF-8', 'bg_BG.ISO_8859-5', 'Bulgarian_Bulgaria.1251', 'bg_BG', 'bg', 'bul', 'bulgarian'),
        'bn'    => array('bn_BD.UTF-8', 'bn_BD', 'bn', 'ben', 'bengali', 'bangla'),
        'bs'    => array('bs_BA.UTF-8', 'bs_BA.ISO_8859-2', 'Bosnian_Bosnia and Herzegovina.1250', 'bs_BA', 'bs', 'bos', 'bosnian'),
        'ca'    => array('ca_ES.UTF-8', 'ca_ES.ISO_8859-1', 'Catalan_Spain.1252', 'ca_ES', 'cat', 'ca', 'catalan'),
        'ceb'   => array('ceb.UTF-8', 'ceb', 'cebuano'),
        'cs'    => array('cs_CZ.UTF-8', 'cs_CZ.ISO_8859-2', 'Czech_Czech Republic.1250', 'cs_CZ', 'ces', 'cze', 'cs', 'csy', 'czech'),
        'cy'    => array('cy_GB.UTF-8', 'cy_GB.ISO_8859-14', 'Welsh_United Kingdom.1252', 'cy_GB', 'cy', 'cym', 'wel', 'welsh'),
        'da'    => array('da_DK.UTF-8', 'da_DK.ISO_8859-1', 'Danish_Denmark.1252', 'da_DK', 'da', 'dan', 'danish'),
        'de'    => array('de_DE.UTF-8', 'de_DE.ISO_8859-1', 'de_DE.ISO_8859-16', 'German_Germany.1252', 'de_DE', 'de', 'deu', 'german'),
        'el'    => array('el_GR.UTF-8', 'el_GR.ISO_8859-7', 'Greek_Greece.1253', 'el_GR', 'el', 'ell', 'gre', 'greek'),
        'en'    => array('en_US.UTF-8', 'en_GB.ISO_8859-1', 'English_USA.1252', 'en_US', 'english-us', 'eng', 'en', 'english', 'C'),
        'en-gb' => array('en_GB.UTF-8', 'en_GB.ISO_8859-1', 'English_UK.1252', 'en_GB', 'en_UK', 'eng', 'en', 'english-uk', 'english', 'C'),
        'en-us' => array('en_US.UTF-8', 'en_US.ISO_8859-1', 'English_USA.1252', 'en_US', 'english-us', 'eng', 'en', 'english'),
        'es'    => array('es_ES.UTF-8', 'es_ES.ISO_8859-1', 'Spanish_Spain.1252', 'es_ES', 'esp', 'esn', 'spanish'),
        'es-ve' => array('es_VE.UTF-8', 'es_VE.ISO_8859-1', 'Spanish_Venezuela.1252', 'es_VE', 'esv', 'spanish-venezuela'),
        'et'    => array('et_EE.UTF-8', 'et_EE.ISO_8859-1', 'et_EE.ISO_8859-15', 'Estonian_Estonia.1257', 'et_EE', 'et', 'est', 'estonian'),
        'fa'    => array('fa_IR.UTF-8', 'Farsi_Iran.1256', 'fa_IR', 'fa', 'persian', 'per', 'fas', 'farsi'),
        'ff'    => array('ff_SN.UTF-8', 'ff_SN', 'ff', 'ful', 'fula', 'fulah'),
        'fi'    => array('fi_FI.UTF-8', 'fi_FI.ISO_8859-1', 'fi_FI.ISO-8859-15', 'fi_FI.ISO_8859-16', 'Finnish_Finland.1252', 'fi_FI', 'fin', 'fi', 'finnish'),
        'fil'   => array('ph_PH.UTF-8', 'ph_PH.ISO_8859-1', 'Filipino_Philippines.1252', 'ph_PH', 'fil', 'filipino'),
        'fr'    => array('fr_FR.UTF-8', 'fr_FR.ISO_8859-1', 'fr_FR.ISO-8859-15', 'fr_FR.ISO_8859-16', 'French_France.1252', 'fr_FR', 'fra', 'fre', 'fr', 'french'),
        'gl'    => array('gl_ES.UTF-8', 'gl_ES.ISO_8859-1', 'Galician_Spain.1252', 'gl_ES', 'gle', 'gl', 'galician', 'galleco'),
        'he'    => array('he_IL.UTF-8', 'he_IL.ISO_8859-8', 'Hebrew_Israel.1255', 'he_IL', 'heb', 'he', 'hebrew'),
        'hi'    => array('hi_IN.UTF-8', 'Hindi.65001', 'hi_IN', 'hi', 'hin', 'hindi-india'),
        'hr'    => array('hr_HR.UTF-8', 'hr_HR.ISO_8859-2', 'hr_HR.ISO_8859-16', 'Croatian_Croatia.1250', 'hr_HR', 'hr', 'hrv', 'croatian'),
        'hu'    => array('hu_HU.UTF-8', 'hu_HU.ISO_8859-2', 'hu_HU.ISO_8859-16', 'Hungarian_Hungary.1250', 'hu_HU', 'hun', 'hu', 'hungarian'),
        'id'    => array('id_ID.UTF-8', 'id_ID.ISO_8859-1', 'Indonesian_indonesia.1252', 'id_ID', 'id', 'ind', 'indonesian'),
        'is'    => array('is_IS.UTF-8', 'is_IS.ISO_8859-1', 'is_IS.ISO_8859-15', 'Icelandic_Iceland.1252', 'is_IS', 'is', 'ice', 'isl', 'icelandic'),
        'it'    => array('it_IT.UTF-8', 'it_IT.ISO_8859-1', 'it_IT.ISO_8859-15', 'it_IT.ISO_8859-16', 'Italian_Italy.1252', 'it_IT', 'it', 'ita', 'italian'),
        'ja'    => array('ja_JP.UTF-8', 'Japanese_Japan.932', 'ja_JP', 'ja', 'jpn', 'japanese'),
        'km'    => array('km_KH.UTF-8', 'Khmer.65001', 'km_KH', 'km', 'khm', 'kxm', 'khmer'),
        'ko'    => array('ko_KR.UTF-8', 'Korean_Korea.949', 'ko_KR', 'ko', 'kor', 'korean'),
        'ky'    => array('ky.UTF-8', 'ky-KG.ISO_8859-5', 'Kyrgyz_Kyrgyzstan.1251', 'ky-KG', 'ky', 'kir', 'kyrgyz', 'kirghiz'),
        'lt'    => array('lt_LT.UTF-8', 'lt_LT.ISO_8859-4', 'lt_LT.ISO_8859-13', 'Lithuanian_Lithuania.1257', 'lt_LT', 'lt', 'lit', 'lithuanian'),
        'lv'    => array('lv_LV.UTF-8', 'lv_LV.ISO_8859-4', 'Latvian_Latvia.1257', 'lv_LV', 'lv', 'lav', 'latvian'),
        'nb'    => array('nb_NO.UTF-8', 'nb_NO.ISO_8859-1', 'nb_NO.ISO_8859-15', 'Norwegian_Norway.1252', 'no_NO', 'nb_NO', 'no', 'nb', 'nor', 'nob', 'norwegian', 'norwegian-bokmal'),
        'nl'    => array('nl_NL.UTF-8', 'nl_NL.ISO_8859-1', 'nl_NL.ISO_8859-15', 'Dutch_Netherlands.1252', 'nl_NL', 'dut', 'nla', 'nl', 'nld', 'dutch'),
        'nn'    => array('nn_NO.UTF-8', 'nn_NO.ISO_8859-1', 'Norwegian-Nynorsk_Norway.1252', 'nn_NO', 'nno', 'non', 'nn', 'nynorsk', 'norwegian-nynorsk'),
        'pa'    => array('pa_IN.UTF-8', 'pa_IN', 'pa', 'pan', 'punjabi'),
        'pl'    => array('pl_PL.UTF-8', 'pl_PL.ISO_8859-2', 'Polish_Poland.1250', 'pl_PL', 'pl', 'pol', 'plk', 'polish'),
        'pt-br' => array('pt_BR.UTF-8', 'pt_BR.ISO_8859-1', 'Portuguese_Brazil.1252', 'pt_BR', 'pt', 'ptb', 'portuguese-brazil'),
        'pt'    => array('pt_PT.UTF-8', 'pt_PT.ISO_8859-1', 'pt_PT.ISO_8859-15', 'Portuguese_Portugal.1252', 'pt_PT', 'por', 'ptg', 'portuguese'),
        'ro'    => array('ro_RO.UTF-8', 'ro_RO.ISO_8859-2', 'ro_RO.ISO_8859-16', 'Romanian_Romania.1250', 'ro_RO', 'ron', 'rum', 'ro', 'romanian'),
        'ru'    => array('ru_RU.UTF-8', 'ru_RU.ISO_8859-5', 'Russian_Russia.1251', 'ru_RU', 'ru', 'rus', 'russian'),
        'sk'    => array('sk_SK.UTF-8', 'sk_SK.ISO_8859-1', 'sk_SK.ISO_8859-2', 'Slovak_Slovakia.1250', 'sk_SK', 'sk', 'slo', 'slk', 'sky', 'slovak'),
        'sl'    => array('sl_SI.UTF-8', 'sl_SI.ISO-8859-2', 'Slovenian_Slovenia.1250', 'sl_SI', 'sl', 'slv', 'slovenian'),
        'sr-rs' => array('sr_RS.UTF-8', 'sr_RS.ISO_8859-5', 'Serbian (Cyrillic)_Serbia and Montenegro (Former).1251', 'sr_RS', 'sr', 'rs', 'srb', 'serbian'),
        'sr'    => array('sr_SP.UTF-8', 'sr_SP.ISO_8859-2', 'Serbian (Latin)_Serbia and Montenegro (Former).1250', 'sr_SP', 'sr', 'sp', 'srb', 'serbian'),
        'sv'    => array('sv_SE.UTF-8', 'sv_SE.ISO_8859-1', 'sv_SE.ISO_8859-15', 'Swedish_Sweden.1252', 'sv_SE', 'sv', 'swe', 'sve', 'swedish'),
        'th'    => array('th_TH.UTF-8', 'th_TH.ISO_8859-11', 'Thai_Thailand.874', 'th_TH', 'th', 'tha', 'thai'),
        'tl'    => array('tl_PH.UTF-8', 'tl_PH.ISO-8859-1', 'tl_PH', 'tl', 'tgl', 'tagalog'),
        'tr'    => array('tr_TR.UTF-8', 'tr_TR.ISO_8859-3', 'tr_TR.ISO_8859-9', 'Turkish_Turkey.1254', 'tr_TR', 'tr', 'tur', 'trk', 'turkish'),
        'uk'    => array('uk_UA.UTF-8', 'uk_UA.ISO_8859-5', 'Ukrainian_Ukraine.1251', 'uk_UA', 'uk', 'ukr', 'ukrainian'),
        'ur-pk' => array('ur_PK.UTF-8', 'Urdu_Islamic Republic of Pakistan.1256', 'ur_PK', 'ur', 'urd', 'urdu-pakistan'),
        'ur'    => array('ur_IN.UTF-8', 'ur_IN', 'ur', 'urd', 'urdu'),
        'vi'    => array('vi_VN.UTF-8', 'Vietnamese_Viet Nam.1258', 'vi_VN', 'vi', 'vie', 'vietnamese'),
        'zh-cn' => array('zh_CN.UTF-8', 'Chinese_China.936', 'zh_CN', 'chinese-simplified', 'chs'),
        'zh-tw' => array('zh_TW.UTF-8', 'Chinese_Taiwan.950', 'zh_TW', 'chinese-traditional', 'cht'),
    );

    /**
     * Used in function validLocale()
     *
     * @var array
     */

    protected $recodeLocale = array(
        'ar-dz' => 'ar',
        'bg-bg' => 'bg',
        'bs-ba' => 'bs',
        'ca-es' => 'ca',
        'cs-cz' => 'cs',
        'da-dk' => 'da',
        'de-de' => 'de',
        'el-gr' => 'el',
        'es-es' => 'es',
        'et-ee' => 'et',
        'fa-ir' => 'fa',
        'fi-fi' => 'fi',
        'fr-fr' => 'fr',
        'gl-gz' => 'gl',
        'gl-es' => 'gl',
        'he-il' => 'he',
        'hr-hr' => 'hr',
        'hu-hu' => 'hu',
        'id-id' => 'id',
        'is-is' => 'is',
        'it-it' => 'it',
        'ja-jp' => 'ja',
        'ko-kr' => 'ko',
        'lt-lt' => 'lt',
        'lv-lv' => 'lv',
        'nl-nl' => 'nl',
        'nn-no' => 'nn',
        'no-no' => 'nb',
        'pl-pl' => 'pl',
        'pt-pt' => 'pt',
        'ro-ro' => 'ro',
        'ru-ru' => 'ru',
        'sk-sk' => 'sk',
        'sr-sp' => 'sr',
        'sv-se' => 'sv',
        'th-th' => 'th',
        'tr-tr' => 'tr',
        'uk-ua' => 'uk',
        'ur-in' => 'ur',
        'vi-vn' => 'vi',
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
     * Txp::get('\Textpattern\L10n\Locale')->setLocale(LC_ALL, 'en-GB');
     * </code>
     *
     * This would format currencies according to the French localisation:
     *
     * <code>
     * Txp::get('\Textpattern\L10n\Locale')->setLocale(LC_MONETARY, 'fr-FR');
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
            $code = isset($this->locales[$code]) ? $this->locales[$code] : $name;

            if (!empty($code) && setlocale($category, $code)) {
                return $this;
            }
        }

        // setlocale($category, null);

        return $this;
    }

    /**
     * Gets the current locale.
     *
     * <code>
     * echo Txp::get('\Textpattern\L10n\Locale')->getLocale(LC_ALL);
     * </code>
     *
     * @param  int $category The localisation category
     * @return mixed
     */

    public function getLocale($category = LC_ALL)
    {
        return @setlocale($category, 0);
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
     * echo Txp::get('\Textpattern\L10n\Locale')->getLanguageLocale('en-GB');
     * </code>
     *
     * Returns the default locale name if the system doesn't have anything
     * more appropriate.
     *
     * @param  string $language The language
     * @return string|bool Locale code, or FALSE on error
     */

    public function getLanguageLocale($language)
    {
        $locale = false;

        if ($original = $this->getLocale(LC_TIME)) {
            $locale = $this->setLocale(LC_TIME, $language)->getLocale(LC_TIME);
            $this->setLocale(LC_TIME, $original);
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
     * All these will return 'en-gb':
     *
     * <code>
     * echo Txp::get('\Textpattern\L10n\Locale')->getLocaleLanguage('en_GB.UTF-8');
     * echo Txp::get('\Textpattern\L10n\Locale')->getLocaleLanguage('en-gb');
     * echo Txp::get('\Textpattern\L10n\Locale')->getLocaleLanguage('english');
     * echo Txp::get('\Textpattern\L10n\Locale')->getLocaleLanguage('c');
     * echo Txp::get('\Textpattern\L10n\Locale')->getLocaleLanguage('English_UK.1252');
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
            return strtok($locale, '.');
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
     * echo Txp::get('\Textpattern\L10n\Locale')->getCharset();
     * </code>
     *
     * @param  int $category The localisation category
     * @return string|bool The character set, or FALSE on failure
     */

    public function getCharset($category = LC_ALL, $default = false)
    {
        if (!($locale = $this->getLocale($category))) {
            $charset = false;
        } elseif (is_callable('nl_langinfo')) {
            $oldLocale = $this->getLocale(LC_CTYPE);
            $this->setLocale(LC_CTYPE, $locale);
            $charset = nl_langinfo(CODESET);
            $this->setLocale(LC_CTYPE, $oldLocale);
        } elseif (strpos($locale, '.')) {
            list($language, $charset) = explode('.', $locale);

            if (is_numeric($charset)) {
                $charset = $charset == 65001 ? 'UTF-8' : 'CP'.$charset;
            }
        } elseif ($locale = $this->getLocaleLanguage($locale) and isset($this->locales[$locale])) {
            foreach ($this->locales[$locale] as $lang) {
                list($lang, $charset) = explode('.', $lang) + array(null, null);

                if (is_numeric($charset)) {
                    $charset = $charset == 65001 ? 'UTF-8' : 'CP'.$charset;
                    break;
                } else {
                    $charset = null;
                }
            }
        }

        return isset($charset) ? $charset : $default;
    }

    /**
     * Gets the language from the current locale.
     *
     * <code>
     * echo Txp::get('\Textpattern\L10n\Locale')->getLanguage();
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
     * print_r(Txp::get('\Textpattern\L10n\Locale')->getLocaleIdentifiers('english'));
     * print_r(Txp::get('\Textpattern\L10n\Locale')->getLocaleIdentifiers('en'));
     * print_r(Txp::get('\Textpattern\L10n\Locale')->getLocaleIdentifiers('en-gb'));
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

    /**
     * Return valid locale, if possible.
     */

    public function validLocale($code)
    {
        $code = strtolower($code);

        if (empty($this->locales[$code])) {
            if (!empty($this->recodeLocale[$code])) {
                $code = $this->recodeLocale[$code];
            } else {
                // Fall back on trying partial match.
                $codePart = explode('-', $code);

                if (array_search($codePart[0], $this->recodeLocale)) {
                    $code = $codePart[0];
                }
            }
        }

        return $code;
    }
}
