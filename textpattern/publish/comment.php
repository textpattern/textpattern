<?php

/*
	This is Textpattern
	Copyright 2004 by Dean Allen - all rights reserved.

	Use of this software denotes acceptance of the Textpattern license agreement 

*/


// -------------------------------------------------------------
	function discuss($ID)
	{
		global $comments_disabled_after;
		$preview = ps('preview');
		extract(	
			safe_row(
				"Annotate,AnnotateInvite,unix_timestamp(Posted) as uPosted",
					"textpattern", "ID='$ID'"
			)
		);

		$darr = (!$preview) 
		?	fetchComments($ID)
		:	array(psas(array('name','email','web','message','parentid','remember')));

		$out = n.'<h3 style="margin-top:2em" id="comment">'.$AnnotateInvite.'</h3>'.n;



		if ($darr) {
			$out.= '<ol>'.n;
			$out.= formatComments($darr);
			$out.= n.'</ol>';
		}
		
			$wasAnnotated = (!$Annotate) ? getCount('txp_discuss',"parentid=$ID") : '';

			if (!$Annotate) {
				$out .= graf(gTxt("comments_closed"));
			} else {
				if($comments_disabled_after) {		
					$lifespan = ( $comments_disabled_after * 86400 );
					$timesince = ( time() - $uPosted );
					if( $lifespan > $timesince ) {
						$out .= commentForm( $ID );
					} else $out .= graf( gTxt( "comments_closed" ) );
				} else {
					$out .= commentForm( $ID );
				}
			}
		return $out;

	}

// -------------------------------------------------------------
	function fetchComments($id)
	{
		$rs = safe_rows(
			"*, unix_timestamp(posted) as time", 
			"txp_discuss", "parentid='$id' and visible='1' order by posted asc"
		);

		if ($rs) return $rs;
	}

// -------------------------------------------------------------
	function formatComments($darr)
	{
		global $prefs,$txpcfg,$comments_disallow_images,$txpac;
		extract($prefs);
		$preview = gps('preview');
		$id = gps('id');
		$Form = fetch('Form','txp_form','name','comments');
		$out = '';
				
		foreach($darr as $vars) {
			extract($vars);
			
			if($preview) {		
				include_once $txpcfg['txpath'].'/lib/classTextile.php';
				$time=time();
				$discussid=0;
				$textile = new Textile();
				$im = (!empty($comments_disallow_images)) ? 1 : '';
				$message = trim(nl2br($textile->TextileThis(strip_tags($message),1,'',$im)));
			} 
			
			if($comments_dateformat == "since") { 
				$comment_time = since($time + $timeoffset); 
			} else {
				$comment_time = date($comments_dateformat,($time + $timeoffset)); 
			}
							
			$web = str_replace("http://", "", $web);
	
			if ($email && !$web && !$txpac['never_display_email'])
				$name = '<a href="'.eE('mailto:'.$email).'">'.$name.'</a>';

			if ($web)
				$name = '<a href="http://'.$web.'" title="'.$web.'">'.$name.'</a>';

			$Section = fetch('Section','textpattern','ID',$parentid);

				if($url_mode==1) {
					$dlink = $path_from_root.
						$Section.'/'.$parentid.'/#c'.$discussid;
				} else if ($url_mode==0) {
					$dlink = $path_from_root.'index.php?id='.$parentid.'#c'.$discussid;
				}
		
			$vals = array(
				'comment_name'=>$name,
				'message'=>$message,
				'comment_time'=>$comment_time
			);
		
			$temp = $Form;
			foreach($vals as $a=>$b) {
				$temp = str_replace('<txp:'.$a.' />',$b,$temp);
			}
			
			$temp = preg_replace('/<(txp:comment_permlink)>(.*)<\/\\1>/U',
				'<a href="'.$dlink.'">$2</a>',$temp);

			$out .= n.t.'<li id="c'.$discussid.'" style="margin-top:2em">'.$temp.'</li>';
		}
			return $out;
		}

// -------------------------------------------------------------
	function commentForm($id) 
	{
		global $txpac;
		$namewarn = '';
		$emailwarn = '';
		$commentwarn = '';
		$name= pcs('name');
		$email= pcs('email');
		$web= pcs('web');		
		extract( psa( array(
			'remember',
			'forget',
			'parentid',
			'preview',
			'message',
			'submit',
			'backpage'
		) ) );
			
		if ( $preview ) {
			$name  = ps( 'name' );
			$email = ps( 'email' );
			$web   = ps( 'web' );		
			$nonce = md5( uniqid( rand(), true ) );
			safe_insert("txp_discuss_nonce", "issue_time=now(), nonce='$nonce'");

			$namewarn = ($txpac['comments_require_name'])
			?	(!trim($name)) 
				?	gTxt('comment_name_required').br
				:	''
			:	'';

			$emailwarn = ($txpac['comments_require_email'])
			?	(!trim($email)) 
				?	gTxt('comment_email_required').br 
				:	''
			:	'';

			$commentwarn = (!trim($message)) ? gTxt('comment_required').br : '';
		}

		$parentid = (!$parentid) ? $id : $parentid;
		
		if ($remember==1) { setCookies($name,$email,$web); }
		if ($forget==1) { destroyCookies(); }

		$out = '<form method="post" action="" style="margin-top:2em">';

		$form = fetch('Form','txp_form','name','comment_form');
					  
	 	$textarea = '<textarea name="message" cols="1" rows="1" style="width:300px;height:250px" tabindex="4">'.htmlspecialchars($message).'</textarea>';

		$comment_submit_button = ($preview)
		?	fInput('submit','submit',gTxt('submit'),'button')
		:	'';
			
		$checkbox = (!empty($_COOKIE['txp_name']))
		?	checkbox('forget',1,0).gTxt('forget')
		:	checkbox('remember',1,1).gTxt('remember');

		$vals = array(
			'comment_name_input'    => $namewarn.input('text','name',  $name, "25",'',"1"),
			'comment_email_input'   => $emailwarn.input('text','email', $email,"25",'',"2"),
			'comment_web_input'     => input('text','web',   $web,  "25",'',"3"),
			'comment_message_input' => $commentwarn.$textarea,
			'comment_remember'      => $checkbox,
			'comment_preview'       => input('submit','preview',gTxt('preview'),'','button'),
			'comment_submit'        => $comment_submit_button
		);

		foreach ($vals as $a=>$b) {
			$form = str_replace('<txp:'.$a.' />',$b,$form);
		}

	  	$form = parse($form);

		$out .= $form;
		$out .= graf(fInput('hidden','parentid',$parentid));
		$out .= ($preview) ? hInput('nonce',$nonce) : '';

		$out .= (!$preview)
		?	graf(fInput('hidden','backpage',serverset("REQUEST_URI")))
		:	graf(fInput('hidden','backpage',$backpage));
		$out .= '</form>'; 
	  return $out;
	}

// -------------------------------------------------------------
	function popComments($id)
	{
		global $sitename,$s;
		$preview = gps('preview');
		$h3 = ($preview) ? hed(gTxt('message_preview'),3) : '';
		$discuss = discuss($id);
		ob_start('parse');
		$out = fetch('Form','txp_form','name','popup_comments');
		$out = str_replace("<txp:popup_comments />",discuss($id),$out);
		return $out;

	}

// -------------------------------------------------------------
	function setCookies($name,$email,$web)
	{
		$cookietime = time() + (365*24*3600);
		ob_start();
		setcookie("txp_name",  $name,  $cookietime, "/");
		setcookie("txp_email", $email, $cookietime, "/");
		setcookie("txp_web",   $web,   $cookietime, "/");
		setcookie("txp_last",  date("H:i d/m/Y"),$cookietime,"/");
	}

// -------------------------------------------------------------
	function destroyCookies() 
	{
		$cookietime = time()-3600;
		ob_start();
		setcookie("txp_name",  '', $cookietime, "/");
		setcookie("txp_email", '', $cookietime, "/");
		setcookie("txp_web",   '', $cookietime, "/");
		setcookie("txp_last",  '', $cookietime, "/");
	}

// -------------------------------------------------------------
	function saveComment() 
	{
		global $siteurl,$comments_moderate,$comments_sendmail,$txpcfg,
			$comments_disallow_images,$txpac;
		include_once $txpcfg['txpath'].'/lib/classTextile.php';
		$im = (!empty($comments_disallow_images)) ? 1 : '';

		$textile = new Textile();

		$ref = serverset('HTTP_REFERRER');

		extract( psa( array(
			'parentid',
			'name',
			'email',
			'web',
			'message',
			'backpage',
			'nonce',
			'remember'
		) ) );

		if ($txpac['comments_require_name']) {
			if (!trim($name)) {
				exit ( graf(gTxt('comment_name_required')).
					graf('<a href="" onClick="history.go(-1)">'.gTxt('back').'</a>') );
			}
		}

		if ($txpac['comments_require_email']) {
			if (!trim($email)) {
				exit ( graf(gTxt('comment_email_required')).
					graf('<a href="" onClick="history.go(-1)">'.gTxt('back').'</a>') );
			}
		}

		if (!trim($message)) {
			exit ( graf(gTxt('comment_required')).
				graf('<a href="" onClick="history.go(-1)">'.gTxt('back').'</a>') );
		}

		$ip= @getHostByAddr(serverset('REMOTE_ADDR'));
		$message = strip_tags(trim($message));
		
		$message2db = addslashes(nl2br($textile->textileThis($message,1,'',$im)));
		$isdup = safe_row("message,name", "txp_discuss", 
			"name='$name' and message='$message2db' and ip='$ip'");

		if (checkBan($ip)) {
			if (!$isdup) {
				if (checkNonce($nonce)) {
					$visible = ($comments_moderate) ? 0 : 1;
					$rs = safe_insert(
						"txp_discuss",
						"parentid  = '$parentid',
						 name      = '$name',
						 email     = '$email',
						 web       = '$web',
						 ip        = '$ip',
						 message   = '$message2db',
						 visible   = $visible,
						 posted    = now()"
					);
				
					 if ($rs) {
						safe_update("txp_discuss_nonce", "used='1'", "nonce='$nonce'");
						if ($txpac['comment_means_site_updated']) {
							safe_update("txp_prefs", "val=now()", "name='lastmod'");
						}
						if ($comments_sendmail) 
							mail_comment($message,$name,$email,$web,$parentid);
						ob_start();
						header('location: '.$backpage);
					}
				} // end check nonce
			} // end check dup
		} else exit(gTxt('you_have_been_banned'));
	}

// -------------------------------------------------------------
	function checkNonce($nonce) 
	{
		if (!$nonce) return false;
			// delete expired nonces
		safe_delete("txp_discuss_nonce", "issue_time < date_sub(now(),interval 10 minute)");
			// check for nonce
		$rs = safe_row("*", "txp_discuss_nonce", "nonce='$nonce' and used='0'");

			// if it's good, 
		if ($rs) return true;
		return false;
	}

// -------------------------------------------------------------
	function checkBan($ip)
	{
		$rs = fetch("ip", "txp_discuss_ipban", "ip", '$ip');
		if (!$rs) return true;
		return false;
	}

// -------------------------------------------------------------
   	function comments_help()
	{
		return <<<eod
		&#60;<a target="_blank" href="http://www.textpattern.com/help/?item=textile_comments" onclick="window.open(this.href, 'popupwindow', 'width=300,height=400,scrollbars,resizable'); return false;" style="color:blue;text-decoration:none">?</a>&#62;
eod;
	}

// -------------------------------------------------------------
	function mail_comment($message, $cname, $cemail, $cweb, $parentid) 
	{
		global $siteurl,$path_from_root,$sitename,$txp_user;
		$myName = $txp_user;
		extract(safe_row("AuthorID,Title", "textpattern", "ID = '$parentid'"));
		extract(safe_row("RealName, email", "txp_users", "name = '$AuthorID'"));

$out = "Dear $RealName,\r\n\r\nA comment on your post on your post \"$Title\" was recorded.\r\n\r\nName: $cname\r\nEmail: $cemail\r\nWeb: $cweb\r\nComment:\r\n$message";

		mail($email, "[$sitename] comment received: $Title", $out,
		 "From: $RealName <$email>\r\n"
		."Reply-To: $cname <$cemail>\r\n"
		."X-Mailer: Textpattern\r\n"
		."Content-Transfer-Encoding: 8bit\r\n"
		."Content-Type: text/plain; charset=\"UTF-8\"\r\n");
	}

?>
