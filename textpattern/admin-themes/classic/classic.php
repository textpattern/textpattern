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

class classic_theme extends \Textpattern\Admin\Theme
{
    function html_head()
    {
        $out[] = '<meta name="viewport" content="width=device-width, initial-scale=1">';
        $out[] = '<link rel="stylesheet" href="'.$this->url.'assets/css/textpattern.css">';
        $out[] = '<link rel="icon" href="'.$this->url.'assets/img/favicon.ico">';
        $out[] = '<meta name="generator" content="Textpattern CMS">';

        return join(n, $out);
    }

    function header()
    {
        global $txp_user;

        $out[] = '<div class="txp-masthead">';
        $out[] = hed('Textpattern', 1, ' class="txp-branding"');
        $out[] = hed(htmlspecialchars(get_pref('sitename')), 2, ' class="txp-accessibility"');
        $out[] = navPop(1);
        $out[] = '</div>';

        if ($txp_user) {
            if (!$this->is_popup) {
                $out[] = '<nav role="navigation" aria-label="'.gTxt('navigation').'">';
                $out[] = '<div class="nav-tabs" id="nav-primary">';
                $out[] = '<ul>';

                $secondary = '';

                foreach ($this->menu as $tab) {
                    $tc = ($tab['active']) ? ' class="active"' : '';
                    $out[] = '<li'.$tc.'>'.
                        href($tab["label"], array('event' => $tab['event'])).
                        '</li>';

                    if ($tab['active'] && !empty($tab['items'])) {
                        $secondary = '<div class="nav-tabs" id="nav-secondary">'.
                            n.'<ul>';

                        foreach ($tab['items'] as $item) {
                            $tc = ($item['active']) ? ' class="active" aria-current="page"' : '';
                            $secondary .= n.'<li'.$tc.'>'.
                                href($item['label'], array('event' => $item['event'])).
                                '</li>';
                        }

                        $secondary .= n.'</ul>'.
                            n.'</div>';
                    }
                }

                $out[] = '<li class="txp-view-site">'.
                    href(gTxt('tab_view_site'), hu, array(
                        'rel'    => 'noopener',
                        'target' => '_blank'
                    )).
                    '</li>';

                $out[] = '</ul>';
                $out[] = '</div>';
                $out[] = $secondary;
                $out[] = '</nav>';
            }
        }

        return join(n, $out);
    }

    function footer()
    {
        global $txp_user;

        $out[] = href('Textpattern CMS'.sp.span(gTxt('opens_external_link'), array('class' => 'ui-icon ui-icon-extlink')), 'https://textpattern.com', array(
                'class'  => 'mothership',
                'rel'    => 'external noopener',
                'target' => '_blank',
            )).
            n.'('.txp_version.')'.
            n.span('&#183;', array('role' => 'separator')).
            n.gTxt('logged_in_as').
            n.span(txpspecialchars($txp_user), array('class' => 'txp-username')).
            n.span('&#183;', array('role' => 'separator')).
            n.href(gTxt('logout'), 'index.php?logout=1', array(
                'class'   => 'txp-logout',
                'onclick' => 'return verify(\''.gTxt('are_you_sure').'\')',
            ));

        return join(n, $out);
    }
}
