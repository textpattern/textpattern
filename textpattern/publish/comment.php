<?php

/*
	This is Textpattern
	Copyright 2005 by Dean Allen - all rights reserved.

	Use of this software denotes acceptance of the Textpattern license agreement 

*/

// -------------------------------------------------------------
	function discuss($ID)
	{
		global $comments_are_ol;
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

		$out = n.'<h3 class="txpCommentInvite" id="'.gTxt('comment').'">'.$AnnotateInvite.'</h3>'.n;



		if ($darr) {
			$out.= ($comments_are_ol) ? '<ol>'.n : '';
			$out.= formatComments($darr);
			$out.= ($comments_are_ol) ? n.'</ol>' : '';
		}
		
			$wasAnnotated = (!$Annotate) ? getCount('txp_discuss',"parentid=$ID") : '';

			if (!checkCommentsAllowed($ID)) {
				$out .= graf(gTxt("comments_closed"));
			} else {
				$out .= commentForm( $ID );
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
		global $prefs,$txpcfg,$comments_disallow_images;
		extract($prefs);
		$preview = gps('preview');
		$id = gps('id');
		$Form = fetch_form('comments');
		$out = array();
				
		foreach($darr as $vars) {
			$GLOBALS['thiscomment'] = $vars;
			extract($vars);
			
			if($preview) {		
				include_once $txpcfg['txpath'].'/lib/classTextile.php';
				$time=time();
				$discussid=0;
				$textile = new Textile();
				$im = (!empty($comments_disallow_images)) ? 1 : '';
				$message = trim(nl2br($textile->TextileThis(strip_tags(deEntBrackets(
					$message
				)),1,'',$im,'',(@$comment_nofollow ? 'nofollow' : ''))));
			} 
			
			if($comments_dateformat == "since") { 
				$comment_time = since($time + tz_offset()); 
			} else {
				$comment_time = safe_strftime($comments_dateformat,$time); 
			}
							
			$web = str_replace("http://", "", $web);
	
			if ($email && !$web && !$never_display_email)
				$name = '<a href="'.eE('mailto:'.$email).'"'.(@$comment_nofollow ? ' rel="nofollow"' : '').'>'.$name.'</a>';

			if ($web)
				$name = '<a href="http://'.$web.'" title="'.$web.'"'.(@$comment_nofollow ? ' rel="nofollow"' : '').'>'.$name.'</a>';

			$dlink = permlinkurl_id($parentid).'#c'.$discussid;
		
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
			$temp = parse($temp);

			$out[] = ($comments_are_ol) 
			?	n.t.'<li id="c'.$discussid.'" class="txpCommentMsg">'.$temp.'</li>' 
			:	$temp;
		}
			return join(n,$out);
		}

// -------------------------------------------------------------
	function commentForm($id) 
	{
		global $prefs;
		extract($prefs);
		$namewarn = '';
		$emailwarn = '';
		$commentwarn = '';
		$name= pcs('name');
		$email= pcs('email');
		$web= pcs('web');		
		extract( doStripTags( doDeEnt ( psa( array(
			'remember',
			'forget',
			'parentid',
			'preview',
			'message',
			'submit',
			'backpage'
		) ) ) ) );
			
		if ( $preview ) {
			$name  = ps( 'name' );
			$email = ps( 'email' );
			$web   = ps( 'web' );		
			$nonce = md5( uniqid( rand(), true ) );
			$secret = md5( uniqid( rand(), true ) );
			safe_insert("txp_discuss_nonce", "issue_time=now(), nonce='$nonce', secret='$secret'");

			$namewarn = ($comments_require_name)
			?	(!trim($name)) 
				?	gTxt('comment_name_required').br
				:	''
			:	'';

			$emailwarn = ($comments_require_email)
			?	(!trim($email)) 
				?	gTxt('comment_email_required').br 
				:	''
			:	'';

			$commentwarn = (!trim($message)) ? gTxt('comment_required').br : '';
		}

		$parentid = (!$parentid) ? $id : $parentid;
		
		if ($remember==1) { setCookies($name,$email,$web); }
		if ($forget==1) { destroyCookies(); }

		$out = '<form method="post" action="" id="txpCommentInputForm">';

		$form = fetch_form('comment_form');
					  
	 	$textarea = '<textarea name="message" cols="30" rows="12" tabindex="1" id="txpCommentInputMessage">'.htmlspecialchars($message).'</textarea>';

		$comment_submit_button = ($preview)
		?	fInput('submit','submit',gTxt('submit'),'button')
		:	'';
			
		$checkbox = (!empty($_COOKIE['txp_name']))
		?	checkbox('forget',1,0).gTxt('forget')
		:	checkbox('remember',1,1).gTxt('remember');

		$vals = array(
			'comment_name_input'    => $namewarn.input('text','name',  $name, "25",'',"2"),
			'comment_email_input'   => $emailwarn.input('text','email', $email,"25",'',"3"),
			'comment_web_input'     => input('text','web',   $web,  "25",'',"4"),
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
		$out = fetch_form('popup_comments');
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
			$comments_disallow_images,$prefs;
		include_once $txpcfg['txpath'].'/lib/classTextile.php';
		$im = (!empty($comments_disallow_images)) ? 1 : '';

		$textile = new Textile();

		$ref = serverset('HTTP_REFERRER');

		$in = psa( array(
			'parentid',
			'name',
			'email',
			'web',
			'message',
			'backpage',
			'nonce',
			'remember'
		) );
		
		extract($in);

		if (!checkCommentsAllowed($parentid))
			exit ( graf(gTxt('comments_closed')));

		if ($prefs['comments_require_name']) {
			if (!trim($name)) {
				exit ( graf(gTxt('comment_name_required')).
					graf('<a href="" onClick="history.go(-1)">'.gTxt('back').'</a>') );
			}
		}

		if ($prefs['comments_require_email']) {
			if (!trim($email)) {
				exit ( graf(gTxt('comment_email_required')).
					graf('<a href="" onClick="history.go(-1)">'.gTxt('back').'</a>') );
			}
		}

		if (!trim($message)) {
			exit ( graf(gTxt('comment_required')).
				graf('<a href="" onClick="history.go(-1)">'.gTxt('back').'</a>') );
		}

		$ip = serverset('REMOTE_ADDR');
		$message = trim($message);
		$blacklisted = is_blacklisted($ip);
		
		$name = doSlash(strip_tags(deEntBrackets($name)));
		$web = doSlash(clean_url(strip_tags(deEntBrackets($web))));
		$email = doSlash(clean_url(strip_tags(deEntBrackets($email))));

		$message2db = doSlash(trim(nl2br($textile->TextileThis(strip_tags(deEntBrackets(
			$message
		)),1,'',$im,'',(@$prefs['comment_nofollow'] ? 'nofollow' : '')))));
				
		$isdup = safe_row("message,name", "txp_discuss", 
			"name='$name' and message='$message2db' and ip='$ip'");

		if (checkBan($ip)) {
			if($blacklisted == false) {
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
							if ($prefs['comment_means_site_updated']) {
								safe_update("txp_prefs", "val=now()", "name='lastmod'");
							}
							if ($comments_sendmail) {
								mail_comment($message,$name,$email,$web,$parentid);
							}
							
							$updated = update_comments_count($parentid);

							ob_start();
							header('location: '.$backpage);
						}
					}                                                        // end check nonce
				}                                                            // end check dup
			} else exit(gTxt('your_ip_is_blacklisted_by'.' '.$blacklisted)); // end check blacklist
		} else exit(gTxt('you_have_been_banned'));                           // end check site ban
	}

// -------------------------------------------------------------
	function checkNonce($nonce) 
	{
		if (!$nonce) return false;
			// delete expired nonces
		safe_delete("txp_discuss_nonce", "issue_time < date_sub(now(),interval 10 minute)");
			// check for nonce
		return (safe_row("*", "txp_discuss_nonce", "nonce='$nonce' and used='0'")) ? true : false;
	}

// -------------------------------------------------------------
	function checkBan($ip)
	{
		return (!fetch("ip", "txp_discuss_ipban", "ip", "$ip")) ? true : false;
	}

// -------------------------------------------------------------
	function checkCommentsAllowed($id)
	{
		global $use_comments, $comments_disabled_after;

		if (!$use_comments)
			return false;

		extract(	
			safe_row(
				"Annotate,AnnotateInvite,unix_timestamp(Posted) as uPosted",
					"textpattern", "ID='$id'"
			)
		);

		if (!$Annotate)
			return false;

		if($comments_disabled_after) {		
			$lifespan = ( $comments_disabled_after * 86400 );
			$timesince = ( time() - $uPosted );
			return ( $lifespan > $timesince );
		}

		return true;
	}

// -------------------------------------------------------------
   	function comments_help()
	{
		return ("
		<a href=\"http://www.textpattern.com/help/?item=textile_comments\" id=\"txpCommentHelpLink\" onclick=\"window.open(this.href, 'popupwindow', 'width=300,height=400,scrollbars,resizable'); return false;\" >".gTxt('textile_help')."</a>");
	}

// -------------------------------------------------------------
	function mail_comment($message, $cname, $cemail, $cweb, $parentid) 
	{
		global $sitename;
		extract(safe_row("AuthorID,Title", "textpattern", "ID = '$parentid'"));
		extract(safe_row("RealName, email", "txp_users", "name = '$AuthorID'"));
		$cname = preg_replace('/[\r\n]/', ' ', $cname);
		$cemail = preg_replace('/[\r\n]/', ' ', $cemail);

		$out = gTxt('greeting')." $RealName,\r\n\r\n";
		$out .= sprintf(gTxt('comment_recorded')."\r\n\r\n", $Title);
		$out .= gTxt('comment_name').": $cname\r\n";
		$out .= gTxt('comment_email').": $cemail\r\n";
		$out .= gTxt('comment_web').": $cweb\r\n";
		$out .= gTxt('comment_comment').": $message";

		$subject = sprintf(gTxt('comment_received'), $sitename, $Title);

		$success = txpMail($email, $subject, $out, "$cname <$cemail>");
	}

// -------------------------------------------------------------
	function input($type,$name,$val,$size='',$class='',$tab='',$chkd='') 
	{
		$o = array(
			'<input type="'.$type.'" name="'.$name.'" value="'.$val.'"',
			($size)  ? ' size="'.$size.'"'     : '',
			($class) ? ' class="'.$class.'"'	: '',
			($tab)	 ? ' tabindex="'.$tab.'"'	: '',
			($chkd)  ? ' checked="checked"'	: '',
			' />'.n
		);
		return join('',$o);
	}
?>
