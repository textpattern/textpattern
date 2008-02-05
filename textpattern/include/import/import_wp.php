<?php

	function doImportWP($b2dblogin, $b2db, $b2dbpass, $b2dbhost, $wpdbprefix, $insert_into_section, $insert_with_status, $default_comment_invite)
	{
		global $txpcfg;

		$b2link = mysql_connect($b2dbhost, $b2dblogin, $b2dbpass, true);

		if (!$b2link)
		{
			return 'WordPress database values don&#8217;t work. Go back, replace them and try again.';
		}

		mysql_select_db($b2db, $b2link);


		// Keep some response on some part
		$results = array();
		$errors = array();

		$results[] = hed('Connected to WordPress database. Importing Data&#8230;', 1);


		/*
		export users
		*/

		$users = array();


		$user_query = mysql_query("
			select
				ID as user_id,
				user_login as name,
				user_email as email,
				display_name as RealName
			from ".$wpdbprefix."users
		", $b2link) or $errors[] = mysql_error();

		while ($user = mysql_fetch_array($user_query))
		{
			$user_privs_query = mysql_query("
				select
					meta_value
				from ".$wpdbprefix."usermeta
				where user_id = ".$user['user_id']." and meta_key = 'capabilities'
			", $b2link) or $errors[] = mysql_error();

			$privs = unserialize(mysql_result($user_privs_query, 0));

			foreach ($privs as $key => $val)
			{
				// convert the built-in WordPress roles
				// to their Txp equivalent
				switch ($key)
				{
					// publisher
					case 'administrator':
						$user['privs'] = 1;
					break;

					// managing editor
					case 'editor':
						$user['privs'] = 2;
					break;

					// staff writer
					case 'author':
						$user['privs'] = 4;
					break;

					// freelancer
					case 'contributor':
						$user['privs'] = 5;
					break;

					// none
					case 'subscriber':
					default:
						$user['privs'] = 0;
					break;
				}
			}

			$users[] = $user;
		}


		/*
		export article and link categories
		*/

		$categories = array();

		$category_query = mysql_query("
			select
				t.slug as name,
				t.name as title,
				tt.taxonomy as type,
				tt.parent as parent
			from ".$wpdbprefix."terms as t inner join ".$wpdbprefix."term_taxonomy as tt
				on(t.term_id = tt.term_id)
			order by field(tt.taxonomy, 'category','post_tag','link_category'), tt.parent asc, t.name asc
		", $b2link) or $errors[] = mysql_error();

		while ($category = mysql_fetch_array($category_query))
		{
			if ($category['parent'] != 0)
			{
				$category_parent_query = mysql_query("
					select
						slug as name
					from ".$wpdbprefix."terms
					where term_id = '".doSlash($category['parent'])."'
				", $b2link) or $errors[] = mysql_error();

				while ($parent = mysql_fetch_array($category_parent_query))
				{
					$category['parent'] = $parent['name'];
				}
			}

			else
			{
				$category['parent'] = 'root';
			}

			switch ($category['type'])
			{
				case 'post_tag':
				case 'category':
					$category['type'] = 'article';
				break;

				case 'link_category':
					$category['type'] = 'link';
				break;
			}

			$categories[] = $category;
		}


		/*
		export articles
		*/

		$article_query = mysql_query("
			select
				p.ID as ID,
				p.post_status as Status,
				p.post_date as Posted,
				p.post_modified as LastMod,
				p.post_title as Title,
				p.post_content as Body,
				p.comment_status as Annotate,
				p.comment_count as comments_count,
				p.post_name as url_title,
				u.user_login as AuthorID
			from ".$wpdbprefix."posts as p left join ".$wpdbprefix."users as u
				on u.ID = p.post_author
			order by p.ID asc
		", $b2link) or $errors[] = mysql_error();

		while ($article = mysql_fetch_array($article_query))
		{
			// convert WP article status to Txp equivalent
			switch ($article['Status'])
			{
				case 'draft':
					$article['Status'] = 1;
				break;

				// hidden
				case 'private':
					$article['Status'] = 2;
				break;

				case 'pending':
					$article['Status'] = 3;
				break;

				// live
				case 'publish':
					$article['Status'] = 4;
				break;

				default:
					$article['Status'] = $insert_with_status;
				break;
			}

			// convert WP comment status to Txp equivalent
			switch ($article['Annotate'])
			{
				// on
				case 'open':
					$article['Annotate'] = 1;
				break;

				// off
				case 'closed':
				case 'registered_only':
					$article['Annotate'] = 0;
				break;
			}

			// article commments
			$comments = array();

			$comment_query = mysql_query("
				select
					comment_author_IP as ip,
					comment_author as name,
					comment_author_email as email,
					comment_author_url as web,
					comment_content as message,
					comment_date as posted
				from ".$wpdbprefix."comments
				where comment_post_ID = '".$article['ID']."'
				order by comment_ID asc
			", $b2link) or $errors[]= mysql_error();

			while ($comment = mysql_fetch_assoc($comment_query))
			{
				$comments[] = $comment;
			}

			$article['comments'] = $comments;


			// article categories
			$article_categories = array();

			$article_category_query = mysql_query("
				select
					t.name as title,
					t.slug as name
				from ".$wpdbprefix."terms as t inner join ".$wpdbprefix."term_taxonomy as tt
					on(t.term_id = tt.term_id)
				inner join ".$wpdbprefix."term_relationships as tr
					on(tt.term_taxonomy_id = tr.term_taxonomy_id)
				where tr.object_id = '".$article['ID']."' and tt.taxonomy in('post_tag', 'category')
				order by tr.object_id asc, t.name asc
				limit 2;
			", $b2link) or $errors[] = mysql_error();

			while ($category = mysql_fetch_array($article_category_query))
			{
				$article_categories[] = $category;
			}

			$article['Category1'] = !empty($article_categories[0]) ? $article_categories[0]['name'] : '';
			$article['Category2'] = !empty($article_categories[1]) ? $article_categories[1]['name'] : '';


			$articles[] = $article;
		}


		/*
		export links
		*/

		$links = array();

		$link_query = mysql_query("
			select
				link_id as id,
				link_name as linkname,
				link_description as description,
				link_updated as date,
				link_url as url
			from ".$wpdbprefix."links
			order by link_id asc
		", $b2link) or $errors[] = mysql_error();

		while ($link = mysql_fetch_array($link_query))
		{
			// link categories
			$link_categories = array();

			$link_category_query = mysql_query("
				select
					t.name as title,
					t.slug as name
				from ".$wpdbprefix."terms as t inner join ".$wpdbprefix."term_taxonomy as tt
					on(t.term_id = tt.term_id)
				inner join ".$wpdbprefix."term_relationships as tr
					on(tt.term_taxonomy_id = tr.term_taxonomy_id)
				where tr.object_id = '".$link['id']."' and tt.taxonomy = 'link_category'
				order by tr.object_id asc, t.name asc
			", $b2link) or $errors[] = mysql_error();

			while ($category = mysql_fetch_array($link_category_query))
			{
				$link['category'] = $category['name'];
			}


			$links[] = $link;
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


		/*
		import users
		*/

		if ($users)
		{
			include_once txpath.'/lib/txplib_admin.php';

			$results[] = hed('Imported Users:', 2).
				n.graf('Because WordPress uses a different password mechanism than Textpattern, you will need to reset each user&#8217;s password from <a href="index.php?event=admin">the Users tab</a>.').
				n.'<ul>';

			foreach ($users as $user)
			{
				extract($user);

				if (!safe_row('user_id', 'txp_users', "name = '".doSlash($name)."'"))
				{
					$pass = doSlash(generate_password(6));
					$nonce = doSlash(md5(uniqid(mt_rand(), TRUE)));

					$rs = mysql_query("
						insert into ".safe_pfx('txp_users')." set
							name     = '".doSlash($name)."',
							pass     = '".doSlash($pass)."',
							email    = '".doSlash($email)."',
							RealName = '".doSlash($RealName)."',
							privs    = ".$privs.",
							nonce    = '".doSlash($nonce)."'
					", $txplink) or $errors[] = mysql_error();

					if (mysql_insert_id())
					{
						$results[] = '<li>'.$name.' ('.$RealName.')</li>';
					}
				}
			}

			$results[] = '</ul>';
		}



		/*
		import categories
		*/

		if ($categories)
		{
			$results[] = hed('Imported Categories:', 2).n.'<ul>';

			foreach ($categories as $category)
			{
				extract($category);

				if (!safe_row('id', 'txp_category', "name = '".doSlash($name)."' and type = '".doSlash($type)."' and parent = '".doSlash($parent)."'"))
				{
					$rs = mysql_query("
						insert into ".safe_pfx('txp_category')." set
							name   = '".doSlash($name)."',
							title  = '".doSlash($title)."',
							type   = '".doSlash($type)."',
							parent = '".doSlash($parent)."'
					", $txplink) or $errors[] = mysql_error();

					if (mysql_insert_id())
					{
						$results[] = '<li>'.$title.' ('.$type.')</li>';
					}
				}
			}

			rebuild_tree_full('article');
			rebuild_tree_full('link');

			$results[] = '</ul>';
		}


		/*
		import articles
		*/

		if ($articles)
		{
			$results[] = hed('Imported Articles and Comments:', 2).n.'<ul>';

			include txpath.'/lib/classTextile.php';

			$textile = new Textile;

			foreach ($articles as $article)
			{
				extract($article);

				// Ugly, really ugly way to workaround the slashes WP gotcha
				$Body = str_replace('<!--more-->', '', $Body);
				$Body_html = $textile->textileThis($Body);

				// can not use array slash due to way on which comments are selected
				$rs = mysql_query("
					insert into ".safe_pfx('textpattern')." set
						Posted		     = '".doSlash($Posted)."',
						LastMod		     = '".doSlash($LastMod)."',
						Title			     = '".doSlash($textile->TextileThis($Title, 1))."',
						url_title      = '".doSlash($url_title)."',
						Body			     = '".doSlash($Body)."',
						Body_html      = '".doSlash($Body_html)."',
						AuthorID	     = '".doSlash($AuthorID)."',
						Category1      = '".doSlash($Category1)."',
						Category2      = '".doSlash($Category2)."',
						Section		     = '$insert_into_section',
						uid            = '".md5(uniqid(rand(), true))."',
						feed_time      = '".substr($Posted, 0, 10)."',
						Annotate       = '".doSlash($Annotate)."',
						AnnotateInvite = '$default_comment_invite',
						Status		     = '".doSlash($Status)."'
				", $txplink) or $errors[] = mysql_error();

				if ((int)$insert_id = mysql_insert_id())
				{
					$results[] = '<li>'.$Title.'</li>';

					if (!empty($comments))
					{
						$inserted_comments = 0;

						foreach ($comments as $comment)
						{
							extract(array_slash($comment));

							// The ugly workaroud again
							$message = nl2br($message);

							$rs = mysql_query("
								insert into ".safe_pfx('txp_discuss')." set
									parentid = '$insert_id',
									name     = '".doSlash($name)."',
									email    = '".doSlash($email)."',
									web      = '".doSlash($web)."',
									ip       = '".doSlash($ip)."',
									posted   = '".doSlash($posted)."',
									message  = '".doSlash($message)."',
									visible  = 1
							", $txplink) or $results[] = mysql_error();

							if (mysql_insert_id())
							{
								$inserted_comments++;
							}
						}

						$results[] = '<li>- '.$inserted_comments.' of '.$comments_count.' comment(s)</li>';
					}
				}
			}

			$results[] = '</ul>';
		}


		/*
		import links
		*/

		if ($links)
		{
			$results[] = hed('Imported Links:', 2).n.'<ul>';

			foreach ($links as $link)
			{
				extract($link);

				$rs = mysql_query("
					insert into ".safe_pfx('txp_link')." set
						linkname    = '".doSlash($linkname)."',
						linksort    = '".doSlash($linkname)."',
						description = '".doSlash($description)."',
						category    = '".doSlash($category)."',
						date        = '".doSlash($date)."',
						url         = '".doSlash($url)."'
				", $txplink) or $errors[] = mysql_error();

				if (mysql_insert_id())
				{
					$results[] = '<li>'.$linkname.'</li>';
				}
			}

			$results[] = '</ul>';
		}


		/*
		show any errors we encountered
		*/

		if ($errors)
		{
			$results[] = hed('Errors Encountered:', 2).n.'<ul>';

			foreach ($errors as $error)
			{
				$results[] = '<li>'.$error.'</li>';
			}

			$results[] = '</ul>';
		}


		return join(n, $results);
	}

	function undoSlash($in)
	{
		return doArray($in, 'stripslashes');
	}

?>