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

class hive_theme extends theme
{
    function html_head()
    {
        $out[] = '<link rel="stylesheet" href="vendors/jquery/ui/css/textpattern/jquery-ui.min.css">';
        $out[] = '<link rel="stylesheet" href="'.$this->url.'css/textpattern.min.css">';

        // Start of custom CSS toggles (see README.textile for usage instructions).
        if (defined('hive_theme_hide_branding')) {
            $out[] = '<link rel="stylesheet" href="'.$this->url.'css/custom/hide_branding.css">';
        }
        if (defined('hive_theme_hide_headings')) {
            $out[] = '<link rel="stylesheet" href="'.$this->url.'css/custom/hide_headings.css">';
        }
        if (defined('hive_theme_hide_preview_tabs_group')) {
            $out[] = '<link rel="stylesheet" href="'.$this->url.'css/custom/hide_preview_tabs.css">';
        }
        if (defined('hive_theme_hide_textfilter_group')) {
            $out[] = '<link rel="stylesheet" href="'.$this->url.'css/custom/hide_textfilter_group.css">';
        }
        if (defined('hive_theme_hide_advanced_group')) {
            $out[] = '<link rel="stylesheet" href="'.$this->url.'css/custom/hide_advanced_group.css">';
        }
        if (defined('hive_theme_hide_custom_field_group')) {
            $out[] = '<link rel="stylesheet" href="'.$this->url.'css/custom/hide_custom_field_group.css">';
        }
        if (defined('hive_theme_hide_image_group')) {
            $out[] = '<link rel="stylesheet" href="'.$this->url.'css/custom/hide_image_group.css">';
        }
        if (defined('hive_theme_hide_keywords_field')) {
            $out[] = '<link rel="stylesheet" href="'.$this->url.'css/custom/hide_keywords_field.css">';
        }
        if (defined('hive_theme_hide_recent_articles_group')) {
            $out[] = '<link rel="stylesheet" href="'.$this->url.'css/custom/hide_recent_articles_group.css">';
        }
        if (defined('hive_theme_hide_comments_group')) {
            $out[] = '<link rel="stylesheet" href="'.$this->url.'css/custom/hide_comments_group.css">';
        }
        if (defined('hive_theme_hide_expires_field')) {
            $out[] = '<link rel="stylesheet" href="'.$this->url.'css/custom/hide_expires_field.css">';
        }
        if (defined('hive_theme_hide_tag_builder_column')) {
            $out[] = '<link rel="stylesheet" href="'.$this->url.'css/custom/hide_tag_builder_column.css">';
        }
        if (defined('hive_theme_hide_form_preview')) {
            $out[] = '<link rel="stylesheet" href="'.$this->url.'css/custom/hide_form_preview.css">';
        }
        // End of custom CSS toggles.

        $out[] = '<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">';
        $out[] = '<meta name="generator" content="Textpattern CMS">';
        $out[] = '<meta name="application-name" content="'.htmlspecialchars($GLOBALS["prefs"]["sitename"]).'">';
        $out[] = '<meta name="apple-mobile-web-app-capable" content="yes">';
        $out[] = '<meta name="apple-mobile-web-app-title" content="'.htmlspecialchars($GLOBALS["prefs"]["sitename"]).'">';
        $out[] = '<script src="vendors/modernizr/modernizr/modernizr.js"></script>';
        $out[] = '<script src="'.$this->url.'js/scripts.js"></script>';
        $out[] = '<!--[if lt IE 9]>';
        $out[] = '<script src="vendors/keithclark/selectivizr/selectivizr.min.js"></script>';
        $out[] = '<link rel="stylesheet" href="vendors/jquery/ui/css/textpattern/jquery-ui-ie8.min.css">';
        $out[] = '<link rel="stylesheet" href="'.$this->url.'css/ie8.min.css">';
        $out[] = '<![endif]-->'.n;

        return join(n, $out);
    }

    function header()
    {
        global $txp_user;
        $out[] = hed(
            href(htmlspecialchars($GLOBALS["prefs"]["sitename"]), hu, array(
                'rel'   => 'external',
                'title' => gTxt('tab_view_site'),
            ))
            , 1);

        if ($txp_user) {
            $out[] = graf(
                href(gTxt('logout'), 'index.php?logout=1', ' onclick="return verify(\''.gTxt('are_you_sure').'\')"')
                , array('class' => 'txp-logout'));

            $out[] = '<nav role="navigation" aria-label="'.gTxt('navigation').'">';
            $out[] = '<div class="txp-nav">';
            $out[] = '<ul class="data-dropdown">';

            foreach ($this->menu as $tab) {
                $class = ($tab['active']) ? ' active' : '';
                $out[] = '<li class="dropdown'.$class.'">'.
                    href($tab["label"], array('event' => $tab['event']), ' class="dropdown-toggle"');

                if (!empty($tab['items'])) {
                    $out[] = '<ul class="dropdown-menu">';

                    foreach ($tab['items'] as $item) {
                        $class = ($item['active']) ? ' class="active"' : '';
                        $out[] = '<li'.$class.'>'.
                            href($item["label"], array('event' => $item['event'])).
                            '</li>';
                    }

                    $out[] = '</ul>';
                }

                $out[] = '</li>';
            }
            $out[] = '</ul>';
            $out[] = '</div>';
            $out[] = '<div class="txp-nav-select">';
            $out[] = '<select>';

            foreach ($this->menu as $tab) {
                $out[] = '<optgroup label="'.$tab['label'].'">';

                if (!empty($tab['items'])) {
                    foreach ($tab['items'] as $item) {
                        $select = ($item['active']) ? ' selected="selected"' : '';
                        $out[] = '<option value="?event='.$item["event"].'"'.$select.'>'.strip_tags($item["label"]).'</option>';
                    }
                }

                $out[] = '</optgroup>';
            }

            $out[] = '</select>';
            $out[] = '</div>';
            $out[] = '</nav>';
        }

        $out[] = '<div id="messagepane">'.$this->announce($this->message).'</div>';

        return join(n, $out);
    }

    function footer()
    {
        $out[] = graf(
            href('Textpattern CMS', 'http://textpattern.com', array(
                'rel'   => 'external',
                'title' => gTxt('go_txp_com'),
            )).
            ' (v'.txp_version.')'
            , array('class' => 'mothership'));

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
                $icon = 'ui-icon-closethick';
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
                sp.href('&#215;', '#close', ' role="button" class="close" title="'.gTxt('close').'" aria-label="'.gTxt('close').'"')
            , array(
                'role'  => 'alert',
                'class' => 'messageflash '.$class,
            ));

            // Try to inject $html into the message pane no matter when _announce()'s output is printed.
            $js = escape_js($html);
            $js = <<< EOS
                $(document).ready(function () {
                    $("#messagepane").html("{$js}");
                    $(window).resize(function () {
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
            'author'      => 'Team Textpattern',
            'author_uri'  => 'http://textpattern.com/',
            'version'     => $prefs['version'],
            'description' => 'Textpattern Hive Theme',
            'help'        => 'https://github.com/philwareham/txp-hive-admin-theme',
        );
    }
}
