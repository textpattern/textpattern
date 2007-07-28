<?php
/*
$HeadURL: http://svn.textpattern.com/current/textpattern/_update.php $
$LastChangedRevision: 711 $
*/
	if (!defined('TXP_UPDATE'))
		exit("Nothing here. You can't access this file directly.");

   if (!safe_field('name', 'txp_page', "name='error_default'")){
		$error_default = "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">\n<head>\n\t<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n\n\t<title><txp:sitename />: <txp:error_status /></title>\n\n\t<txp:feed_link flavor=\"atom\" format=\"link\" label=\"Atom\" />\n\t<txp:feed_link flavor=\"rss\" format=\"link\" label=\"RSS\" />\n\n\t<txp:css format=\"link\" />\n</head>\n<body>\n\n<!-- accessibility -->\n<div id=\"accessibility\">\n\t<ul>\n\t\t<li><a href=\"#content\"><txp:text item=\"go_content\" /></a></li>\n\t\t<li><a href=\"#sidebar-1\"><txp:text item=\"go_nav\" /></a></li>\n\t\t<li><a href=\"#sidebar-2\"><txp:text item=\"go_search\" /></a></li>\n\t</ul>\n</div>\n\n<div id=\"container\">\n\n<!-- head -->\n\t<div id=\"head\">\n\t\t<h1><txp:link_to_home><txp:sitename /></txp:link_to_home></h1>\n\t\t<h2><txp:site_slogan /></h2>\n\t</div>\n\n<!-- left -->\n\t<div id=\"sidebar-1\">\n\t<txp:linklist wraptag=\"p\" />\n\t</div>\n\n<!-- right -->\n\t<div id=\"sidebar-2\">\n\t\t<txp:search_input wraptag=\"p\" />\n\n\t\t<txp:popup type=\"c\" wraptag=\"p\" />\n\n\t\t<p><txp:feed_link label=\"RSS\" /> / <txp:feed_link flavor=\"atom\" label=\"Atom\" /></p>\n\n\t\t<p><img src=\"<txp:site_url />textpattern/txp_img/txp_slug105x45.gif\" width=\"105\" height=\"45\" alt=\"Textpattern\" title=\"\" /></p>\n\t</div>\n\n<!-- center -->\n\t<div id=\"content\">\n\t\t<h3 style=\"font: 1.3em Georgia, Times, serif;\"><txp:error_status /></h3>\n\t\t<p><txp:error_message /></p>\n\t</div>\n\n<!-- footer -->\n\t<div id=\"foot\">&nbsp;</div>\n\n</div>\n\n</body>\n</html>";

      safe_insert('txp_page',"
         name='error_default',
         user_html='".$error_default."'");
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
