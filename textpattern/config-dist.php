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
 * Must be equal to the table-charset, e.g. latin1 or utf8.
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
 * @link   https://www.php.net/manual/en/mysqli.real-connect.php
 */

$txpcfg['client_flags'] = 0;

/**
 * Optional, advanced: use a particular collation for tables.
 *
 * @global string $txpcfg['table_collation']
 */

//$txpcfg['table_collation'] = 'utf8mb4_general_ci';

/*
 * Optional, advanced: use a different database engine.
 */

//$txpcfg['dbengine'] = 'InnoDB';

/*
 * Optional, advanced: include an external PHP script if needed.
 */

//$txpcfg['pre_publish_script'] = 'path/to/file.php';

/*
 * Optional, advanced: http address of the site serving images.
 * see https://forum.textpattern.com/viewtopic.php?id=34493
 */

// define('ihu', 'https://static.example.com/');

/*
 * Optional, advanced: custom CSS rules for admin-side themes.
 */

// define('admin_custom_css', 'your_custom_rules.css');

/*
 * Optional, advanced: custom JavaScript rules for admin-side themes.
 */

// define('admin_custom_js', 'your_custom_javascript.js');

/*
 * Optional, advanced: use https in generated URLs.
 */

// define('PROTOCOL', 'https://');
