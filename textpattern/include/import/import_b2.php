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

        // Copy & Paste your table-definitions from b2config.php
        $tableposts = 'b2posts';
        $tableusers = 'b2users';
        $tablecategories = 'b2categories';
        $tablecomments = 'b2comments';


		$a = mysql_query("
			select
			".$tableposts.".ID as ID,
			".$tableposts.".post_date as Posted,
			".$tableposts.".post_title as Title,
			".$tableposts.".post_content as Body,
			".$tablecategories.".cat_name as Category1,
			".$tableusers.".user_login as AuthorID
			from ".$tableposts."
			left join ".$tablecategories." on
				".$tablecategories.".cat_ID = ".$tableposts.".post_category
			left join ".$tableusers." on
				".$tableusers.".ID = ".$tableposts.".post_author
            ORDER BY post_date DESC
		",$b2link) or $results[]= mysql_error();

		while($b=mysql_fetch_array($a)) {
			$articles[] = $b;
		}

		$a = mysql_query("
			select
			".$tablecomments.".comment_ID as discussid,
			".$tablecomments.".comment_post_ID as parentid,
			".$tablecomments.".comment_author_IP as ip,
			".$tablecomments.".comment_author as name,
			".$tablecomments.".comment_author_email as email,
			".$tablecomments.".comment_author_url as web,
			".$tablecomments.".comment_content as message,
			".$tablecomments.".comment_date as posted
			from ".$tablecomments."
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

		//Yes, we have to make a new connection
		//otherwise doArray complains
		$DB = new DB;
		$txplink = &$DB->link;

		mysql_select_db($txpdb,$txplink);

		include txpath.'/lib/classTextile.php';

		$textile = new Textile;

		if (!empty($articles)) {
			foreach($articles as $a){
				if (is_callable('utf8_encode'))
                {
                    // Also fixing break-tags for users with b2s Auto-BR
					$a['Body'] = utf8_encode(str_replace("<br />\n","\n",stripslashes($a['Body'])));
					$a['Title'] = utf8_encode(stripslashes($a['Title']));
            		$a['Title'] = $textile->TextileThis($a['Title'],'',1);
                }
                // b2 uses the magic word "<!--more-->" to generate excerpts
                if (strpos($a['Body'],'<!--more-->'))
                {
                    //Everything that is before "more" can be treated as the excerpt.
                    $pos = strpos($a['Body'],'<!--more-->');
                    $a['Excerpt'] = substr($a['Body'],0,$pos);
                    $a['Excerpt_html'] = $textile->textileThis($a['Excerpt']);
                    $a['Body'] = str_replace('<!--more-->','',$a['Body']);
                }
                else
                {
                    $a['Excerpt'] = '';
                    $a['Excerpt_html'] = '';
                }
                $a['url_title'] = stripSpace($a['Title'],1);
				$a['Body_html'] = $textile->textileThis($a['Body']);
				extract(array_slash($a));
				$q = mysql_query("
					insert into `".PFX."textpattern` set
					ID        = '$ID',
					Posted    = '$Posted',
					Title     = '$Title',
                    url_title = '$url_title',
					Body      = '$Body',
					Body_html = '$Body_html',
					Excerpt   = '$Excerpt',
					Excerpt_html = '$Excerpt_html',
					Category1 = '$Category1',
					AuthorID  = '$AuthorID',
					Section   = '$insert_into_section',
					AnnotateInvite = '$default_comment_invite',
					uid='".md5(uniqid(rand(),true))."',
					feed_time='".substr($Posted,0,10)."',
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
				if (is_callable('utf8_encode'))
					$message = utf8_encode($message);
				$message = nl2br($message);

				$q = mysql_query("insert into `".PFX."txp_discuss` values
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
