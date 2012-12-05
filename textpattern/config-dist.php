<?php

	/**
	 *  mysql database
	 */
		$txpcfg['db'] = 'databasename';

	/**
	 *  database login name
	 */
		$txpcfg['user'] = 'root';

	/**
	 *  database password
	 */
		$txpcfg['pass'] = '';

	/**
	 *  database host
	 */

		$txpcfg['host'] = 'localhost';

	/**
	 *  table prefix (Use ONLY if you require multiple installs in one db)
	 */

		$txpcfg['table_prefix'] = '';

	/**
	 *  full server path to textpattern dir (no slash at end)
	 */

		$txpcfg['txpath'] = '/home/path/to/textpattern';

	/**
	 *  DB Connection Charset, only for MySQL4.1 and up. Must be equal to the Table-Charset.
	 */

		$txpcfg['dbcharset'] = 'latin1';

	/**
	 *  optional: database client flags as needed (@see http://www.php.net/manual/function.mysql-connect.php)
	 */

	#	$txpcfg['client_flags'] = MYSQL_CLIENT_SSL | MYSQL_CLIENT_COMPRESS;

	/**
	 *  optional, advanced: http address of the site serving images
	 *  @see http://forum.textpattern.com/viewtopic.php?id=34493
	 */

	# define('ihu', 'http://static.example.com/');

?>