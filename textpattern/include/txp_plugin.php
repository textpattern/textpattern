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

	function plugin_list($message = '')
	{	
		pagetop(gTxt('edit_plugins'), $message);

		echo n.n.startTable('list').
			tr(
				tda(
					plugin_form()
				,' colspan="8" style="height: 30px; border: none;"')
			);

		$rs = safe_rows_start('*', 'txp_plugin', "1 order by name");

		if ($rs and numRows($rs) > 0)
		{
			echo assHead('plugin', 'author', 'version', 'description', 'active', 'help', '', '');

			while ($a = nextRow($rs))
			{
				foreach ($a as $key => $value) {
					$$key = htmlspecialchars($value);
				}
				// Fix up the description for clean cases
				$description = preg_replace(array('#&lt;br /&gt;#',
												  '#&lt;(/?(a|b|i|em|strong))&gt;#',
												  '#&lt;a href=&quot;(https?|\.|\/|ftp)([A-Za-z0-9:/?.=_]+?)&quot;&gt;#'),
											array('<br />','<$1>','<a href="$1$2">'),
											$description);

				$help = !empty($help) ? 
					'<a href="?event=plugin'.a.'step=plugin_help'.a.'name='.$name.'">'.gTxt('view').'</a>' : 
					gTxt('none');

				echo tr(

					n.td($name).

					td(
						href($author, $author_uri)
					).

					td($version, 10).
					td($description, 260).

					td(
						status_link($status, $name, yes_no($status))
					,30).

					td($help).

					td(
						eLink('plugin', 'plugin_edit', 'name', $name, gTxt('edit'))
					).

					td(
						dLink('plugin', 'plugin_delete', 'name', $name)
					,30)
				);

				unset($name, $page, $deletelink);
			}
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
		$textarea = '<textarea id="plugin-code" class="code" name="code" rows="28" cols="90">'.htmlentities(utf8_decode($thing)).'</textarea>';

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

		$plugin = preg_replace('@.*\$plugin=\'([\w=+/]+)\'.*@s', '$1', $plugin);
		$plugin = preg_replace('/^#.*$/m', '', $plugin);

		if(isset($plugin)) {
			$plugin_encoded = $plugin;
			$plugin = base64_decode($plugin);
			if (strncmp($plugin,"\x1F\x8B",2)===0)
				$plugin = gzinflate(substr($plugin, 10));

			if ($plugin = unserialize($plugin)) { 

				if(is_array($plugin)){
					extract(doSlash($plugin));
					$source = '';
					if(version_compare(PHP_VERSION, "4.2.0", "<") === 1)
					{
						ob_start();
						highlight_string('<?php'.$plugin['code'].'?>');
						$source = ob_get_contents();
						ob_end_clean();
						$help_source= '<pre>'.htmlspecialchars($plugin['help']).'</pre>';
					}
					else
					{
						$source.= highlight_string('<?php'.$plugin['code'].'?>', true);
						$help_source= highlight_string($plugin['help'], true);
					}
					$sub = fInput('submit','',gTxt('install'),'publish');
		
					pagetop(gTxt('edit_plugins'));
					echo 
					form(startTable('edit')
					.	tr(td(hed(gTxt('previewing_plugin'),3)))
					.	tr(td(tag($source, 'div', ' id="preview-plugin" class="code"')))
					.	tr(td(hed(gTxt('plugin_help').':',3)))
					.	tr(td(tag($help_source, 'div', ' id="preview-help" class="code"')))
					.	tr(td($sub))
					.	endTable().sInput('plugin_install').eInput('plugin').hInput('plugin64', $plugin_encoded)
					, 'margin: 0 auto; width: 75%;');
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

			$plugin = base64_decode($plugin);
			if (strncmp($plugin,"\x1F\x8B",2)===0)
				$plugin = gzinflate(substr($plugin, 10));

			if ($plugin = unserialize($plugin)) { 

				if(is_array($plugin)){
	
					extract(doSlash($plugin));
					if (empty($type)) $type = 0;
	
					$exists = fetch('name','txp_plugin','name',$name);

					if (isset($plugin['help_type']) && $plugin['help_type'] == 1) {
						include_once txpath.'/lib/classTextile.php';
						$textile = new Textile();
						$help = $textile->TextileThis(strip_tags($help));
					}

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
		return n.n.form(
			graf(
			tag(gTxt('install_plugin'), 'span', ' style="vertical-align: top;"').sp.

			'<textarea id="plugin-install" class="code" name="plugin" cols="62" rows="1"></textarea>'.sp.

			tag(
				popHelp('install_plugin').sp.
				fInput('submit', 'install_new', gTxt('upload'), 'smallerbox')
		   , 'span', ' style="vertical-align: 6px;"').

				eInput('plugin').
				sInput('plugin_verify')
			)
		, 'text-align: center;');
	}

?>
