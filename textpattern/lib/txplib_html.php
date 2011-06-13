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


/**
 * Render the admin-side theme's footer partial via the "admin_side" > "footer" pluggable UI.
 * and send the "admin_side" > "body_end" event.
 */
	function end_page()
	{
		global $txp_user, $event, $app_mode, $theme;

		if ($app_mode != 'async' && $event != 'tag') {
			echo pluggable_ui('admin_side', 'footer', $theme->footer());
			callback_event('admin_side', 'body_end');
			echo n.'</body>'.n.'</html>';
		}
	}


/**
 * Render the user interface for one head cell of columnar data.
 *
 * @param	string	$value	Element text
 * @param	string	$sort	Sort criterion ['']
 * @param	string	$event	Event name ['']
 * @param	boolean	$is_link	Include link to admin action in user interface according to the other params [false]
 * @param	string	$dir	Sort direction, either "asc" or "desc" ['']
 * @param	string	$crit	Search criterion ['']
 * @param	string	$method	Search method ['']
 * @param	string	$class	HTML "class" attribute applied to the resulting element ['']
 * @return 	string	HTML
 */

	function column_head($value, $sort = '', $event = '', $is_link = '', $dir = '', $crit = '', $method = '', $class = '')
	{
		return column_multi_head( array(
					array ('value' => $value, 'sort' => $sort, 'event' => $event, 'is_link' => $is_link,
						   'dir' => $dir, 'crit' => $crit, 'method' => $method)
				), $class);
	}


/**
 * Render the user interface for multiple head cells of columnar data.
 *
 * @param	array	$head_items	An array of hashed elements.
 * 								Valid keys:
 * 							 	'value'
 * 								'sort'
 * 								'event'
 * 								'is_link'
 * 								'dir'
 * 								'crit'
 * 								'method'
 * @param	string	$class	HTML "class" attribute applied to the resulting element
 * @return	string	HTML
 */

	function column_multi_head($head_items, $class='')
	{
		$o = n.t.'<th'.($class ? ' class="'.$class.'"' : '').'>';
		$first_item = true;
		foreach ($head_items as $item)
		{
			if (empty($item)) continue;
			extract(lAtts(array(
				'value'   => '',
				'sort'    => '',
				'event'   => '',
				'is_link' => '',
				'dir'     => '',
				'crit'    => '',
				'method'  => '',
			),$item));

			$o .= ($first_item) ? '' : ', '; $first_item = false;

			if ($is_link)
			{
				$o .= '<a href="index.php?step=list';

				$o .= ($event) ? a."event=$event" : '';
				$o .= ($sort) ? a."sort=$sort" : '';
				$o .= ($dir) ? a."dir=$dir" : '';
				$o .= ($crit != '') ? a."crit=$crit" : '';
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


/**
 * Render a <th> element.
 *
 * @param	string	$text	Cell text [space]
 * @param	string	$caption	unused  ['']
 * @param	string	$atts	HTML attributes  ['']
 * @return	string	HTML
 */
	function hCell($text='',$caption='',$atts='')
	{
		$text = ('' === $text) ? sp : $text;
		return tag($text,'th',$atts);
	}


/**
 * Render a link invoking an admin-side action.
 *
 * @param	string	$event	Event
 * @param	string	$step	Step
 * @param	string	$linktext	Link text
 * @param	string	$class	HTML class attribute for link
 * @return	string	HTML
 */
	function sLink($event,$step,$linktext,$class='')
	{
		$c = ($class) ? ' class="'.$class.'"' : '';
		return '<a href="?event='.$event.a.'step='.$step.'"'.$c.'>'.$linktext.'</a>';
	}


/**
 * Render a link invoking an admin-side action while taking up to two additional URL parameters.
 *
 * @param	string	$event	Event
 * @param	string	$step	Step ['']
 * @param	string	$thing	URL parameter key #1 ['']
 * @param	string	$value	URL parameter value #1 ['']
 * @param	string	$linktext	Link text
 * @param	string	$thing2	URL parameter key #2 ['']
 * @param	string	$val2	URL parameter value #2 ['']
 * @return	string	HTML
 */
	function eLink($event,$step='',$thing='',$value='',$linktext,$thing2='',$val2='')
	{
		return join('',array(
			'<a href="?event='.$event,
			($step) ? a.'step='.$step : '',
			($thing) ? a.''.$thing.'='.urlencode($value) : '',
			($thing2) ? a.''.$thing2.'='.urlencode($val2) : '',
			a.'_txp_token='.form_token(),
			'">'.escape_title($linktext).'</a>'
		));
	}


/**
 * Render a link invoking an admin-side action while taking up to one additional URL parameter.
 *
 * @param	string	$event	Event
 * @param	string	$step	Step
 * @param	string	$thing	URL parameter key
 * @param	string	$value	URL parameter value
 * @return			string 	HTML
 */
	function wLink($event,$step='',$thing='',$value='')
	{
		// TODO: Why index.php? while we don't need this in eLinkj etc.
		return join('',array(
			'<a href="index.php?event='.$event,
			($step) ? a.'step='.$step : '',
			($thing) ? a.''.$thing.'='.urlencode($value) : '',
			a.'_txp_token='.form_token(),
			'" class="dlink">'.sp.'!'.sp.'</a>'
		));
	}


/**
 * Render a link invoking an admin-side "delete" action while taking up to two additional URL parameters.
 *
 * @param	string	$event	Event
 * @param	string	$step	Step
 * @param	string	$thing	URL parameter key #1
 * @param	string	$value	URL parameter value #1
 * @param	string	$verify	Show an "Are you sure?" dialogue with this text ['confirm_delete_popup']
 * @param	string	$thing2	URL parameter key #2 ['']
 * @param	string	$thing2val	URL parameter value #2 ['']
 * @param	boolean	$get	Use GET request [false: Use POST request]
 * @param	array	$remember	Convey URL parameters for page state. Member sequence is $page, $sort, $dir, $crit, $search_method
 */

	function dLink($event, $step, $thing, $value, $verify = '', $thing2 = '', $thing2val = '', $get = '', $remember = null) {
		if ($remember) {
			list($page, $sort, $dir, $crit, $search_method) = $remember;
		}

		if ($get) {
			$url = '?event='.$event.a.'step='.$step.a.$thing.'='.urlencode($value).a.'_txp_token='.form_token();

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
			 n.tInput(),
			 '</form>'
		));
	}


/**
 * Render a link invoking an admin-side "add" action while taking up to two additional URL parameters.
 *
 * @param	string	$event	Event
 * @param	string	$step	Step
 * @param	string	$thing	URL parameter key #1
 * @param	string	$value	URL parameter value #1
 * @param	string	$thing2	URL parameter key #2
 * @param	string	$value2	URL parameter value #2
 * @return			string 	HTML
 */

	function aLink($event,$step,$thing,$value,$thing2,$value2)
	{
		$o = '<a href="?event='.$event.a.'step='.$step.a.'_txp_token='.form_token().
			a.$thing.'='.urlencode($value).a.$thing2.'='.urlencode($value2).'"';
		$o.= ' class="alink">+</a>';
		return $o;
	}


/**
 * Render a link invoking an admin-side "previous/next article" action.
 *
 * @param	string	$name	Link text
 * @param	string	$event	Event
 * @param	string	$step	Step
 * @param	integer	$id	ID of target Textpattern object (article,...)
 * @param	string	$titling	HTML title attribute
 * @return	string	HTML
 */

	function prevnext_link($name,$event,$step,$id,$titling='')
	{
		return '<a href="?event='.$event.a.'step='.$step.a.'ID='.$id.
			'" class="navlink" title="'.$titling.'">'.$name.'</a> ';
	}


/**
 * Render a link invoking an admin-side "previous/next page" action.
 *
 * @param	string	$event	Event
 * @param	integer	$page	Target page number
 * @param	string	$label	Link text
 * @param	string	$type	Direction, either "prev" or "next" ['next']
 * @param	string	$sort	Sort field ['']
 * @param	string	$dir	Sort direction, either "asc" or "desc" ['']
 * @param	string	$crit	Search criterion ['']
 * @param	string	$search_method	Search method ['']
 */

	function PrevNextLink($event, $page, $label, $type, $sort = '', $dir = '', $crit = '', $search_method = '')
	{
		return '<a href="?event='.$event.a.'step=list'.a.'page='.$page.
			($sort ? a.'sort='.$sort : '').
			($dir ? a.'dir='.$dir : '').
			(($crit != '') ? a.'crit='.$crit : '').
			($search_method ? a.'search_method='.$search_method : '').
			'" class="navlink">'.
			($type == 'prev' ? '&#8249;'.sp.$label : $label.sp.'&#8250;').
			'</a>';
	}


/**
 * Render a page navigation form.
 *
 * @param	string	$event	Event
 * @param	integer	$page	Current page number
 * @param	integer	$numPages	Total pages
 * @param	string	$sort	Sort criterion
 * @param	string	$dir	Sort direction, either "asc" or "desc"
 * @param	string	$crit	Search criterion
 * @param	string	$search_method	Search method
 * @param	integer	$total	Total search term hit count [0]
 * @param	integer	$limit	First visible search term hit number [0]
 * @return	string	HTML
 */

	function nav_form($event, $page, $numPages, $sort, $dir, $crit, $search_method, $total=0, $limit=0)
	{
		global $theme;
		if ($crit != '' && $total > 1)
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
				( ($crit != '') ? n.hInput('crit', $crit).n.hInput('search_method', $search_method) : '' ).
				join('', $nav).
				n.tInput().
				n.'</form>';
		}
		else
		{
			$out[] = graf($page.'/'.$numPages, ' class="prev-next"');
		}

		return join(n, $out);
	}


/**
 * Render start of a layout &lt;table&gt; element.
 *
 * @deprecated
 * @return	string	HTML
 */

	function startSkelTable()
	{
		return
		'<table width="300" cellpadding="0" cellspacing="0" style="border:1px #ccc solid">';
	}


/**
 * Render start of a layout &lt;table&gt; element.
 *
 * @param	string	$type	Layout view type, either "edit" or "list"
 * @param	string	$align	HTML align attribute ['center']
 * @param	string	$class	HTML class attribute ['']
 * @param	integer	$p	HTML cellpadding attribute
 * @param	integer	$w	HTML width atttribute
 * @return		string	HTML
 */

	function startTable($type,$align='',$class='',$p='',$w='')
	{
		if ('' === $p) $p = ($type=='edit') ? 3 : 0;
		$align = (!$align) ? 'center' : $align;
		$class = ($class) ? ' class="'.$class.'"' : '';
		$width = ($w) ? ' width="'.$w.'"' : '';
		return '<table cellpadding="'.$p.'" cellspacing="0" border="0" id="'.
			$type.'" align="'.$align.'"'.$class.$width.'>'.n;
	}


/**
 * Render &lt;/table&gt; tag
 *
 * @return	string	HTML
 */

	function endTable ()
	{
		return n.'</table>'.n;
	}


/**
 * Render &lt;tr&gt; elements from input parameters.
 *
 * @param	mixed,...	$rows	Row contents [null]
 * @return	string	HTML
 */

	function stackRows()
	{
		foreach(func_get_args() as $a) { $o[] = tr($a); }
		return join('',$o);
	}


/**
 * Render a &lt;td&gt; element.
 *
 * @param	string	$content	Cell content ['']
 * @param	integer	$width	HTML width attribute ['']
 * @param	string	$class	HTML class attribute ['']
 * @param	string	$id	HTML id attribute ['']
 * @return	string	HTML
 */

	function td($content='',$width='',$class='',$id='')
	{
		$content = ('' === $content) ? '&#160;' : $content;
		$atts[] = ($width)  ? ' width="'.$width.'"' : '';
		$atts[] = ($class)  ? ' class="'.$class.'"' : '';
		$atts[] = ($id)  ? ' id="'.$id.'"' : '';
		return t.tag($content,'td',join('',$atts)).n;
	}


/**
 * Render a &lt;td&gt; element with attributes.
 *
 * @param	string	$content	Cell content
 * @param	string	$atts	Cell attributes ['']
 * @return	string	HTML
 */

	function tda($content,$atts='')
	{
		return tag($content,'td',$atts);
	}


/**
 * Render a &lt;td&gt; element with top/left text orientation and other attributes.
 *
 * @param	string	$content	Cell content
 * @param	string	$atts	Cell attributes ['']
 * @return	string	HTML
 */

	function tdtl($content,$atts='')
	{
		return tag($content,'td',' style="vertical-align:top;text-align:left;padding:8px"'.$atts);
	}


/**
 * Render a &lt;tr&gt; element with attributes.
 *
 * @param	string	$content	Cell content
 * @param	string	$atts	Cell attributes ['']
 * @return	string	HTML
 */

	function tr($content,$atts='')
	{
		return tag($content,'tr',$atts);
	}


/**
 * Render a &lt;td&gt; element with top/left text orientation, colspan and other attributes.
 *
 * @param	string	$content	Cell content
 * @param	integer	$span	Cell colspan attribute
 * @param	integer	$width	Cell width attribute ['']
 * @param	string	$class	Cell class attribute ['']
 * @return	string	HTML
 */

	function tdcs($content,$span,$width="",$class='')
	{
		return join('',array(
			t.'<td align="left" valign="top" colspan="'.$span.'"',
			($width) ? ' width="'.$width.'"' : '',
			($class) ? ' class="'.$class.'"' : '',
			">$content</td>\n"
		));
	}


/**
 * Render a &lt;td&gt; element with top/left text orientation, rowspan and other attributes.
 *
 * @param	string	$content	Cell content
 * @param	integer	$span	Cell rowspan attribute
 * @param	integer	$width	Cell width attribute
 * @return	string	HTML
 */

	function tdrs($content,$span,$width="")
	{
		return join('',array(
			t.'<td align="left" valign="top" rowspan="'.$span.'"',
			($width) ? ' width="'.$width.'"' : '',">$content</td>".n
		));
	}


/**
 * Render a form label inside a table cell
 *
 * @param	string	$text	Label text
 * @param	string	$help	Help text ['']
 * @param	string	$label_id	HTML "for" attribute, i.e. id of corresponding form element
 * @return	string	HTML
 */

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


/**
 * Render a form input inside a table cell.
 *
 * @param	string	$name	HTML name attribute
 * @param	string	$var	Input value ['']
 * @param	integer	$tabindex	HTML tabindex attribute ['']
 * @param	integer	$size	HTML size attribute ['']
 * @param	string	$help	Help text ['']
 * @param	string	$id	HTML id attribute
 * @return		string	HTML
 */

	function fInputCell($name, $var = '', $tabindex = '', $size = '', $help = '', $id = '')
	{
		$pop = ($help) ? sp.popHelp($name) : '';

		return tda(
			fInput('text', $name, $var, 'edit', '', '', $size, $tabindex, $id).$pop
		,' class="noline"');
	}


/**
 * Render anything as a XML element.
 *
 * @param	string	$content	Enclosed content
 * @param	string	$tag	The tag without brackets
 * @param	string	$atts	The element's HTML attributes ['']
 * @return	string	HTML
 */

	function tag($content,$tag,$atts='')
	{
		return ('' !== $content) ? '<'.$tag.$atts.'>'.$content.'</'.$tag.'>' : '';
	}


/**
 * Render a &lt;p&gt; element.
 *
 * @param	string	$item	Enclosed content
 * @param	string	$atts	HTML attributes ['']
 * @return	string	HTML
 */

	function graf ($item,$atts='')
	{
		return tag($item,'p',$atts);
	}


/**
 * Render a &lt;hx&gt; element.
 *
 * @param	string	$item	Enclosed content
 * @param	integer	$level	Heading level 1...6
 * @param	string	$atts	HTML attributes ['']
 * @return	string	HTML
 */

	function hed($item,$level,$atts='')
	{
		return tag($item,'h'.$level,$atts);
	}

/**
 * Render an &lt;a&gt; element.
 *
 * @param	string	$item	Enclosed content
 * @param	integer	$level	Heading level 1...6
 * @param	string	$atts	HTML attributes ['']
 * @return	string	HTML
 */
	function href($item,$href,$atts='')
	{
		return tag($item,'a',$atts.' href="'.$href.'"');
	}

/**
 * Render a &lt;strong&gt; element.
 *
 * @param	string	$item	Enclosed content
 * @return		string	HTML
 */
	function strong($item)
	{
		return tag($item,'strong');
	}

/**
 * Render a &lt;span&gt; element.
 *
 * @param	string	$item	Enclosed content
 * @return	string	HTML
 */
 	function span($item)
	{
		return tag($item,'span');
	}

/**
 * Render a &lt;pre&gt; element.
 *
 * @param	string	$item	Enclosed content
 * @return	string	HTML
 */
	function htmlPre($item)
	{
		return '<pre>'.tag($item,'code').'</pre>';
	}

/**
 * Render a HTML comment (&lt;!-- --&gt;) element.
 *
 * @param	string	$item	Enclosed content
 * @return	string	HTML
 */
	function comment($item)
	{
		return '<!-- '.$item.' -->';
	}

/**
 * Render a &lt;small&gt element.
 *
 * @param	string	$item	Enclosed content
 * @return	string	HTML
 */
	function small($item)
	{
		return tag($item,'small');
	}

/**
 * Render a table data row from an array of content => width pairs.
 *
 * @param	array	$array	Array of content => width pairs
 * @param	string	$atts	Table row atrributes
 * @return	string	HTML
 */
	function assRow($array, $atts ='')
	{
		foreach($array as $a => $b) $o[] = tda($a,' width="'.$b.'"');
		return tr(join(n.t,$o), $atts);
	}

/**
 * Render a table head row from an array of strings.
 *
 * @param	array	$value,...	Array of head text strings. L10n is applied to the strings.
 * @return	string	HTML
 */
	function assHead()
	{
		$array = func_get_args();
		foreach($array as $a) $o[] = hCell(gTxt($a));
		return tr(join('',$o));
	}

/**
 * Render the ubiquitious popup help button.
 *
 * @param	string	$help_var	Help topic
 * @param	integer	$width	Popup window width
 * @param	integer	$height	Popup window height
 * @return	string	HTML
 */
	function popHelp($help_var, $width = '', $height = '')
	{
		return '<a target="_blank"'.
			' href="http://rpc.textpattern.com/help/?item='.$help_var.a.'language='.LANG.'"'.
			' onclick="popWin(this.href'.
			($width ? ', '.$width : '').
			($height ? ', '.$height : '').
			'); return false;" class="pophelp">?</a>';
	}

/**
 * Render the ubiquitious popup help button with a little less visual noise.
 *
 * @param	string	$help_var	Help topic
 * @param	integer	$width	Popup window width ['']
 * @param	integer	$height	Popup window height ['']
 * @return	string	HTML
 */
	function popHelpSubtle($help_var, $width = '', $height = '')
	{
		return '<a target="_blank"'.
			' href="http://rpc.textpattern.com/help/?item='.$help_var.a.'language='.LANG.'"'.
			' onclick="popWin(this.href'.
			($width ? ', '.$width : '').
			($height ? ', '.$height : '').
			'); return false;">?</a>';
	}

/**
 * Popup tag help window.
 *
 * @param	string	$var	Tag name
 * @param	string	$text	Link text
 * @param	integer	$width	Popup window width
 * @param	integer	$height	Popup window height
 * @return	string	HTML
 */

	function popTag($var, $text, $width = '', $height = '')
	{
		return '<a target="_blank"'.
			' href="?event=tag'.a.'tag_name='.$var.'"'.
			' onclick="popWin(this.href'.
			($width ? ', '.$width : '').
			($height ? ', '.$height : '').
			'); return false;">'.$text.'</a>';
	}

/**
 * Render tag builder links.
 *
 * @param	string	$type	Tag type
 * @return	string	HTML
 */

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

/**
 * Render admin-side message text.
 *
 * @param	string	$thing	Subject
 * @param	string	$thething	Predicate (strong)
 * @param	string	$action	Object
 * @return	string	HTML
 */
	function messenger($thing, $thething='', $action='')
	{
		return gTxt($thing).' '.strong($thething).' '.gTxt($action);
	}

/**
 * Render a form to select various amounts to page lists by.
 *
 * @param	string	$event	Event
 * @param	integer	$val	Current setting
 * @return	string	HTML
 */

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
		, '', '', 'post', 'pageby');
	}

/**
 * Render a file upload form via the "$event_ui" > "upload_form" pluggable UI.
 *
 * @param	string	$label	File name label
 * @param	string	$pophelp	Help item
 * @param	string	$step	Step
 * @param	string	$event	Event
 * @param	string	$id	File id
 * @param	integer	$max_file_size	Maximum allowed file size
 * @param	string	$label_id	HTML id attribute for the filename input element
 * @param	string	$class	HTML class attribute for the form element
 */

	function upload_form($label, $pophelp, $step, $event, $id = '', $max_file_size = '1000000', $label_id = '', $class = 'upload-form')
	{
		global $sort, $dir, $page, $search_method, $crit;

		extract(gpsa(array('page', 'sort', 'dir', 'crit', 'search_method')));

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
			n.tInput().
			n.'</form>',
			$argv);
	}

/**
 * Render a admin-side search form.
 *
 * @param	string	$event	Event
 * @param	string	$step	Step
 * @param	string	$crit	Search criterion
 * @param	array	$methods	Valid search methods
 * @param	string	$method	Actual search method
 * @param	string	$default_method	Default search method
 * @return	string	HTML
 */

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

/**
 * Render a dropdown for selecting text filter method preferences.
 *
 * @param	string	$name	Element name
 * @param	string	$val	Current value
 * @param	string	$id	HTML id attribute for the select input element
 * @return	string	HTML
 */

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

/**
 * Attach a HTML fragment to a DOM node.
 *
 * @param	string	$id	Target DMO node's id
 * @param	string	$content	HTML fragment
 * @param	string	$noscript	noscript alternative	fragment ['']
 * @param	string	$wraptag	Wrapping HTML element
 * @param	string	$wraptagid	Wrapping element's HTML id
 * @return	string	HTML/JS
 */

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


/**
 * Render a &lt:script&gt; element.
 *
 * @param	string	$js	JavaScript code
 * @param	string	$noscript	noscript alternative
 * @return	string	HTML with embedded script element
 */

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


/**
 * Render a "Details" toggle checkbox.
 *
 * @param	string	$classname	Unique identfier. The cookie's name will be derived from this value.
 * @param	boolean	$form		Create as a stand-along &lt;form&gt; element [false]
 * @return	string	HTML
 */

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

/**
 * Render a checkbox to set/unset a browser cookie.
 *
 * @param	string	$classname	Label text. The cookie's name will be derived from this value.
 * @param	boolean	$form		Create as a stand-along &lt;form&gt; element [true]
 * @return	string	HTML
 */

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
			return '<form class="'.$name.'" method="post" action="index.php'.$args.'">'.$i.eInput(gps('event')).n.'<noscript><div><input type="submit" value="'.gTxt('go').'" /></div></noscript>'.tInput().'</form>';
		} else {
			return n.$i;
		}
	}


/**
 * Render a &lt;fieldset&gt; element.
 *
 * @param	string	$content	Enclosed content
 * @param	string	$legend	Legend text ['']
 * @param	string	$id		HTML id attribute ['']
 * @return	string	HTML
 */

	function fieldset($content, $legend='', $id='') {
		$a_id = ($id ? ' id="'.$id.'"' : '');
		return tag(trim(tag($legend, 'legend').n.$content), 'fieldset', $a_id);
	}

?>
