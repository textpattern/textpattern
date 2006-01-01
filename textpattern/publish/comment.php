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
			"txp_discuss", "parentid='".doSlash($id)."' and visible='.VISIBLE.' order by posted asc"
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
	function getNextNonce($check_only = false)
	{
		static $nonce = '';
		if (!$nonce && !$check_only)
			$nonce = md5( uniqid( rand(), true ) );
		return $nonce;
	}
	function getNextSecret($check_only = false)
	{
		static $secret = '';
		if (!$secret && !$check_only)
			$secret = md5( uniqid( rand(), true ) );
		return $secret;
	}

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

		$namewarn = false;
		$emailwarn = false;
		$commentwarn = false;
		$name  = pcs('name');
		$email = clean_url(pcs('email'));
		$web   = clean_url(pcs('web'));
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
			$name  = ps('name');
			$email = clean_url(ps('email'));
			$web   = clean_url(ps('web'));
			$nonce = getNextNonce();
			$secret = getNextSecret();
			safe_insert("txp_discuss_nonce", "issue_time=now(), nonce='$nonce', secret='$secret'");

			$namewarn = ($comments_require_name && !trim($name));
			$emailwarn = ($comments_require_email && !trim($email));
			$commentwarn = (!trim($message));

			$evaluator =& get_comment_evaluator();
			if ($namewarn) $evaluator -> add_estimate(RELOAD,1,gTxt('comment_name_required'));
			if ($emailwarn) $evaluator -> add_estimate(RELOAD,1,gTxt('comment_email_required'));
			if ($commentwarn) $evaluator -> add_estimate(RELOAD,1,gTxt('comment_required'));

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

		$url = $GLOBALS['pretext']['request_uri'];

		// Experimental clean urls with only 404-error-document on apache 
		// possibly requires messy urls for POST requests.
		if (defined('PARTLY_MESSY') and (PARTLY_MESSY)) 
			$url = hu.'?id='.intval($parentid);

		$out = '<form method="post" action="'.$url.'#cpreview" id="txpCommentInputForm">'.
		# Juts to prevent XHTML Strict validation gotchas
		'<div class="comments-wrapper">';

		$Form = fetch('Form','txp_form','name',$form);
		$msgstyle = ($msgstyle ? ' style="'.$msgstyle.'"' : '');
		$msgrows = ($msgrows and is_numeric($msgrows)) ? ' rows="'.intval($msgrows).'"' : '';
		$msgcols = ($msgcols and is_numeric($msgcols)) ? ' cols="'.intval($msgcols).'"' : '';
		$textarea = '<textarea class="txpCommentInputMessage'.(($commentwarn) ? ' comments_error"' : '"')
					.' name="message" id="message" '.$msgcols.$msgrows.$msgstyle.'>'
					.htmlspecialchars($message).'</textarea>';

		$comment_submit_button = ($preview)
		?	fInput('submit','submit',gTxt('submit'),'button')
		:	'';
			
		$checkbox = (!empty($_COOKIE['txp_name']))
		?	checkbox('forget',1,0).tag(gTxt('forget'),'label',' for="forget"')
		:	checkbox('remember',1,1).tag(gTxt('remember'),'label',' for="remember"');

		$vals = array(
			'comment_name_input'    => input('text','name' , htmlspecialchars($name) , $isize,'comment_name_input' .(($namewarn)    ? ' comments_error' : ''),""),
			'comment_email_input'   => input('text','email', htmlspecialchars($email), $isize,'comment_email_input'.(($emailwarn)   ? ' comments_error' : ''),""),
			'comment_web_input'     => input('text','web'  , htmlspecialchars($web)  , $isize,'comment_web_input',""),
			'comment_message_input' => $textarea.'<!-- plugin-place-holder -->',
			'comment_remember'      => $checkbox,
			'comment_preview'       => input('submit','preview',gTxt('preview'),'','button'),
			'comment_submit'        => $comment_submit_button
		);

		foreach ($vals as $a=>$b) {
			$Form = str_replace('<txp:'.$a.' />',$b,$Form);
		}

		$form = parse($Form);

		$out .= $form;
		$out .= fInput('hidden','parentid',$parentid);
		$split = rand(1, 31);
		$out .= ($preview) ? hInput(substr($nonce, 0, $split),substr($nonce, $split)) : '';

		$out .= (!$preview)
		?	fInput('hidden','backpage',serverset("REQUEST_URI"))
		:	fInput('hidden','backpage',$backpage);
		$out = str_replace( '<!-- plugin-place-holder -->', callback_event('comment.form'), $out);
		$out .= '</div></form>'; 
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
	function getComment()
	{
		// comment spam filter plugins: call this function to fetch comment contents
		
		$c = psa( array(
			'parentid',
			'name',
			'email',
			'web',
			'message',
			'backpage',
			'remember'
		) );

		$n = array();
		foreach (stripPost() as $k=>$v)
			if (strlen($k.$v) == 32) $n[] = "'".doSlash($k.$v)."'";
		$c['nonce'] = '';
		$c['secret'] = '';
		if (!empty($n)) {
			$rs = safe_row('nonce, secret', 'txp_discuss_nonce', "nonce in (".join(',', $n).")");
			$c['nonce'] = $rs['nonce'];
			$c['secret'] = $rs['secret'];
		}
		return $c;
	}

// -------------------------------------------------------------
	function saveComment()
	{
		global $siteurl,$comments_moderate,$comments_sendmail,$txpcfg,
			$comments_disallow_images,$prefs;

		$ref = serverset('HTTP_REFERRER');
		$in = getComment();
		$evaluator =& get_comment_evaluator();

		extract($in);

		if (!checkCommentsAllowed($parentid))
			txp_die ( gTxt('comments_closed'), '403');

		$ip = serverset('REMOTE_ADDR');

		if (!checkBan($ip)) 
			txp_die(gTxt('you_have_been_banned'), '403');

		$blacklisted = is_blacklisted($ip);
		if ($blacklisted)
			txp_die(gTxt('your_ip_is_blacklisted_by'.' '.$blacklisted), '403');

		$name = doSlash(strip_tags(deEntBrackets($name)));
		$web = doSlash(clean_url(strip_tags(deEntBrackets($web))));
		$email = doSlash(clean_url(strip_tags(deEntBrackets($email))));

		$message = trim($message);
		$message2db = doSlash(markup_comment($message));

		$isdup = safe_row("message,name", "txp_discuss", 
			"name='$name' and message='$message2db' and ip='$ip'");

		if (   ($prefs['comments_require_name'] && !trim($name))
			|| ($prefs['comments_require_email'] && !trim($email))
			|| (!trim($message)))
		{ 
			$evaluator -> add_estimate(RELOAD,1); // The error-messages are added in the preview-code
		}

		if ($isdup) 
			$evaluator -> add_estimate(RELOAD,1); // FIXME? Tell the user about dupe?

		if ( ($evaluator->get_result() != RELOAD) && checkNonce($nonce) ) {
			callback_event('comment.save');
			$visible = $evaluator->get_result();
			if ($visible != RELOAD) {
				$rs = safe_insert(
					"txp_discuss",
					"parentid  = '".doSlash($parentid)."',
					 name		  = '$name',
					 email	  = '$email',
					 web		  = '$web',
					 ip		  = '$ip',
					 message   = '$message2db',
					 visible   = $visible,
					 posted	  = now()"
				);
				if ($rs) {
					safe_update("txp_discuss_nonce", "used='1'", "nonce='".doslash($nonce)."'");
					if ($prefs['comment_means_site_updated']) {
						safe_update("txp_prefs", "val=now()", "name='lastmod'");
					}
					if ($comments_sendmail) {
						mail_comment($message,$name,$email,$web,$parentid, $rs);
					}

					$updated = update_comments_count($parentid);

					$backpage = substr($backpage, 0, $prefs['max_url_len']);
					$backpage = preg_replace("/[\x0a\x0d#].*$/s",'',$backpage);
					$backpage .= ((strstr($backpage,'?')) ? '&' : '?') . 'commented='.(($visible==VISIBLE) ? '1' : '0');
					txp_status_header('302 Found');
					if($comments_moderate){
						header('Location: '.$backpage.'#txpCommentInputForm');
					}else{
						header('Location: '.$backpage.'#c'.sprintf("%06s",$rs));
					}
					if($prefs['logging'] == 'refer') { 
						logit('refer'); 
					} elseif ($prefs['logging'] == 'all') {
						logit();
					}
					$evaluator->write_trace();
					exit;
				}
			}
		}
		// Force another Preview
		$_POST['preview'] = RELOAD;
		//$evaluator->write_trace();
	}

// -------------------------------------------------------------
	class comment_evaluation {
		var $status;
		var $message;
		var $txpspamtrace = array();

		function comment_evaluation() {
			global $prefs;
			extract(getComment());
			$this->status = array( SPAM  => array(),
								   MODERATE => array(),
								   VISIBLE  => array(),
								   RELOAD  => array()
								);
			$this->message = $this->status;
			$this -> txpspamtrace[] = "Comment on $parentid by $name (".safe_strftime($prefs['archive_dateformat'],time()).")";
			if ($prefs['comments_moderate'])
				$this->status[MODERATE][]=0.5;
			else
				$this->status[VISIBLE][]=0.5;
		}

		function add_estimate($type = SPAM, $probability = 0.75, $msg='') {
			global $production_status;

			if (!array_key_exists($type, $this->status))
				trigger_error(gTxt('unknown_spam_estimate'), E_USER_WARNING);

			$this -> txpspamtrace[] = "   $type; ".max(0,min(1,$probability))."; $msg";
			//FIXME trace is only viewable for RELOADS. Maybe add info to HTTP-Headers in debug-mode

			$this->status[$type][] = max(0,min(1,$probability));
			if (trim($msg)) $this->message[$type][] = $msg;
		}

		function get_result() {
			$result = array();
			foreach ($this->status as $key => $value)
				$result[$key] = array_sum($value)/max(1,count($value));
			arsort($result, SORT_NUMERIC);
			reset($result);
			return key($result);
		}
		function get_result_message() {
			return $this->message[$this->get_result()];
		}
		function write_trace() {
			global $prefs;
			$file = $prefs['tempdir'].DS.'evaluator_trace.php';
			if (!file_exists($file)) {
				$fp = fopen($file,'wb');
				if ($fp) 
					fwrite($fp,"<?php exit; ?>\n".
					"This trace-file tracks saved comments. (created ".safe_strftime($prefs['archive_dateformat'],time()).")\n".
					"Format is: Type; Probability; Message (Type can be -1 => spam, 0 => moderate, 1 => visible)\n\n");
			} else {
				$fp = fopen($file,'ab');
			}
			if ($fp) {
				fwrite($fp, implode("\n", $this->txpspamtrace ));
				fwrite($fp, "\n  RESULT: ".$this->get_result()."\n\n");
				fclose($fp);
			}
		}
	}

	function &get_comment_evaluator() {
	    static $instance; 
	 
	    // If the instance is not there, create one
	    if(!isset($instance)) { 
	        $instance = new comment_evaluation(); 
	    } 
	    return $instance; 
	}

// -------------------------------------------------------------
	function checkNonce($nonce) 
	{
		if (!$nonce && !preg_match('#^[a-zA-Z0-9]*$#',$nonce)) 
			return false;
			// delete expired nonces
		safe_delete("txp_discuss_nonce", "issue_time < date_sub(now(),interval 10 minute)");
			// check for nonce
		return (safe_row("*", "txp_discuss_nonce", "nonce='".doslash($nonce)."' and used='0'")) ? true : false;
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

		$id = intval($id);

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
	function mail_comment($message, $cname, $cemail, $cweb, $parentid, $discussid) 
	{
		global $sitename;
		$article = safe_row("Section, Posted, ID, url_title, AuthorID, Title", "textpattern", "ID = '$parentid'");
		extract($article);
		extract(safe_row("RealName, email", "txp_users", "name = '".doSlash($AuthorID)."'"));

		$out = gTxt('greeting')." $RealName,\r\n\r\n";
		$out .= str_replace('{title}',$Title,gTxt('comment_recorded'))."\r\n";
		$out .= permlinkurl_id($parentid)."\r\n";
		if (has_privs('discuss', $AuthorID))
			$out .= hu.'textpattern/?event=discuss&step=discuss_edit&discussid='.$discussid."\r\n";
		$out .= "\r\n";
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
			'<input type="'.$type.'" name="'.$name.'" id="'.$name.'" value="'.$val.'"',
			($size)	? ' size="'.$size.'"'	  : '',
			($class) ? ' class="'.$class.'"'	: '',
			($tab)	 ? ' tabindex="'.$tab.'"'	: '',
			($chkd)	? ' checked="checked"'	: '',
			' />'.n
		);
		return join('',$o);
	}
?>
