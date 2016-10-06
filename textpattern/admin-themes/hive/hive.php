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

if (!defined('txpinterface')) {
    die('txpinterface is undefined.');
}

class hive_theme extends \Textpattern\Admin\Theme
{
    function html_head()
    {
        $cssPath = 'assets'.DS.'css';
        $jsPath = 'assets'.DS.'js';

        $out[] = '<link rel="stylesheet" href="'.$this->url.'assets/css/textpattern.min.css">';

        // Custom CSS (see theme README for usage instructions).
        if (defined('admin_custom_css')) {
            $custom_css = admin_custom_css;
        } else {
            $custom_css = 'custom.css';
        }

        if (file_exists(txpath.DS.THEME.$this->name.DS.$cssPath.DS.$custom_css)) {
            $out[] = '<link rel="stylesheet" href="'.$this->url.'assets/css/'.$custom_css.'">';
        }

        $out[] = '<link rel="icon" href="'.$this->url.'assets/img/favicon.ico">';
        $out[] = '<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">';
        $out[] = '<meta name="generator" content="Textpattern CMS">';
        $out[] = '<script src="'.$this->url.'assets/js/main.min.js"></script>'.n;

        // Custom JavaScript (see theme README for usage instructions).
        if (defined('admin_custom_js')) {
            $custom_js = admin_custom_js;
        } else {
            $custom_js = 'custom.js';
        }

        if (file_exists(txpath.DS.THEME.$this->name.DS.$jsPath.DS.$custom_js)) {
            $out[] = '<script src="'.$this->url.'assets/js/'.$custom_js.'"></script>'.n;
        }

        return join(n, $out);
    }

    function header()
    {
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
                        $out[] = '<li'.$class.' role="presentation">'.
                            href($item['label'], array('event' => $item['event']), ' role="menuitem" tabindex="-1"').
                            '</li>';
                    }

                    $out[] = '</ul>';
                }

                $out[] = '</li>';
            }

            $out[] = '</ul>';
            $out[] = '</nav>';
            $out[] = graf(
                href(span(htmlspecialchars($GLOBALS['prefs']['sitename']), array('class' => 'txp-view-site-name')), hu, array(
                    'target' => '_blank',
                    'title'  => gTxt('tab_view_site'),
                )), array('class' => 'txp-view-site'));
            $out[] = graf(
                href(gTxt('logout'), 'index.php?logout=1', ' onclick="return verify(\''.gTxt('are_you_sure').'\')"'), array('class' => 'txp-logout'));
        }

        return join(n, $out);
    }

    function footer()
    {
        $out[] = graf(
            href('Textpattern CMS'.sp.span(gTxt('opens_external_link'), array('class' => 'ui-icon ui-icon-extlink')), 'http://textpattern.com', array(
                'rel'    => 'external',
                'target' => '_blank',
            )).
            ' (v'.txp_version.')', array('class' => 'mothership'));

        $out[] = graf(href(gTxt('back_to_top'), '#'), array('class' => 'pagejump'));

        return join(n, $out);
    }

    function announce($thing = array('', 0), $modal = false)
    {
        return $this->_announce($thing, false, $modal);
    }

    function announce_async($thing = array('', 0), $modal = false)
    {
        return $this->_announce($thing, true, $modal);
    }

    private function _announce($thing, $async, $modal)
    {
        // $thing[0]: message text.
        // $thing[1]: message type, defaults to "success" unless empty or a different flag is set.

        if ($thing === '') {
            return '';
        }

        if (!is_array($thing) || !isset($thing[1])) {
            $thing = array($thing, 0);
        }

        switch ($thing[1]) {
            case E_ERROR:
                $class = 'error';
                $icon = 'ui-icon-alert';
                break;
            case E_WARNING:
                $class = 'warning';
                $icon = 'ui-icon-alert';
                break;
            default:
                $class = 'success';
                $icon = 'ui-icon-check';
                break;
        }

        if ($modal) {
            $html = ''; // TODO: Say what?
            $js = 'window.alert("'.escape_js(strip_tags($thing[0])).'")';
        } else {
            $html = span(
                span(null, array('class' => 'ui-icon '.$icon)).' '.gTxt($thing[0]).
                sp.href('&#215;', '#close', ' class="close" role="button" title="'.gTxt('close').'" aria-label="'.gTxt('close').'"'),
                array(
                    'class'     => 'messageflash '.$class,
                    'role'      => 'alert',
                    'aria-live' => 'assertive',
                )
            );

            // Try to inject $html into the message pane no matter when _announce()'s output is printed.
            $js = escape_js($html);
            $js = <<< EOS
                $(document).ready(function ()
                {
                    $("#messagepane").html("{$js}");
                });
EOS;
        }

        if ($async) {
            return $js;
        } else {
            return script_js(str_replace('</', '<\/', $js), $html);
        }
    }

    function manifest()
    {
        global $prefs;

        return array(
            'title'       => 'Hive',
            'description' => 'Textpattern Hive admin theme (Classic Yellow)',
            'version'     => '4.6.2',
            'author'      => 'Phil Wareham',
            'author_uri'  => 'https://github.com/philwareham',
            'help'        => 'https://github.com/philwareham/textpattern-hive-admin-theme',
        );
    }
}
