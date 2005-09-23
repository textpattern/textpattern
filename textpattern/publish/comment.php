<?php

/*
	This is Textpattern
	Copyright 2005 by Dean Allen - all rights reserved.

	Use of this software denotes acceptance of the Textpattern license agreement 

$HeadURL$
$LastChangedRevision$

*/

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
	function discuss($id)
	{
		$rs = safe_row('*, unix_timestamp(Posted) as uPosted', 'textpattern', "ID='".doSlash($id)."' and Status >= 4");
		if ($rs) {
			populateArticleData($rs);
			if (ps('preview')) 
				$GLOBALS['comment_preview'] = 1;

			$result = parse(fetch_form('comments_display'));
			unset($GLOBALS['comment_preview']);
			return $result;
		}

		return '';
	}


// -------------------------------------------------------------
	function commentForm($id, $atts=NULL) 
	{
		global $prefs;
		extract($prefs);

		extract(lAtts(array(
			'isize'	  => '25',
			'msgrows'   => '5',
			'msgcols'   => '25',
			'msgstyle'  => '',
			'form'   => 'comment_form',
		),$atts));

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
			$web	 = ps( 'web' );		
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
		// If the form fields are filled (anything other than blank), pages
		// really should not be saved by a public cache. rfc2616/14.9.1
		if ($name || $email || $web) {
			header('Cache-Control: private');
		}

		$parentid = (!$parentid) ? $id : $parentid;

		if (pcs('name') || pcs('email') || pcs('web'))
		{
			// Form-input different from Cookie, let's update the Cookie.
			if ( cs('name') != ps('name') 
				OR cs('email')!= ps('email') 
				OR cs('web') != ps('web'));
				$remember = 1;
		}
		
		if ($remember==1) { setCookies($name,$email,$web); }
		if ($forget==1) { destroyCookies(); }

		$out = '<form method="post" action="#cpreview" id="txpCommentInputForm">';

		$Form = fetch('Form','txp_form','name',$form);
		$msgstyle = ($msgstyle ? ' style="'.$msgstyle.'"' : '');
		$msgrows = ($msgrows and is_numeric($msgrows)) ? ' rows="'.intval($msgrows).'"' : '';
		$msgcols = ($msgcols and is_numeric($msgcols)) ? ' cols="'.intval($msgcols).'"' : '';
		$textarea = '<textarea class="txpCommentInputMessage" name="message"'.$msgcols.$msgrows.$msgstyle.' tabindex="1">'.htmlspecialchars($message).'</textarea>';

		$comment_submit_button = ($preview)
		?	fInput('submit','submit',gTxt('submit'),'button')
		:	'';
			
		$checkbox = (!empty($_COOKIE['txp_name']))
		?	checkbox('forget',1,0).gTxt('forget')
		:	checkbox('remember',1,1).gTxt('remember');

		$vals = array(
			'comment_name_input'    => $namewarn.input('text','name',  $name, $isize,'comment_name_input',"2"),
			'comment_email_input'   => $emailwarn.input('text','email', $email,$isize,'comment_email_input',"3"),
			'comment_web_input'     => input('text','web',   $web, $isize,'comment_web_input',"4"),
			'comment_message_input' => $commentwarn.$textarea,
			'comment_remember'      => $checkbox,
			'comment_preview'       => input('submit','preview',gTxt('preview'),'','button'),
			'comment_submit'        => $comment_submit_button
		);

		foreach ($vals as $a=>$b) {
			$Form = str_replace('<txp:'.$a.' />',$b,$Form);
		}

		$form = parse($Form);

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
		global $sitename,$s,$thisarticle;
		$preview = gps('preview');
		$h3 = ($preview) ? hed(gTxt('message_preview'),3) : '';
		$discuss = discuss($id);
		ob_start('parse');
		$out = fetch_form('popup_comments');
		$out = str_replace("<txp:popup_comments />",$discuss,$out);
		
		return $out;

	}

// -------------------------------------------------------------
	function setCookies($name,$email,$web)
	{
		$cookietime = time() + (365*24*3600);
		ob_start();
		setcookie("txp_name",  $name,  $cookietime, "/");
		setcookie("txp_email", $email, $cookietime, "/");
		setcookie("txp_web",   $web,	 $cookietime, "/");
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
			txp_die ( gTxt('comments_closed'), '403');

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

		$message2db = doSlash(markup_comment($message));

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
							 name		  = '$name',
							 email	  = '$email',
							 web		  = '$web',
							 ip		  = '$ip',
							 message   = '$message2db',
							 visible   = $visible,
							 posted	  = now()"
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
							$backpage = substr($backpage, 0, $prefs['max_url_len']);
							$backpage = preg_replace("/[\x0a\x0d#].*$/s",'',$backpage);
							$backpage .= ((strstr($backpage,'?')) ? '&' : '?') . 'commented=1';
							txp_status_header('302 Found');
							if($comments_moderate){
								header('Location: '.$backpage.'#txpCommentInputForm');
							}else{
								header('Location: '.$backpage.'#c'.sprintf("%06s",$rs));
							}
						}
					}																			// end check nonce
				}																				 // end check dup
			} else txp_die(gTxt('your_ip_is_blacklisted_by'.' '.$blacklisted), '403'); // end check blacklist
		} else txp_die(gTxt('you_have_been_banned'), '403');									// end check site ban
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
		global $use_comments, $comments_disabled_after, $thisarticle;

		if (!$use_comments || !$id)
			return false;

		if (isset($thisarticle['thisid']) && ($thisarticle['thisid'] == $id) && isset($thisarticle['annotate']))
		{
			$Annotate = $thisarticle['annotate'];
			$uPosted  = $thisarticle['posted'];
		} 
		else
		{
			extract(	
				safe_row(
					"Annotate,unix_timestamp(Posted) as uPosted",
						"textpattern", "ID='$id'"
				)
			);
		}

		if ($Annotate != 1)
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
		$article = safe_row("Section, Posted, ID, url_title, AuthorID, Title", "textpattern", "ID = '$parentid'");
		extract($article);
		extract(safe_row("RealName, email", "txp_users", "name = '".doSlash($AuthorID)."'"));

		$out = gTxt('greeting')." $RealName,\r\n\r\n";
		$out .= str_replace('{title}',$Title,gTxt('comment_recorded'))."\r\n";
		$out .= permlinkurl_id($parentid)."\r\n\r\n";
		$out .= gTxt('comment_name').": $cname\r\n";
		$out .= gTxt('comment_email').": $cemail\r\n";
		$out .= gTxt('comment_web').": $cweb\r\n";
		$out .= gTxt('comment_comment').": $message";

		$subject = strtr(gTxt('comment_received'),array('{site}' => $sitename, '{title}' => $Title));

		$success = txpMail($email, $subject, $out, $cemail);
	}

// -------------------------------------------------------------
	function input($type,$name,$val,$size='',$class='',$tab='',$chkd='') 
	{
		$o = array(
			'<input type="'.$type.'" name="'.$name.'" value="'.$val.'"',
			($size)	? ' size="'.$size.'"'	  : '',
			($class) ? ' class="'.$class.'"'	: '',
			($tab)	 ? ' tabindex="'.$tab.'"'	: '',
			($chkd)	? ' checked="checked"'	: '',
			' />'.n
		);
		return join('',$o);
	}
?>
