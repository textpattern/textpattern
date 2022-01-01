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
 * Help subsystem.
 *
 * @since   4.7.0
 * @package Admin\Help
 */

namespace Textpattern\Module\Help;

class HelpAdmin
{
    private static $available_steps = array(
        'pophelp'   => false,
        'dashboard' => false,
    );

    private static $textile;
    protected static $pophelp_xml;
    protected static $fallback_xml;

    /**
     * Constructor.
     */

    public static function init()
    {
        global $step;

        require_privs('help');

        if ($step && bouncer($step, self::$available_steps)) {
            self::$step();
        } else {
            self::dashboard();
        }
    }


    /**
     * Load given pophelp.xml file
     *
     * Also load fallback file if it's not the same language.
     *
     * @param string $lang
     */

    private static function pophelp_load($lang)
    {
        $file = txpath."/lang/{$lang}_pophelp.xml";
        $fallback_file = txpath."/lang/".TEXTPATTERN_DEFAULT_LANG."_pophelp.xml";

        if (is_readable($fallback_file) && $fallback_file !== $file) {
            if (empty(self::$fallback_xml)) {
                self::$fallback_xml = simplexml_load_file($fallback_file, "SimpleXMLElement", LIBXML_NOCDATA);
            }
        }

        if (!is_readable($file)) {
            return false;
        }

        if (empty(self::$pophelp_xml)) {
            self::$pophelp_xml = simplexml_load_file($file, "SimpleXMLElement", LIBXML_NOCDATA);
        }

        return self::$pophelp_xml;
    }

    /**
     * Fetch pophelp group keys.
     *
     * @param string $group The help topic group to return
     */

    public static function pophelp_keys($group)
    {
        $xml = self::pophelp_load(TEXTPATTERN_DEFAULT_LANG);
        $help = $xml ? $xml->xpath("//group[@id='{$group}']/item") : array();

        $keys = array();

        foreach ($help as $item) {
            if ($item->attributes()->id) {
                $keys[] = (string)$item->attributes()->id;
            }
        }

        return $keys;
    }

    /**
     * Popup help topic.
     *
     * @param string $string The help topic item identifier to return
     * @param string $lang   The language in which to return the topic. Default=current
     */

    public static function pophelp($string = '', $lang = null)
    {
        global $app_mode;

        $item = empty($string) ? gps('item') : $string;

        if (empty($item) || preg_match('/[^\w]/i', $item)) {
            exit;
        }

        $lang_ui = ($lang) ? $lang : get_pref('language_ui', LANG);

        if (!$xml = self::pophelp_load($lang_ui)) {
            $lang_ui = TEXTPATTERN_DEFAULT_LANG;

            if (!empty(self::$fallback_xml)) {
                $xml = self::$fallback_xml;
            }
        }

        $x = $xml ? $xml->xpath("//item[@id='{$item}']") : array();
        $pophelp = $x ? trim($x[0]) : false;

        if (!$pophelp && !empty(self::$fallback_xml)) {
            $xml = self::$fallback_xml;
            $x = $xml ? $xml->xpath("//item[@id='{$item}']") : array();
            $pophelp = $x ? trim($x[0]) : false;
        }

        $title = '';

        if ($pophelp) {
            $title = txpspecialchars($x[0]->attributes()->title);
            $format = $x[0]->attributes()->format;

            if ($format == 'textile') {
                $textile = new \Netcarver\Textile\Parser();
                $out = $textile->parse($pophelp).n;
            } else {
                $out = $pophelp.n;
            }
        } else {
            // Check if the pophelp item is installed in the DB as a regular string.
            $exists = \Txp::get('\Textpattern\L10n\Lang')->hasString($item);

            if ($exists) {
                $out = gTxt($item);
            } else {
                $out = gTxt('pophelp_missing', array('{item}' => $item));
            }
        }

        $out = tag($out, 'div', array(
            'id'  => 'pophelp-event',
            'dir' => 'auto',
        ));

        if ($app_mode == 'async') {
            pagetop('');
            exit($out);
        }

        return $out;
    }

    /**
     * Stub, awaiting implementation.
     */

    public static function dashboard()
    {
        pagetop(gTxt('tab_help'));
    }
}
