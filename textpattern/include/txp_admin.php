<?php

/*
	This is Textpattern

	Copyright 2005 by Dean Allen
	www.textpattern.com
	All rights reserved

	Use of this software indicates acceptance of the Textpattern license agreement 

$HeadURL$
$LastChangedRevision$

*/

if (!defined('txpinterface')) die('txpinterface is undefined.');

$levels = array(
	1 => gTxt('publisher'),
	2 => gTxt('managing_editor'),
	3 => gTxt('copy_editor'),
	4 => gTxt('staff_writer'),
	5 => gTxt('freelancer'),
	6 => gTxt('designer'),
	0 => gTxt('none')
);

if ($event == 'admin') {
	require_privs('admin');

	if(!$step or !in_array($step, array('admin','author_change_pass','author_delete','author_list','author_save','author_save_new','change_email','change_pass'))){
		admin();
	} else $step();
}

// -------------------------------------------------------------
	function admin($message='') 
	{
		global $txp_user;
		pagetop(gTxt('site_administration'),$message);
		$themail = fetch('email','txp_users','name',$txp_user);

		echo new_pass_form();
		echo change_email_form($themail);
		if (has_privs('admin.list'))
			echo author_list();
		if (has_privs('admin.edit'))
			echo new_author_form();
		if (has_privs('admin.edit'))
			echo reset_author_pass_form();
		
	}

// -------------------------------------------------------------
	function change_email() 
	{
		global $txp_user;
		require_privs('admin.edit');

		$new_email = gps('new_email');
		$rs = safe_update("txp_users", 
			"email  = '$new_email'", 
			"name = '$txp_user'"
		);	
		if ($rs) {
			admin('email address changed to '.$new_email);
		}
	}
	
// -------------------------------------------------------------
	function author_save() 
	{
		require_privs('admin.edit');

		extract(doSlash(psa(array('privs','user_id','RealName','email'))));
		$rs = safe_update("txp_users", 
			"privs = $privs, 
			RealName = '$RealName',
			email = '$email'",
			"user_id='$user_id'");
		if ($rs) admin(messenger('author',$RealName,'updated'));
	}

// -------------------------------------------------------------
	function change_pass() 
	{
		global $txp_user;
		$message = '';
		$themail = fetch('email','txp_users','name',$txp_user);
		if (!empty($_POST["new_pass"])) {
			$NewPass = $_POST["new_pass"];
			$rs = safe_update(
				"txp_users", 
				"pass = password(lower('$NewPass'))",
				"name='$txp_user'"
			);
			if ($rs) {
				$message .= gTxt('password_changed');
				if (!empty($_POST['mailpassword'])) {
					send_new_password($NewPass,$themail,$txp_user);
					$message .= sp.gTxt('and_mailed_to').sp.$themail;
				}
				$message .= ".";
			} else echo comment(mysql_error());
			admin($message);
		}
	}

// -------------------------------------------------------------
	function author_save_new() 
	{
		require_privs('admin.edit');

		extract(doSlash(psa(array('privs','name','email','RealName'))));
		$pw = generate_password(6);
		$nonce = md5( uniqid( rand(), true ) );

		if ($name) {
			$rs = safe_insert(
				"txp_users", 
				"privs    = '$privs',
				 name     = '$name',
				 email    = '$email',
				 RealName = '$RealName',
				 pass     =  password(lower('$pw')),
				 nonce    = '$nonce'"
			);
		}
		
		if ($name && $rs) {
			send_password($pw,$email);
			admin(gTxt('password_sent_to').sp.$email);
		} else {
			admin(gTxt('error_adding_new_author'));
		}
	}

// -------------------------------------------------------------
	function privs($priv='') 
	{
		global $levels;
		return selectInput("privs", $levels, $priv);
	}

// -------------------------------------------------------------
	function get_priv_level($priv) 
	{
		global $levels;
		return $levels[$priv];
	}

// -------------------------------------------------------------
	function send_password($pw,$email) {
		global $sitename,$txp_user;

		require_privs('admin.edit');

		$myName = $txp_user;
		extract(safe_row("RealName as myName, email as myEmail", 
			"txp_users", "name = '$myName'"));

		$message = gTxt('greeting').' '.$_POST['RealName'].','."\r\n"."\r\n".
	
		gTxt('you_have_been_registered').' '.$sitename."\r\n".
	
		gTxt('your_login_is').': '.$_POST['name']."\r\n".
		gTxt('your_password_is').': '.$pw."\r\n"."\r\n".
	
		gTxt('log_in_at').' '.hu.'textpattern/index.php';

		return txpMail($email, "[$sitename] ".gTxt('your_login_info'), $message);
	}

// -------------------------------------------------------------
	function send_new_password($NewPass,$themail,$name) 
	{
		global $txp_user,$sitename;

		require_privs('admin.edit');

		$message = gTxt('greeting').' '.$name.','."\r\n".
		gTxt('your_password_is').': '.$NewPass."\r\n"."\r\n".

		gTxt('log_in_at').' '.hu.'textpattern/index.php';

		return txpMail($themail, "[$sitename] ".gTxt('your_new_password'), $message);
	}

// -------------------------------------------------------------
	function generate_password($length=10)
	{
		$pass = "";
		$chars = "023456789bcdfghjkmnpqrstvwxyz"; 
		$i = 0; 
		while ($i < $length) {
			$char = substr($chars, mt_rand(0, strlen($chars)-1), 1);
			if (!strstr($pass, $char)) {
				$pass .= $char;
				$i++;
			}
		}
		return $pass;
	}

// -------------------------------------------------------------
	function new_pass_form() 
	{
		return '<div align="center" style="margin-top:3em">'.
		form(
			tag(gTxt('change_password'),'h3').
			graf(gTxt('new_password').' '.
				fInput('password','new_pass','','edit','','','20','1').
				checkbox('mailpassword','1',1).gTxt('mail_it').' '.
				fInput('submit','change_pass',gTxt('submit'),'smallerbox').
				eInput('admin').sInput('change_pass')
			,' style="text-align:center"')
		).'</div>';
	}

// -------------------------------------------------------------
	function reset_author_pass_form() 
	{
		global $txp_user;

		$them = safe_rows_start("*","txp_users","name != '".doSlash($txp_user)."'");
		
		while ($a = nextRow($them)) {
			$names[$a['name']] = $a['RealName'].' ('.$a['name'].')';
		}
		if (!empty($names)) {
			return '<div align="center" style="margin-top:3em">'.
			form(
				tag(gTxt('reset_author_password'),'h3').
				graf(gTxt('a_new_password_will_be_mailed')).
					graf(selectInput("name", $names, '',1).
					fInput('submit','author_change_pass',gTxt('submit'),'smallerbox').
					eInput('admin').sInput('author_change_pass')
				,' style="text-align:center"')
			).'</div>';
		}
	}

// -------------------------------------------------------------
	function author_change_pass() 
	{
		require_privs('admin.edit');

		$name = ps('name');
		$themail = safe_field("email","txp_users","name='".doSlash($name)."'");
		$NewPass = generate_password(6);
		
		$rs = safe_update("txp_users","pass=password(lower('$NewPass'))",
			"name='".doSlash($name)."'");
		
		if ($rs) { 
			if (send_new_password($NewPass,$themail,$name)) {
				admin(gTxt('password_sent_to').' '.$themail);
			} else admin(gTxt('could_not_mail').' '.$themail);
		} else admin(gTxt('could_not_update_author').' '.$name);
		
	}
	
// -------------------------------------------------------------
	function change_email_form($themail) 
	{
		return '<div align="center" style="margin-top:3em">'.
		form(
			tag(gTxt('change_email_address'),'h3').
			graf(gTxt('new_email').' '.
				fInput('text','new_email',$themail,'edit','','','20','2').
				fInput('submit','change_email',gTxt('submit'),'smallerbox').
				eInput('admin').sInput('change_email')
			,' style="text-align:center"')
		).'</div>';
	}

// -------------------------------------------------------------
	function author_list() 
	{
		global $txp_user;
		$out[] = hed(gTxt('authors'),3,' align="center"');
		$out[] = startTable('list');
		$out[] = tr(
			hCell(gTxt('real_name'))
		.	hCell(gTxt('login_name'))
		.	hCell(gTxt('email'))
		.	hCell(gTxt('privileges'))
		.	td()
		.	td()
		);
		$rs = safe_rows_start("*", "txp_users", "1");
		if ($rs) {
			while ($a = nextRow($rs)) {
				extract($a);
				if ($name == $txp_user)
					$deletelink = '';
				else
					$deletelink = dLink('admin','author_delete','user_id',$user_id);
				$savelink = fInput("submit",'save',gTxt('save'),'smallerbox');
				$emailhref = '<a href="mailto:'.$email.'">'.$email.'</a>';
				$RealNameInput = fInput('text','RealName',$RealName,'edit');
				$emailInput = fInput('text','email',$email,'edit');
				
				$row[] = '<form action="index.php" method="post">';
				
				$row[] = (has_privs('admin.edit')) 
					?	td($RealNameInput)
					:	td($RealName);
					
				$row[] = td($name);

				$row[] = (has_privs('admin.edit')) 
					?	td($emailInput)
					:	td($emailhref);
				
				$row[] = (has_privs('admin.edit')) 
					?	td(privs($privs).popHelp("about_privileges"))
					:	td(get_priv_level($privs).popHelp("about_privileges"));

				$row[] = (has_privs('admin.edit')) ? td($savelink) : '';
				
				$row[] = (has_privs('admin.edit'))
					?	hInput("user_id",$user_id). eInput("admin").sInput('author_save')
					:	td();

				$row[] = '</form>';


				$row[] = (has_privs('admin.edit'))
					?	td($deletelink,10)
					:	td();

				$out[] = 
					tr(join('',$row));
				unset($row);
			}
		
			$out[] = endTable();
			return join('',$out);
		}
	}

// -------------------------------------------------------------
	function author_delete() 
	{
		require_privs('admin.edit');

		$user_id = ps('user_id');
		$name = fetch('Realname','txp_users','user_id',$user_id);
		if ($name) {
			$rs = safe_delete("txp_users","user_id = '$user_id'");
			if ($rs) admin(messenger('author',$name,'deleted'));
		}
	}

// -------------------------------------------------------------
	function new_author_form() 
	{
		$out = array(
			hed(gTxt('add_new_author' ),3,' align="center" style="margin-top:2em"'),
			graf(gTxt('a_message_will_be_sent_with_login'), ' align="center"'),
			startTable('edit'),
			tr( fLabelCell( 'real_name' ) . fInputCell('RealName') ),
			tr( fLabelCell( 'login_name' ) . fInputCell('name') ),
			tr( fLabelCell( 'email' ) . fInputCell('email') ),
			tr( fLabelCell( 'privileges' ) . td(privs().popHelp('about_privileges')) ),
			tr( td() . td( fInput( 'submit','',gTxt('save'),'publish').
				popHelp('add_new_author')) ),
			endTable(),
			eInput('admin').sInput('author_save_new'));

		return form(join('',$out));
	}
?>
