<?php
	//Absolutely untested. Any volunteer with a b2 db dump to collaborate?
	
	function doImportB2($b2dblogin, $b2db, $b2dbpass, $b2dbhost, $insert_into_section, $insert_with_status, $default_comment_invite)
	{
		global $txpcfg;
		//Keep some response on some part
		$results = array();	
		
		
		// let's go - Dean says ;-).		
		$b2link = mysql_connect($b2dbhost,$b2dblogin,$b2dbpass,true);
		if(!$b2link){ 
			return 'b2 database values don&#8217;t work. Go back, replace them and try again';
		}				
		mysql_select_db($b2db,$b2link);
		$results[]='connected to b2 database. Importing Data';
		
		$a = mysql_query("
			select 
			b2posts.ID as ID,
			b2posts.post_date as Posted, 
			b2posts.post_title as Title, 
			b2posts.post_content as Body, 
			b2categories.cat_name as Category1, 
			b2users.user_login as AuthorID 
			from b2posts 
			left join b2categories on 
				b2categories.cat_ID = b2posts.post_category 
			left join b2users on 
				b2users.ID = b2posts.post_author
		",$b2link) or $results[]= mysql_error();
		
		while($b=mysql_fetch_array($a)) {
			$articles[] = $b;
		}
	
		$a = mysql_query("
			select
			b2comments.comment_ID as discussid, 
			b2comments.comment_post_ID as parentid, 
			b2comments.comment_author_IP as ip, 
			b2comments.comment_author as name, 
			b2comments.comment_author_email as email, 
			b2comments.comment_author_url as web, 
			b2comments.comment_content as message, 
			b2comments.comment_date as posted
			from b2comments
		",$b2link) or $results[]= mysql_error();
	
		
	
		while($b=mysql_fetch_assoc($a)){
			$comments[] = $b;
		}
	
		mysql_close($b2link);	
		
		//keep a handy copy of txpdb values, and do not alter Dean code
		// for now! ;-)

		$txpdb      = $txpcfg['db'];
		$txpdblogin = $txpcfg['user'];
		$txpdbpass  = $txpcfg['pass'];
		$txpdbhost  = $txpcfg['host'];		
	
		$txplink = mysql_connect($txpdbhost,$txpdblogin,$txpdbpass);

		mysql_select_db($txpdb,$txplink);
	
		include txpath.'/lib/classTextile.php';
		
		$textile = new Textile;
	
		if (!empty($articles)) {
			foreach($articles as $a){	
				$a['Body_html'] = $textile->textileThis($a['Body']);
				extract(array_slash($a));
				$q = mysql_query("
					insert into ".PFX."textpattern set 
					ID        = '$ID',
					Posted    = '$Posted',
					Title     = '$Title',
					Body      = '$Body',
					Body_html = '$Body_html',
					Category1 = '$Category1',
					AuthorID  = '$AuthorID',
					Section   = '$insert_into_section',
					AnnotateInvite = '$default_comment_invite',
					Status    = '$insert_with_status'
				",$txplink) or $results[]= mysql_error();
		
				if (mysql_insert_id() ) {	
					$results[]= 'inserted b2 entry '.$Title.
						' into Textpattern as article '.$ID.'';
				}
			}
		}
	
		if (!empty($comments)) {
			foreach ($comments as $comment) {
				extract(array_slash($comment));
				$message = nl2br($message);
		
				$q = mysql_query("insert into ".PFX."txp_discuss values 
					($discussid,$parentid,'$name','$email','$web','$ip','$posted','$message',1)",
				$txplink) or $results[]= mysql_error($q);
		
				if(mysql_insert_id()) {
					$results[]= 'inserted b2 comment <strong>'.$parentid
						.'</strong> into txp_discuss';
				} 
			}
		}
		return join('<br />', $results);
	}	
		
?>		