<?php

/*
 * Textpattern Content Management System
 * https://textpattern.io/
 *
 * Copyright (C) 2017 The Textpattern Development Team
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

if (!defined('txpinterface')) {
    die('txpinterface is undefined.');
}

/**
 * Help subsystem.
 *
 * @package Admin\Help
 */

class Textpattern_Admin_Help
{
    /**
     * Constructor.
     */

    public function __construct()
    {
        global $step;

        $steps = array(
            'pophelp'    => false,
        );

        if ($step && bouncer($step, $steps)) {
            $this->$step();
        }
    }

    public function pophelp_load($lang)
    {
        $file = txpath."/lang/{$lang}_pophelp.xml";
        if (!file_exists($file)) {
            return false;
        }

        return simplexml_load_file($file, "SimpleXMLElement", LIBXML_NOCDATA);
    }

    /**
     * pophelp.
     */

    public function pophelp()
    {
        $item = gps('item');
        if (empty($item) || preg_match('/[^\w]/i', $item)) {
            exit;
        }

        $lang_ui = get_pref('language_ui', LANG);

        if (!$xml = $this->pophelp_load($lang_ui)) {
            $lang_ui = TEXTPATTERN_DEFAULT_LANG;
            $xml = $this->pophelp_load($lang_ui);
        }

        $x = $xml->xpath("//item[@id='{$item}']");
        if (!$x && $lang_ui != TEXTPATTERN_DEFAULT_LANG) {
            $xml = $this->pophelp_load(TEXTPATTERN_DEFAULT_LANG);
            $x = $xml->xpath("//item[@id='{$item}']");
        }

        $out = $title = '';
        if ($x) {
            $pophelp = trim($x[0]);
            $title = $x[0]->attributes()->title;
            $format = $x[0]->attributes()->format;
            if ($format == 'textile') {
                $textile = new \Netcarver\Textile\Parser();
                $out .= $textile->textileThis($pophelp).n;
            } else {
                $out .= $pophelp.n;
            }
        }

        // Temporary code, it will be deleted in the next step.
        echo <<<EOF
<!DOCTYPE html>
<html lang="en-gb" class="no-js">
<head>
    <meta charset="utf-8">
    <title>{$title}</title>
    <meta name="robots" content="noindex, follow, noodp, noydir">
</head>
<body>
    <hr />
    {$out}
    <hr />
</body>
</html>
EOF;

        exit;
    }
}

new Textpattern_Admin_Help();
