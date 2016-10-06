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

/**
 * Database abstraction layer.
 *
 * @package DB
 */

if (!defined('PFX')) {
    /**
     * Database table prefix.
     */

    define('PFX', !empty($txpcfg['table_prefix']) ? $txpcfg['table_prefix'] : '');
}

if (version_compare(PHP_VERSION, '5.3.0') < 0) {
    // We are deliberately using a deprecated function for PHP 4 compatibility.
    if (get_magic_quotes_runtime()) {
        set_magic_quotes_runtime(0);
    }
}

/**
 * Initialises a database connection.
 *
 * @package DB
 */

class DB
{
    /**
     * The database server hostname.
     *
     * @var string
     */

    public $host;

    /**
     * The database server port.
     *
     * @var int
     */

    public $port;

    /**
     * The database server socket.
     *
     * @var string
     */

    public $socket;

    /**
     * The database name.
     *
     * @var string
     */

    public $db;

    /**
     * The username.
     *
     * @var string
     */

    public $user;

    /**
     * The password.
     *
     * @var string
     */

    public $pass;

    /**
     * Database table prefix.
     *
     * @var   string
     * @since 4.6.0
     */

    public $table_prefix = '';

    /**
     * Database client flags.
     *
     * @var int
     */

    public $client_flags = 0;

    /**
     * Database connection charset.
     *
     * @var   string
     * @since 4.6.0
     */

    public $charset = '';

    /**
     * The database link identifier.
     *
     * @var resource
     */

    public $link;

    /**
     * Database server version.
     *
     * @var string
     */

    public $version;

    /**
     * Default table options definition.
     *
     * @var array
     */

    public $table_options = array();

    /**
     * The default character set for the connection.
     *
     * @var   string
     * @since 4.6.0
     */

    public $default_charset;

    /**
     * Creates a new link.
     */

    public function __construct()
    {
        global $txpcfg, $connected;

        if (strpos($txpcfg['host'], ':') === false) {
            $this->host = $txpcfg['host'];
            $this->port = ini_get("mysqli.default_port");
        } else {
            list($this->host, $this->port) = explode(':', $txpcfg['host'], 2);
            $this->port = intval($this->port);
        }

        if (isset($txpcfg['socket'])) {
            $this->socket = $txpcfg['socket'];
        } else {
            $this->socket = ini_get("mysqli.default_socket");
        }

        $this->db = $txpcfg['db'];
        $this->user = $txpcfg['user'];
        $this->pass = $txpcfg['pass'];
        $this->table_options['type'] = 'MyISAM';

        if (!empty($txpcfg['table_prefix'])) {
            $this->table_prefix = $txpcfg['table_prefix'];
        }

        if (isset($txpcfg['client_flags'])) {
            $this->client_flags = $txpcfg['client_flags'];
        } else {
            $this->client_flags = 0;
        }

        if (isset($txpcfg['dbcharset'])) {
            $this->charset = $txpcfg['dbcharset'];
        }

        $this->link = mysqli_init();

        // Suppress screen output from mysqli_real_connect().
        $error_reporting = error_reporting();
        error_reporting($error_reporting & ~(E_WARNING | E_NOTICE));

        if (!mysqli_real_connect($this->link, $this->host, $this->user, $this->pass, $this->db, $this->port, $this->socket, $this->client_flags)) {
            die(db_down());
        }

        error_reporting($error_reporting);

        $version = $this->version = mysqli_get_server_info($this->link);
        $connected = true;

        // Be backwards compatible.
        if ($this->charset && (intval($version[0]) >= 5 || preg_match('#^4\.[1-9]#', $version))) {
            mysqli_query($this->link, "SET NAMES ".$this->charset);
            $this->table_options['charset'] = $this->charset;

            if ($this->charset == 'utf8mb4') {
                $this->table_options['collate'] = "utf8mb4_unicode_ci";
            } elseif ($this->charset == 'utf8') {
                $this->table_options['collate'] = "utf8_general_ci";
            }
        }

        $this->default_charset = mysqli_character_set_name($this->link);

        // Use "ENGINE" if version of MySQL > (4.0.18 or 4.1.2).
        if (version_compare($version, '5') >= 0 || preg_match('#^4\.(0\.[2-9]|(1[89]))|(1\.[2-9])#', $version)) {
            $this->table_options['engine'] = 'MyISAM';
            unset($this->table_options['type']);
        }
    }
}

/**
 * Current database link.
 *
 * @access private
 * @global DB $DB
 */

$DB = new DB;

/**
 * Prefixes a database table's name for use in a query.
 *
 * Textpattern can be installed to a shared database, this is achieved by
 * prefixing database tables. This function can be used to add those prefixes to
 * a known named table when building SQL statements.
 *
 * Always use this function, or the safe_pfx_j(), when you refer tables in raw
 * SQL statements, including where clauses, joins and sub-queries.
 *
 * This function will also quote the table name if necessary.
 *
 * You don't need to use this function in any of the dedicated "table"
 * parameters database functions offer. Any table used in a table parameter is
 * prefixed for you.
 *
 * @param  string $table The database table
 * @return string The $table with a prefix
 * @see    safe_pfx_j()
 * @example
 * if (safe_query('DROP TABLE '.safe_pfx('myTable'))
 * {
 *     echo 'myTable dropped';
 * }
 */

function safe_pfx($table)
{
    global $DB;
    $name = $DB->table_prefix.$table;

    if (preg_match('@[^\w._$]@', $name)) {
        return '`'.$name.'`';
    }

    return $name;
}

/**
 * Prefixes a database table's name for use in a joined query.
 *
 * This function prefixes the given table name similarly to safe_pfx(), but also
 * creates a named, unprefixed, AS alias for it.
 *
 * The created alias is same as the table name given. This function is here to
 * help to make joined queries where you need to refer to two or more tables in
 * a one query.
 *
 * As with safe_pfx(), you don't need to use this function in any of the
 * dedicated "table" parameters database functions offer. Any table used in a
 * table parameter is prefixed for you.
 *
 * @param  string $table The database table, or comma-separated list of tables
 * @return string The $table with a prefix
 * @see    safe_pfx()
 * @example
 * if ($r = getRows('SELECT id FROM '.safe_pfx_j('tableA').' JOIN '.safe_pfx('tableB').' ON tableB.id = tableA.id and tableB.active = 1'))
 * {
 *     print_r($r);
 * }
 */

function safe_pfx_j($table)
{
    global $DB;
    $ts = array();

    foreach (explode(',', $table) as $t) {
        $name = $DB->table_prefix.trim($t);
        if (preg_match('@[^\w._$]@', $name)) {
            $ts[] = "`$name`".($DB->table_prefix ? " as `$t`" : '');
        } else {
            $ts[] = "$name".($DB->table_prefix ? " as $t" : '');
        }
    }

    return join(', ', $ts);
}

/**
 * Escapes special characters in a string for use in an SQL statement.
 *
 * @param  string $in The input string
 * @return string
 * @since  4.5.0
 * @see    doSlash()
 * @example
 * if (safe_update('myTable', "value = '".doSlash($user_value)."'", "name = '".doSlash($user_name)."'"))
 * {
 *     echo 'Updated.';
 * }
 */

function safe_escape($in = '')
{
    global $DB;

    return mysqli_real_escape_string($DB->link, $in);
}

/**
 * Escape LIKE pattern's wildcards in a string for use in an SQL statement.
 *
 * @param  string $in The input string
 * @return string
 * @since  4.6.0
 * @see    doLike()
 * @example
 * if (safe_update('myTable', "value = '".doLike($user_value)."'", "name LIKE '".doLike($user_name)."'"))
 * {
 *     echo 'Updated.';
 * }
 */

function safe_escape_like($in = '')
{
    return safe_escape(str_replace(
        array('\\', '%', '_', '\''),
        array('\\\\', '\\%', '\\_', '\\\''),
        (string) $in
    ));
}

/**
 * Executes an SQL statement.
 *
 * @param  string $q     The SQL statement to execute
 * @param  bool   $debug Dump query
 * @param  bool   $unbuf If TRUE, executes the statement without fetching and buffering the results
 * @return mixed
 * @example
 * echo safe_query('SELECT * FROM table');
 */

function safe_query($q = '', $debug = false, $unbuf = false)
{
    global $DB, $trace, $production_status;
    $method = ($unbuf) ? MYSQLI_USE_RESULT : MYSQLI_STORE_RESULT;

    if (!$q) {
        return false;
    }

    if ($debug or TXP_DEBUG === 1) {
        dmp($q);
    }

    if ($production_status !== 'live') {
        $trace->start("[SQL: $q ]", true);
    }

    $result = mysqli_query($DB->link, $q, $method);

    if ($production_status !== 'live') {
        if (is_bool($result)) {
            $trace->stop();
        } else {
            $trace->stop("[Rows: ".intval(@mysqli_num_rows($result))."]");
        }
    }

    if ($result === false) {
        trigger_error(mysqli_error($DB->link), E_USER_ERROR);
    }

    if (!$result) {
        return false;
    }

    return $result;
}

/**
 * Deletes a row from a table.
 *
 * @param  string $table The table
 * @param  string $where The where clause
 * @param  bool   $debug Dump query
 * @return bool FALSE on error
 * @see    safe_update()
 * @see    safe_insert()
 * @example
 * if (safe_delete('myTable', "name = 'test'"))
 * {
 *     echo "'test' removed from 'myTable'.";
 * }
 */

function safe_delete($table, $where, $debug = false)
{
    return (bool) safe_query("DELETE FROM ".safe_pfx($table)." WHERE $where", $debug);
}

/**
 * Updates a table row.
 *
 * @param  string $table The table
 * @param  string $set   The set clause
 * @param  string $where The where clause
 * @param  bool   $debug Dump query
 * @return bool FALSE on error
 * @see    safe_insert()
 * @see    safe_delete()
 * @example
 * if (safe_update('myTable', "myField = 'newValue'", "name = 'test'"))
 * {
 *     echo "'test' updated, 'myField' set to 'newValue'";
 * }
 */

function safe_update($table, $set, $where, $debug = false)
{
    return (bool) safe_query("UPDATE ".safe_pfx($table)." SET $set WHERE $where", $debug);
}

/**
 * Inserts a new row into a table.
 *
 * @param  string $table The table
 * @param  string $set   The set clause
 * @param  bool   $debug Dump query
 * @return int|bool The last generated ID or FALSE on error. If the ID is 0, returns TRUE
 * @see    safe_update()
 * @see    safe_delete()
 * @example
 * if ($id = safe_insert('myTable', "name = 'test', myField = 'newValue'"))
 * {
 *     echo "Created a row to 'myTable' with the name 'test'. It has ID of {$id}.";
 * }
 */

function safe_insert($table, $set, $debug = false)
{
    global $DB;
    $q = "INSERT INTO ".safe_pfx($table)." SET $set";

    if ($r = safe_query($q, $debug)) {
        $id = mysqli_insert_id($DB->link);

        return ($id === 0 ? true : $id);
    }

    return false;
}

/**
 * Inserts a new row, or updates an existing if a matching row is found.
 *
 * @param  string $table The table
 * @param  string $set   The set clause
 * @param  string $where The where clause
 * @param  bool   $debug Dump query
 * @return int|bool The last generated ID or FALSE on error. If the ID is 0, returns TRUE
 * @example
 * if ($r = safe_upsert('myTable', "data = 'foobar'", "name = 'example'"))
 * {
 *     echo "Inserted new row to 'myTable', or updated 'example'.";
 * }
 */

function safe_upsert($table, $set, $where, $debug = false)
{
    global $DB;
    // FIXME: lock the table so this is atomic?
    $r = safe_update($table, $set, $where, $debug);

    if ($r and (mysqli_affected_rows($DB->link) or safe_count($table, $where, $debug))) {
        return $r;
    } else {
        return safe_insert($table, join(', ', array($where, $set)), $debug);
    }
}

/**
 * Changes the structure of a table.
 *
 * @param  string $table The table
 * @param  string $alter The statement to execute
 * @param  bool   $debug Dump query
 * @return bool FALSE on error
 * @example
 * if (safe_alter('myTable', 'ADD myColumn TINYINT(1)'))
 * {
 *     echo "'myColumn' added to 'myTable'";
 * }
 */

function safe_alter($table, $alter, $debug = false)
{
    return (bool) safe_query("ALTER TABLE ".safe_pfx($table)." $alter", $debug);
}

/**
 * Locks a table.
 *
 * The $table argument accepts comma-separated list of table names, if you need
 * to lock multiple tables at once.
 *
 * @param  string $table The table
 * @param  string $type  The lock type
 * @param  bool   $debug Dump the query
 * @return bool TRUE if the tables are locked
 * @since  4.6.0
 * @example
 * if (safe_lock('myTable'))
 * {
 *     echo "'myTable' is 'write' locked.";
 * }
 */

function safe_lock($table, $type = 'write', $debug = false)
{
    return (bool) safe_query("LOCK TABLES ".join(' '.$type.', ', doArray(do_list_unique($table), 'safe_pfx')).' '.$type, $debug);
}

/**
 * Unlocks tables.
 *
 * @param  bool $debug Dump the query
 * @return bool TRUE if tables aren't locked
 * @since  4.6.0
 * @example
 * if (safe_unlock())
 * {
 *     echo 'Tables are unlocked.';
 * }
 */

function safe_unlock($debug = false)
{
    return (bool) safe_query("UNLOCK TABLES", $debug);
}

/**
 * Gets an array of information about an index.
 *
 * @param  string $table The table
 * @param  string $index The index
 * @param  bool   $debug Dump the query
 * @return array|bool Array of information about the index, or FALSE on error
 * @since  4.6.0
 * @example
 * if ($index = safe_index('myTable', 'myIndex'))
 * {
 *     echo "'myIndex' found in 'myTable' with the type of {$index['Index_type']}.";
 * }
 */

function safe_index($table, $index, $debug = false)
{
    $index = strtolower($index);

    if ($r = safe_show("INDEX", $table, $debug)) {
        foreach ($r as $a) {
            if (strtolower($a['Key_name']) === $index) {
                return $a;
            }
        }
    }

    return false;
}

/**
 * Creates an index.
 *
 * @param  string $table   The table
 * @param  string $columns Indexed columns
 * @param  string $name    The name
 * @param  string $index   The index. Either 'unique', 'fulltext', 'spatial' (default = standard index)
 * @param  string $type    The index type
 * @param  bool   $debug   Dump the query
 * @return bool TRUE if index exists
 * @since  4.6.0
 * @example
 * if (safe_create_index('myTable', 'col1(11), col2(11)', 'myIndex'))
 * {
 *     echo "'myIndex' exists in 'myTable'.";
 * }
 */

function safe_create_index($table, $columns, $name, $index = '', $type = '', $debug = false)
{
    if (safe_index($table, $name, $debug)) {
        return true;
    }

    if (strtolower($name) == 'primary') {
        $q = "ALTER TABLE ".safe_pfx($table)." ADD PRIMARY KEY ($columns)";
    } else {
        $q = "CREATE $index INDEX `$name` ".($type ? " USING ".$type : '')." ON ".safe_pfx($table)." ($columns)";
    }

    return (bool) safe_query($q, $debug);
}

/**
 * Removes an index.
 *
 * @param  string $table The table
 * @param  string $index The index
 * @param  bool   $debug Dump the query
 * @return bool TRUE if the index no longer exists
 * @since  4.6.0
 * @example
 * if (safe_drop_index('myTable', 'primary'))
 * {
 *     echo "Primary key no longer exists in 'myTable'.";
 * }
 */

function safe_drop_index($table, $index, $debug = false)
{
    if (!safe_index($table, $index, $debug)) {
        return true;
    }

    if (strtolower($index) === 'primary') {
        $q = "ALTER TABLE ".safe_pfx($table)." DROP PRIMARY KEY";
    } else {
        $q = "DROP INDEX `$index` ON ".safe_pfx($table);
    }

    return (bool) safe_query($q, $debug);
}

/**
 * Optimises a table.
 *
 * @param  string $table The table
 * @param  bool   $debug Dump query
 * @return bool FALSE on error
 * @example
 * if (safe_optimize('myTable'))
 * {
 *     echo "myTable optimised successfully.";
 * }
 */

function safe_optimize($table, $debug = false)
{
    return (bool) safe_query("OPTIMIZE TABLE ".safe_pfx($table), $debug);
}

/**
 * Repairs a table.
 *
 * @param  string $table The table
 * @param  bool   $debug Dump query
 * @return bool   FALSE on error
 * @example
 * if (safe_repair('myTable'))
 * {
 *     echo "myTable repaired successfully.";
 * }
 */

function safe_repair($table, $debug = false)
{
    return (bool) safe_query("REPAIR TABLE ".safe_pfx($table), $debug);
}

/**
 * Truncates a table.
 *
 * Running this function empties a table completely, resets indexes and the auto
 * increment value.
 *
 * @param  string $table The table
 * @param  bool   $debug Dump query
 * @return bool   TRUE if the table is empty
 * @see    safe_delete()
 * @since  4.6.0
 * @example
 * if (safe_truncate('myTable'))
 * {
 *     echo "myTable emptied successfully.";
 * }
 */

function safe_truncate($table, $debug = false)
{
    return (bool) safe_query("TRUNCATE TABLE ".safe_pfx($table), $debug);
}

/**
 * Removes a table.
 *
 * This function removes all data and definitions associated with a table.
 *
 * @param  string $table The table
 * @param  bool   $debug Dump query
 * @return bool   TRUE if the table no longer exists
 * @since  4.6.0
 * @example
 * if (safe_drop('myTable'))
 * {
 *     echo "'myTable' no longer exists.";
 * }
 */

function safe_drop($table, $debug = false)
{
    return (bool) safe_query("DROP TABLE IF EXISTS ".safe_pfx($table), $debug);
}

/**
 * Creates a table.
 *
 * Creates a table with the given name. This table will be created with
 * identical properties to core tables, ensuring the best possible compatibility.
 *
 * @param  string $table      The table
 * @param  string $definition The create definition
 * @param  string $options    Table options
 * @param  bool   $debug      Dump the query
 * @return bool   TRUE if table exists
 * @since  4.6.0
 * @example
 * if (safe_create('myTable', "id int(11)"))
 * {
 *     echo "'myTable' exists.";
 * }
 */

function safe_create($table, $definition, $options = '', $debug = false)
{
    global $DB;

    foreach ($DB->table_options as $name => $value) {
        $options .= ' '.strtoupper($name).' = '.$value;
    }

    $q = "CREATE TABLE IF NOT EXISTS ".safe_pfx($table)." ($definition) $options";

    return (bool) safe_query($q, $debug);
}

/**
 * Renames a table.
 *
 * @param  string $table   The table
 * @param  string $newname The new name
 * @param  bool   $debug   Dump the query
 * @return bool   FALSE on error
 * @since  4.6.0
 */

function safe_rename($table, $newname, $debug = false)
{
    return (bool) safe_query("RENAME TABLE ".safe_pfx($table)." TO ".safe_pfx($newname), $debug);
}

/**
 * Gets a field from a row.
 *
 * If the query results in multiple matches, the first row returned is used.
 *
 * @param  string $thing The field
 * @param  string $table The table
 * @param  string $where The where clause
 * @param  bool   $debug Dump query
 * @return mixed  The field or FALSE on error
 * @example
 * if ($field = safe_field("column", 'table', "1 = 1"))
 * {
 *     echo $field;
 * }
 */

function safe_field($thing, $table, $where, $debug = false)
{
    $q = "SELECT $thing FROM ".safe_pfx_j($table)." WHERE $where";
    $r = safe_query($q, $debug);

    if (@mysqli_num_rows($r) > 0) {
        $row = mysqli_fetch_row($r);
        mysqli_free_result($r);

        return $row[0];
    }

    return false;
}

/**
 * Gets a list of values from a table's column.
 *
 * @param  string $thing The column
 * @param  string $table The table
 * @param  string $where The where clause
 * @param  bool   $debug Dump query
 * @return array
 */

function safe_column($thing, $table, $where, $debug = false)
{
    $q = "SELECT $thing FROM ".safe_pfx_j($table)." WHERE $where";
    $rs = getRows($q, $debug);

    if ($rs) {
        foreach ($rs as $a) {
            $v = array_shift($a);
            $out[$v] = $v;
        }

        return $out;
    }

    return array();
}

/**
 * Fetch a column as an numeric array.
 *
 * @param  string $thing The field
 * @param  string $table The table
 * @param  string $where The where clause
 * @param  bool   $debug Dump query
 * @return array  Numeric array of column values
 * @since  4.5.0
 */

function safe_column_num($thing, $table, $where, $debug = false)
{
    $q = "SELECT $thing FROM ".safe_pfx_j($table)." WHERE $where";
    $rs = getRows($q, $debug);
    if ($rs) {
        foreach ($rs as $a) {
            $v = array_shift($a);
            $out[] = $v;
        }

        return $out;
    }

    return array();
}

/**
 * Gets a row from a table as an associative array.
 *
 * @param  string $things The select clause
 * @param  string $table  The table
 * @param  string $where  The where clause
 * @param  bool   $debug  Dump query
 * @return array
 * @see    safe_rows()
 * @see    safe_rows_start()
 * @uses   getRow()
 * @example
 * if ($row = safe_row("column", 'table', "1 = 1"))
 * {
 *     echo $row['column'];
 * }
 */

function safe_row($things, $table, $where, $debug = false)
{
    $q = "SELECT $things FROM ".safe_pfx_j($table)." WHERE $where";
    $rs = getRow($q, $debug);

    if ($rs) {
        return $rs;
    }

    return array();
}

/**
 * Gets a list rows from a table as an associative array.
 *
 * When working with large result sets remember that this function, unlike
 * safe_rows_start(), loads results to memory all at once. To optimise
 * performance in such situations, use safe_rows_start() instead.
 *
 * @param  string $things The select clause
 * @param  string $table  The table
 * @param  string $where  The where clause
 * @param  bool   $debug  Dump query
 * @return array  Returns an empty array if no mathes are found
 * @see    safe_row()
 * @see    safe_rows_start()
 * @uses   getRows()
 * @example
 * $rs = safe_rows("column", 'table', "1 = 1");
 * foreach ($rs as $row)
 * {
 *     echo $row['column'];
 * }
 */

function safe_rows($things, $table, $where, $debug = false)
{
    $q = "SELECT $things FROM ".safe_pfx_j($table)." WHERE $where";
    $rs = getRows($q, $debug);

    if ($rs) {
        return $rs;
    }

    return array();
}

/**
 * Selects rows from a table and returns result as a resource.
 *
 * @param  string        $things The select clause
 * @param  string        $table  The table
 * @param  string        $where  The where clause
 * @param  bool          $debug  Dump query
 * @return resource|bool A result resouce or FALSE on error
 * @see    nextRow()
 * @see    numRows()
 * @example
 * if ($rs = safe_rows_start("column", 'table', "1 = 1"))
 * {
 *     while ($row = nextRow($rs))
 *     {
 *         echo $row['column'];
 *     }
 * }
 */

function safe_rows_start($things, $table, $where, $debug = false)
{
    $q = "SELECT $things FROM ".safe_pfx_j($table)." WHERE $where";

    return startRows($q, $debug);
}

/**
 * Counts number of rows in a table.
 *
 * @param  string   $table The table
 * @param  string   $where The where clause
 * @param  bool     $debug Dump query
 * @return int|bool Number of rows or FALSE on error
 * @example
 * if (($count = safe_count("table", "1 = 1")) !== false)
 * {
 *     echo "myTable contains {$count} rows.";
 * }
 */

function safe_count($table, $where, $debug = false)
{
    return getCount($table, $where, $debug);
}

/**
 * Shows information about a table.
 *
 * @param  string   $thing The information to show, e.g. "index", "columns"
 * @param  string   $table The table
 * @param  bool     $debug Dump query
 * @return array
 * @example
 * print_r(safe_show('columns', 'myTable'));
 */

function safe_show($thing, $table, $debug = false)
{
    $q = "SHOW $thing FROM ".safe_pfx($table)."";
    $rs = getRows($q, $debug);

    if ($rs) {
        return $rs;
    }

    return array();
}

/**
 * Gets a field from a row.
 *
 * This function offers an alternative short-hand syntax to safe_field().
 * Most notably, this internally manages value escaping.
 *
 * @param  string $col   The field to get
 * @param  string $table The table
 * @param  string $key   The searched field
 * @param  string $val   The searched value
 * @param  bool   $debug Dump query
 * @return mixed  The field or FALSE on error
 * @see    safe_field()
 * @example
 * echo fetch('name', 'myTable', 'id', 12);
 */

function fetch($col, $table, $key, $val, $debug = false)
{
    $key = doSlash($key);
    $val = (is_int($val)) ? $val : "'".doSlash($val)."'";
    $q = "SELECT $col FROM ".safe_pfx($table)." WHERE `$key` = $val LIMIT 1";

    if ($r = safe_query($q, $debug)) {
        if (mysqli_num_rows($r) > 0) {
            $row = mysqli_fetch_row($r);
            mysqli_free_result($r);
            return $row[0];
        }

        return '';
    }

    return false;
}

/**
 * Gets a row as an associative array.
 *
 * @param  string     $query The SQL statement to execute
 * @param  bool       $debug Dump query
 * @return array|bool The row's values or FALSE on error
 * @see    safe_row()
 */

function getRow($query, $debug = false)
{
    if ($r = safe_query($query, $debug)) {
        $row = (mysqli_num_rows($r) > 0) ? mysqli_fetch_assoc($r) : false;
        mysqli_free_result($r);

        return $row;
    }

    return false;
}

/**
 * Gets multiple rows as an associative array.
 *
 * If you need to run simple SELECT queries that select rows from a table,
 * please see safe_rows() and safe_rows_start() first.
 *
 * @param  string     $query The SQL statement to execute
 * @param  bool       $debug Dump query
 * @return array|bool The rows or FALSE on error
 * @see    safe_rows()
 * @example
 * if ($rs = getRows('SELECT * FROM table'))
 * {
 *     print_r($rs);
 * }
 */

function getRows($query, $debug = false)
{
    if ($r = safe_query($query, $debug)) {
        if (mysqli_num_rows($r) > 0) {
            while ($a = mysqli_fetch_assoc($r)) {
                $out[] = $a;
            }

            mysqli_free_result($r);

            return $out;
        }
    }

    return false;
}

/**
 * Executes an SQL statement and returns results.
 *
 * This function is indentical to safe_query() apart from the missing
 * $unbuf argument.
 *
 * @param  string $query The SQL statement to execute
 * @param  bool   $debug Dump query
 * @return mixed
 * @see    safe_query()
 * @access private
 */

function startRows($query, $debug = false)
{
    return safe_query($query, $debug);
}

/**
 * Gets a next row as an associative array from a result resource.
 *
 * The function will free up memory reserved by the result resource if called
 * after the last row.
 *
 * @param   resource    $r The result resource
 * @return  array|bool  The row, or FALSE if there are no more rows
 * @see     safe_rows_start()
 * @example
 * if ($rs = safe_rows_start("column", 'table', "1 = 1"))
 * {
 *     while ($row = nextRow($rs))
 *     {
 *         echo $row['column'];
 *     }
 * }
 */

function nextRow($r)
{
    $row = mysqli_fetch_assoc($r);

    if ($row === false) {
        mysqli_free_result($r);
    }

    return $row;
}

/**
 * Gets the number of rows in a result resource.
 *
 * @param  resource $r The result resource
 * @return int|bool The number of rows or FALSE on error
 * @see    safe_rows_start()
 * @example
 * if ($rs = safe_rows_start("column", 'table', "1 = 1"))
 * {
 *     echo numRows($rs);
 * }
 */

function numRows($r)
{
    return mysqli_num_rows($r);
}

/**
 * Gets the contents of a single cell from a resource set.
 *
 * @param  string      $query The SQL statement to execute
 * @param  bool        $debug Dump query
 * @return string|bool The contents, empty if no results were found or FALSE on error
 */

function getThing($query, $debug = false)
{
    if ($r = safe_query($query, $debug)) {
        if (mysqli_num_rows($r) != 0) {
            $row = mysqli_fetch_row($r);
            mysqli_free_result($r);

            return $row[0];
        }

        return '';
    }

    return false;
}

/**
 * Return values of one column from multiple rows in a num indexed array.
 *
 * @param  string $query The SQL statement to execute
 * @param  bool   $debug Dump query
 * @return array
 */

function getThings($query, $debug = false)
{
    $rs = getRows($query, $debug);

    if ($rs) {
        foreach ($rs as $a) {
            $out[] = array_shift($a);
        }

        return $out;
    }

    return array();
}

/**
 * Counts number of rows in a table.
 *
 * This function is identical to safe_count().
 *
 * @param  string   $table The table
 * @param  string   $where The where clause
 * @param  bool     $debug Dump query
 * @return int|bool Number of rows or FALSE on error
 * @access private
 * @see    safe_count()
 */

function getCount($table, $where, $debug = false)
{
    return getThing("SELECT COUNT(*) FROM ".safe_pfx_j($table)." WHERE $where", $debug);
}

/**
 * Gets a tree structure.
 *
 * This function is used by categories.
 *
 * @param  string $root  The root
 * @param  string $type  The type
 * @param  string $where The where clause
 * @param  string $tbl   The table
 * @return array
 */

function getTree($root, $type, $where = "1 = 1", $tbl = 'txp_category')
{
    $root = doSlash($root);
    $type = doSlash($type);

    $rs = safe_row(
        "lft AS l, rgt AS r",
        $tbl,
        "name = '$root' AND type = '$type'"
    );

    if (!$rs) {
        return array();
    }

    extract($rs);

    $out = array();
    $right = array();

    $rs = safe_rows_start(
        "id, name, lft, rgt, parent, title",
        $tbl,
        "lft BETWEEN $l AND $r AND type = '$type' AND name != 'root' AND $where ORDER BY lft ASC"
    );

    while ($rs and $row = nextRow($rs)) {
        extract($row);

        while (count($right) > 0 && $right[count($right) - 1] < $rgt) {
            array_pop($right);
        }

        $out[] = array(
            'id' => $id,
            'name' => $name,
            'title' => $title,
            'level' => count($right),
            'children' => ($rgt - $lft - 1) / 2,
            'parent' => $parent,
        );

        $right[] = $rgt;
    }

    return $out;
}

/**
 * Gets a tree path up to a target.
 *
 * This function is used by categories.
 *
 * @param  string $target The target
 * @param  string $type   The category type
 * @param  string $tbl    The table
 * @return array
 */

function getTreePath($target, $type, $tbl = 'txp_category')
{
    $rs = safe_row(
        "lft AS l, rgt AS r",
        $tbl,
        "name = '".doSlash($target)."' AND type = '".doSlash($type)."'"
    );

    if (!$rs) {
        return array();
    }

    extract($rs);

    $rs = safe_rows_start(
        "*",
        $tbl,
        "lft <= $l AND rgt >= $r AND type = '".doSlash($type)."' ORDER BY lft ASC"
    );

    $out = array();
    $right = array();

    while ($rs and $row = nextRow($rs)) {
        extract($row);

        while (count($right) > 0 && $right[count($right) - 1] < $rgt) {
            array_pop($right);
        }

        $out[] = array(
            'id' => $id,
            'name' => $name,
            'title' => $title,
            'level' => count($right),
            'children' => ($rgt - $lft - 1) / 2,
        );

        $right[] = $rgt;
    }

    return $out;
}

/**
 * Rebuilds a nested tree set.
 *
 * This function is used by categories.
 *
 * @param  string $parent The parent
 * @param  string $left   The left ID
 * @param  string $type   The category type
 * @param  string $tbl    The table
 * @return int The next left ID
 */

function rebuild_tree($parent, $left, $type, $tbl = 'txp_category')
{
    $left = assert_int($left);
    $right = $left + 1;

    $parent = doSlash($parent);
    $type = doSlash($type);

    $result = safe_column("name", $tbl,
        "parent = '$parent' AND type = '$type' ORDER BY name");

    foreach ($result as $row) {
        $right = rebuild_tree($row, $right, $type, $tbl);
    }

    safe_update(
        $tbl,
        "lft = $left, rgt = $right",
        "name = '$parent' AND type = '$type'"
    );

    return $right + 1;
}

/**
 * Rebuilds a tree.
 *
 * This function is used by categories.
 *
 * @param  string $type   The category type
 * @param  string $tbl    The table
 * @return int The next left ID
 */

function rebuild_tree_full($type, $tbl = 'txp_category')
{
    // Fix circular references, otherwise rebuild_tree() could get stuck in a loop.
    safe_update($tbl, "parent = ''", "type = '".doSlash($type)."' AND name = 'root'");
    safe_update($tbl, "parent = 'root'", "type = '".doSlash($type)."' AND parent = name");
    rebuild_tree('root', 1, $type, $tbl);
}

/**
 * Returns an error page.
 *
 * This function is used to return a bailout page when resolving database
 * connections fails. Sends a HTTP 503 error status and displays the last logged
 * MySQL error message.
 *
 * @return string HTML5 document
 * @access private
 */

function db_down()
{
    global $DB;
    // 503 status might discourage search engines from indexing or caching the
    // error message.
    txp_status_header('503 Service Unavailable');
    if (is_object($DB)) {
        $error = txpspecialchars(mysqli_error($DB->link));
    } else {
        $error = '$DB object is not available.';
    }

    return <<<eod
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Database unavailable</title>
</head>
<body>
    <p>Database unavailable.</p>
    <!-- $error -->
</body>
</html>
eod;
}

/**
 * Replacement for SQL NOW()
 *
 * This function can be used when constructing SQL SELECT queries as a
 * replacement for the NOW() function to allow the SQL server to cache the
 * queries. Should only be used when comparing with the Posted or Expired
 * columns from the textpattern (articles) table or the Created column from
 * the txp_file table.
 *
 * @param  string $type   Column name, lower case (one of 'posted', 'expires', 'created')
 * @param  bool   $update Force update
 * @return string SQL query string partial
 */

function now($type, $update = false)
{
    static $nows = array();
    static $time = null;

    if (!in_array($type, array('posted', 'expires', 'created'))) {
        return false;
    }

    if (isset($nows[$type])) {
        $now = $nows[$type];
    } else {
        if ($time === null) {
            $time = time();
        }

        $pref = 'sql_now_'.$type;
        $now = get_pref($pref, $time - 1);

        if ($time > $now or $update) {
            $table = ($type === 'created') ? 'txp_file' : 'textpattern';
            $where = '1=1 having utime > '.$time.' order by utime asc limit 1';
            $now = safe_field('unix_timestamp('.$type.') as utime', $table, $where);
            $now = ($now === false) ? 2147483647 : intval($now) - 1;
            update_pref($pref, $now);
            $nows[$type] = $now;
        }
    }

    return 'from_unixtime('.$now.')';
}
