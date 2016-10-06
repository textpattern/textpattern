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

class classic_theme extends \Textpattern\Admin\Theme
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
        $out[] = '<div class="txp-masthead">';
        $out[] = hed('Textpattern', 1, ' class="txp-branding"');
        $out[] = hed(htmlspecialchars($GLOBALS["prefs"]["sitename"]), 2, ' class="txp-accessibility"');
        $out[] = navPop(1);
        $out[] = '</div>';

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
                        $tc = ($item['active']) ? ' class="active"' : '';
                        $secondary .= n.'<li'.$tc.'>'.
                            href($item['label'], array('event' => $item['event'])).
                            '</li>';
                    }

                    $secondary .= n.'</ul>'.
                        n.'</div>';
                }
            }

            $out[] = '<li class="txp-view-site">'.
                href(gTxt('tab_view_site'), hu, array('target' => '_blank')).
                '</li>';

            $out[] = '</ul>';
            $out[] = '</div>';
            $out[] = $secondary;
            $out[] = '</nav>';
        }

        return join(n, $out);
    }

    function footer()
    {
        global $txp_user;

        $out[] = href('Textpattern CMS'.sp.span(gTxt('opens_external_link'), array('class' => 'ui-icon ui-icon-extlink')), 'http://textpattern.com', array(
                'class'  => 'mothership',
                'rel'    => 'external',
                'target' => '_blank',
            )).
            n.'('.txp_version.')';

        if ($txp_user) {
            $out[] = span('&#183;', array('role' => 'separator')).
                n.gTxt('logged_in_as').
                n.span(txpspecialchars($txp_user), array('class' => 'txp-username')).
                n.span('&#183;', array('role' => 'separator')).
                n.href(gTxt('logout'), 'index.php?logout=1', array(
                'class'   => 'txp-logout',
                'onclick' => 'return verify(\''.gTxt('are_you_sure').'\')',
            ));
        }

        return join(n, $out);;
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
                    $('#message.success, #message.warning, #message.error').fadeOut('fast').fadeIn('fast');
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
            'title'       => 'Classic',
            'description' => 'Textpattern Classic admin theme',
            'version'     => '4.6.2',
            'author'      => 'Phil Wareham',
            'author_uri'  => 'https://github.com/philwareham',
            'help'        => 'https://github.com/philwareham/textpattern-classic-admin-theme',
        );
    }
}
