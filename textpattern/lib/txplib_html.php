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
		global $txp_user,$event;
		if($event!='tag') {
			echo '<div style="text-align:center;margin:4em">',
			navPop(),
			'<a href="http://www.textpattern.com"><img src="txp_img/carver.gif" width="60" height="48" border="0" alt="" /></a>';
			echo graf('Textpattern &#183; '.txp_version);
			echo($txp_user)
			?	graf(gTxt('logged_in_as').' '.$txp_user.br.
					'<a href="index.php?logout=1">'.gTxt('logout').'</a>').'</div>'
			:	'</div>';
			echo n.'</body>'.n.'</html>';
		}
	}

// -------------------------------------------------------------
	function column_head($value, $sort='', $current_event='', $islink='', $dir='')
	{
		$o = '<td class="small"><strong>';
			if ($islink) {
				$o.= '<a href="index.php';
				$o.= ($sort) ? "?sort=$sort":'';
				$o.= ($dir) ? a."dir=$dir":'';
				$o.= ($current_event) ? a."event=$current_event":'';
				$o.= a.'step=list">';
			}
		$o .= gTxt($value);
			if ($islink) { $o .= "</a>"; }
		$o .= '</strong></td>';
		return $o;
	}
	
// -------------------------------------------------------------
	function hCell($text="",$caption="")
	{
		$text = (!$text) ? sp : $text;
		return tag($text,'th');
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
			'">'.$linktext.'</a>'
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
	function dLink($event,$step,$thing,$value,$verify='',$thing2='',$thing2val='',$get='')
	{
		if ($get) {
			return join('',array(
				'<a href="?event='.$event.a.'step='.$step.a.$thing.'='.urlencode($value),
				($thing2) ? a.$thing2.'='.$thing2val : '',
				'"',
				' class="dlink"',
				' onclick="return verify(\'',
				($verify) ? gTxt($verify) : gTxt('confirm_delete_popup'),
				'\')">&#215;</a>'
			));
		}

		return join('',array(
			'<form action="index.php" method="post" onsubmit="return confirm(\''.gTxt('confirm_delete_popup').'\');">',
			fInput('submit','','&#215;','smallerbox'),
			eInput($event).sInput($step),
			hInput($thing,$value),
			($thing2) ? hInput($thing2,$thing2val) : '',
			'</form>'));
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
	function PrevNextLink($event,$topage,$label,$type,$sort='',$dir='')
	{
		return join('',array(
			'<a href="?event='.$event.a.'step=list'.a.'page='.$topage,
			($sort) ? a.'sort='.$sort : '',
			($dir) ? a.'dir='.$dir : '',
			'" class="navlink">',
			($type=="prev") ? '&#8249;'.sp.$label : $label.sp.'&#8250;',
			'</a>'
		));
	}

// -------------------------------------------------------------
	function startSkelTable()
	{
		return 
		'<table width="300" cellpadding="0" cellspacing="0" style="border:1px #ccc solid">';
	}

// -------------------------------------------------------------
	function startTable($type,$align='',$class='',$p='')
	{
		if (!$p) $p = ($type=='edit') ? 3 : 0;
		$align = (!$align) ? 'center' : $align;
		$class = ($class) ? ' class="'.$class.'"' : '';
		return
		'<table cellpadding="'.$p.'" cellspacing="0" border="0" id="'.
			$type.'" align="'.$align.'"'.$class.'>'.n;
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
		$content = (!$content) ? '&#160;' : $content;
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
		return tag($content,'td',' style="vertical-align:top;text-align;left;padding:8px"'.$atts);
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
	function fLabelCell ($text,$help='') 
	{
		$help = ($help) ? popHelp($help) : '';
		return tda(gTxt($text).$help,' style="vertical-align:middle;text-align:right;border:0px"');
	}

// -------------------------------------------------------------
	function fInputCell ($name,$var='',$tabindex='',$size='',$help="") 
	{
		$pop = ($help) ? popHelp($name) : '';
		return tda(fInput('text',$name,$var,'edit','','',$size,$tabindex).$pop
		,' style="vertical-align:top;text-align:left;border:0px"');
	}

// -------------------------------------------------------------
	function tag($content,$tag,$atts='') 
	{
		return ($content) ? '<'.$tag.$atts.'>'.$content.'</'.$tag.'>' : '';
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
	function href($item,$href) 
	{
		return tag($item,'a',' href="'.$href.'"');
	}

// -------------------------------------------------------------
	function strong($item)
	{
		return tag($item,'strong');
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
	function popHelp($helpvar,$winW='',$winH='') 
	{
		$the_lang = (LANG == 'cs-cz' || LANG == 'el-gr' || LANG == 'ja-jp') ? substr(LANG,3,2): substr(LANG,0,2);
		return join('',array(
			' <a target="_blank" href="http://rpc.textpattern.com/help/?item='.$helpvar.'&#38;lang='.$the_lang.'"',
			' onclick="',
			"window.open(this.href, 'popupwindow', 'width=",
			($winW) ? $winW : 400,
			',height=',
			($winH) ? $winH : 400,
			',scrollbars,resizable\'); return false;" class="pophelp">?</a>'
		));
	}

// -------------------------------------------------------------
	function popHelpSubtle($helpvar,$winW='',$winH='') 
	{
		$the_lang = (LANG == 'cs-cz' || LANG == 'el-gr' || LANG == 'ja-jp') ? substr(LANG,3,2): substr(LANG,0,2);
		return join('',array(
			' <a target="_blank" href="http://rpc.textpattern.com/help/?item='.$helpvar.'&lang='.$the_lang.'"',
			' onclick="',
			"window.open(this.href, 'popupwindow', 'width=",
			($winW) ? $winW : 400,
			',height=',
			($winH) ? $winH : 400,
			',scrollbars,resizable\'); return false;">?</a>'
		));
	}


// -------------------------------------------------------------
	function popTag($var,$text,$winW='',$winH='') 
	{
		return join('',array(
			' <a target="_blank" href="?event=tag'.a.'name='.$var.'"',
			' onclick="',
			"window.open(this.href, 'popupwindow', 'width=",
			($winW) ? $winW : 400,
			',height=',
			($winH) ? $winH : 400,
			',scrollbars,resizable\'); return false;">',
			$text,'</a>'
		));
	}
	
// -------------------------------------------------------------
	function popTagLinks($type) 
	{
		global $txpcfg;
		include txpath.'/lib/taglib.php';
		$arname = $type.'_tags';
		asort($$arname);
		foreach($$arname as $a) {
			$out[] = popTag($a,gTxt('tag_'.$a));
		}
		return join(br,$out);
	}

//-------------------------------------------------------------
	function messenger($thing,$thething,$action)
	{
		return gTxt($thing).' '.strong($thething).' '.gTxt($action);
	}

// -------------------------------------------------------------
	function pageby_form($event,$curval) 
	{
		$qtys = array(15=>15,25=>25,50=>50,100=>100);
		return form(graf(
			gTxt('view').sp.
			selectInput('qty', $qtys, $curval,'',1).sp.
			gTxt('per_page').
			eInput($event).sInput($event.'_change_pageby')
		,' align="center"'));
	}
// -------------------------------------------------------------
	function upload_form($label, $pophelp, $step, $event, $id='', $max_file_size = '1000000')
	{
		return
			'<form enctype="multipart/form-data" action="index.php" method="post">'.
			((!empty($max_file_size))? hInput('MAX_FILE_SIZE',$max_file_size): '').
			graf($label.': '.
			fInput('file','thefile','','edit').
			popHelp($pophelp).
			fInput('submit','',gTxt('upload'),'smallerbox')).
			eInput($event).
			sInput($step).
			hInput('id',$id).
			'</form>';
	}
	
//-------------------------------------------------------------
	function pref_text($item,$var)
	{
		$things = array(
			"2" => gTxt('convert_linebreaks'),
			"1" => gTxt('use_textile'),
			"0" => gTxt('leave_text_untouched'));
		return selectInput($item, $things, $var);
	}


?>
