<?php

/*
 * Textpattern Content Management System
 * http://textpattern.com
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
 * along with Textpattern. If not, see <http://www.gnu.org/licenses/>.
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
     *
     */

    public function __construct()
    {
        $this->tables_dir = dirname(__FILE__).DS.'Tables';
        $this->data_dir = dirname(__FILE__).DS.'Data';
    }

    private function getStructure()
    {
        if (empty($this->tables_structure)) {
            $this->tables_structure = get_files_content($this->tables_dir, 'table');
        }
    }

    /**
     * Create tables
     *
     */

    public function createAllTables()
    {
        $this->getStructure();
        foreach ($this->tables_structure as $key=>$data) {
            safe_create($key, $data);
        }
    }

    /**
     * Initial mandatory data
     *
     */

    public function initData()
    {
        foreach (get_files_content($this->data_dir, 'xml') as $key=>$data) {
            import_txp_xml($data, $key);
        }
    }

    /**
     * Create core prefs
     *
     */

    public function initPrefs()
    {
        foreach ($this->getPrefsDefault() as $name => $p) {
            if (empty($p['private'])) {
                @create_pref($name, $p['val'], $p['event'], $p['type'], $p['html'], $p['position']);
            }
        }
    }



    public function getTablesName()
    {
        $this->getStructure();

        return array_keys($this->tables_structure);
    }

    /**
     * Get default core prefs
     *
     */

    public function getPrefsDefault()
    {
        global $permlink_mode, $siteurl, $blog_uid, $theme_name, $pref, $language;

        $out = @json_decode(file_get_contents($this->data_dir.DS.'core.prefs'), true);
        if (empty($out)) {
            return array();
        }

        if (empty($language)) {
            $language = safe_field('lang', 'txp_lang', '1=1 GROUP BY lang ORDER BY COUNT(*) DESC');
            if (empty($language)) {
                $language = TEXTPATTERN_DEFAULT_LANG;
            }
        }

        $pf = array();
        $pf['file_base_path'] = dirname(txpath).DS.'files';
        $pf['path_to_site']   = dirname(txpath);
        $pf['tempdir']        = find_temp_dir();
        $pf['siteurl']        = $siteurl;
        $pf['theme_name']     = empty($theme_name) ? 'hive' : $theme_name;
        $pf['blog_mail_uid']  = empty($_SESSION['email']) ? md5(rand()).'blog@gmail.com' : $_SESSION['email'];
        $pf['blog_uid']       = empty($blog_uid) ? md5(uniqid(rand(), true)) : $blog_uid;
        $pf['language']       = $language;
        $pf['locale']         = getlocale($language);
        $pf['sitename']       = gTxt('my_site');
        $pf['site_slogan']    = gTxt('my_slogan');
        $pf['gmtoffset']      = sprintf("%+d", gmmktime(0, 0, 0) - mktime(0, 0, 0));
        $pf['permlink_mode']  = empty($permlink_mode) ? 'messy' : $permlink_mode;
        $pf['sql_now_posted'] = $pf['sql_now_expires'] = $pf['sql_now_created'] = time();
        $pf['comments_default_invite'] = (gTxt('setup_comment_invite') == 'setup_comment_invite') ? 'Comment'
            : gTxt('setup_comment_invite');
        $pf['default_section']= empty($pref['default_section']) ? safe_field('name', 'txp_section', "name<>'default'")
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
     *
     */

    public function checkPrefsIntegrity()
    {
        global $prefs, $txp_user;

        $prefs_check = array_merge(
            get_prefs_theme(),
            $this->getPrefsDefault()
        );

        if ($rs = safe_rows_start('name, type, event, html, position', 'txp_prefs', "user_name = '' OR user_name = '".doSlash($txp_user)."'")) {
            while ($row = nextRow($rs)) {
                $name = array_shift($row);
                if ($def = @$prefs_check[$name]) {
                    $private = empty($def['private']) ? PREF_GLOBAL : PREF_PRIVATE;
                    unset($def['val'], $def['private']);
                    if ($def != $row) {
                        @update_pref($name, null, $def['event'], $def['type'], $def['html'], $def['position'], $private);
                    }
                    unset($prefs_check[$name]);
                }
            }
        }

        // Create missing prefs
        foreach ($prefs_check as $name => $p) {
            $private = empty($p['private']) ? PREF_GLOBAL : PREF_PRIVATE;
            @create_pref($name, $p['val'], $p['event'], $p['type'], $p['html'], $p['position'], $private);
        }

        $prefs = get_prefs();
    }


}
