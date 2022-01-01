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
 * Textpattern Wrapper Class for Textpattern 4.0.x.
 *
 * Main goal for this class is to be used as a textpattern data wrapper by
 * any code which needs to have access to the textpattern articles data,
 * like XML-RPC, Atom, Moblogging or other external implementations.
 *
 * This class requires including some Textpattern files in order to work
 * properly. See RPC Server implementation to view an example of the required
 * files and predefined variables.
 *
 * @link      https://web.archive.org/web/20141201035729/http://txp.kusor.com/wrapper
 * @author    Pedro Palazón
 * @copyright 2005-2008 The Textpattern Development Team
 */

if (!defined('txpath')) {
    die('txpath is undefined.');
}

include_once txpath.'/include/txp_auth.php';

if (!defined('LEAVE_TEXT_UNTOUCHED')) {
    /**
     * @ignore
     */

    define('LEAVE_TEXT_UNTOUCHED', 0);
}

if (!defined('USE_TEXTILE')) {
    /**
     * @ignore
     */

    define('USE_TEXTILE', 1);
}

if (!defined('CONVERT_LINEBREAKS')) {
    /**
     * @ignore
     */

    define('CONVERT_LINEBREAKS', 2);
}

/**
 * Wrapper for Textpattern.
 *
 * @package Wrapper
 */

class TXP_Wrapper
{
    /**
     * The current user.
     *
     * Remember to always use $this->txp_user when checking
     * for permissions with this class.
     *
     * @var string
     */

    public $txp_user = null;

    /**
     * Authenticated connection.
     *
     * @var bool
     */

    public $loggedin = false;

    /**
     * Predefined Textpattern variables to be populated.
     *
     * @var array
     */

    public $vars = array(
        'ID',
        'Title',
        'Title_html',
        'Body',
        'Body_html',
        'Excerpt',
        'Excerpt_html',
        'textile_excerpt',
        'Image',
        'textile_body',
        'Keywords',
        'Status',
        'Posted',
        'Section',
        'Category1',
        'Category2',
        'Annotate',
        'AnnotateInvite',
        'AuthorID',
        'Posted',
        'override_form',
        'url_title',
        'custom_1',
        'custom_2',
        'custom_3',
        'custom_4',
        'custom_5',
        'custom_6',
        'custom_7',
        'custom_8',
        'custom_9',
        'custom_10',
    );

    /**
     * Constructor.
     *
     * This is used to pass user credentials
     * to the wrapper.
     *
     * @param string $txp_user The user login name
     * @param string $txpass   User password
     * @example
     * $wrapper = new TXP_wrapper('username', 'password');
     */

    public function __construct($txp_user, $txpass = null)
    {
        if ($this->_validate($txp_user, $txpass)) {
            $this->txp_user = $txp_user;
            $this->loggedin = true;
        }
    }

    /**
     * Deletes an article with the given ID.
     *
     * @param  int $article_id The article
     * @return bool TRUE on success
     */

    public function deleteArticleID($article_id)
    {
        $article_id = assert_int($article_id);

        if ($this->loggedin && has_privs('article.delete', $this->txp_user)) {
            return safe_delete('textpattern', "ID = $article_id");
        } elseif ($this->loggedin && has_privs('article.delete.own', $this->txp_user)) {
            $r = safe_field("ID", 'textpattern', "ID = $article_id AND AuthorID = '".doSlash($this->txp_user)."'");

            if ($r || has_privs('article.delete', $this->txp_user)) {
                return safe_delete('textpattern', "ID = $article_id");
            }
        }

        return false;
    }

    /**
     * Retrieves a list of articles matching the given criteria.
     *
     * This method forms an SQL query from the given arguments and returns an
     * array of resulting articles.
     *
     * This method requires authentication and at least 'article.edit.own'
     * privileges. If the user doesn't have 'article.edit' privileges,
     * only the user's own articles can be accessed.
     *
     * @param  string $what   The select clause
     * @param  string $where  The where clause
     * @param  int    $offset The offset
     * @param  int    $limit  The limit
     * @param  bool   $slash  If TRUE, escapes $where and $what
     * @return array|bool Array of articles, or FALSE on failure
     */

    public function getArticleList($what = '*', $where = '1', $offset = 0, $limit = 10, $slash = true)
    {
        if ($this->loggedin && has_privs('article.edit.own', $this->txp_user)) {
            $offset = assert_int($offset);
            $limit = assert_int($limit);

            if ($slash) {
                $where = doSlash($where);
                $what = doSlash($what);
            }

            if (has_privs('article.edit', $this->txp_user)) {
                $rs = safe_rows_start($what, 'textpattern', $where." ORDER BY Posted DESC LIMIT $offset, $limit");
            } else {
                $rs = safe_rows_start($what, 'textpattern', $where." AND AuthorID = '".doSlash($this->txp_user)."' ORDER BY Posted DESC LIMIT $offset, $limit");
            }

            $out = array();

            if ($rs) {
                while ($a = nextRow($rs)) {
                    $out[] = $a;
                }
            }

            return $out;
        }

        return false;
    }

    /**
     * Retrieves an article matching the given criteria.
     *
     * This method forms an SQL query from the given arguments and returns an
     * article as an associative array.
     *
     * This method requires authentication and at least 'article.edit.own'
     * privileges. If the user doesn't have 'article.edit' privileges,
     * only the user's own articles can be accessed.
     *
     * @param  string $what  Select clause
     * @param  string $where Where clause
     * @param  bool   $slash If TRUE, escapes $where and $what
     * @return array|bool An article, or FALSE on failure
     * @see    TXP_Wrapper::getArticleList()
     * @example
     * $wrapper = new TXP_wrapper('username', 'password');
     * if ($r = $wrapper->getArticle())
     * {
     *     echo "Returned an article by the title '{$r['Title']}'.";
     * }
     */

    public function getArticle($what = '*', $where = '1', $slash = true)
    {
        if ($this->loggedin && has_privs('article.edit.own', $this->txp_user)) {
            if ($slash) {
                $what  = doSlash($what);
                $where = doSlash($where);
            }

            // Higher user groups should be able to edit any article.
            if (has_privs('article.edit', $this->txp_user)) {
                return safe_row($what, 'textpattern', $where);
            } else {
                // While restricted users should be able to edit their own
                // articles only.
                return safe_row($what, 'textpattern', $where." AND AuthorID = '".doSlash($this->txp_user)."'");
            }
        }

        return false;
    }

    /**
     * Gets an article with the given ID.
     *
     * This method is an shortcut for TXP_Wrapper::getArticle().
     *
     * @param  int    $article_id The article
     * @param  string $what       The SQL select clause
     * @return array|bool The article, or FALSE on failure
     * @see    TXP_Wrapper::getArticle()
     * @example
     * $wrapper = new TXP_wrapper('username', 'password');
     * if ($r = $wrapper->getArticleID(11))
     * {
     *     echo "Returned an article by the ID of '{$r['ID']}'.";
     * }
     */

    public function getArticleID($article_id, $what = '*')
    {
        if ($this->loggedin && has_privs('article.edit.own', $this->txp_user)) {
            $article_id = assert_int($article_id);

            if (has_privs('article.edit', $this->txp_user)) {
                return safe_row(doSlash($what), 'textpattern', "ID = $article_id");
            } else {
                return safe_row(doSlash($what), 'textpattern', "ID = $article_id AND AuthorID = '".doSlash($this->txp_user)."'");
            }
        }

        return false;
    }

    /**
     * Updates an existing article.
     *
     * This method takes an array of article fields, and updates an article with
     * the given ID. Supplied values are sanitised and prepared internally.
     *
     * @param  int   $article_id The article
     * @param  array $params     The article fields to update
     * @return int|bool The article id, or FALSE on failure
     * @example
     * $wrapper = new TXP_wrapper('username', 'password');
     * if (($id = $wrapper->updateArticleID(11, array(
     *     'Title' => 'New title',
     *     'Body'  => 'Body text.',
     * )) !== false)
     * {
     *     echo "Updated article '{$id}'.";
     * }
     */

    public function updateArticleID($article_id, $params)
    {
        $article_id = assert_int($article_id);
        $r = safe_field("ID", 'textpattern', "AuthorID = '".doSlash($this->txp_user)."' AND ID = $article_id");

        if ($this->loggedin && $r && has_privs('article.edit.own', $this->txp_user)) {
            // Unprivileged user, check if they can edit published articles.
            $r = assert_int($r);
            $oldstatus = safe_field("Status", 'textpattern', "ID = $r");

            if (($oldstatus == 4 || $oldstatus == 5) && !has_privs('article.edit.published', $this->txp_user)) {
                return false;
            }

            // If they can, let's go.
            return $this->_setArticle($params, $article_id);
        } elseif ($this->loggedin && has_privs('article.edit', $this->txp_user)) {
            // Admin editing. Desires are behest.
            return $this->_setArticle($params, $article_id);
        }

        return false;
    }

    /**
     * Creates a new article.
     *
     * @param  array $params The article fields
     * @return int|bool Article ID, or FALSE on failure
     * @example
     * $wrapper = new TXP_wrapper('username', 'password');
     * if (($id = $wrapper->newArticle(array(
     *     'Title' => 'My article',
     *     'Body'  => 'My body text',
     * )) !== false)
     * {
     *     echo "Created a new article with the ID of '{$id}'.";
     * }
     */

    public function newArticle($params)
    {
        if ($this->loggedin && has_privs('article', $this->txp_user)) {
            if (($params['Status'] == 4 || $params['Status'] == 5) && !has_privs('article.publish', $this->txp_user)) {
                $params['Status'] = 3;
            }

            return $this->_setArticle($params);
        }

        return false;
    }

    /**
     * Gets a list of sections as an associative array.
     *
     * This method requires authentication and 'article' privileges.
     *
     * @return array|bool FALSE on failure
     * @example
     * $wrapper = new TXP_wrapper('username', 'password');
     * if ($sections = $wrapper->getSectionsList())
     * {
     *     foreach ($sections as $section)
     *     {
     *         echo $section['title'];
     *     }
     * }
     */

    public function getSectionsList()
    {
        if ($this->loggedin && has_privs('article', $this->txp_user)) {
            return safe_rows("*", 'txp_section', "name != 'default'");
        }

        return false;
    }

    /**
     * Gets a section as an associative array.
     *
     * This method requires authentication and 'article' privileges.
     *
     * @param  string $name The section name
     * @return array|bool FALSE on failure
     * @example
     * $wrapper = new TXP_wrapper('username', 'password');
     * if ($section = $wrapper->getSection('my-section'))
     * {
     *     echo $section['title'];
     * }
     */

    public function getSection($name)
    {
        if ($this->loggedin && has_privs('article', $this->txp_user)) {
            $name = doSlash($name);

            return safe_row("*", 'txp_section', "name = '$name'");
        }

        return false;
    }

    /**
     * Gets a list of categories as an associative array.
     *
     * This method requires authentication and 'article' privileges.
     *
     * @return array|bool FALSE on failure
     * @example
     * $wrapper = new TXP_wrapper('username', 'password');
     * if ($categories = $wrapper->getCategoryList())
     * {
     *     foreach ($categories as $category)
     *     {
     *         echo $category['title'];
     *     }
     * }
     */

    public function getCategoryList()
    {
        if ($this->loggedin && has_privs('article', $this->txp_user)) {
            return safe_rows("*", 'txp_category', "name != 'root' AND type = 'article'");
        }

        return false;
    }

    /**
     * Gets a category as an associative array.
     *
     * This method requires authentication and 'article' privileges.
     *
     * @param  string $name The category name
     * @return array|bool FALSE on failure
     * @example
     * $wrapper = new TXP_wrapper('username', 'password');
     * if ($category = $wrapper->getCategory('my-category'))
     * {
     *     echo $category['title'];
     * }
     */

    public function getCategory($name)
    {
        if ($this->loggedin && has_privs('article', $this->txp_user)) {
            $name = doSlash($name);

            return safe_row("*", 'txp_category', "name = '$name' AND type = 'article'");
        }

        return false;
    }

    /**
     * Gets a category as an associative array by ID.
     *
     * This method is an alternative to TXP_wrapper::getCategory().
     *
     * This method requires authentication and 'article' privileges.
     *
     * @param  string $id The category ID
     * @return array|bool FALSE on failure
     */

    public function getCategoryID($id)
    {
        if ($this->loggedin && has_privs('article', $this->txp_user)) {
            $id = assert_int($id);

            return safe_row("*", 'txp_category', "id = $id");
        }

        return false;
    }

    /**
     * Gets a category as an associative array by title.
     *
     * This method is an alternative to TXP_wrapper::getCategory().
     *
     * This method requires authentication and 'article' privileges.
     *
     * @param  string $title The category title
     * @return array|bool FALSE on failure
     */

    public function getCategoryTitle($title)
    {
        if ($this->loggedin && has_privs('article', $this->txp_user)) {
            $title = doSlash($title);

            return safe_row("*", 'txp_category', "title = '$title' AND type = 'article'");
        }

        return false;
    }

    /**
     * Gets an array of information about the current user.
     *
     * This method requires authentication. Resulting array contains all columns
     * from 'txp_users' database table.
     *
     * @return array|bool FALSE on failure
     * @example
     * $wrapper = new TXP_wrapper('username', 'password');
     * if ($user = $wrapper->getUser())
     * {
     *     echo $user['RealName'] . ' ' . $user['email'];
     * }
     */

    public function getUser()
    {
        if ($this->loggedin) {
            return safe_row("*", 'txp_users', "name = '".$this->txp_user."'");
        }

        return false;
    }

    /**
     * Retrieves a page template contents with the given name.
     *
     * This method requires authentication and 'page' privileges.
     *
     * @param  string $name The template
     * @return string|bool The template, or FALSE on failure
     */

    public function getTemplate($name)
    {
        if ($this->loggedin && has_privs('page', $this->txp_user)) {
            $name = doSlash($name);

            return safe_field("user_html", 'txp_page', "name = '$name'");
        }

        return false;
    }

    /**
     * Updates a page template with the given name.
     *
     * This method requires authentication and 'page' privileges.
     *
     * @param  string $name The template name
     * @param  string $html The template contents
     * @return bool TRUE on success
     * @example
     * $wrapper = new TXP_wrapper('username', 'password');
     * if ($wrapper->setTemplate('default', '&lt;txp:site_name /&gt;'))
     * {
     *     echo "Page template updated.";
     * }
     */

    public function setTemplate($name, $html)
    {
        if ($this->loggedin && has_privs('page', $this->txp_user)) {
            $name = doSlash($name);
            $html = doSlash($html);

            return safe_update('txp_page', "user_html = '$html'", "name = '$name'");
        }

        return false;
    }

    /**
     * Intended for updating an article's non-content fields, like categories,
     * sections or keywords.
     *
     * This method requires authentication and 'article.edit' privileges.
     *
     * @param  int    $article_id The article
     * @param  string $field      The field to update
     * @param  mixed  $value      The new value
     * @return bool TRUE on success
     * @see    TXP_wrapper::updateArticleID()
     * @example
     * $wrapper = new TXP_wrapper('username', 'password');
     * if ($wrapper->updateArticleField(11, 'Section', 'new-section'))
     * {
     *     echo "Section updated.";
     * }
     */

    public function updateArticleField($article_id, $field, $value)
    {
        $disallow = array(
            'Body',
            'Body_html',
            'Title',
            'Title_html',
            'Excerpt',
            'Excerpt_html',
            'textile_excerpt',
            'textile_body',
            'LastMod',
            'LastModID',
            'feed_time',
            'uid',
        );

        if ($this->loggedin && has_privs('article.edit', $this->txp_user) && !in_array(doSlash($field), $disallow)) {
            $field = doSlash($field);
            $value = doSlash($value);

            if ($field == 'Posted') {
                $value = strtotime($value) - tz_offset();
                $value = "FROM_UNIXTIME($value)";
                $sql = "Posted = $value";
            } elseif ($field == 'Status') {
                $value = assert_int($value);
                if (!has_privs('article.publish', $this->txp_user) && $value >= 4) {
                    $value = 3;
                }
                $sql = "Status = $value";
            } else {
                $sql = "$field = '$value'";
            }

            $sql .= ", LastMod = NOW(), LastModID = '".$this->txp_user."'";
            $article_id = assert_int($article_id);
            $rs = safe_update('textpattern', $sql, "ID = $article_id");

            return $rs;
        }

        return false;
    }

    /**
     * Creates and updates articles.
     *
     * @param  array $incoming   The article fields
     * @param  int   $article_id The ID of the article to update
     * @return int|bool The article ID on success, or FALSE on failure
     * @access private
     * @see    TXP_wrapper::udpateArticleId()
     * @see    TXP_wrapper::newArticle()
     */

    public function _setArticle($incoming, $article_id = null)
    {
        global $txpcfg;

        $prefs = get_prefs();

        extract($prefs);

        if (!empty($incoming['Section']) && !$this->getSection($incoming['Section'])) {
            return false;
        }

        if (!empty($incoming['Category1']) && !$this->getCategory($incoming['Category1'])) {
            return false;
        }

        if (!empty($incoming['Category2']) && !$this->getCategory($incoming['Category2'])) {
            return false;
        }

        if ($article_id !== null) {
            $article_id = assert_int($article_id);
        }

        // All validation rules assumed to be passed before this point.
        // Do content processing here.

        $incoming_with_markup = $this->textile_main_fields($incoming, $use_textile);

        $incoming['Title'] = $incoming_with_markup['Title'];

        if (empty($incoming['Body_html']) && !empty($incoming['Body'])) {
            $incoming['Body_html'] = $incoming_with_markup['Body_html'];
        }

        if (empty($incoming['Excerpt_html']) && !empty($incoming['Excerpt'])) {
            $incoming['Excerpt_html'] = $incoming_with_markup['Excerpt_html'];
        }

        unset($incoming_with_markup);

        if (empty($incoming['Posted'])) {
            if ($article_id === null) {
                $when = (!$article_id) ? 'NOW()' : '';
                $incoming['Posted'] = $when;
            } else {
                // Do not override post time for existing articles unless Posted
                // is present.
                unset($incoming['Posted']);
            }
        } else {
            $when = strtotime($incoming['Posted']) - tz_offset();
            $when = "FROM_UNIXTIME($when)";
        }

        if ($incoming['Title'] || $incoming['Body'] || $incoming['Excerpt']) {
            // Build SQL then and run query.
            // Prevent data erase if not defined on the update action but it
            // was on the database from a previous creation/edition time.
            if ($article_id) {
                $old = safe_row("*", 'textpattern', "ID = $article_id");

                if (!has_privs('article.publish', $this->txp_user) && $incoming['Status'] == 4 && $old['Status'] != 4) {
                    $incoming['Status'] = 3;
                }

                foreach ($old as $key => $val) {
                    if (!isset($incoming[$key])) {
                        $incoming[$key] = $val;
                    }
                }
            } else {
                if (!has_privs('article.publish', $this->txp_user) && $incoming['Status'] == 4) {
                    $incoming['Status'] = 3;
                }
            }

            if (empty($incoming['Section']) && $article_id) {
                $incoming['Section'] = safe_field("Section", 'textpattern', "ID = $article_id");
            }

            $incoming = $this->_check_keys($incoming, array(
                'AuthorID'        => $this->txp_user,
                'Annotate'        => $comments_on_default,
                'AnnotateInvite'  => $comments_default_invite,
                'textile_body'    => $use_textile,
                'textile_excerpt' => $use_textile,
                'url_title'       => stripSpace($incoming['Title']),
            ));

            // Build the SQL query.
            $sql = array();

            foreach ($incoming as $key => $val) {
                if ($key == 'Posted' && $val == 'NOW()') {
                    $sql[] = "$key = $val";
                } elseif ($key != 'ID' && $key != 'uid' && $key != 'feed_time' && $key != 'LastMod' && $key != 'LastModID') {
                    $sql[] = "$key = '".doSlash($val)."'";
                }
            }

            $sql[] = "LastMod = NOW()";
            $sql[] = "LastModID = '".doSlash($this->txp_user)."'";

            if (!$article_id) {
                $sql[] = "uid = '".doSlash(md5(uniqid(rand(), true)))."'";
            }

            if (!$article_id) {
                if (empty($incoming['Posted'])) {
                    $sql[] = "feed_time = CURDATE()";
                } else {
                    $when = strtotime($incoming['Posted']) - tz_offset();
                    $when = date('Y-m-d', $when);
                    $sql[] = "feed_time = '".doSlash($when)."'";
                }
            }

            $sql = join(', ', $sql);

            $rs = ($article_id) ? safe_update('textpattern', $sql, "ID = $article_id") : safe_insert('textpattern', $sql);

            $oldstatus = ($article_id) ? $old['Status'] : '';

            if (!$article_id && $rs) {
                $article_id = $rs;
            }

            if (($incoming['Status'] >= 4 && !$article_id) || ($oldstatus != 4 && $article_id)) {
                safe_update('txp_prefs', "val = NOW()", "name = 'lastmod'");
            }

            return $article_id;
        }

        return false;
    }

    /**
     * Validates the given user credentials.
     *
     * @param  string $user     The username
     * @param  string $password The password
     * @return bool TRUE on success
     * @access private
     */

    public function _validate($user, $password = null)
    {
        if ($password !== null) {
            $r = txp_validate($user, $password);
        } else {
            $r = true;
        }

        if ($r) {
            // Update the last access time.
            $safe_user = doSlash($user);
            safe_update('txp_users', "last_access = NOW()", "name = '$safe_user'");

            return true;
        }

        return false;
    }

    /**
     * Validates and filters the given article fields.
     *
     * Checks if the given parameters are appropriate for the article.
     *
     * @param  array $incoming The incoming associative array
     * @param  array $default  An associative array containing default values for the desired keys
     * @return array Filtered data array
     * @access private
     */

    public function _check_keys($incoming, $default = array())
    {
        $out = array();

        // Strip off unsuited keys.
        foreach ($incoming as $key => $val) {
            if (in_array($key, $this->vars)) {
                $out[$key] = $val;
            }
        }

        foreach ($this->vars as $def_key) {
            // Add those ones nonexistent in the incoming array.
            if (!array_key_exists($def_key, $out)) {
                $out[$def_key] = '';
            }

            // Setup the provided default value, if any, only when the incoming
            // value is empty.
            if (array_key_exists($def_key, $default) && empty($out[$def_key])) {
                $out[$def_key] = $default[$def_key];
            }
        }

        return $out;
    }

    /**
     * Apply Textile to the main article fields.
     *
     * This is duplicated from txp_article.php.
     *
     * @param  array $incoming    The incoming fields
     * @param  bool  $use_textile Use Textile or not
     * @return array The $incoming array formatted
     * @access private
     */

    public function textile_main_fields($incoming, $use_textile = 1)
    {
        global $txpcfg;

        $textile = new \Textpattern\Textile\Parser();

        if (!empty($event) and $event == 'article') {
            $incoming['Title_plain'] = $incoming['Title'];
        }

        if ($incoming['textile_body'] == USE_TEXTILE) {
            $incoming['Title'] = $textile->textileEncode($incoming['Title']);
        }

        $incoming['url_title'] = preg_replace('|[\x00-\x1f#%+/?\x7f]|', '', $incoming['url_title']);
        $incoming['Body_html'] = TXP_Wrapper::format_field($incoming['Body'], $incoming['textile_body'], $textile);
        $incoming['Excerpt_html'] = TXP_Wrapper::format_field($incoming['Excerpt'], $incoming['textile_excerpt'], $textile);

        return $incoming;
    }

    /**
     * Formats a article field according to the given options.
     *
     * @param  string  $field  The field contents
     * @param  int     $format Either LEAVE_TEXT_UNTOUCHED, CONVERT_LINEBREAKS, USE_TEXTILE
     * @param  Textile An instance of Textile
     * @return string HTML formatted field
     * @access private
     */

    public function format_field($field, $format, $textile)
    {
        switch ($format) {
            case LEAVE_TEXT_UNTOUCHED:
                $html = trim($field);
                break;
            case CONVERT_LINEBREAKS:
                $html = nl2br(trim($field));
                break;
            case USE_TEXTILE:
                $html = $textile->parse($field);
                break;
        }

        return $html;
    }
}
