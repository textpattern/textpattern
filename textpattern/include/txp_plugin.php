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

	if ($event == 'plugin') {
		require_privs('plugin');		

		if(!$step or !in_array($step, array('plugin_delete','plugin_edit','plugin_help','plugin_list','plugin_install','plugin_save','plugin_verify','switch_status'))){
			plugin_list();
		} else $step();
	}
	
// -------------------------------------------------------------
	function plugin_list($message='')
	{	
		pagetop(gTxt('edit_plugins'),$message);

		echo 

		startTable('list').
		tr(tda(plugin_form()
		
		,' colspan="8" style="border:0;height:50px"')).
		 assHead('plugin','author','version','description','active','','','');
			
		$rs = safe_rows_start("*","txp_plugin", "1=1 order by name");
		
		while ($a = nextRow($rs)) {
		  extract($a);

		$elink = eLink('plugin','plugin_edit','name',$name,gTxt('edit'));
		$hlink = '<a href="?event=plugin&#38;step=plugin_help&#38;name='.$name.'">'.
					gTxt('help').'</a>';
		  echo 
		  tr(
			td($name).
			td('<a href="'.$author_uri.'">'.$author.'</a>').
			td($version,10).
			td($description,260).
			td(status_link($status,$name,yes_no($status)),30).
			td($elink).
			td($hlink).
			td(dLink('plugin','plugin_delete','name',$name),30)
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
		plugin_list(messenger('plugin',$name,'updated'));
	}

// -------------------------------------------------------------
  function plugin_edit()
  {
		$name = gps('name');
		pagetop(gTxt('edit_plugins'));
		echo plugin_edit_form($name);
  }

  
// -------------------------------------------------------------
	function plugin_help() 
	{
		$name = gps('name');
		pagetop(gTxt('plugin_help'));
		$help = ($name) ? safe_field('help','txp_plugin',"name = '$name'") : '';
		echo 
		startTable('edit')
		.	tr(tda($help,' width="600"'))
		.	endTable();
		
	}

// -------------------------------------------------------------
	function plugin_edit_form($name='') 
	{
		$sub = fInput('submit','',gTxt('save'),'publish');
		$code = ($name) ? fetch('code','txp_plugin','name',$name) : '';
		$thing = ($code)
		?	$code
		:	'';
		$textarea = '<textarea name="code" rows="30" cols="90">'.htmlentities(utf8_decode($thing)).'</textarea>';

		return 
		form(startTable('edit')
		.	tr(td($textarea))
		.	tr(td($sub))
#		.	tr(td($help))
		.	endTable().sInput('plugin_save').eInput('plugin').hInput('name',$name));
	}

// -------------------------------------------------------------  
	function plugin_save()
	{
		extract(doSlash(gpsa(array('name','code'))));
		safe_update("txp_plugin","code='$code'", "name='$name'");
		plugin_list(messenger('plugin',$name,'saved'));
	}
  
// -------------------------------------------------------------
	function plugin_delete()
	{
		$name = doSlash(ps('name'));
		safe_delete("txp_plugin","name='$name'");
		plugin_list(messenger('plugin',$name,'deleted'));
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
		$plugin64 = preg_replace('/^#.*$/m', '', $plugin64);

		if(isset($plugin64)) {
			$plugin64 = base64_decode($plugin64);
			if (strncmp($plugin64,"\x1F\x8B",2)===0)
				$plugin64 = gzinflate(substr($plugin64, 10));

			if ($plugin = unserialize($plugin64)) { 

				if(is_array($plugin)){
					extract(doSlash($plugin));
					$source = '';
					if(version_compare(PHP_VERSION, "4.2.0", "<") === 1)
					{
						ob_start();
						highlight_string('<?php'.$plugin['code'].'?>');
						$source = ob_get_contents();
						ob_end_clean();
					}
					else
					{
						$source.= highlight_string('<?php'.$plugin['code'].'?>', true);
					}
					$sub = fInput('submit','',gTxt('install'),'publish');
		
					pagetop(gTxt('edit_plugins'));
					echo 
					form(startTable('edit')
					.	tr(td(hed(gTxt('previewing_plugin'),3)))
					.	tr(td($source))
					.	tr(td($sub))
					.	endTable().sInput('plugin_install').eInput('plugin').hInput('plugin64', base64_encode($plugin64)));
					return;
				}
			}
		}
		plugin_list(gTxt('bad_plugin_code'));

	}
	
// -------------------------------------------------------------
	function plugin_install() 
	{	

		$plugin = ps('plugin64');	

		if(isset($plugin)) {

			if ($plugin = unserialize(base64_decode($plugin))) { 

				if(is_array($plugin)){
	
					extract(doSlash($plugin));
					if (empty($type)) $type = 0;
	
					$exists = fetch('name','txp_plugin','name',$name);
	
					if ($exists) {
						$rs = safe_update(
						   "txp_plugin",
							"status      = 0,
							type         = '$type',
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
							type         = '$type',
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
						plugin_list(messenger('plugin',$name,'installed'));
					} else plugin_list('plugin install failed');
				}
			} else plugin_list(gTxt('bad_plugin_code'));
		}
	}

// -------------------------------------------------------------
	function plugin_form() 
	{
		return 
		'<form action="index.php" method="post">'.
		tag(gTxt('install_plugin').': ', 'span', ' style="vertical-align:top;"').
		text_area('plugin',30,400,'').
		tag(
			popHelp('install_plugin').sp.
			fInput('submit','install_new',gTxt('upload'),'smallerbox')
		    , 'span', ' style="vertical-align:100%;"').
		eInput('plugin').sInput('plugin_verify').
		'</form>';
	}
?>