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
		    ",$b2link) or $results[]= mysql_error();
		    
		    
		    while($b=mysql_fetch_array($a)) {
		    	//Clean ugly wp slashes before to continue
		    	$b = undoSlash(undoSlash($b));
		    	
		    	//Trap comments for each article
		    	$comments = array();
				$q= "
			        select
			        ".$wpdbprefix."comments.comment_author_IP as ip,
			        ".$wpdbprefix."comments.comment_author as name,
			        ".$wpdbprefix."comments.comment_author_email as email,
			        ".$wpdbprefix."comments.comment_author_url as web,
			        ".$wpdbprefix."comments.comment_content as message,
			        ".$wpdbprefix."comments.comment_date as posted
			        from ".$wpdbprefix."comments where comment_post_ID='".$b['ID']."'
			    ";				
				$c = mysql_query($q,$b2link) or $results[]= mysql_error();
							
			    while($d=mysql_fetch_assoc($c)){
			        $d = undoSlash(undoSlash($d));
			    	$comments[] = $d;
			    }
			    $b['comments'] = $comments;
			    unset($comments);
			    //Post categories now
			    $q = "
			      select
			        ".$wpdbprefix."post2cat.category_id as catid,
			        ".$wpdbprefix."categories.cat_name as catname,
					".$wpdbprefix."categories.category_nicename as catnicename
			        from ".$wpdbprefix."post2cat
			        left join ".$wpdbprefix."categories on
			          ".$wpdbprefix."categories.cat_ID = ".$wpdbprefix."post2cat.category_id where ".$wpdbprefix."post2cat.post_id='".$b['ID']."' limit 2        
			        ";
			    			    
			    $e = mysql_query($q ,$b2link) or $results[]= mysql_error();			    
			    
			    while($f=mysql_fetch_array($e)) {
			      $categories[] = $f;
			    }
			    
			    $b['Category1'] = (!empty($categories[0]))?$categories[0]['catnicename']:'';
			    $b['Category2'] = (!empty($categories[1]))?$categories[1]['catnicename']:'';			    
			    
			    unset($categories);
			    
			    $articles[] = $b;
		    }
		    
		    $a = mysql_query("
		      select
		        ".$wpdbprefix."categories.cat_ID as catid,
		        ".$wpdbprefix."categories.cat_name as catname,
		        ".$wpdbprefix."categories.category_nicename as catnicename,
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
	
		//Yes, we have to make a new connection
		//otherwise doArray complains
		$DB = new DB; 
		$txplink = &$DB->link;

		mysql_select_db($txpdb,$txplink);
	
		include txpath.'/lib/classTextile.php';
		
		$textile = new Textile;
	
		   if (!empty($articles)) {
			        foreach($articles as $a){
			        	//Ugly, really ugly way to workaround the slashes WP gotcha
			        	$a['Body'] = str_replace('<!--more-->','',$a['Body']);
			            $a['Body_html'] = $textile->textileThis($a['Body']);
			            extract($a);
			            //can not use array slash due to way on which comments are selected
			            $q = mysql_query("
			                insert into `".PFX."textpattern` set
			                Posted    = '".addslashes($Posted)."',
			                Title     = '".addslashes($textile->TextileThis($Title,1))."',
			                url_title = '".stripSpace($Title)."',
			                Body      = '".addslashes($Body)."',
			                Body_html = '".addslashes($Body_html)."',
			                AuthorID  = '".addslashes($AuthorID)."',
			                Category1 = '".addslashes($Category1)."',
			                Category2 = '".addslashes($Category2)."',
			                Section   = '$insert_into_section',
			                uid='".md5(uniqid(rand(),true))."',
							feed_time='".substr($Posted,0,10)."',
			                AnnotateInvite = '$default_comment_invite',
			                Status    = '$insert_with_status'
			            ",$txplink) or $results[]= mysql_error();
			    
			            if ($insertID = mysql_insert_id() ) {    
			                $results[]= 'inserted wp_ entry '.$Title.
			                    ' into Textpattern as article '.$insertID.'';
			                    
			                if (!empty($comments)) {
						        foreach ($comments as $comment) {
							            extract(array_slash($comment));
							            //The ugly workaroud again
							            $message = nl2br($message);
							    
							            $r = mysql_query("insert into `".PFX."txp_discuss` set					
							                parentid = '$insertID',
							                name = '$name',
							                email = '$email',
							                web = '$web',
							                ip = '$ip',
							                posted = '$posted',
							                message = '$message',
							                visible = 1",$txplink) or $results[]= mysql_error();
							    
							            if($commentID = mysql_insert_id()) {
							                $results[]='inserted wp_ comment <strong>'.$commentID
							                    .'</strong> into txp_discuss';
							            }
						        }
						    }
			            }
			            
			            
			        }
			    }
			
			    
			    
			    if (!empty($cats)) {
			    $right = 2;
			    $left = 1;
			        foreach ($cats as $cat) {
			            extract(array_slash($cat));
			            //Prevent repeated categories
			            $rs = safe_row('id', 'txp_category', "name='$catnicename'");			            			            
			            if (!$rs){
			            	$left++;
			            	$right++;
				            $q = mysql_query("
				            insert into `".PFX."txp_category` set
				             name = '$catnicename',
					         title = '$catname',
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
			        }
			    }			    			    
		
		return join('<br />', $results);
	}

	
	function undoSlash($in)
	{ 
			return doArray($in,'stripslashes');		
	}
?>
