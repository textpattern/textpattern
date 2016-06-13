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

class hiveNeutral_theme extends \Textpattern\Admin\Theme
{
    function html_head()
    {
        $out[] = '<link rel="stylesheet" href="'.$this->url.'assets/css/textpattern.min.css">';

        // Start of custom CSS toggles (see README.textile for usage instructions).
        if (defined('hive_theme_hide_branding')) {
            $out[] = '<link rel="stylesheet" href="'.$this->url.'assets/css/custom/hide_branding.css">';
        }
        if (defined('hive_theme_hide_headings')) {
            $out[] = '<link rel="stylesheet" href="'.$this->url.'assets/css/custom/hide_headings.css">';
        }
        if (defined('hive_theme_hide_preview_tabs_group')) {
            $out[] = '<link rel="stylesheet" href="'.$this->url.'assets/css/custom/hide_preview_tabs.css">';
        }
        if (defined('hive_theme_hide_textfilter_group')) {
            $out[] = '<link rel="stylesheet" href="'.$this->url.'assets/css/custom/hide_textfilter_group.css">';
        }
        if (defined('hive_theme_hide_advanced_group')) {
            $out[] = '<link rel="stylesheet" href="'.$this->url.'assets/css/custom/hide_advanced_group.css">';
        }
        if (defined('hive_theme_hide_custom_field_group')) {
            $out[] = '<link rel="stylesheet" href="'.$this->url.'assets/css/custom/hide_custom_field_group.css">';
        }
        if (defined('hive_theme_hide_image_group')) {
            $out[] = '<link rel="stylesheet" href="'.$this->url.'assets/css/custom/hide_image_group.css">';
        }
        if (defined('hive_theme_hide_keywords_field')) {
            $out[] = '<link rel="stylesheet" href="'.$this->url.'assets/css/custom/hide_keywords_field.css">';
        }
        if (defined('hive_theme_hide_recent_articles_group')) {
            $out[] = '<link rel="stylesheet" href="'.$this->url.'assets/css/custom/hide_recent_articles_group.css">';
        }
        if (defined('hive_theme_hide_comments_group')) {
            $out[] = '<link rel="stylesheet" href="'.$this->url.'assets/css/custom/hide_comments_group.css">';
        }
        if (defined('hive_theme_hide_expires_field')) {
            $out[] = '<link rel="stylesheet" href="'.$this->url.'assets/css/custom/hide_expires_field.css">';
        }
        if (defined('hive_theme_hide_image_caption')) {
            $out[] = '<link rel="stylesheet" href="'.$this->url.'assets/css/custom/hide_image_caption.css">';
        }
        // End of custom CSS toggles.

        $out[] = '<link rel="icon" href="'.$this->url.'assets/img/favicon.ico">';
        $out[] = '<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">';
        $out[] = '<meta name="generator" content="Textpattern CMS">';
        $out[] = '<meta name="theme-color" content="#ffda44">';
        $out[] = '<meta name="application-name" content="'.htmlspecialchars($GLOBALS["prefs"]["sitename"]).'">';
        $out[] = '<meta name="apple-mobile-web-app-capable" content="yes">';
        $out[] = '<meta name="apple-mobile-web-app-title" content="'.htmlspecialchars($GLOBALS["prefs"]["sitename"]).'">';
        $out[] = '<script src="'.$this->url.'assets/js/main.min.js"></script>'.n;

        return join(n, $out);
    }

    function header()
    {
        global $txp_user;
        $out[] = hed(htmlspecialchars($GLOBALS["prefs"]["sitename"]), 1);

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
                            href($item["label"], array('event' => $item['event']), ' role="menuitem" tabindex="-1"').
                            '</li>';
                    }

                    $out[] = '</ul>';
                }

                $out[] = '</li>';
            }

            $out[] = '</ul>';
            $out[] = '</nav>';
            $out[] = graf(
                href(span(htmlspecialchars($GLOBALS["prefs"]["sitename"]), array('class' => 'txp-view-site-name')), hu, array(
                    'rel'    => 'external',
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
            href('Textpattern CMS', 'http://textpattern.com', array(
                'rel'    => 'external',
                'target' => '_blank',
                'title'  => gTxt('go_txp_com'),
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
                    $(window).resize(function ()
                    {
                        $("#messagepane").css({
                            left: ($(window).width() - $("#messagepane").outerWidth()) / 2
                        });
                    });
                    $(window).resize();
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
            'title'       => 'Hive (Flat Neutral)',
            'description' => 'Textpattern Hive (Flat Neutral) admin theme',
            'version'     => '4.6.0-beta',
            'author'      => 'Phil Wareham',
            'author_uri'  => 'https://github.com/philwareham',
            'help'        => 'https://github.com/philwareham/textpattern-hive-admin-theme',
        );
    }
}
