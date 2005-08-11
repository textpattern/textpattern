<?php

/*
$HeadURL$
$LastChangedRevision$
*/

// -------------------------------------------------------------
	function pagetop($pagetitle,$message="")
	{
		global $css_mode,$siteurl,$txp_user,$event;
		$area = gps('area');
		$event = (!$event) ? 'article' : $event;
		$bm = gps('bm');

		$privs = safe_field("privs", "txp_users", "`name`='$txp_user'");
		
		$GLOBALS['privs'] = $privs;

		$areas = areas();
		foreach ($areas as $k=>$v) {
			if (in_array($event, $v))
				$area = $k;
		}
		
	?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
			"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<title>Textpattern &#8250; <?php echo $pagetitle?></title>
	<link href="textpattern.css" rel="Stylesheet" type="text/css" />
	<script language="JavaScript" type="text/javascript">
	<!--
		function verify(msg) { return confirm(msg); }

		var date = new Date();
		date.setTime(date.getTime()+(60*1000));
		var expires = "; expires="+date.toGMTString();
		document.cookie="testcookie=enabled"+expires+"; path=/";
		cookieEnabled=(document.cookie.length>2)? true : false
		date.setTime(date.getTime()-(60*1000));
		var expires = "; expires="+date.toGMTString();
		document.cookie="testcookie"+expires+"; path=/"; //erase dummy value
		if(!cookieEnabled){
			confirm(<?php echo "'".trim(gTxt('cookies_must_be_enabled'))."'"; ?>)
		}

		function toggleDisplay(obj_id) {
			if (document.getElementById){
				var obj = document.getElementById(obj_id);
				if (obj.style.display == '' || obj.style.display == 'none'){
					var state = 'block';
				} else {
					var state = 'none';
				}
				obj.style.display = state;
			}
		}

		function selectall() {
			var cnt = 0;
			var elem = window.document.longform.elements;
			cnt = elem.length;
			for (var i=0; i < cnt; i++) elem[i].checked = true;
		}
		
		function deselectall() {
			var cnt = 0;
			var elem = window.document.longform.elements;
			cnt = elem.length;
			for (var i=0; i < cnt; i++) elem[i].checked = false;
		}
		
		function selectrange() {
			var inrange = false;
			var cnt = 0;
			var elem = window.document.longform.elements;
			cnt = elem.length;
			for (var i=0; i < cnt; i++) {
				if (elem[i].type == 'checkbox') {
					if (elem[i].checked == true) {
						if (!inrange) inrange = true;
						else inrange = false;
					}
					if (inrange) elem[i].checked = true;
				}
			}
		}
	-->
	</script>
	</head>
	<body>
  <table cellpadding="0" cellspacing="0" width="100%" style="margin-bottom:2em">
  <tr><td align="left" style="background:#FFCC33"><img src="txp_img/textpattern.gif" height="15" width="368" alt="textpattern" /></td><td style="background:#FFCC33" align="right"><?php echo navPop(1); ?></td></tr>
  <tr><td align="center" class="tabs" colspan="2">
 		<?php
 		if (!$bm) {
			echo '<table cellpadding="0" cellspacing="0" align="center"><tr>
  <td valign="middle" style="width:368px">&nbsp;'.$message.'</td>',
  			
			has_privs('tab.content')
			? areatab(gTxt('tab_content'), 'content', 'article', $area)
			: '',
			has_privs('tab.presentation')
			?	areatab(gTxt('tab_presentation'), 'presentation', 'page', $area)
			:	'',
			has_privs('tab.admin')
			?	areatab(gTxt('tab_admin'), 'admin', 'prefs', $area)
			:	'',
			(has_privs('tab.extensions') and !empty($areas['extensions']))
			?	areatab(gTxt('tab_extensions'), 'extensions', array_shift($areas['extensions']), $area)
			:	'',

			'<td class="tabdown"><a href="'.hu.'" class="plain" target="blank">'.gTxt('tab_view_site').'</a></td>',
		 '</tr></table>',
		
		'</td></tr><tr><td align="center" class="tabs" colspan="2">
			<table cellpadding="0" cellspacing="0" align="center"><tr>',
				tabsort($area,$event),
			'</tr></table>';
		}
		echo '</td></tr></table>';
	}

// -------------------------------------------------------------
	function areatab($label,$event,$tarea,$area) 
	{
		$tc = ($area == $event) ? 'tabup' : 'tabdown';
		$atts=' class="'.$tc.'" onclick="window.location.href=\'?event='.$tarea.'\'"';
		$hatts=' href="?event='.$tarea.'" class="plain"';
      	return tda(tag($label,'a',$hatts),$atts);
	}

// -------------------------------------------------------------
	function tabber($label,$tabevent,$event) 
	{		
		$tc = ($event==$tabevent) ? 'tabup' : 'tabdown2';
		$out = '<td class="'.$tc.'" onclick="window.location.href=\'?event='.$tabevent.'\'" ><a href="?event='.$tabevent.'" class="plain">'.$label.'</a></td>';
      	return $out;
	}

// -------------------------------------------------------------
	function tabsort($area,$event) 
	{
		$areas = areas();
		foreach($areas[$area] as $a=>$b) {
			$out[] = tabber($a,$b,$event,2);
		}
		return join('',$out);
	}

// -------------------------------------------------------------
	function areas() 
	{
		global $privs, $plugin_areas;
		
		$areas['content'] = array(
			gTxt('tab_organise') => 'category',
			gTxt('tab_write')    => 'article',
			gTxt('tab_list')    =>  'list',
			gTxt('tab_image')    => 'image',
			gTxt('tab_file')	 => 'file',			
			gTxt('tab_link')     => 'link',
			gTxt('tab_comments') => 'discuss'
		);
		
		$areas['presentation'] = array(
			gTxt('tab_sections') => 'section',
			gTxt('tab_pages')    => 'page',
			gTxt('tab_forms')    => 'form',
			gTxt('tab_style')    => 'css'
		);

		$areas['admin'] = array(
			gTxt('tab_diagnostics') => 'diag',
			gTxt('tab_preferences') => 'prefs',
			gTxt('tab_site_admin')  => 'admin',
			gTxt('tab_logs')        => 'log',
			gTxt('tab_plugins')     => 'plugin',
			gTxt('tab_import')      => 'import'
		);	

		$areas['extensions'] = array(
		);

		if (is_array($plugin_areas))
			$areas = array_merge_recursive($areas, $plugin_areas);

		return $areas;	
	}

// -------------------------------------------------------------
	function navPop($inline='') 
	{
		$areas = areas();
		$st = ($inline) ? ' style="display:inline"': '';
		$o = '<form action="index.php" method="get"'.$st.'>
				<select name="event" onchange="submit(this.form)">
				<option>'.gTxt('go').'...</option>';
		foreach ($areas as $a => $b) {
			if (count($b) > 0) {
				$o .= '<optgroup label="'.gTxt('tab_'.$a).'">';
				foreach ($b as $c => $d) {
					$o .= '<option value="'.$d.'">'.$c.'</option>';
				}
				$o .= '</optgroup>';
			}
		}
		$o .= '</select></form>';
		return $o;
	}

// -------------------------------------------------------------
	function button($label,$link) 
	{
		return '<span style="margin-right:2em"><a href="?event='.$link.'" class="plain">'.$label.'</a></span>';
	}
?>
