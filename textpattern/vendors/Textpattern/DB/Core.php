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
 * Core.
 *
 * @since   4.7.0
 * @package DB
 */

namespace Textpattern\DB;

class Core
{
    /**
     * Textpattern table structure directory.
     *
     * @var string
     */

    protected $tables_dir;
    protected $tables_structure = array();

    protected $data_dir;

    /**
     * Constructor.
     */

    public function __construct()
    {
        $this->tables_dir = dirname(__FILE__).DS.'Tables';
        $this->data_dir = dirname(__FILE__).DS.'Data';
    }

    /**
     * getStructure
     *
     * @param table name or empty
     */

    public function getStructure($table = '')
    {
        if (empty($this->tables_structure)) {
            $this->tables_structure = get_files_content($this->tables_dir, 'table');
        }

        if (!empty($table)) {
            return isset($this->tables_structure[$table]) ? $this->tables_structure[$table] : '';
        }

        return $this->tables_structure;
    }

    /**
     * Create All Tables
     */

    public function createAllTables()
    {
        foreach ($this->getStructure() as $key => $data) {
            safe_create($key, $data);
        }
    }

    /**
     * Create Table
     *
     * @param table name
     */

    public function createTable($table)
    {
        if ($data = $this->getStructure($table)) {
            safe_create($table, $data);
        }
    }

    /**
     * Initial mandatory data
     */

    public function initData()
    {
        $import = new \Textpattern\Import\TxpXML();

        foreach (get_files_content($this->data_dir, 'xml') as $key => $data) {
            $import->importXml($data);
        }
    }

    /**
     * Create core prefs
     */

    public function initPrefs()
    {
        foreach ($this->getPrefsDefault() as $name => $p) {
            if (empty($p['private'])) {
                create_pref($name, $p['val'], $p['event'], $p['type'], $p['html'], $p['position']);
            }
        }
    }

    /**
     * Get Textpattern tables name
     *
     * @return array
     */

    public function getTablesName()
    {
        return array_keys($this->getStructure());
    }

    /**
     * Get default core prefs
     *
     * @return array
     */

    public function getPrefsDefault()
    {
        global $permlink_mode, $siteurl, $theme_name, $pref, $language;

        $out = json_decode(txp_get_contents($this->data_dir.DS.'core.prefs'), true);

        if (empty($out)) {
            return array();
        }

        if (empty($language)) {
            $language = safe_field('lang', 'txp_lang', '1=1 GROUP BY lang ORDER BY COUNT(*) DESC');

            if (empty($language)) {
                $language = TEXTPATTERN_DEFAULT_LANG;
            }
        }

        // Legacy pref name, just in case.
        $permlink_format = get_pref('permalink_title_format', null);

        if ($permlink_format === null) {
            $permlink_format = get_pref('permlink_format', 1);
        }

        $language = \Txp::get('\Textpattern\L10n\Locale')->validLocale($language);

        $path_to_public_site = (isset($txpcfg['multisite_root_path'])) ? $txpcfg['multisite_root_path'].DS.'public' : dirname(txpath);

        $pf = array();
        $pf['file_base_path']  = $path_to_public_site.DS.'files';
        $pf['path_to_site']    = $path_to_public_site;
        $pf['tempdir']         = find_temp_dir();
        $pf['siteurl']         = $siteurl;
        $pf['theme_name']      = empty($theme_name) ? 'hive' : $theme_name;
        $pf['blog_mail_uid']   = empty($_SESSION['email']) ? md5(rand()).'blog@example.com' : $_SESSION['email'];
        $pf['blog_uid']        = empty($pref['blog_uid']) ? md5(uniqid(rand(), true)) : $pref['blog_uid'];
        $pf['language']        = $language;
        $pf['language_ui']     = $language;
        $pf['locale']          = getlocale($language);
        $pf['sitename']        = gTxt('my_site');
        $pf['site_slogan']     = gTxt('my_slogan');
        $pf['gmtoffset']       = sprintf("%+d", gmmktime(0, 0, 0) - mktime(0, 0, 0));
        $pf['permlink_mode']   = empty($permlink_mode) ? 'messy' : $permlink_mode;
        $pf['permlink_format'] = $permlink_format;
        $pf['sql_now_posted']  = $pf['sql_now_expires'] = $pf['sql_now_created'] = time();
        $pf['comments_default_invite'] = (gTxt('setup_comment_invite') == 'setup_comment_invite') ? 'Comment'
            : gTxt('setup_comment_invite');
        $pf['default_section'] = empty($pref['default_section']) ? safe_field('name', 'txp_section', "name<>'default'")
            : $pref['default_section'];

        foreach ($pf as $name => $val) {
            if (isset($out[$name])) {
                $out[$name]['val'] = $val;
            }
        }

        return $out;
    }

    /**
     * Checks prefs integrity and AutoCreate missing prefs.
     */

    public function checkPrefsIntegrity()
    {
        global $prefs, $txp_user;

        // Rename previous Global/Private prefs.
        $renamed = json_decode(txp_get_contents($this->data_dir.DS.'renamed.prefs'), true);

        if (!empty($renamed['global'])) {
            foreach ($renamed['global'] as $oldKey => $newKey) {
                rename_pref($newKey, $oldKey);
            }
        }

        if (!empty($deleted['private'])) {
            foreach ($renamed['private'] as $oldKey => $newKey) {
                safe_update('txp_prefs', "name = '".doSlash($newKey)."'", "name='".doSlash($oldKey)."' AND user_name != ''");
            }
        }

        // Delete old Global/Private prefs.
        $deleted = json_decode(txp_get_contents($this->data_dir.DS.'deleted.prefs'), true);

        if (!empty($deleted['global'])) {
            safe_delete('txp_prefs', "name in ('".join("','", doSlash($deleted['global']))."') AND user_name = ''");
        }

        if (!empty($deleted['private'])) {
            safe_delete('txp_prefs', "name in ('".join("','", doSlash($deleted['private']))."') AND user_name != ''");
        }

        $prefs_check = array_merge(
            get_prefs_theme(),
            $this->getPrefsDefault()
        );

        if ($rs = safe_rows_start('name, type, event, html, position', 'txp_prefs', "user_name = '' OR user_name = '".doSlash($txp_user)."'")) {
            while ($row = nextRow($rs)) {
                $name = array_shift($row);

                if (!empty($prefs_check[$name])) {
                    $def = $prefs_check[$name];

                    $private = empty($def['private']) ? PREF_GLOBAL : PREF_PRIVATE;
                    unset($def['val'], $def['private']);


                    if ($def['event'] != 'custom' && $def != $row) {
                        set_pref($name, null, $def['event'], $def['type'], $def['html'], $def['position'], $private);
                    }

                    unset($prefs_check[$name]);
                }
            }
        }

        // Create missing prefs.
        foreach ($prefs_check as $name => $p) {
            $private = empty($p['private']) ? PREF_GLOBAL : PREF_PRIVATE;
            create_pref($name, $p['val'], $p['event'], $p['type'], $p['html'], $p['position'], $private);
        }

        $prefs = get_prefs();
    }
}
