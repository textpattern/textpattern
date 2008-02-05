<?php

// ----------------------------------------------------------------
	function doImportMTDB($mt_dblogin, $mt_db, $mt_dbpass, $mt_dbhost, $blog_id, $insert_into_section, $insert_with_status, $default_comment_invite)
	{
		global $txpcfg;
		//Keep some response on some part
		$results = array();

		//Avoid left joins
		$authors_map = array();
		$categories_map = array();

		// let's go - Dean says ;-).
		$mtlink = mysql_connect($mt_dbhost,$mt_dblogin,$mt_dbpass,true);
		if(!$mtlink){
				return 'mt database values don&#8217;t work. Please replace them and try again';
		}
		mysql_select_db($mt_db,$mtlink);
		$results[]= 'connected to mt database. Importing Data';

		sleep(2);

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
			mt_entry.entry_author_id as AuthorID
			from mt_entry
			where entry_blog_id = '$blog_id'
		",$mtlink);

		$results[]= mysql_error();

		while($b = mysql_fetch_assoc($a)){
			$cat = mysql_query("select placement_category_id as category_id from mt_placement where placement_entry_id='{$b['ID']}'");
			while ($cat_id = mysql_fetch_row($cat)){
				$categories[] = $cat_id[0];
			}

			if (!empty($categories[0])) $b['Category1'] = $categories[0];
			if (!empty($categories[1])) $b['Category2'] = $categories[1];

			unset($categories);

			//Trap comments for each article
		    $comments = array();

		    $q = "
				select
				mt_comment.comment_id as discussid,
				mt_comment.comment_ip as ip,
				mt_comment.comment_author as name,
				mt_comment.comment_email as email,
				mt_comment.comment_url as web,
				mt_comment.comment_text as message,
				mt_comment.comment_created_on as posted
				from mt_comment where comment_blog_id = '$blog_id' AND comment_entry_id='{$b['ID']}'
			";

		    $c = mysql_query($q, $mtlink);

		    while($d=mysql_fetch_assoc($c)){
				$comments[] = $d;
			}
			//Attach comments to article
			$b['comments'] = $comments;
			unset($comments);

			//Article finished
			$articles[] = $b;
		}


		$a = mysql_query("
			select category_id,category_label from mt_category where category_blog_id='{$blog_id}'
		",$mtlink);

		while($b=mysql_fetch_assoc($a)){
			$categories_map[$b['category_id']] = $b['category_label'];
		}

		mysql_close($mtlink);

		//Yes, we have to make a new connection
		//otherwise doArray complains
		$DB = new DB;

		include txpath.'/lib/classTextile.php';

		$textile = new Textile;

		if (!empty($authors)) {
			foreach($authors as $author) {
				extract($author);
				$name = (empty($name)) ? $RealName : $name;

				$authors_map[$user_id] = $name;

				$authorid = safe_field('user_id', 'txp_users', "name = '".doSlash($name)."'");
				if (!$authorid){
					//Add new authors
					$q = safe_insert("txp_users","
						name     = '".doSlash($RealName)."',
						email    = '".doSlash($email)."',
						pass     = '".doSlash($pass)."',
						RealName = '".doSlash($RealName)."',
						privs='1'"
					);

					if($q) {
						$results[]= 'inserted '.$RealName.' into txp_users';
					} else $results[]=mysql_error();
				}
			}
		}

		if (!empty($categories_map)) {

			foreach ($categories_map as $category) {
				$category = doSlash($category);
				$rs = safe_row('id', 'txp_category', "name='$category' and type='article'");
				if (!$rs){
					$q = safe_insert("txp_category","name='$category',type='article',parent='root'");

					if($q) {
						$results[]= 'inserted '.stripslashes($category).' into txp_category';
					} else $results[]=mysql_error();
				}

			}
		}

		if (!empty($articles)) {
			foreach ($articles as $article) {
				extract($article);
				$Body .= (trim($Body2)) ? "\n\n".$Body2 : '';

				$Body_html = $textile->textileThis($Body);
				$Excerpt_html = $textile->textileThis($Excerpt);
				$Title = $textile->textileThis($Title,1);

				$Category1 = (!empty($Category1)) ? doSlash($Category1) : '';

				$AuthorID = (!empty($authors_map[$AuthorID])) ? doSlash($authors_map[$AuthorID]) : '';

				$insertID = safe_insert("textpattern","
					ID        	   = '$ID',
					Posted         = '$Posted',
					LastMod        = '$LastMod',
					Title          = '".doSlash($Title)."',
					Body           = '".doSlash($Body)."',
					Excerpt		   = '".doSlash($Excerpt)."',
					Excerpt_html   = '".doSlash($Excerpt_html)."',
					Keywords	   = '".doSlash($Keywords)."',
					Body_html      = '".doSlash($Body_html)."',
					AuthorID       = '$AuthorID',
					Category1      = '$Category1',
					AnnotateInvite = '".doSlash($default_comment_invite)."',
					Section        = '".doSlash($insert_into_section)."',
					uid            = '".md5(uniqid(rand(),true))."',
					feed_time      = '".substr($Posted,0,10)."',
					Status         = '$insert_with_status'
				");

				if($insertID) {
					$results[] = 'inserted MT entry '.strong($Title).
						' into Textpattern as article '.strong($insertID).'';

					//Do coment for article
					if (!empty($comments) && is_array($comments)) {
						foreach ($comments as $comment) {
							extract($comment);
							$message = nl2br($message);

							$commentID = safe_insert("txp_discuss","
								discussid = $discussid,
								parentid  = $insertID,
								name      = '".doSlash($name)."',
								email     = '".doSlash($email)."',
								web       = '".doSlash($web)."',
								message   = '".doSlash($message)."',
								ip        = '$ip',
								posted    = '$posted',
								visible   = 1"
							);

							if($commentID) {
								$results[] = 'inserted MT comment '.$commentID.
									' for article '.$insertID.' into txp_discuss';
							} else $results[]=mysql_error();
						}
					}
				} else $results[] = mysql_error();
			}
		}
		return join('<br />', $results);
	}

?>
