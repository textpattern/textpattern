<?php

/*
	This is Textpattern

	Copyright 2004 by Dean Allen
	www.textpattern.com
	All rights reserved

	Use of this software indicates acceptance of the Textpattern license agreement 
*/

    check_privs(1,2);

    switch(strtolower($step)) {
      case "list": list_plugins(); break;
      case "edit": edit_plugin(); break;
      case "switch_status": switch_status(); break;
      case "plugin_save": plugin_save(); break;
		case "plugin_verify": plugin_verify(); break;
      case "plugin_install": plugin_install(); break;
      case "view_hilighted": view_hilighted(); break;
      case "view_help": view_help(); break;
      case "delete": plugin_delete();break;
      default: list_plugins();
    }
    
// -------------------------------------------------------------
	function list_plugins($message='')
	{	
		pagetop(gTxt('edit_plugins'),$message);

		echo 

		startTable('list').
		tr(tda(plugin_form().

		// 1.0rc - deprecate this soon
		plugin_form_old()
		
		,' colspan="5" style="border:0;height:50px"')).
		 assHead('plugin','author','version','description','active','');
			
		$rs = safe_rows("*","txp_plugin", "1 order by name");
	
		foreach ($rs as $a) {
		  extract($a);

			// nice to have eval() do a syntax check on the plugin here

		  echo 
		  tr(
			td(eLink('plugin','edit','name',$name,$name),150).
			td('<a href="'.$author_uri.'">'.$author.'</a>',100).
			td($version,10).
			td($description,260).
			td(status_link($status,$name,yes_no($status)),30).
			td(dLink('plugin','delete','name',$name),30)
		  );
		  unset($name,$page,$deletelink);
		}    
		echo endTable();	
	}
	
// -------------------------------------------------------------
	function switch_status()
	{
		extract(gpsa(array('name','status')));
		$change = ($status) ? 0 : 1;
		safe_update("txp_plugin", "status=$change", "name='$name'");
		list_plugins(messenger('plugin',$name,'updated'));
	}

// -------------------------------------------------------------
  function edit_plugin()
  {
		$name = gps('name');
		pagetop(gTxt('edit_plugins'));
		echo plugin_edit_form($name);
//		echo graf('<a href="?event=plugin'.
//				a.'step=view_hilighted'.
//				a.'name='.urlencode($name).
//				'">View highlighted</a>');
  }

  
// -------------------------------------------------------------
	function plugin_edit_form($name='') 
	{
		$sub = fInput('submit','',gTxt('save'),'publish');
		$code = ($name) ? fetch('code','txp_plugin','name',$name) : '';
		$help = ($name) ? fetch('help','txp_plugin','name',$name) : '';
		$thing = ($code)
		?	$code
		:	'';
		$textarea = '<textarea name="code" rows="30" cols="90">'.htmlentities($thing).'</textarea>';

		return 
		form(startTable('edit')
		.	tr(td($textarea))
		.	tr(td($sub))
		.	tr(td($help))
		.	endTable().sInput('plugin_save').eInput('plugin').hInput('name',$name));
	}

// -------------------------------------------------------------  
	function plugin_save()
	{
		extract(doSlash(gpsa(array('name','code'))));
		safe_update("txp_plugin","code='$code'", "name='$name'");
		list_plugins(messenger('plugin',$name,'saved'));
	}
  
// -------------------------------------------------------------
	function plugin_delete()
	{
		$name = doSlash(ps('name'));
		safe_delete("txp_plugin","name='$name'");
		list_plugins(messenger('plugin',$name,'deleted'));
	}

// -------------------------------------------------------------
	function status_link($status,$name,$linktext)
	{
		$out = '<a href="index.php?';
		$out .= 'event=plugin&#38;step=switch_status&#38;status='.
			$status.'&#38;name='.urlencode($name).'"';
		$out .= '>'.$linktext.'</a>';
		return $out;
	}

// -------------------------------------------------------------
	function plugin_verify() 
	{	

		if (ps('txt_plugin')) {
			$plugin = join("\n", file($_FILES['theplugin']['tmp_name']));
		} else {
			$plugin = ps('plugin');	
		}

		$plugin64 = preg_replace('@.*\$plugin=\'([\w=+/]+)\'.*@s', '$1', $plugin);

		if(isset($plugin64)) {

			if ($plugin = unserialize(base64_decode($plugin64))) { 

				if(is_array($plugin)){
					extract(doSlash($plugin));
					$source = highlight_string($plugin['code'], true);
					$sub = fInput('submit','',gTxt('save'),'publish');
		
					pagetop(gTxt('edit_plugins'));
					echo 
					form(startTable('edit')
					.	tr(td($source))
					.	tr(td($sub))
					.	endTable().sInput('plugin_install').eInput('plugin').hInput('plugin64', $plugin64));
					return;
				}
			}
		}
		list_plugins(gTxt('bad_plugin_code'));

	}
	
// -------------------------------------------------------------
	function plugin_install() 
	{	

		$plugin = ps('plugin64');	

		if(isset($plugin)) {

			if ($plugin = unserialize(base64_decode($plugin))) { 

				if(is_array($plugin)){
	
					extract(doSlash($plugin));
	
					$exists = fetch('name','txp_plugin','name',$name);
	
					if ($exists) {
						$rs = safe_update(
						   "txp_plugin",
							"status      = 0,
							author       = '$author',
							author_uri   = '$author_uri',
							version      = '$version',
							description  = '$description',
							help         = '$help',
							code         = '$code',
							code_restore = '$code',
							code_md5     = '$md5'",
							"name        = '$name'"
						);
	
					} else {
					
						$rs = safe_insert(
						   "txp_plugin",
						   "name         = '$name',
							status       = 0,
							author       = '$author',
							author_uri   = '$author_uri',
							version      = '$version',
							description  = '$description',
							help         = '$help',
							code         = '$code',
							code_restore = '$code',
							code_md5     = '$md5'"
						);
					}
					if ($rs and $code) {
						list_plugins(messenger('plugin',$name,'installed'));
					} else list_plugins('plugin install failed');
				}
			} else list_plugins(gTxt('bad_plugin_code'));
		}
	}
	
// -------------------------------------------------------------
	function plugin_form_old() // deprecated after 1.0 rc
	{
		return 
		'<form enctype="multipart/form-data" action="index.php" method="post">'.
		hInput('MAX_FILE_SIZE','100000').
		gTxt('old_plugin').': '.
		fInput('file','theplugin','','edit').
		popHelp('install_plugin').sp.
		fInput('submit','install_old','install','smallerbox').
		eInput('plugin').sInput('plugin_verify').hInput('txt_plugin',true).
		'</form>';
	}

// -------------------------------------------------------------
	function plugin_form() 
	{
		return 
		'<form action="index.php" method="post">'.
		gTxt('install_plugin').': '.
		text_area('plugin',30,400,'').
		popHelp('install_plugin').sp.
		fInput('submit','install_new','install','smallerbox').
		eInput('plugin').sInput('plugin_verify').
		'</form>';
	}


?>
