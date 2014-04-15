<?php

/*
 * Textpattern Content Management System
 * http://textpattern.com
 *
 * Copyright (C) 2014 The Textpattern Development Team
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

if (!defined('txpinterface')) {
    die('txpinterface is undefined.');
}

theme::based_on('classic');

class remora_theme extends classic_theme
{
    function html_head()
    {
        $js = <<<SF
            $(document).ready(function () {
                $("#nav li").hover(function () {
                    $(this).addClass("sfhover");
                },
                function () {
                    $(this).removeClass("sfhover");
                });
            });
SF;
        return parent::html_head().script_js($js);
    }

    function header()
    {
        global $txp_user;
        $out[] = hed(htmlspecialchars($GLOBALS["prefs"]["sitename"]), 1, ' class="txp-accessibility"');
        $out[] = '<nav role="navigation" id="masthead" aria-label="'.gTxt('navigation').'">';
        $out[] = '<ul id="nav">';

        foreach ($this->menu as $tab) {
            $class = ($tab['active']) ? ' active' : '';
            $out[] = '<li class="primary'.$class.'">'.href($tab["label"], array('event' => $tab['event']));

            if (!empty($tab['items'])) {
                $out[] = '<ul>';

                foreach ($tab['items'] as $item) {
                    $class = ($item['active']) ? ' active' : '';
                    $out[] = '<li class="secondary'.$class.'">'.
                        href($item["label"], array('event' => $item['event'])).
                        '</li>';
                }

                $out[] = '</ul>';
            }

            $out[] = '</li>';
        }

        $out[] = '<li id="view-site" class="primary tabdown inactive">'.
            href(gTxt('tab_view_site'), hu, ' target="_blank"').
            '</li>';

        if ($txp_user) {
            $out[] = '<li id="logout" class="primary tabdown inactive">'.
                href(gTxt('logout'), 'index.php?logout=1', ' onclick="return verify(\''.gTxt('are_you_sure').'\')"').
                '</li>';
        }

        $out[] = '</ul>';
        $out[] = '</nav>';
        $out[] = '<div id="messagepane">'.$this->announce($this->message).'</div>'.n;

        return join(n, $out);
    }

    function footer()
    {
        return graf(
            href('Textpattern CMS', 'http://textpattern.com/', ' rel="external" title="'.gTxt('go_txp_com').'" target="_blank"').
            n.span('&#183;', array('role' => 'separator')).
            n.txp_version
        );
    }

    function manifest()
    {
        global $prefs;

        return array(
            'author'      => 'Team Textpattern',
            'author_uri'  => 'http://textpattern.com/',
            'version'     => $prefs['version'],
            'description' => 'Textpattern Remora Theme',
            'help'        => 'http://textpattern.com/admin-theme-help',
        );
    }
}
