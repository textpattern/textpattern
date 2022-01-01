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

if (!defined('txpinterface')) {
    die('txpinterface is undefined.');
}

class hiveNeutral_theme extends \Textpattern\Admin\Theme
{
    function html_head()
    {
        $out[] = '<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">';
        $out[] = '<link rel="stylesheet" href="'.$this->url.'assets/css/textpattern.css">';
        $out[] = '<link rel="icon" href="'.$this->url.'assets/img/favicon.ico">';
        $out[] = '<meta name="color-scheme" content="dark light">';
        $out[] = '<meta name="generator" content="Textpattern CMS">';
        $out[] = '<script defer src="'.$this->url.'assets/js/main.js"></script>'.n;

        if (!defined('no_autosize')) {
            $out[] = '<script defer src="'.$this->url.'assets/js/autosize.js"></script>'.n;
        }

        return join(n, $out);
    }

    function header()
    {
        $out[] = '<script src="'.$this->url.'assets/js/darkmode.js"></script>'.n;

        global $txp_user;

        $default_event = get_pref('default_event');
        $homelink = span('Textpattern');

        if (!empty($default_event) && has_privs($default_event)) {
            $homelink = href($homelink, array('event' => $default_event));
        }

        $out[] = hed($homelink, 1);

        if ($txp_user) {
            $out[] = '<button class="txp-nav-toggle collapsed" type="button" data-toggle="collapse" data-target="#txp-nav" aria-expanded="false" aria-controls="txp-nav"><span class="txp-accessibility">'.gTxt('navigation').'</span></button>';
            $out[] = '<nav class="txp-nav" id="txp-nav" aria-label="'.gTxt('navigation').'">';
            $out[] = '<ul class="data-dropdown">';
            $txpnavdrop = 0;

            foreach ($this->menu as $tab) {
                $txpnavdrop++;
                $class = ($tab['active']) ? ' selected' : '';
                $out[] = '<li class="dropdown'.$class.'">'.
                    n.href($tab['label'], '#',
                    ' class="dropdown-toggle" id="txp-nav-drop'.$txpnavdrop.'" role="button" aria-controls="txp-nav-drop'.$txpnavdrop.'-menu" data-toggle="dropdown"');

                if (!empty($tab['items'])) {
                    $out[] = '<ul class="dropdown-menu" id="txp-nav-drop'.$txpnavdrop.'-menu" role="menu" aria-labelledby="txp-nav-drop'.$txpnavdrop.'">';

                    foreach ($tab['items'] as $item) {
                        $class = ($item['active']) ? ' class="selected"' : '';
                        $ariacurrent = ($item['active']) ? ' aria-current="page"' : '';
                        $out[] = '<li'.$class.' role="presentation">'.
                            href($item['label'], array('event' => $item['event']), ' role="menuitem"'.$ariacurrent.' tabindex="-1"').
                            '</li>';
                    }

                    $out[] = '</ul>';
                }

                $out[] = '</li>';
            }

            $out[] = '</ul>';
            $out[] = '</nav>';

            if (get_pref('sitename')) {
                $out[] = graf(
                    span(href(htmlspecialchars(get_pref('sitename')), hu, array(
                        'rel'        => 'noopener',
                        'target'     => '_blank',
                        'title'      => gTxt('tab_view_site'),
                        'aria-label' => gTxt('tab_view_site'),
                    )), array('class' => 'txp-view-site-name'))
                , array('class' => 'txp-view-site'));
            } else {
                $out[] = graf(
                    span(href(gTxt('tab_view_site'), hu, array(
                        'rel'        => 'noopener',
                        'target'     => '_blank',
                    )), array('class' => 'txp-view-site-name'))
                , array('class' => 'txp-view-site'));
            }

            $out[] = graf(
                href(span(gTxt('lightswitch'), array('class' => 'ui-icon ui-icon-lightbulb')), '#', array(
                    'id'         => 'lightswitch',
                    'title'      => gTxt('lightswitch'),
                    'aria-label' => gTxt('lightswitch'),
                )), array('class' => 'txp-lightswitch'));
            $out[] = graf(
                href(gTxt('logout'), 'index.php?logout=1', ' onclick="return verify(\''.gTxt('are_you_sure').'\')"'), array('class' => 'txp-logout'));
        }

        return join(n, $out);
    }

    function footer()
    {
        $out[] = graf(
            href('Textpattern CMS'.sp.span(gTxt('opens_external_link'), array('class' => 'ui-icon ui-icon-extlink')), 'https://textpattern.com/', array(
                'rel'    => 'external noopener',
                'target' => '_blank',
            )).
            ' (v'.txp_version.')', array('class' => 'mothership'));

        $out[] = graf(href(gTxt('back_to_top'), '#'), array('class' => 'pagejump'));

        return join(n, $out);
    }
}
