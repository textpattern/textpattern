<?php
/*
$HeadURL: http://svn.textpattern.com/current/textpattern/_update.php $
$LastChangedRevision: 711 $
*/
	if (!defined('TXP_UPDATE'))
		exit("Nothing here. You can't access this file directly.");

   if (!safe_field('name', 'txp_page', "name='error_default'")){
		$error_default = "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\r\n<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">\r\n<head>\r\n\t<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\r\n\t<title><txp:sitename />: <txp:error_status /></title>\r\n\t<link rel=\"stylesheet\" type=\"text/css\" media=\"screen\" href=\"<txp:css n=\"default\" />\" />\r\n</head>\r\n<body>\r\n\r\n<!-- accessibility -->\r\n<div id=\"accessibility\">\r\n\t<ul>\r\n\t\t<li><a href=\"#content\">Go to content</a></li>\r\n\t\t<li><a href=\"#sidebar-1\">Go to navigation</a></li>\r\n\t\t<li><a href=\"#sidebar-2\">Go to search</a></li>\r\n\t</ul>\r\n</div>\r\n\r\n<div id=\"container\">\r\n\r\n<!-- head -->\r\n\t<div id=\"head\">\r\n\t\t<h1><txp:link_to_home><txp:sitename /></txp:link_to_home></h1>\r\n\t\t<h2><txp:site_slogan /></h2>\r\n\t</div>\r\n\r\n<!-- left -->\r\n\t<div id=\"sidebar-1\">\r\n\t<txp:linklist wraptag=\"p\" />\r\n\t</div>\r\n\r\n<!-- right -->\r\n\t<div id=\"sidebar-2\">\r\n\t\t<txp:search_input label=\"Search\" wraptag=\"p\" />\r\n\t\t<txp:popup type=\"c\" label=\"Browse\" wraptag=\"p\" />\r\n\t\t<p><txp:feed_link label=\"RSS\" /> / <txp:feed_link flavor=\"atom\" label=\"Atom\" /></p>\r\n\r\n\t\t<p><img src=\"<txp:site_url />textpattern/txp_img/txp_slug105x45.gif\" width=\"105\" height=\"45\" alt=\"Textpattern\" title=\"\" /></p>\r\n\t</div>\r\n\r\n<!-- center -->\r\n\t<div id=\"content\">\r\n\t\t<h3 style=\"font: 1.3em Georgia, Times, serif;\"><txp:error_status /></h3>\r\n\t\t<p><txp:error_message /></p>\r\n\t</div>\r\n\r\n<!-- footer -->\r\n\t<div id=\"foot\">&nbsp;</div>\r\n\r\n</div>\r\n\r\n</body>\r\n</html>";

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
