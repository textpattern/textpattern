<?php

/*
$HeadURL$
$LastChangedRevision$
*/

	define("t","\t");
	define("n","\n");
	define("br","<br />");
	define("sp","&#160;");
	define("a","&#38;");

// -------------------------------------------------------------
	function end_page()
	{
		global $txp_user, $event, $app_mode, $theme;

		if ($app_mode != 'async' && $event != 'tag') {
			echo $theme->footer();
			callback_event('admin_side', 'body_end');
			echo n.'</body>'.n.'</html>';
		}
	}

// -------------------------------------------------------------

	function column_head($value, $sort = '', $event = '', $is_link = '', $dir = '', $crit = '', $method = '', $class = '')
	{
		return column_multi_head( array(
					array ('value' => $value, 'sort' => $sort, 'event' => $event, 'is_link' => $is_link,
						   'dir' => $dir, 'crit' => $crit, 'method' => $method)
				), $class);
	}

// -------------------------------------------------------------

	function column_multi_head($head_items, $class='')
	{
		$o = n.t.'<th'.($class ? ' class="'.$class.'"' : '').'>';
		$first_item = true;
		foreach ($head_items as $item)
		{
			if (empty($item)) continue;
			extract(lAtts(array(
				'value'		=> '',
				'sort'		=> '',
				'event'		=> '',
				'is_link'	=> '',
				'dir'		=> '',
				'crit'		=> '',
				'method'	=> '',
			),$item));

			$o .= ($first_item) ? '' : ', '; $first_item = false;

			if ($is_link)
			{
				$o .= '<a href="index.php?step=list';

				$o .= ($event) ? a."event=$event" : '';
				$o .= ($sort) ? a."sort=$sort" : '';
				$o .= ($dir) ? a."dir=$dir" : '';
				$o .= ($crit) ? a."crit=$crit" : '';
				$o .= ($method) ? a."search_method=$method" : '';

				$o .= '">';
			}

			$o .= gTxt($value);

			if ($is_link)
			{
				$o .= '</a>';
			}
		}
		$o .= '</th>';

		return $o;
	}

// -------------------------------------------------------------
	function hCell($text='',$caption='',$atts='')
	{
		$text = ('' === $text) ? sp : $text;
		return tag($text,'th',$atts);
	}

// -------------------------------------------------------------
	function sLink($event,$step,$linktext,$class='')
	{
		$c = ($class) ? ' class="'.$class.'"' : '';
		return '<a href="?event='.$event.a.'step='.$step.'"'.$c.'>'.$linktext.'</a>';
	}

// -------------------------------------------------------------
	function eLink($event,$step='',$thing='',$value='',$linktext,$thing2='',$val2='')
	{
		return join('',array(
			'<a href="?event='.$event,
			($step) ? a.'step='.$step : '',
			($thing) ? a.''.$thing.'='.urlencode($value) : '',
			($thing2) ? a.''.$thing2.'='.urlencode($val2) : '',
			'">'.escape_title($linktext).'</a>'
		));
	}

// -------------------------------------------------------------
	function wLink($event,$step='',$thing='',$value='')
	{
		return join('',array(
			'<a href="index.php?event='.$event,
			($step) ? a.'step='.$step : '',
			($thing) ? a.''.$thing.'='.urlencode($value) : '',
			'" class="dlink">'.sp.'!'.sp.'</a>'
		));
	}

// -------------------------------------------------------------

	function dLink($event, $step, $thing, $value, $verify = '', $thing2 = '', $thing2val = '', $get = '', $remember = null) {
		if ($remember) {
			list($page, $sort, $dir, $crit, $search_method) = $remember;
		}

		if ($get) {
			$url = '?event='.$event.a.'step='.$step.a.$thing.'='.urlencode($value);

			if ($thing2) {
				$url .= a.$thing2.'='.urlencode($thing2val);
			}

			if ($remember) {
				$url .= a.'page='.$page.a.'sort='.$sort.a.'dir='.$dir.a.'crit='.$crit.a.'search_method='.$search_method;
			}

			return join('', array(
				'<a href="'.$url.'" class="dlink" onclick="return verify(\'',
				($verify) ? gTxt($verify) : gTxt('confirm_delete_popup'),
				'\')">×</a>'
			));
		}

		return join('', array(
			'<form method="post" action="index.php" onsubmit="return confirm(\''.gTxt('confirm_delete_popup').'\');">',
			 fInput('submit', '', '×', 'smallerbox'),
			 eInput($event).
			 sInput($step),
			 hInput($thing, $value),
			 ($thing2) ? hInput($thing2, $thing2val) : '',
			 ($remember) ? hInput('page', $page) : '',
			 ($remember) ? hInput('sort', $sort) : '',
			 ($remember) ? hInput('dir', $dir) : '',
			 ($remember) ? hInput('crit', $crit) : '',
			 ($remember) ? hInput('search_method', $search_method) : '',
			'</form>'
		));
	}

// -------------------------------------------------------------
	function aLink($event,$step,$thing,$value,$thing2,$value2)
	{
		$o = '<a href="?event='.$event.a.'step='.$step.
			a.$thing.'='.urlencode($value).a.$thing2.'='.urlencode($value2).'"';
		$o.= ' class="alink">+</a>';
		return $o;
	}

// -------------------------------------------------------------
	function prevnext_link($name,$event,$step,$id,$titling='')
	{
		return '<a href="?event='.$event.a.'step='.$step.a.'ID='.$id.
			'" class="navlink" title="'.$titling.'">'.$name.'</a> ';
	}

// -------------------------------------------------------------

	function PrevNextLink($event, $page, $label, $type, $sort = '', $dir = '', $crit = '', $search_method = '')
	{
		return '<a href="?event='.$event.a.'step=list'.a.'page='.$page.
			($sort ? a.'sort='.$sort : '').
			($dir ? a.'dir='.$dir : '').
			($crit ? a.'crit='.$crit : '').
			($search_method ? a.'search_method='.$search_method : '').
			'" class="navlink">'.
			($type == 'prev' ? '&#8249;'.sp.$label : $label.sp.'&#8250;').
			'</a>';
	}

// -------------------------------------------------------------

	function nav_form($event, $page, $numPages, $sort, $dir, $crit, $search_method, $total=0, $limit=0)
	{
		global $theme;
		if ($crit && $total > 1)
		{
			$out[] = $theme->announce(
				gTxt('showing_search_results',
					array(
						'{from}'	=> (($page - 1) * $limit) + 1,
						'{to}' 		=> min($total, $page * $limit),
						'{total}' 	=> $total
						)
					)
				);
		}

		if ($numPages > 1)
		{
			$option_list = array();

			for ($i = 1; $i <= $numPages; $i++)
			{
				if ($i == $page)
				{
					$option_list[] = '<option value="'.$i.'" selected="selected">'."$i/$numPages".'</option>';
				}

				else
				{
					$option_list[] = '<option value="'.$i.'">'."$i/$numPages".'</option>';
				}
			}

			$nav = array();

			$nav[] = ($page > 1) ?
				PrevNextLink($event, $page - 1, gTxt('prev'), 'prev', $sort, $dir, $crit, $search_method).sp :
				tag('&#8249; '.gTxt('prev'), 'span', ' class="navlink-disabled"').sp;

			$nav[] = '<select name="page" class="list" onchange="submit(this.form);">';
			$nav[] = n.join(n, $option_list);
			$nav[] = n.'</select>';
			$nav[] = '<noscript> <input type="submit" value="'.gTxt('go').'" class="smallerbox" /></noscript>';

			$nav[] = ($page != $numPages) ?
				sp.PrevNextLink($event, $page + 1, gTxt('next'), 'next', $sort, $dir, $crit, $search_method) :
				sp.tag(gTxt('next').' &#8250;', 'span', ' class="navlink-disabled"');

			$out[] = '<form class="prev-next" method="get" action="index.php">'.
				n.eInput($event).
				( $sort ? n.hInput('sort', $sort).n.hInput('dir', $dir) : '' ).
				( $crit ? n.hInput('crit', $crit).n.hInput('search_method', $search_method) : '' ).
				join('', $nav).
				'</form>';
		}
		else
		{
			$out[] = graf($page.'/'.$numPages, ' class="prev-next"');
		}

		return join(n, $out);
	}

// -------------------------------------------------------------
	function startSkelTable()
	{
		return
		'<table width="300" cellpadding="0" cellspacing="0" style="border:1px #ccc solid">';
	}

// -------------------------------------------------------------
	function startTable($type,$align='',$class='',$p='',$w='')
	{
		if ('' === $p) $p = ($type=='edit') ? 3 : 0;
		$align = (!$align) ? 'center' : $align;
		$class = ($class) ? ' class="'.$class.'"' : '';
		$width = ($w) ? ' width="'.$w.'"' : '';
		return '<table cellpadding="'.$p.'" cellspacing="0" border="0" id="'.
			$type.'" align="'.$align.'"'.$class.$width.'>'.n;
	}

// -------------------------------------------------------------
	function endTable ()
	{
		return n.'</table>'.n;
	}

// -------------------------------------------------------------
	function stackRows()
	{
		foreach(func_get_args() as $a) { $o[] = tr($a); }
		return join('',$o);
	}

// -------------------------------------------------------------
	function td($content='',$width='',$class='',$id='')
	{
		$content = ('' === $content) ? '&#160;' : $content;
		$atts[] = ($width)  ? ' width="'.$width.'"' : '';
		$atts[] = ($class)  ? ' class="'.$class.'"' : '';
		$atts[] = ($id)  ? ' id="'.$id.'"' : '';
		return t.tag($content,'td',join('',$atts)).n;
	}

// -------------------------------------------------------------
	function tda($content,$atts='')
	{
		return tag($content,'td',$atts);
	}

// -------------------------------------------------------------
	function tdtl($content,$atts='')
	{
		return tag($content,'td',' style="vertical-align:top;text-align:left;padding:8px"'.$atts);
	}

// -------------------------------------------------------------
	function tr($content,$atts='')
	{
		return tag($content,'tr',$atts);
	}

// -------------------------------------------------------------
	function tdcs($content,$span,$width="",$class='')
	{
		return join('',array(
			t.'<td align="left" valign="top" colspan="'.$span.'"',
			($width) ? ' width="'.$width.'"' : '',
			($class) ? ' class="'.$class.'"' : '',
			">$content</td>\n"
		));
	}

// -------------------------------------------------------------
	function tdrs($content,$span,$width="")
	{
		return join('',array(
			t.'<td align="left" valign="top" rowspan="'.$span.'"',
			($width) ? ' width="'.$width.'"' : '',">$content</td>".n
		));
	}

// -------------------------------------------------------------

	function fLabelCell($text, $help = '', $label_id = '')
	{
		$help = ($help) ? popHelp($help) : '';

		$cell = gTxt($text).' '.$help;

		if ($label_id)
		{
			$cell = '<label for="'.$label_id.'">'.$cell.'</label>';
		}

		return tda($cell,' class="noline" style="text-align: right; vertical-align: middle;"');
	}

// -------------------------------------------------------------

	function fInputCell($name, $var = '', $tabindex = '', $size = '', $help = '', $id = '')
	{
		$pop = ($help) ? sp.popHelp($name) : '';

		return tda(
			fInput('text', $name, $var, 'edit', '', '', $size, $tabindex, $id).$pop
		,' class="noline"');
	}

// -------------------------------------------------------------
	function tag($content,$tag,$atts='')
	{
		return ('' !== $content) ? '<'.$tag.$atts.'>'.$content.'</'.$tag.'>' : '';
	}

// -------------------------------------------------------------
	function graf ($item,$atts='')
	{
		return tag($item,'p',$atts);
	}

// -------------------------------------------------------------
	function hed($item,$level,$atts='')
	{
		return tag($item,'h'.$level,$atts);
	}

// -------------------------------------------------------------
	function href($item,$href,$atts='')
	{
		return tag($item,'a',$atts.' href="'.$href.'"');
	}

// -------------------------------------------------------------
	function strong($item)
	{
		return tag($item,'strong');
	}

// -------------------------------------------------------------
	function span($item)
	{
		return tag($item,'span');
	}

// -------------------------------------------------------------
	function htmlPre($item)
	{
		return '<pre>'.tag($item,'code').'</pre>';
	}

// -------------------------------------------------------------
	function comment($item)
	{
		return '<!-- '.$item.' -->';
	}

// -------------------------------------------------------------
	function small($item)
	{
		return tag($item,'small');
	}

// -------------------------------------------------------------
	function assRow($array, $atts ='')
	{
		foreach($array as $a => $b) $o[] = tda($a,' width="'.$b.'"');
		return tr(join(n.t,$o), $atts);
	}

// -------------------------------------------------------------
	function assHead()
	{
		$array = func_get_args();
		foreach($array as $a) $o[] = hCell(gTxt($a));
		return tr(join('',$o));
	}

// -------------------------------------------------------------

	function popHelp($help_var, $width = '', $height = '')
	{
		return '<a target="_blank"'.
			' href="http://rpc.textpattern.com/help/?item='.$help_var.a.'language='.LANG.'"'.
			' onclick="popWin(this.href'.
			($width ? ', '.$width : '').
			($height ? ', '.$height : '').
			'); return false;" class="pophelp">?</a>';
	}

// -------------------------------------------------------------

	function popHelpSubtle($help_var, $width = '', $height = '')
	{
		return '<a target="_blank"'.
			' href="http://rpc.textpattern.com/help/?item='.$help_var.a.'language='.LANG.'"'.
			' onclick="popWin(this.href'.
			($width ? ', '.$width : '').
			($height ? ', '.$height : '').
			'); return false;">?</a>';
	}

// -------------------------------------------------------------

	function popTag($var, $text, $width = '', $height = '')
	{
		return '<a target="_blank"'.
			' href="?event=tag'.a.'tag_name='.$var.'"'.
			' onclick="popWin(this.href'.
			($width ? ', '.$width : '').
			($height ? ', '.$height : '').
			'); return false;">'.$text.'</a>';
	}

// -------------------------------------------------------------

	function popTagLinks($type)
	{
		global $txpcfg;

		include txpath.'/lib/taglib.php';

		$arname = $type.'_tags';

		$out = array();

		$out[] = n.'<ul class="plain-list small">';

		foreach ($$arname as $a)
		{
			$out[] = n.t.tag(popTag($a,gTxt('tag_'.$a)), 'li');
		}

		$out[] = n.'</ul>';

		return join('', $out);
	}

//-------------------------------------------------------------
	function messenger($thing, $thething='', $action='')
	{
		return gTxt($thing).' '.strong($thething).' '.gTxt($action);
	}

// -------------------------------------------------------------

	function pageby_form($event, $val)
	{
		$vals = array(
			15  => 15,
			25  => 25,
			50  => 50,
			100 => 100
		);

		$select_page = selectInput('qty', $vals, $val,'', 1);

		// proper localisation
		$page = str_replace('{page}', $select_page, gTxt('view_per_page'));

		return form(
			'<div style="margin: auto; text-align: center;">'.
				$page.
				eInput($event).
				sInput($event.'_change_pageby').
				'<noscript> <input type="submit" value="'.gTxt('go').'" class="smallerbox" /></noscript>'.
			'</div>'
		);
	}
// -------------------------------------------------------------

	function upload_form($label, $pophelp, $step, $event, $id = '', $max_file_size = '1000000', $label_id = '', $class = 'upload-form')
	{
		global $sort, $dir, $page, $search_method, $crit;

		$class = ($class) ? ' class="'.$class.'"' : '';

		$label_id = ($label_id) ? $label_id : $event.'-upload';

		$argv = func_get_args();
		return pluggable_ui($event.'_ui', 'upload_form',
			n.n.'<form'.$class.' method="post" enctype="multipart/form-data" action="index.php">'.
			n.'<div>'.

			(!empty($max_file_size)? n.hInput('MAX_FILE_SIZE', $max_file_size): '').
			n.eInput($event).
			n.sInput($step).
			n.hInput('id', $id).

			n.hInput('sort', $sort).
			n.hInput('dir', $dir).
			n.hInput('page', $page).
			n.hInput('search_method', $search_method).
			n.hInput('crit', $crit).

			n.graf(
				'<label for="'.$label_id.'">'.$label.'</label>'.sp.popHelp($pophelp).sp.
					fInput('file', 'thefile', '', 'edit', '', '', '', '', $label_id).sp.
					fInput('submit', '', gTxt('upload'), 'smallerbox')
			).

			n.'</div>'.
			n.'</form>',
			$argv);
	}

//-------------------------------------------------------------

	function search_form($event, $step, $crit, $methods, $method, $default_method)
	{
		$method = ($method) ? $method : $default_method;

		return n.n.form(
			graf(
				'<label for="'.$event.'-search">'.gTxt('search').'</label>'.sp.
				selectInput('search_method', $methods, $method, '', '', $event.'-search').sp.
				fInput('text', 'crit', $crit, 'edit', '', '', '15').
				eInput($event).
				sInput($step).
				fInput('submit', 'search', gTxt('go'), 'smallerbox')
			)
		, '', '', 'get', 'search-form');
	}

//-------------------------------------------------------------

	function pref_text($name, $val, $id = '')
	{
		$id = ($id) ? $id : $name;

		$vals = array(
			USE_TEXTILE          => gTxt('use_textile'),
			CONVERT_LINEBREAKS   => gTxt('convert_linebreaks'),
			LEAVE_TEXT_UNTOUCHED => gTxt('leave_text_untouched')
		);

		return selectInput($name, $vals, $val, '', '', $id);
	}

//-------------------------------------------------------------
	function dom_attach($id, $content, $noscript='', $wraptag='div', $wraptagid='')
	{

		$c = addcslashes($content, "\r\n\"\'");
		$c = preg_replace('@<(/?)script@', '\\x3c$1script', $c);
		$js = <<<EOF
var e = document.getElementById('{$id}');
var n = document.createElement('{$wraptag}');
n.innerHTML = '{$c}';
n.setAttribute('id','{$wraptagid}');
e.appendChild(n);
EOF;

		return script_js($js, $noscript);
	}

//-------------------------------------------------------------
	function script_js($js, $noscript='')
	{
		$out = '<script type="text/javascript">'.n.
			'<!--'.n.
			trim($js).n.
			'// -->'.n.
			'</script>'.n;
		if ($noscript)
			$out .= '<noscript>'.n.
				trim($noscript).n.
				'</noscript>'.n;
		return $out;
	}

//-------------------------------------------------------------
	function toggle_box($classname, $form=0) {

		$name = 'cb_toggle_'.$classname;
		$i =
			'<input type="checkbox" name="'.$name.'" id="'.$name.'" value="1" '.
			(cs('toggle_'.$classname) ? 'checked="checked" ' : '').
			'class="checkbox" onclick="toggleClassRemember(\''.$classname.'\');" />'.
			' <label for="'.$name.'">'.gTxt('detail_toggle').'</label> '.
			script_js("setClassRemember('".$classname."');addEvent(window, 'load', function(){setClassRemember('".$classname."');});");
		if ($form)
			return n.form($i);
		else
			return n.$i;
	}

//-------------------------------------------------------------
	function cookie_box($classname, $form=1) {

		$name = 'cb_'.$classname;
		$val = cs('toggle_'.$classname) ? 1 : 0;

		$i =
			'<input type="checkbox" name="'.$name.'" id="'.$name.'" value="1" '.
			($val ? 'checked="checked" ' : '').
			'class="checkbox" onclick="setClassRemember(\''.$classname.'\','.(1-$val).');submit(this.form);" />'.
			' <label for="'.$name.'">'.gTxt($classname).'</label> ';

		if ($form) {
			$args = empty($_SERVER['QUERY_STRING']) ? '' : '?'.htmlspecialchars($_SERVER['QUERY_STRING']);
			return '<form class="'.$name.'" method="post" action="index.php'.$args.'">'.$i.eInput(gps('event')).n.'<noscript><div><input type="submit" value="'.gTxt('go').'" /></div></noscript></form>';
		} else {
			return n.$i;
		}
	}


//-------------------------------------------------------------
	function fieldset($content, $legend='', $id='') {
		$a_id = ($id ? ' id="'.$id.'"' : '');
		return tag(trim(tag($legend, 'legend').n.$content), 'fieldset', $a_id);
	}

?>
