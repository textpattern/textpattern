<?php

// ----------------------------------------------------------------
	function doImportMTDB($mt_dblogin, $mt_db, $mt_dbpass, $mt_dbhost, $blog_id, $insert_into_section, $insert_with_status, $default_comment_invite)
	{
		global $txpcfg;			
		//Keep some response on some part
		$results = array();
		
		// let's go - Dean says ;-).		
		$mtlink = mysql_connect($mt_dbhost,$mt_dblogin,$mt_dbpass,true);
		if(!$mtlink){ 
				return 'mt database values don&#8217;t work. Go back, replace them and try again';
		}				
		mysql_select_db($mt_db,$mtlink);
		$results[]= 'connected to mt database. Importing Data';
		
		$a = mysql_query("
			select
			author_id as user_id, 
			author_nickname as name, 
			author_name as RealName, 
			author_email as email, 
			author_password as pass
			from mt_author 
		",$mtlink);
		
		while($b = mysql_fetch_assoc($a)){
			$authors[] = $b;
		}
		
		$a = mysql_query("
			select
			mt_entry.entry_id as ID, 
			mt_entry.entry_text as Body, 
			mt_entry.entry_text_more as Body2, 
			mt_entry.entry_title as Title, 
			mt_entry.entry_excerpt as Excerpt,
			mt_entry.entry_keywords as Keywords,
			mt_entry.entry_created_on as Posted, 
			mt_entry.entry_modified_on as LastMod,
			mt_category.category_label as Category1,
			mt_author.author_name as AuthorID 
			from mt_entry 
			left join mt_author on 
				mt_author.author_id = mt_entry.entry_author_id
			left join mt_placement on
				mt_placement.placement_entry_id = mt_entry.entry_id
			left join mt_category on
				mt_category.category_id = mt_placement.placement_category_id
			where entry_blog_id = '$blog_id'
		",$mtlink);
	
		$results[]= mysql_error();
	
		while($b = mysql_fetch_assoc($a)){			
			$articles[] = $b;
			//FIX ME: Still not working for multiple categories
		}
		
	
		$a = mysql_query("
			select
			mt_comment.comment_id as discussid, 
			mt_comment.comment_entry_id as parentid, 
			mt_comment.comment_ip as ip, 
			mt_comment.comment_author as name, 
			mt_comment.comment_email as email, 
			mt_comment.comment_url as web, 
			mt_comment.comment_text as message, 
			mt_comment.comment_created_on as posted
			from mt_comment where comment_blog_id = '$blog_id'
		",$mtlink);
	
		while($b=mysql_fetch_assoc($a)){
			$comments[] = $b;
		}
		
		$a = mysql_query("
			select category_label from mt_category 
		",$mtlink);
	
		while($b=mysql_fetch_assoc($a)){
			$categories[] = $b;
		}
	
		mysql_close($mtlink);
		
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
	
		if (!empty($authors)) {
			foreach($authors as $author) {
				extract(array_slash($author));
				$name = (empty($name)) ? $RealName : $name;
		
				mysql_query("insert into ".PFX."txp_users set
					user_id  = '$user_id',
					name     = '$RealName',
					email    = '$email',
					pass     = '$pass',
					RealName = '$RealName',
					privs='1'
				",$txplink);
		
				if(mysql_insert_id()) {
					$results[]= 'inserted '.$RealName.' into txp_users';
				} else $results[]=mysql_error();
			}
		}

		if (!empty($categories)) {
			foreach ($categories as $category) {
				extract(array_slash($category));
				mysql_query("insert into ".PFX."txp_category 
						set name='$category_label',type='article'");
				if(mysql_insert_id()) {
					$results[]= 'inserted '.$category_label.' into txp_category';
				} else $results[]=mysql_error();
						
			}
		}
	
		if (!empty($articles)) {
			foreach ($articles as $article) {
				extract(array_slash($article));
				$Body .= (trim($Body2)) ? "\n\n".$Body2 : '';
			
				$Body_html = $textile->textileThis($Body);
				$Excerpt_html = $textile->textileThis($Excerpt);
				$Title = $textile->textileThis($Title,1);
			
				mysql_query("
					insert into ".PFX."textpattern set 
					ID             = '$ID',
					Posted         = '$Posted',
					LastMod        = '$LastMod',
					Title          = '$Title',
					Body           = '$Body',
					Excerpt		   = '$Excerpt',
					Excerpt_html   = '$Excerpt_html',
					Keywords	   = '$Keywords',
					Body_html      = '$Body_html',
					AuthorID       = '$AuthorID',
					Category1      = '$Category1',
					AnnotateInvite = '$default_comment_invite',
					Section        = '$insert_into_section',
					Status         = '$insert_with_status'
				",$txplink);
		
				if(mysql_insert_id()) {
					$results[]='inserted MT entry '.stripslashes($Title).
					' into Textpattern as article '.$ID.'';
				} else $results[]=mysql_error();
			}
		}
		
		if (!empty($comments)) {
			foreach ($comments as $comment) {
				extract(array_slash($comment));
				$message = nl2br($message);
		
				mysql_query("insert into ".PFX."txp_discuss values 
					($discussid,$parentid,'$name','$email','$web','$ip','$posted','$message',1)",
				$txplink);
		
				if(mysql_insert_id()) {
					$results[]='<p>inserted MT comment for article '.$parentid.' into txp_discuss</p>';
				} else $results[]=mysql_error();
			}
		}
		
		return join('<br />', $results);
	}

?>