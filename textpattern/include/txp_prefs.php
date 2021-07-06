<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2021 The Textpattern Development Team
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
 * Preferences panel user interface and interaction.
 *
 * @package Admin\Prefs
 */

use PHPMailer\PHPMailer\PHPMailer;

if (!defined('txpinterface')) {
    die('txpinterface is undefined.');
}

if ($event == 'prefs') {
    require_privs('prefs');

    bouncer($step, array(
        'prefs_save' => true,
        'prefs_list' => false,
    ));

    switch (strtolower($step)) {
        case '':
        case 'prefs_list':
            prefs_list();
            break;
        case 'prefs_save':
            prefs_save();
            break;
    }
}

/**
 * Commits prefs to the database.
 */

function prefs_save()
{
    global $prefs, $gmtoffset, $is_dst, $auto_dst, $timezone_key, $txp_user, $theme;

    // Update custom fields count from database schema and cache it as a hidden pref.
    // TODO: move this when custom fields are refactored.
    $max_custom_fields = count(preg_grep('/^custom_\d+/', getThings("DESCRIBE ".safe_pfx('textpattern'))));
    set_pref('max_custom_fields', $max_custom_fields, 'publish', PREF_HIDDEN);

    $sql = array();
    $sql[] = "event != '' AND type IN (".PREF_CORE.", ".PREF_PLUGIN.", ".PREF_HIDDEN.")";
    $sql[] = "(user_name = '' OR (user_name = '".doSlash($txp_user)."' AND name NOT IN (
            SELECT name FROM ".safe_pfx('txp_prefs')." WHERE user_name = ''
        )))";

    if (!get_pref('use_comments', 0, 1)) {
        $sql[] = "event != 'comments'";
    }

    $prefnames = safe_rows_start(
        "name, event, collection, user_name, val",
        'txp_prefs',
        join(" AND ", $sql)
    );

    $post = stripPost();

    if (isset($post['tempdir']) && empty($post['tempdir'])) {
        $post['tempdir'] = find_temp_dir();
    }

    if (!empty($post['file_max_upload_size'])) {
        $post['file_max_upload_size'] = real_max_upload_size($post['file_max_upload_size'], false);
    }

    if (isset($post['auto_dst'])) {
        $prefs['auto_dst'] = $auto_dst = $post['auto_dst'];

        if (isset($post['is_dst']) && !$post['auto_dst']) {
            $is_dst = $post['is_dst'];
        }
    }

    // Forge $gmtoffset and $is_dst from $timezone_key if present.
    if (!empty($post['timezone_key'])) {
        $key = $post['timezone_key'];
        $tzd = Txp::get('\Textpattern\Date\Timezone')->getTimeZones();

        if (isset($tzd[$key])) {
            $prefs['timezone_key'] = $timezone_key = $key;

            if ($auto_dst) {
                $post['gmtoffset'] = $prefs['gmtoffset'] = $gmtoffset = $tzd[$key]['offset'];
                $post['is_dst'] = $prefs['is_dst'] = $is_dst = (int)Txp::get('\Textpattern\Date\Timezone')->isDst(null, $key);
            }
        }
    }

    if (isset($post['siteurl'])) {
        $post['siteurl'] = preg_replace('#^https?://#', '', rtrim($post['siteurl'], '/ '));
    }

    $theme_name = get_pref('theme_name');

    while ($a = nextRow($prefnames)) {
        extract($a);

        if (!isset($post[$name]) || (!has_privs('prefs.'.$event) && $user_name === '')) {
            continue;
        }

        if (is_array($post[$name])) {
            $post[$name] = implode(',', array_diff($post[$name], array('')));
        }

        if ($name === 'logging' && $post[$name] === 'none' && $post[$name] !== $val) {
            safe_truncate('txp_log');
        }

        if ($name === 'expire_logs_after' && (int)$post[$name] !== (int) $val) {
            safe_delete('txp_log', "time < DATE_SUB(NOW(), INTERVAL ".intval($post[$name])." DAY)");
        }

        if ((string) $post[$name] !== $val) {
            update_pref($name, (string) $post[$name], null, null, null, null, (string) $user_name);
        }
    }

    update_lastmod('preferences_saved');
    $prefs = get_prefs(array('', $txp_user));
    plug_privs();

    if (!empty($post['theme_name']) && $post['theme_name'] != $theme_name) {
        $theme = \Textpattern\Admin\Theme::init();
    }

    prefs_list(gTxt('preferences_saved'));
}

/**
 * Renders the list of preferences.
 *
 * Plugins may add their own prefs, for example by using plugin lifecycle events
 * or raising a (pre) callback on event=admin / step=prefs_list so they are
 * installed or updated when accessing the Preferences panel. Access to the
 * prefs can be controlled by using add_privs() on 'prefs.your-prefs-event-name'.
 *
 * @param string $message The feedback / error string to display
 */

function prefs_list($message = '')
{
    global $prefs, $txp_user, $txp_options;

    extract($prefs);

    pagetop(gTxt('tab_preferences'), $message);

    $locale = setlocale(LC_ALL, $locale);

    echo n.'<form class="prefs-form" id="prefs_form" method="post" action="index.php">';

    // TODO: remove 'custom' when custom fields are refactored.
    $core_events = array('site', 'admin', 'publish', 'feeds', 'mail', 'comments', 'custom');
    $joined_core = join(',', quote_list($core_events));
    $level = has_privs();

    $sql = array();

    foreach ($txp_options as $pref => $option) {
        if (is_array($option) && isset($option[0]) && !in_list($level, $option[0])) {
            $sql[] = "name != '".doSlash($pref)."'";
        }
    }

    $sql[] = 'event != "" AND type IN('.PREF_CORE.', '.PREF_PLUGIN.')';
    $sql[] = "(user_name = '' OR (user_name = '".doSlash($txp_user)."' AND name NOT IN (
            SELECT name FROM ".safe_pfx('txp_prefs')." WHERE user_name = ''
        )))";

    if (!get_pref('use_comments', 0, 1)) {
        $sql[] = "event != 'comments'";
    }

    $rs = safe_rows_start(
        "*, FIELD(event, $joined_core) AS sort_value",
        'txp_prefs',
        join(" AND ", $sql)." ORDER BY sort_value = 0, sort_value, event, collection, position"
    );

    $last_event = $last_sub_event = null;
    $out = array();
    $build = array();
    $groupOut = array();

    if (class_exists('\Textpattern\Module\Help\HelpAdmin')) {
        $pophelp_keys = \Txp::get('\Textpattern\Module\Help\HelpAdmin')->pophelp_keys('prefs');
    } else {
        $pophelp_keys = array();
    }

    if (numRows($rs)) {
        while ($a = nextRow($rs)) {
            $eventParts = explode('.', $a['event']);
            $mainEvent = $eventParts[0];
            $subEvent = isset($eventParts[1]) ? $eventParts[1] : $a['collection'];

            if (!has_privs('prefs.'.$a['event']) && $a['user_name'] === '') {
                continue;
            }

            if ($mainEvent !== $last_event) {
                if ($last_event !== null) {
                    $overview_help = in_array($last_event.'_overview', $pophelp_keys, true) ? $last_event.'_overview' : '';
                    $build[] = tag(
                        hed(gTxt($last_event).popHelp($overview_help), 2, array('id' => 'prefs_group_'.$last_event.'-label')).
                        join(n, $out), 'section', array(
                            'class'           => 'txp-tabs-vertical-group',
                            'id'              => 'prefs_group_'.$last_event,
                            'aria-labelledby' => 'prefs_group_'.$last_event.'-label',
                        )
                    );

                    $groupOut[] = n.tag(href(
                        gTxt($last_event),
                        '#prefs_group_'.$last_event,
                        array(
                                'data-txp-pane'  => $last_event,
                                'data-txp-token' => md5($last_event.'prefs'.form_token().get_pref('blog_uid')),
                            )),
                        'li');
                }

                $last_event = $mainEvent;
                $out = array();
            }

            switch ($a['html']) {
                case 'yesnoradio':
                case 'is_dst':
                    $label = '';
                    break;
                case 'gmtoffset_select':
                    $label = 'tz_timezone';
                    break;
                default:
                    $label = $a['name'];
                    break;
            }

            $help = in_array($a['name'], $pophelp_keys, true) ? $a['name'] : '';

            // @todo: Ready for constraints to be read from $a['constraints'].
            $constraints = array();

            if ($subEvent !== '' && $last_sub_event !== $subEvent) {
                $collectionClass = (!empty($a['collection']) ? $a['collection'] : '');
                $out[] = hed(gTxt($subEvent), 3, array('class' => $collectionClass));
                $last_sub_event = $subEvent;
            }

            $out[] = inputLabel(
                $a['name'],
                pref_func($a['html'], $a['name'], $a['val'], $constraints),
                $label,
                array($help, 'instructions_'.$a['name']),
                array(
                    'class' => 'txp-form-field'.(!empty($a['collection']) ? ' '.$a['collection'] : ''),
                    'id'    => 'prefs-'.$a['name'],
                )
            );
        }
    }

    if ($last_event === null) {
        echo graf(
            span(null, array('class' => 'ui-icon ui-icon-info')).' '.
            gTxt('no_preferences'),
            array('class' => 'alert-block information')
        );
    } else {
        $overview_help = in_array($last_event.'_overview', $pophelp_keys, true) ? $last_event.'_overview' : '';
        $build[] = tag(
            hed(gTxt($last_event).popHelp($overview_help), 2, array('id' => 'prefs_group_'.$last_event.'-label')).
            join(n, $out), 'section', array(
                'class'           => 'txp-tabs-vertical-group',
                'id'              => 'prefs_group_'.$last_event,
                'aria-labelledby' => 'prefs_group_'.$last_event.'-label',
            )
        );

        $groupOut[] = n.tag(href(
            gTxt($last_event),
            '#prefs_group_'.$last_event,
            array(
                    'data-txp-pane'  => $last_event,
                    'data-txp-token' => md5($last_event.'prefs'.form_token().get_pref('blog_uid')),
                )),
            'li').n;

        echo n.'<div class="txp-layout">'.
            n.tag(
                hed(gTxt('tab_preferences'), 1, array('class' => 'txp-heading')),
                'div', array('class' => 'txp-layout-1col')
            ).
            n.tag_start('div', array('class' => 'txp-layout-4col-alt')).
            wrapGroup(
                'all_preferences',
                n.tag(join($groupOut), 'ul', array('class' => 'switcher-list')),
                'all_preferences'
            );

        if ($last_event !== null) {
            echo graf(fInput('submit', 'Submit', gTxt('save'), 'publish'), array('class' => 'txp-save'));
        }

        echo n.tag_end('div'). // End of .txp-layout-4col-alt.
            n.tag_start('div', array('class' => 'txp-layout-4col-3span')).
            join(n, $build).
            n.tag_end('div'). // End of .txp-layout-4col-3span.
            sInput('prefs_save').
            eInput('prefs').
            tInput();
    }

    echo n.'</div>'. // End of .txp-layout.
        n.'</form>';
}

/**
 * Calls a core or custom function to render a preference input control.
 *
 * @param  string    $func        Callable in a string presentation
 * @param  string    $name        HTML name/id of the input control
 * @param  string    $val         Initial (or current) value of the input control
 * @param  int|array $constraints Input constraints (width, depth, pattern, size, min, max, etc)
 * @return string HTML
 */

function pref_func($func, $name, $val, $constraints = array())
{
    if ($func != 'func' && is_callable('pref_'.$func)) {
        $func = 'pref_'.$func;
    } else {
        $string = new \Textpattern\Type\StringType($func);
        $func = $string->toCallback();

        if (!is_callable($func)) {
            $func = 'text_input';
        }
    }

    return call_user_func($func, $name, $val, $constraints);
}

/**
 * Renders a HTML &lt;input&gt; element.
 *
 * @param  string    $name        HTML name and id of the text box
 * @param  string    $val         Initial (or current) content of the text box
 * @param  int|array $constraints Textbox size constraints or single size int. Array options:
 *                                -> size (medium, small, xsmall)
 *                                -> minlength
 *                                -> maxlength
 *                                -> pattern
 * @return string HTML
 */

function text_input($name, $val, $constraints = array())
{
    $atts['id'] = $name;
    $atts['size'] = INPUT_REGULAR;

    $siz = array();
    $pat = array();
    $cons = array();

    if (is_numeric($constraints) || is_string($constraints)) {
        // Backwards compatibility with old text_input signature.
        $siz['size'] = $constraints;
    } elseif (!empty($constraints)) {
        foreach ($constraints as $constraint => $option) {
            switch ($constraint) {
                case 'size':
                case 'min':
                case 'max':
                    $siz[$constraint] = $option;
                    break;
                case 'pattern':
                    $pat[$constraint] = $option;
                    break;
            }
        }
    }

    if ($siz) {
        $cons[] = Txp::get('Textpattern\Validator\SizeConstraint', null, $siz);
    }

    if ($pat) {
        $cons[] = Txp::get('Textpattern\Validator\PatternConstraint', null, $pat);
    }

    $out = Txp::get('\Textpattern\UI\Input', $name, 'text', $val)->setAtts($atts);

    if (!empty($cons)) {
        $out->setConstraints($cons);
    }

    return $out;
}

/**
 * Renders a HTML &lt;input&gt; element of number type with min/max/step.
 *
 * @param  string $name        HTML name and id of the number box
 * @param  int    $val         Initial (or current) value of the number box
 * @param  array  $constraints Number box size constraints (min, max step)
 * @return string HTML
 */

function pref_number($name, $val, $constraints = array())
{
    $out = Txp::get('\Textpattern\UI\Number', $name, $val);

    if (!empty($constraints)) {
        $out->setConstraints(Txp::get('\Textpattern\Validator\RangeConstraint', null, $constraints));
    }

    return $out;
}

/**
 * Renders a HTML &lt;textarea&gt; element.
 *
 * @param  string    $name        HTML name of the textarea
 * @param  string    $val         Initial (or current) content of the textarea
 * @param  int|array $constraints Textbox size constraints or single number of rows. Array options:
 *                                -> rows
 *                                -> cols
 *                                -> minlength
 *                                -> maxlength
 * @return string HTML
 */

function pref_longtext_input($name, $val, $constraints = array())
{
    if (is_numeric($constraints)) {
        $atts['rows'] = (int)$constraints;
    } else {
        $atts = $constraints;
    }

    $out = Txp::get('\Textpattern\UI\Textarea', $name, $val);

    if (!empty($atts)) {
        $out->setConstraints(Txp::get('Textpattern\Validator\SizeConstraint', null, $atts));
    }

    return $out;
}

/**
 * Renders a HTML &lt;select&gt; list of cities for timezone selection.
 *
 * Can be altered by plugins via the 'prefs_ui > gmtoffset'
 * pluggable UI callback event.
 *
 * @param  string $name HTML name of the list
 * @param  string $val  Initial (or current) selected option
 * @return string HTML
 */

function gmtoffset_select($name, $val)
{
    // Fetch *hidden* pref
    $key = get_pref('timezone_key', '', true);

    if ($key === '') {
        $key = (string) Txp::get('\Textpattern\Date\Timezone')->getTimezone();
    }

    $ui = timezoneSelectInput('timezone_key', $key, false, '', 'gmtoffset');

    return pluggable_ui('prefs_ui', 'gmtoffset', $ui, $name, $val);
}

/**
 * Renders a HTML choice for whether Daylight Savings Time is in effect.
 *
 * Can be altered by plugins via the 'prefs_ui > is_dst'
 * pluggable UI callback event.
 *
 * @param  string $name HTML name of the input control
 * @param  string $val  Initial (or current) selected item
 * @return string HTML
 */

function is_dst($name, $val)
{
    global $timezone_key, $auto_dst;

    if ($auto_dst) {
        $val = (int)Txp::get('\Textpattern\Date\Timezone')->isDst(null, $timezone_key);
    }


    $ui = Txp::get('\Textpattern\UI\YesNoRadioSet', $name, $val).
    script_js(<<<EOS
        $(document).ready(function ()
        {
            var radio = $("#prefs-is_dst");
            var radioInput = radio.find('input');
            var radioLabel = radio.find('.txp-form-field-label');
            var dstOn = $("#auto_dst-1");
            var dstOff = $("#auto_dst-0");

            if (radio.length) {
                if (dstOn.prop("checked")) {
                    radioInput.prop("disabled", "disabled");
                    radioLabel.addClass('disabled');
                }

                dstOff.click(function () {
                    radioInput.prop("disabled", null);
                    radioLabel.removeClass('disabled');
                });

                dstOn.click(function () {
                    radioInput.prop("disabled", "disabled");
                    radioLabel.addClass('disabled');
                });
            }
        });
EOS
    , false);

    return pluggable_ui('prefs_ui', 'is_dst', $ui, $name, $val);
}

/**
 * Renders a HTML &lt;select&gt; list of hit logging options.
 *
 * @param  string $name HTML name and id of the list
 * @param  string $val  Initial (or current) selected item
 * @return string HTML
 */

function logging($name, $val)
{
    $vals = array(
        'all'   => gTxt('all_hits'),
        'refer' => gTxt('referrers_only'),
        'none'  => gTxt('none'),
    );

    return Txp::get('\Textpattern\UI\Select', $name, $vals, $val)->setAtt('id', $name);
}

/**
 * Renders a HTML input control overridable by constants.
 *
 * @param  string $name HTML name and id of the list
 * @param  string $val  Initial (or current) selected item
 * @return string HTML
 */

function smtp_handler($name, $val, $constraints = array())
{
    $constName = strtoupper($name);
    $enabled = defined($constName) ? false : true;
    $ui = '';

    switch ($name) {
        case 'smtp_host':
        case 'smtp_user':
            $ui = text_input($name, $val, $constraints);
            break;
        case 'smtp_pass':
            $ui = Txp::get('\Textpattern\UI\Input', $name, 'password', $val)->setAtt('id', $name);
            break;
        case 'smtp_port':
            // @todo: remove this and read constraints from prefs table.
            $constraints = array('min' => 1, 'max' => '65535');
            $ui = pref_number($name, $val, $constraints);
            break;
        case 'smtp_sectype':
            $vals = array(
                PHPMailer::ENCRYPTION_SMTPS    => 'SSL',
                PHPMailer::ENCRYPTION_STARTTLS => 'TLS',
                'none'                         => gTxt('none'),
            );
            $ui = Txp::get('\Textpattern\UI\Select', $name, $vals, $val)->setAtt('id', $name);
    }

    if ($enabled === false) {
        if ($ui instanceof \Textpattern\UI\TagCollection) {
            $elems = $ui->keys();

            foreach ($elems as $elem) {
                $elem->setAtt('disabled', true);
            }
        } elseif ($ui instanceof \Textpattern\UI\Tag) {
            $ui->setAtt('disabled', true);
        }
    }

    return $ui;
}

/**
 * Renders a yes/no radio button with toggle for the other SMTP settings.
 *
 * @param  string $name HTML name and id of the input control
 * @param  string $val  Initial (or current) selected option
 * @return string HTML
 */

function enhanced_email($name, $val)
{
    $js = script_js(<<<EOS
        $(document).ready(function ()
        {
            var block = $(".mail_enhanced");
            var smtpOn = $("#enhanced_email-1");
            var smtpOff = $("#enhanced_email-0");

            if (block.length) {
                if (smtpOff.prop("checked")) {
                    block.hide();
                } else {
                    block.show();
                }

                smtpOff.click(function () {
                    block.hide();
                });

                smtpOn.click(function () {
                    block.show();
                });
            }
        });
EOS
    , false);

    return Txp::get('\Textpattern\UI\YesNoRadioSet', $name, $val).$js;
}

/**
 * Render a multi-select list of Form Types
 *
 * @param  string $name HTML name and id of the input control
 * @param  string $val  Initial (or current) selected item(s)
 * @return string HTML
 */

function overrideTypes($name, $val)
{
    $instance = Txp::get('Textpattern\Skin\Form');
    $form_types = array();

    $val = do_list($val);

    foreach ($instance->getTypes() as $type) {
        $form_types[$type] = gTxt($type);
    }

    $js = script_js(<<<EOS
        $(document).ready(function ()
        {
            var block = $("#prefs-override_form_types");
            var overrideOn = $("#allow_form_override-1");
            var overrideOff = $("#allow_form_override-0");

            if (block.length) {
                if (overrideOff.prop("checked")) {
                    block.hide();
                } else {
                    block.show();
                }

                overrideOff.click(function () {
                    block.hide();
                });

                overrideOn.click(function () {
                    block.show();
                });
            }
        });
EOS
    , false);


    return Txp::get('\Textpattern\UI\Select', $name, $form_types, $val)->setAtt('id', $name)->setMultiple('name').$js;
}

/**
 * Renders a HTML choice of comment popup modes.
 *
 * @param  string $name HTML name and id of the input control
 * @param  string $val  Initial (or current) selected item
 * @return string HTML
 */

function commentmode($name, $val)
{
    $vals = array(
        '0' => gTxt('nopopup'),
        '1' => gTxt('popup'),
    );

    return Txp::get('\Textpattern\UI\Select', $name, $vals, $val)->setAtt('id', $name);
}

/**
 * Renders a HTML &lt;select&gt; list of new comment validity periods.
 *
 * Can be altered by plugins via the 'prefs_ui > weeks'
 * pluggable UI callback event.
 *
 * @param  string $name HTML name and id of the input control
 * @param  string $val  Initial (or current) selected item
 * @return string HTML
 */

function weeks($name, $val)
{
    $weeks = gTxt('weeks');

    $vals = array(
        '0' => gTxt('never'),
        7   => '1 '.gTxt('week'),
        14  => '2 '.$weeks,
        21  => '3 '.$weeks,
        28  => '4 '.$weeks,
        35  => '5 '.$weeks,
        42  => '6 '.$weeks,
        56  => '8 '.$weeks,
        84  => '12 '.$weeks,
    );

    return pluggable_ui('prefs_ui', 'weeks', Txp::get('\Textpattern\UI\Select', $name, $vals, $val)->setAtt('id', $name), $name, $val);
}

/**
 * Renders a HTML &lt;select&gt; list of available ways to display the date.
 *
 * Can be altered by plugins via the 'prefs_ui > dateformats'
 * pluggable UI callback event.
 *
 * @param  string $name HTML name and id of the input control
 * @param  string $val  Initial (or current) selected item
 * @return string HTML
 */

function dateformats($name, $val)
{
    $formats = txp_dateformats();
    $ts = time();
    $vals = array();

    foreach ($formats as $f) {
        if ($d = safe_strftime($f, $ts)) {
            $vals[$f] = $d;
        }
    }

    $vals['since'] = gTxt('hours_days_ago');

    return pluggable_ui('prefs_ui', 'dateformats', Txp::get('\Textpattern\UI\Select', $name, array_unique($vals), $val)->setAtt('id', $name), compact('vals', 'name', 'val', 'ts'));
}

/**
 * Renders a HTML &lt;select&gt; list of content permlink options.
 *
 * @param  string $name HTML name and id of the input control
 * @param  string $val  Initial (or current) selected item
 * @return string HTML
 */

function permlink_format($name, $val)
{
    $vals = array(
        '0' => gTxt('permlink_intercapped'),
        '1' => gTxt('permlink_hyphenated'),
    );

    return Txp::get('\Textpattern\UI\Select', $name, $vals, $val)->setAtt('id', $name);
}

/**
 * Renders a HTML &lt;select&gt; list of site production status.
 *
 * @param  string $name HTML name and id of the input control
 * @param  string $val  Initial (or current) selected item
 * @return string HTML
 */

function prod_levels($name, $val)
{
    $vals = array(
        'debug'   => gTxt('production_debug'),
        'testing' => gTxt('production_test'),
        'live'    => gTxt('production_live'),
    );

    return Txp::get('\Textpattern\UI\Select', $name, $vals, $val)->setAtt('id', $name);
}

/**
 * Renders a HTML &lt;select&gt; list of available panels to show immediately
 * after login.
 *
 * @param  string $name HTML name of the input control
 * @param  string $val  Initial (or current) selected item
 * @return string HTML
 */

function default_event($name, $val)
{
    $vals = areas();

    $out = array();

    foreach ($vals as $a => $b) {
        if (count($b) > 0) {
            $out[] = n.'<optgroup label="'.gTxt('tab_'.$a).'">';

            foreach ($b as $c => $d) {
                $out[] = n.'<option value="'.$d.'"'.($val == $d ? ' selected="selected"' : '').'>'.$c.'</option>';
            }

            $out[] = n.'</optgroup>';
        }
    }

    return n.'<select class="default-events" id="default_event" name="'.$name.'">'.
        join('', $out).
        n.'</select>';
}

/**
 * Renders a HTML &lt;select&gt; list of sendmail options.
 *
 * @param  string $name HTML name and id of the input control
 * @param  string $val  Initial (or current) selected item
 * @return string HTML
 */

function commentsendmail($name, $val)
{
    $vals = array(
        '1' => gTxt('all'),
        '0' => gTxt('none'),
        '2' => gTxt('ham'),
    );

    return Txp::get('\Textpattern\UI\Select', $name, $vals, $val)->setAtt('id', $name);
}

/**
 * Renders a HTML custom field.
 *
 * Can be altered by plugins via the 'prefs_ui > custom_set'
 * pluggable UI callback event.
 *
 * @param  string $name HTML name of the input control
 * @param  string $val  Initial (or current) content
 * @return string HTML
 * @todo   deprecate or move this when CFs are migrated to the meta store
 */

function custom_set($name, $val)
{
    return pluggable_ui('prefs_ui', 'custom_set', text_input($name, $val, INPUT_REGULAR), $name, $val);
}

/**
 * Renders a HTML &lt;select&gt; list of installed admin-side themes.
 *
 * Can be altered by plugins via the 'prefs_ui > theme_name'
 * pluggable UI callback event.
 *
 * @param  string $name HTML name and id of the input control
 * @param  string $val  Initial (or current) selected item
 * @return string HTML
 */

function themename($name, $val)
{
    $vals = \Textpattern\Admin\Theme::names(1);
    asort($vals, SORT_STRING);

    return pluggable_ui('prefs_ui', 'theme_name', Txp::get('\Textpattern\UI\Select', $name, $vals, $val)->setAtt('id', $name));
}

/**
 * Renders a HTML &lt;select&gt; list of available public site markup schemes to
 * adhere to.
 *
 * @param  string $name HTML name and id of the input control
 * @param  string $val  Initial (or current) selected item
 * @return string HTML
 */

function doctypes($name, $val)
{
    $vals = array(
        'xhtml' => 'XHTML',
        'html5' => 'HTML5',
    );

    return Txp::get('\Textpattern\UI\Select', $name, $vals, $val)->setAtt('id', $name);
}

/**
 * Renders a HTML &lt;select&gt; list of available publishing
 * status values.
 *
 * @param  string $name HTML name and id of the input control
 * @param  string $val  Initial (or current) selected item
 * @return string HTML
 */

function defaultPublishStatus($name, $val)
{
    $statuses = status_list();
    $statusa = has_privs('article.publish') ? $statuses : array_diff_key($statuses, array(STATUS_LIVE => 'live', STATUS_STICKY => 'sticky'));

    return Txp::get('\Textpattern\UI\Select', $name, $statusa, $val)->setAtt('id', $name);
}

/**
 * Renders a HTML &lt;select&gt; list of module_pophelp options.
 *
 * @param  string $name HTML name and id of the list
 * @param  string $val  Initial (or current) selected item
 * @return string HTML
 */

function module_pophelp($name, $val)
{
    $vals = array(
        '0' => gTxt('none'),
        '1' => gTxt('pophelp'),
    );

    return Txp::get('\Textpattern\UI\Select', $name, $vals, $val)->setAtt('id', $name);
}
