<?php

/*
	This is Textpattern

	Copyright 2004 by Dean Allen
	www.textpattern.com
	All rights reserved

	Use of this software indicates acceptance ofthe Textpattern license agreement 
*/

if ($txp_lang_updated < (time()-(60*60*24))) {
	update_txp_lang();
}


// -------------------------------------------------------------
	function update_txp_lang() 
	{
		global $txp_lang_updated,$txpcfg;
		
		if ($re = mysql_connect('textpattern.otherwords.net',
				'textpattern_user','textpattern')) {
			if (mysql_select_db('textpattern_master',$re)) {
				if ($q = mysql_query("select unix_timestamp(updated) from 
						textpattern_master.update where
						`table`='txp_lang'",$re)) {
					$updated = (mysql_num_rows($q)!=0) ? mysql_result($q,0) : false;
				
					if ($updated > $txp_lang_updated) {
						if ($get = mysql_query("select * from 
								textpattern_master.txp_lang order by var")) {
							if (mysql_num_rows($get) > 0) {
								while($a = mysql_fetch_assoc($get)) {
									$incoming[] = $a;
								}
								mysql_close($re);
							}
							if (!empty($incoming)) {
									dbconnect(
										$txpcfg['db'],
										$txpcfg['user'],
										$txpcfg['pass'],
										$txpcfg['host']
									);
								safe_query("truncate txp_lang");
								foreach ($incoming as $b) {
									extract(doSlash($b));
									safe_query("
										insert into txp_lang set 
										var='$var',english='$english'");
								}
								safe_query("update txp_prefs set val= 	
									".time()."
									where `name`='txp_lang_updated'",1);
								echo mysql_error();

							}
		
						}
					}
				}
			} 
		} 
	}



?>
