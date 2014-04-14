<?php

/**
 * Database credentials and site configuration.
 *
 * @package DB
 */

/**
 * MySQL database.
 *
 * @global string $txpcfg['db']
 */

    $txpcfg['db'] = 'databasename';

/**
 * Database login name.
 *
 * @global string $txpcfg['user']
 */

    $txpcfg['user'] = 'root';

/**
 * Database password.
 *
 * @global string $txpcfg['pass']
 */

    $txpcfg['pass'] = '';

/**
 * Database hostname.
 *
 * @global string $txpcfg['host']
 */

    $txpcfg['host'] = 'localhost';

/**
 * Table prefix.
 *
 * Use only if you require multiple installs in one database.
 *
 * @global string $txpcfg['table_prefix']
 */

    $txpcfg['table_prefix'] = '';

/**
 * Full server path to textpattern directory, no slash at the end.
 *
 * @global string $txpcfg['txpath']
 */

    $txpcfg['txpath'] = '/home/path/to/textpattern';

/**
 * Database connection charset.
 *
 * Only for MySQL 4.1 and up. Must be equal to the table-charset, e.g. latin1 or utf8.
 *
 * @global string $txpcfg['dbcharset']
 */

    $txpcfg['dbcharset'] = 'utf8';

/**
 * Database client flags.
 *
 * These are optional. Use the database client flags as needed.
 * Available flags include: MYSQL_CLIENT_SSL, MYSQL_CLIENT_COMPRESS,
 * MYSQL_CLIENT_IGNORE_SPACE, MYSQL_CLIENT_INTERACTIVE
 *
 * @global int $txpcfg['client_flags']
 * @link   http://www.php.net/manual/function.mysql-connect.php
 */

    $txpcfg['client_flags'] = 0;

/*
 * Optional, advanced: http address of the site serving images
 * see http://forum.textpattern.com/viewtopic.php?id=34493
 */

    // define('ihu', 'http://static.example.com/');
