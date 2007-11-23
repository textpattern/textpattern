<?php

/*
$HeadURL: http://svn.textpattern.com/development/4.0/textpattern/update/_to_4.0.6.php $
$LastChangedRevision: 2464 $
*/

	if (!defined('TXP_UPDATE'))
	{
		exit("Nothing here. You can't access this file directly.");
	}


	# replace deprecated tags with functionally equivalent, valid tags
	$tags = array(
		'sitename'    => 'site_name',
		'request_uri' => 'page_url',
		'id'          => 'page_url type="id"',
		's'           => 'page_url type="s"',
		'c'           => 'page_url type="c"',
		'q'           => 'page_url type="q"',
		'pg'          => 'page_url type="pg"',
	);

	foreach($tags as $search => $replace)
	{
		foreach(array(' ', '/') as $end)
		{
			safe_update('txp_page', "user_html = REPLACE(user_html, '<txp:".$search.$end."', '<txp:".$replace.' '.trim($end)."')", '1=1');
			safe_update('txp_form', "Form = REPLACE(Form, '<txp:".$search.$end."', '<txp:".$replace.' '.trim($end)."')", '1=1');
		}
	}

?>
