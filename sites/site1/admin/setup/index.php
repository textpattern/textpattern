<?php
/*
$HeadURL: https://textpattern.googlecode.com/svn/development/4.x/sites/site1/admin/setup/index.php $
$LastChangedRevision: 3238 $
*/

// Use buffering to ensure bogus whitespace is ignored
ob_start(NULL, 2048);
@include '../../private/config.php';
ob_end_clean();

if (!defined('txpath')) {
	define("txpath", realpath(dirname(__FILE__).'/../../../../textpattern'));
}

include txpath.'/setup/index.php';

?>
