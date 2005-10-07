<?php
/*
$HeadURL: http://svn.textpattern.com/current/textpattern/_update.php $
$LastChangedRevision: 711 $
*/
	if (!defined('TXP_UPDATE'))
		exit("Nothing here. You can't access this file directly.");

   if (!safe_field('name', 'txp_page', "name='error_default'")){
		$error_default = <<<EOF
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<title><txp:sitename />: <txp:error_status /></title>
<style type="text/css">
<!--
#content
{
	margin-left: 155px;
	margin-right: 155px;
	padding-top: 30px;
        text-align: center;
}
h1,h2,h3,h4,h5,h6
{
	font-weight: normal;
	font-family: Georgia, Times, Serif;
}
p
{
	font-family: Verdana, "Lucida Grande", Tahoma, Helvetica;
	font-size: 0.9em;
	line-height: 1.6em;
}
a img
{
        border: none;
}
-->
</style>
</head>
<body>

<div id="content">
        <h1><txp:sitename />: <txp:error_status /></h1>
	<p><txp:error_message /></p>
        <p><txp:link_to_home><txp:site_url /></txp:link_to_home></p>
        <p><a href="http://textpattern.com/"><txp:img src="textpattern/txp_img/txp_slug105x45.gif" /></a></p>
</div>

</body>
</html>

EOF;

      safe_insert('txp_page',"
         name='error_default',
         user_html='".doSlash($error_default)."'");
   }
   //take back use_textile
   safe_update('txp_prefs',"html='pref_text'","name='use_textile'");
   // ugly way to change somethign which could break BC:
   // changed use_textile == 2 to convert breaks and 
   // use_textile == 1 to use textile - the same than in 
   // textile_body or textile_excerpt
   if (safe_field('val','txp_prefs',"name='textile_updated'") === false) {
   		   $ut = safe_field('val','txp_prefs',"name='use_textile'");
   		   if ($ut == 1) {
   		   		safe_update('txp_prefs',"val='2'","name='use_textile'");
   		   }elseif ($ut == 2){
   		   		safe_update('txp_prefs',"val='1'","name='use_textile'");
   		   }
   		   safe_insert('txp_prefs', "prefs_id=1, name='textile_updated',val='1', type='2'");
   }



?>
