<?php

	function doImportWP($b2dblogin, $b2db, $b2dbpass, $b2dbhost, $wpdbprefix, $insert_into_section, $insert_with_status, $default_comment_invite)
	{
		global $txpcfg;
		//Keep some response on some part
		$results = array();	
		
		// let's go - Dean says ;-).		
		$b2link = mysql_connect($b2dbhost,$b2dblogin,$b2dbpass,true);
		if(!$b2link){ 
			return 'wp database values don&#8217;t work. Go back, replace them and try again';
		}				
		mysql_select_db($b2db,$b2link);
		$results[]= 'connected to wp database. Importing Data';
		
		    $a = mysql_query("
		        select
		        ".$wpdbprefix."posts.ID as ID,
		        ".$wpdbprefix."posts.post_date as Posted,
		        ".$wpdbprefix."posts.post_title as Title,
		        ".$wpdbprefix."posts.post_content as Body,
		        ".$wpdbprefix."users.user_login as AuthorID
		        from ".$wpdbprefix."posts
		        left join ".$wpdbprefix."users on
		            ".$wpdbprefix."users.ID = ".$wpdbprefix."posts.post_author
		    ",$b2link) or $results= mysql_error();
		    
		    
		    while($b=mysql_fetch_array($a)) {
		        $articles[] = $b;
		    }
		
		    $a = mysql_query("
		        select
		        ".$wpdbprefix."comments.comment_ID as discussid,
		        ".$wpdbprefix."comments.comment_post_ID as parentid,
		        ".$wpdbprefix."comments.comment_author_IP as ip,
		        ".$wpdbprefix."comments.comment_author as name,
		        ".$wpdbprefix."comments.comment_author_email as email,
		        ".$wpdbprefix."comments.comment_author_url as web,
		        ".$wpdbprefix."comments.comment_content as message,
		        ".$wpdbprefix."comments.comment_date as posted
		        from ".$wpdbprefix."comments
		    ",$b2link) or $results[]= mysql_error();
		
		    
		
		    while($b=mysql_fetch_assoc($a)){
		        $comments[] = $b;
		    }
		    
		    $a = mysql_query("
		      select
		        ".$wpdbprefix."post2cat.post_id as post,
		        ".$wpdbprefix."post2cat.category_id as catid,
		        ".$wpdbprefix."categories.cat_name as catname
		        from ".$wpdbprefix."post2cat
		        left join ".$wpdbprefix."categories on
		          ".$wpdbprefix."categories.cat_ID = ".$wpdbprefix."post2cat.category_id        
		        ",$b2link) or $results[]= mysql_error();
		    
		    while($b=mysql_fetch_array($a)) {
		      $categories[] = $b;
		    }
		    
		    $a = mysql_query("
		      select
		        ".$wpdbprefix."categories.cat_ID as catid,
		        ".$wpdbprefix."categories.cat_name as catname,
		        ".$wpdbprefix."categories.category_parent as catparent
		        from ".$wpdbprefix."categories
		        ",$b2link) or $results[]= mysql_error();
		    
		    while($b=mysql_fetch_array($a)) {
		      $cats[] = $b;
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
			          $a['Body'] = str_replace('<!--more-->','',$a['Body']);    
			            $a['Body_html'] = $textile->textileThis($a['Body']);
			            extract(array_slash($a));
			            $q = mysql_query("
			                insert into ".PFX."textpattern set
			                ID        = '$ID',
			                Posted    = '$Posted',
			                Title     = '$Title',
			                Body      = '$Body',
			                Body_html = '$Body_html',
			                AuthorID  = '$AuthorID',
			                Section   = '$insert_into_section',
			                AnnotateInvite = '$default_comment_invite',
			                Status    = '$insert_with_status'
			            ",$txplink) or $results[]= mysql_error();
			    
			            if (mysql_insert_id() ) {    
			                $results[]= 'inserted wp_ entry '.$Title.
			                    ' into Textpattern as article '.$ID.'';
			            }
			        }
			    }
			
			    if (!empty($comments)) {
//			    $empty = mysql_query("truncate table txp_discuss");
			        foreach ($comments as $comment) {
			            extract(array_slash($comment));
			            $message = nl2br($message);
			    
			            $q = mysql_query("insert into ".PFX."txp_discuss values
			                ($discussid,$parentid,'$name','$email','$web','$ip','$posted','$message',1)",
			            $txplink) or $results[]= mysql_error($q);
			    
			            if(mysql_insert_id()) {
			                $results[]='inserted wp_ comment <strong>'.$parentid
			                    .'</strong> into txp_discuss';
			            }
			        }
			    }
			    
			    if (!empty($cats)) {
			    $right = 2;
			    $left = 1;
			        foreach ($cats as $cat) {
			            extract(array_slash($cat));
			            
			            $left++;
			            $right++;
			            
			            $q = mysql_query("
			            insert into ".PFX."txp_category set
			             name = '$catname',
			             type = 'article',
			             parent = 'root',
			             lft = '$left',
			             rgt = '$right'",
			            $txplink) or $results[]= mysql_error($q);
			    
			            if(mysql_insert_id()) {
			                $results[]= 'inserted wp_ category <strong>'.$catname
			                    .'</strong> into txp_category';
			            }
			        }
			     $num = mysql_query("select * from ".PFX."txp_category");
			     $num = mysql_num_rows($num)+1;
			     $renum = mysql_query("update ".PFX."txp_category set rgt = '$num' where type = 'article' and name = 'root'",$txplink) or print mysql_error($num);
			    }
			    
			    if (!empty($categories)) {
			      foreach ($categories as $category) {
			          extract(array_slash($category));
			            
			            $chk = mysql_query("select Category1, Category2 from ".PFX."textpattern where ID ='$post'");
			            while ($row = mysql_fetch_array($chk)) {
			              if (!$row['Category1']) {
			                    $q = mysql_query("update ".PFX."textpattern set Category1 = '$catname' where ID = '$post'",$txplink) or print mysql_error($q);
			                    
			                    if(mysql_insert_id()) {
			                $results[]= 'inserted wp_ category <strong>'.$catname
			                    .'</strong> to post number <strong>'.$post.'</strong> into textpattern';
			                    }
			                }
			                elseif ($row['Category1']  && !$row['Category2']) {
			                  $q = mysql_query("update ".PFX."textpattern set Category2 = '$catname' where ID = '$post'",$txplink) or print mysql_error($q);
			                    
			                    if(mysql_insert_id()) {
			                $results[]= 'inserted wp_ category <strong>'.$catname
			                    .'</strong> to post number <strong>'.$post.'</strong> into textpattern';
			                    }
			                }
			                else {
			                  $results[]= "Only two categories are supported by Textpattern. $catname has not been added to this post.";
			                }
			            }
			        }
			    }
		
		return join('<br />', $results);
	}

?>