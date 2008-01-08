<?php

	function doImportWP($b2dblogin, $b2db, $b2dbpass, $b2dbhost, $wpdbprefix, $insert_into_section, $insert_with_status, $default_comment_invite)
	{
		global $txpcfg;

		// Keep some response on some part
		$results = array();

		$b2link = mysql_connect($b2dbhost, $b2dblogin, $b2dbpass, true);

		if (!$b2link)
		{
			return 'WordPress database values don&#8217;t work. Go back, replace them and try again.';
		}

		mysql_select_db($b2db, $b2link);

		$results[] = hed('Connected to WordPress database. Importing Data&#8230;', 1).
			n.'<ul>'.n;

		/*
		export articles
		*/

		$a = mysql_query("
			select
				".$wpdbprefix."posts.ID as ID,
				".$wpdbprefix."posts.post_status as Status,
				".$wpdbprefix."posts.post_date as Posted,
				".$wpdbprefix."posts.post_modified as LastMod,
				".$wpdbprefix."posts.post_title as Title,
				".$wpdbprefix."posts.post_content as Body,
				".$wpdbprefix."posts.comment_status as Annotate,
				".$wpdbprefix."posts.comment_count as comments_count,
				".$wpdbprefix."post_name as url_title,
				".$wpdbprefix."users.user_login as AuthorID
			from ".$wpdbprefix."posts left join ".$wpdbprefix."users 
				on ".$wpdbprefix."users.ID = ".$wpdbprefix."posts.post_author
		", $b2link) or $results[] = mysql_error();

		while ($b = mysql_fetch_array($a))
		{
			// Clean ugly wp slashes before to continue
			$b = undoSlash(undoSlash($b));

			// article commments
			$comments = array();

			$c = mysql_query("
				select
					".$wpdbprefix."comments.comment_author_IP as ip,
					".$wpdbprefix."comments.comment_author as name,
					".$wpdbprefix."comments.comment_author_email as email,
					".$wpdbprefix."comments.comment_author_url as web,
					".$wpdbprefix."comments.comment_content as message,
					".$wpdbprefix."comments.comment_date as posted
				from ".$wpdbprefix."comments 
				where comment_post_ID = '".$b['ID']."'
			", $b2link) or $results[]= mysql_error();

			while ($d = mysql_fetch_assoc($c))
			{
				$d = undoSlash(undoSlash($d));
				$comments[] = $d;
			}

			$b['comments'] = $comments;

			// article categories
			$categories = array();

			$e = mysql_query("
				select 
					".$wpdbprefix."terms.name as title, 
					".$wpdbprefix."terms.slug as name
				from ".$wpdbprefix."terms inner join ".$wpdbprefix."term_taxonomy
					on(".$wpdbprefix."terms.term_id = ".$wpdbprefix."term_taxonomy.term_id) 
				inner join ".$wpdbprefix."term_relationships
					on(".$wpdbprefix."term_taxonomy.term_taxonomy_id = ".$wpdbprefix."term_relationships.term_taxonomy_id)
				where ".$wpdbprefix."term_relationships.object_id = '".$b['ID']."' and ".$wpdbprefix."term_taxonomy.taxonomy in('post_tag', 'category') 
				order by ".$wpdbprefix."term_relationships.object_id asc, ".$wpdbprefix."terms.name asc
				limit 2;
			", $b2link) or $results[]= mysql_error();

			while ($f = mysql_fetch_array($e))
			{
				$categories[] = $f;
			}

			$b['Category1'] = !empty($categories[0]) ? $categories[0]['name'] : '';
			$b['Category2'] = !empty($categories[1]) ? $categories[1]['name'] : '';

			$articles[] = $b;
		}


		/*
		export categories
		*/

		$cats = array();

		$a = mysql_query("
			select 
				".$wpdbprefix."terms.slug as name, 
				".$wpdbprefix."terms.name as title
			from ".$wpdbprefix."terms inner join ".$wpdbprefix."term_taxonomy 
				on(".$wpdbprefix."terms.term_id = ".$wpdbprefix."term_taxonomy.term_id)
			where ".$wpdbprefix."term_taxonomy.taxonomy in('post_tag', 'category') 
			order by ".$wpdbprefix."terms.name asc
		", $b2link) or $results[] = mysql_error();

		while ($b = mysql_fetch_array($a))
		{
			$cats[] = $b;
		}

		mysql_close($b2link);


		/*
		begin import
		*/


		// keep a handy copy of txpdb values, and do not alter Dean code
		// for now! ;-)

		$txpdb			= $txpcfg['db'];
		$txpdblogin = $txpcfg['user'];
		$txpdbpass	= $txpcfg['pass'];
		$txpdbhost	= $txpcfg['host'];

		// Yes, we have to make a new connection
		// otherwise doArray complains
		$DB = new DB;
		$txplink = &$DB->link;

		mysql_select_db($txpdb, $txplink);

		include txpath.'/lib/classTextile.php';

		$textile = new Textile;

		/*
		import articles
		*/

		if ($articles)
		{
			foreach ($articles as $a)
			{
				// some of WP's status' are supported by Txp directly,
				// so let's try and keep those
				switch ($a['Status'])
				{
					case 'draft':
						$a['Status'] = 1;
					break;

					// hidden
					case 'private':
						$a['Status'] = 2;
					break;

					case 'pending':
						$a['Status'] = 3;
					break;

					// live
					case 'publish':
						$a['Status'] = 4;
					break;

					default:
						$a['Status'] = $insert_with_status;
					break;
				}

				switch ($a['Annotate'])
				{
					case 'open':
						$a['Annotate'] = 1;
					break;

					case 'closed':
					case 'registered_only':
						$a['Annotate'] = 0;
					break;
				}

				// Ugly, really ugly way to workaround the slashes WP gotcha
				$a['Body'] = str_replace('<!--more-->', '', $a['Body']);
				$a['Body_html'] = $textile->textileThis($a['Body']);

				extract($a);

				// can not use array slash due to way on which comments are selected
				$q = mysql_query("
					insert into ".safe_pfx('textpattern')." set
					Posted		     = '".addslashes($Posted)."',
					LastMod		     = '".addslashes($LastMod)."',
					Title			     = '".addslashes($textile->TextileThis($Title, 1))."',
					url_title      = '".addslashes($url_title)."',
					Body			     = '".addslashes($Body)."',
					Body_html      = '".addslashes($Body_html)."',
					AuthorID	     = '".addslashes($AuthorID)."',
					Category1      = '".addslashes($Category1)."',
					Category2      = '".addslashes($Category2)."',
					Section		     = '$insert_into_section',
					uid            = '".md5(uniqid(rand(), true))."',
					feed_time      = '".substr($Posted, 0, 10)."',
					Annotate       = '".addslashes($Annotate)."',
					AnnotateInvite = '$default_comment_invite',
					Status		     = '".addslashes($Status)."'
				", $txplink) or $results[] = mysql_error();

				if ($insertID = mysql_insert_id())
				{
					$results[] = '<li>Imported article, &#8220;'.$Title.'&#8221; (<strong>ID#'.$insertID.'</strong>).</li>';

					if (!empty($comments))
					{
						$inserted_comments = 0;

						foreach ($comments as $comment)
						{
							extract(array_slash($comment));

							// The ugly workaroud again
							$message = nl2br($message);

							$r = mysql_query("
								insert into ".safe_pfx('txp_discuss')." set
									parentid = '$insertID',
									name     = '$name',
									email    = '$email',
									web      = '$web',
									ip       = '$ip',
									posted   = '$posted',
									message  = '$message',
									visible  = 1
							", $txplink) or $results[] = mysql_error();

							if ($commentID = mysql_insert_id())
							{
								$inserted_comments++;
							}
						}

						$results[] = '<li>Imported '.$inserted_comments.' of '.$comments_count.' comment(s) for article <strong>ID#'.$insertID.'</strong>.</li>';
					}
				}
			}
		}

		/*
		import categories
		*/

		if ($cats)
		{
			foreach ($cats as $cat)
			{
				extract(array_slash($cat));

				if (!safe_row('id', 'txp_category', "name = '$name'"))
				{
					$q = mysql_query("
						insert into ".safe_pfx('txp_category')." set
							name   = '".addslashes($name)."',
							title  = '".addslashes($title)."',
							type   = 'article',
							parent = 'root'
					", $txplink) or $results[] = mysql_error($q);

					if (mysql_insert_id())
					{
						$results[] = '<li>Imported category, &#8220;'.$title.'&#8221;.</li>';
					}
				}
			}

			rebuild_tree_full('article');
		}

		return join(n, $results).n.'</ul>';
	}

	function undoSlash($in)
	{
		return doArray($in, 'stripslashes');
	}

?>