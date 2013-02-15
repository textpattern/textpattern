<?php

/*
 * Textpattern Content Management System
 * http://textpattern.com
 *
 * Copyright (C) 2012 The Textpattern Development Team
 *
 * This file is part of Textpattern.
 *
 * Textpattern is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation, version 2.
 *
 * Textpattern is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Textpattern. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Collection of HTML widgets.
 *
 * @package HTML
 */

/**
 * A tab character.
 *
 * @var string
 */

	define("t", "\t");

/**
 * A line feed.
 *
 * @var string
 */

	define("n", "\n");

/**
 * A self-closing HTML line-break tag.
 *
 * @var string
 */

	define("br", "<br />");

/**
 * A non-breaking space as a HTML entity.
 *
 * @var string
 */

	define("sp", "&#160;");

/**
 * An ampersand as a HTML entity.
 *
 * @var string
 */

	define("a", "&#38;");

/**
 * Renders the admin-side footer.
 *
 * Theme's footer partial via the "admin_side" > "footer" pluggable UI
 * and send the "admin_side" > "body_end" event.
 */

	function end_page()
	{
		global $txp_user, $event, $app_mode, $theme, $textarray_script;

		if ($app_mode != 'async' && $event != 'tag')
		{
			echo n.'</div><!-- /txp-body -->'.n.'<footer role="contentinfo" class="txp-footer">';
			echo pluggable_ui('admin_side', 'footer', $theme->footer());
			callback_event('admin_side', 'body_end');
			echo script_js('textpattern.textarray = '.json_encode($textarray_script)).
				n.'</footer><!-- /txp-footer -->'.n.'</body>'.n.'</html>';
		}
	}

/**
 * Renders the user interface for one head cell of columnar data.
 *
 * @param  string $value   Element text
 * @param  string $sort    Sort criterion
 * @param  string $event   Event name
 * @param  bool   $is_link Include link to admin action in user interface according to the other params
 * @param  string $dir     Sort direction, either "asc" or "desc"
 * @param  string $crit    Search criterion
 * @param  string $method  Search method
 * @param  string $class   HTML "class" attribute applied to the resulting element
 * @param  string $step    Step name
 * @return string HTML
 */

	function column_head($value, $sort = '', $event = '', $is_link = '', $dir = '', $crit = '', $method = '', $class = '', $step = 'list')
	{
		return column_multi_head(array(array(
			'value'   => $value,
			'sort'    => $sort,
			'event'   => $event,
			'step'    => $step,
			'is_link' => $is_link,
			'dir'     => $dir,
			'crit'    => $crit,
			'method'  => $method,
		)), $class);
	}

/**
 * Renders the user interface for multiple head cells of columnar data.
 *
 * @param  array  $head_items An array of hashed elements. Valid keys: 'value', 'sort', 'event', 'is_link', 'dir', 'crit', 'method'
 * @param  string $class      HTML "class" attribute applied to the resulting element
 * @return string HTML
 */

	function column_multi_head($head_items, $class = '')
	{
		$o = '';
		$first_item = true;

		foreach ($head_items as $item)
		{
			if (empty($item))
			{
				continue;
			}

			extract(lAtts(array(
				'value'   => '',
				'sort'    => '',
				'event'   => '',
				'step'    => 'list',
				'is_link' => '',
				'dir'     => '',
				'crit'    => '',
				'method'  => '',
			), $item));

			$o .= ($first_item) ? '' : ', ';
			$first_item = false;

			if ($is_link)
			{
				$o .= href(gTxt($value), array(
					'event'         => $event,
					'step'          => $step,
					'sort'          => $sort,
					'dir'           => $dir,
					'crit'          => $crit,
					'search_method' => $method,
				), array());
			}
			else
			{
				$o .= gTxt($value);
			}
		}

		return hCell($o, '', array(
			'scope' => 'col',
			'class' => $class,
		));
	}

/**
 * Renders a &lt;th&gt; element.
 *
 * @param  string       $text    Cell text
 * @param  string       $caption Is not used
 * @param  string|array $atts    HTML attributes
 * @return string       HTML
 */

	function hCell($text = '', $caption = '', $atts = '')
	{
		$text = ('' === $text) ? sp : $text;
		return n.tag($text, 'th', $atts);
	}

/**
 * Renders a link invoking an admin-side action.
 *
 * @param  string $event    Event
 * @param  string $step     Step
 * @param  string $linktext Link text
 * @param  string $class    HTML class attribute for link
 * @return string HTML
 */

	function sLink($event, $step, $linktext, $class = '')
	{
		if ($linktext === '')
		{
			$linktext = null;
		}

		return href($linktext, array(
			'event' => $event,
			'step'  => $step,
		), array('class' => $class));
	}

/**
 * Renders a link with two additional URL parameters.
 *
 * Renders a link invoking an admin-side action
 * while taking up to two additional URL parameters.
 *
 * @param  string $event    Event
 * @param  string $step     Step
 * @param  string $thing    URL parameter key #1
 * @param  string $value    URL parameter value #1
 * @param  string $linktext Link text
 * @param  string $thing2   URL parameter key #2
 * @param  string $val2     URL parameter value #2
 * @param  string $title    Anchor title
 * @return string HTML
 */

	function eLink($event, $step, $thing, $value, $linktext, $thing2 = '', $val2 = '', $title = 'edit')
	{
		if ($title)
		{
			$title = gTxt($title);
		}

		if ($linktext === '')
		{
			$linktext = null;
		}
		else
		{
			$linktext = escape_title($linktext);
		}

		return href($linktext, array(
			'event'      => $event,
			'step'       => $step,
			$thing       => $value,
			$thing2      => $val2,
			'_txp_token' => form_token(),
		), array(
			'title' => $title,
		));
	}

/**
 * Renders a link with one additional URL parameter.
 * 
 * Renders an link invoking an admin-side action while
 * taking up to one additional URL parameter.
 *
 * @param  string $event Event
 * @param  string $step  Step
 * @param  string $thing URL parameter key
 * @param  string $value URL parameter value
 * @return string HTML
 */

	function wLink($event, $step = '', $thing = '', $value = '')
	{
		return href(sp.'!'.sp, array(
			'event'      => $event,
			'step'       => $step,
			$thing       => $value,
			'_txp_token' => form_token(),
		), array('class' => 'dlink'));
	}

/**
 * Renders a delete link.
 *
 * Renders a link invoking an admin-side "delete" action
 * while taking up to two additional URL parameters.
 *
 * @param  string $event     Event
 * @param  string $step      Step
 * @param  string $thing     URL parameter key #1
 * @param  string $value     URL parameter value #1
 * @param  string $verify    Show an "Are you sure?" dialogue with this text
 * @param  string $thing2    URL parameter key #2
 * @param  string $thing2val URL parameter value #2
 * @param  bool   $get       Use GET request [false: Use POST request]
 * @param  array  $remember  Convey URL parameters for page state. Member sequence is $page, $sort, $dir, $crit, $search_method
 * @return string HTML
 */

	function dLink($event, $step, $thing, $value, $verify = '', $thing2 = '', $thing2val = '', $get = '', $remember = null)
	{
		if ($remember)
		{
			list($page, $sort, $dir, $crit, $search_method) = $remember;
		}

		if ($get)
		{
			$url = '?event='.$event.a.'step='.$step.a.$thing.'='.urlencode($value).a.'_txp_token='.form_token();

			if ($thing2)
			{
				$url .= a.$thing2.'='.urlencode($thing2val);
			}

			if ($remember)
			{
				$url .= a.'page='.$page.a.'sort='.$sort.a.'dir='.$dir.a.'crit='.$crit.a.'search_method='.$search_method;
			}

			return join('', array(
				'<a href="'.$url.'" class="dlink destroy" title="'.gTxt('delete').'" data-verify="',
				($verify) ? gTxt($verify) : gTxt('confirm_delete_popup'),
				'">×</a>'
			));
		}

		return join('', array(
			n.'<form method="post" action="index.php" data-verify="'.gTxt('confirm_delete_popup').'">',
			fInput('submit', '', '×', 'destroy', gTxt('delete')),
			eInput($event).
			sInput($step),
			hInput($thing, $value),
			($thing2) ? hInput($thing2, $thing2val) : '',
			($remember) ? hInput('page', $page) : '',
			($remember) ? hInput('sort', $sort) : '',
			($remember) ? hInput('dir', $dir) : '',
			($remember) ? hInput('crit', $crit) : '',
			($remember) ? hInput('search_method', $search_method) : '',
			tInput(),
			n.'</form>'
		));
	}

/**
 * Renders an add link.
 *
 * This function can be used for invoking an admin-side "add" action
 * while taking up to two additional URL parameters.
 *
 * @param  string $event  Event
 * @param  string $step   Step
 * @param  string $thing  URL parameter key #1
 * @param  string $value  URL parameter value #1
 * @param  string $thing2 URL parameter key #2
 * @param  string $value2 URL parameter value #2
 * @return string HTML
 */

	function aLink($event, $step, $thing = '', $value = '', $thing2 = '', $value2 = '')
	{
		return href('+', array(
			'event'      => $event,
			'step'       => $step,
			$thing       => $value,
			$thing2      => $value2,
			'_txp_token' => form_token(),
		), array('class' => 'alink'));
	}

/**
 * Renders a link invoking an admin-side "previous/next article" action.
 *
 * @param  string $name    Link text
 * @param  string $event   Event
 * @param  string $step    Step
 * @param  int    $id      ID of target Textpattern object (article,...)
 * @param  string $titling HTML title attribute
 * @param  string $rel     HTML rel attribute
 * @return string HTML
 */

	function prevnext_link($name, $event, $step, $id, $titling = '', $rel = '')
	{
		return '<a href="?event='.$event.a.'step='.$step.a.'ID='.$id.
			'" class="navlink"'.($titling ? ' title="'.$titling.'"' : '').($rel ? ' rel="'.$rel.'"' : '').'>'.$name.'</a>';
	}

/**
 * Renders a link invoking an admin-side "previous/next page" action.
 *
 * @param  string $event         Event
 * @param  int    $page          Target page number
 * @param  string $label         Link text
 * @param  string $type          Direction, either "prev" or "next"
 * @param  string $sort          Sort field
 * @param  string $dir           Sort direction, either "asc" or "desc"
 * @param  string $crit          Search criterion
 * @param  string $search_method Search method
 * @param  string $step          Step
 * @return string HTML
 */

	function PrevNextLink($event, $page, $label, $type, $sort = '', $dir = '', $crit = '', $search_method = '', $step = 'list')
	{
		return href($label, array(
			'event'         => $event,
			'step'          => $step,
			'page'          => (int) $page,
			'dir'           => $dir,
			'crit'          => $crit,
			'search_method' => $search_method,
		), array('class' => 'navlink', 'rel' => $type));
	}

/**
 * Renders a page navigation form.
 *
 * @param  string $event         Event
 * @param  int    $page          Current page number
 * @param  int    $numPages	     Total pages
 * @param  string $sort          Sort criterion
 * @param  string $dir           Sort direction, either "asc" or "desc"
 * @param  string $crit          Search criterion
 * @param  string $search_method Search method
 * @param  int    $total	     Total search term hit count [0]
 * @param  int    $limit	     First visible search term hit number [0]
 * @param  string $step	         Step
 * @return string HTML
 */

	function nav_form($event, $page, $numPages, $sort = '', $dir = '', $crit = '', $search_method = '', $total = 0, $limit = 0, $step = 'list')
	{
		if ($crit != '' && $total > 1)
		{
			$out[] = announce(
				gTxt('showing_search_results', array(
					'{from}'  => (($page - 1) * $limit) + 1,
					'{to}'    => min($total, $page * $limit),
					'{total}' => $total,
				)),
				ANNOUNCE_REGULAR
			);
		}

		if ($numPages > 1)
		{
			$option_list = array();

			for ($i = 1; $i <= $numPages; $i++)
			{
				$option_list[$i] = $i.'/'.$numPages;
			}

			$nav = array();

			if ($page > 1)
			{
				$nav[] = PrevNextLink($event, $page - 1, gTxt('prev'), 'prev', $sort, $dir, $crit, $search_method, $step).sp;
			}
			else
			{
				$nav[] = tag(gTxt('prev'), 'span', ' class="navlink-disabled" aria-disabled="true"').sp;	
			}

			$nav[] = selectInput('page', $option_list, $page, false, true);

			if ($page < $numPages)
			{
				$nav[] = sp.PrevNextLink($event, $page + 1, gTxt('next'), 'next', $sort, $dir, $crit, $search_method, $step);
			}
			else
			{
				$nav[] = sp.tag(gTxt('next'), 'span', ' class="navlink-disabled" aria-disabled="true"');
			}

			$out[] = form(
				eInput($event).
				sInput($step).
				($sort ? n.hInput('sort', $sort).n.hInput('dir', $dir) : '' ).
				(($crit != '') ? n.hInput('crit', $crit).hInput('search_method', $search_method) : '').
				graf(join('', $nav), array('class' => 'prev-next')), '', '', 'get', 'nav-form');
		}
		else
		{
			$out[] = graf($page.'/'.$numPages, ' class="prev-next"');
		}

		return join(n, $out);
	}

/**
 * Wraps a collapsible region and group structure around content.
 *
 * @param  string $id        HTML id attribute for the region wrapper and ARIA label
 * @param  string $content   Content to wrap. If empty, only the outer wrapper will be rendered
 * @param  string $anchor_id HTML id attribute for the collapsible wrapper
 * @param  string $label     L10n label name
 * @param  string $pane      Pane reference for maintaining toggle state in prefs. Prefixed with 'pane_', suffixed with '_visible'
 * @param  string $class     CSS class name to apply to wrapper
 * @param  string $role      ARIA role name
 * @param  string $help      Help text item
 * @return string HTML
 * @since  4.6.0
 */

	function wrapRegion($id, $content = '', $anchor_id = '', $label = '', $pane = '', $class = '', $role = 'region', $help = '')
	{
		$label = $label ? gTxt($label) : null;

		if ($anchor_id && $pane)
		{
			$visible = get_pref('pane_'.$pane.'_visible');
			$heading_class = 'txp-summary' . ($visible ? ' expanded' : '');
			$display_state = array(
				'role'  => 'group',
				'id'    => $anchor_id,
				'class' => 'toggle',
				'style' => $visible ? 'display: block' : 'display: none',
			);

			$label = href($label, '#'.$anchor_id, array('role' => 'button'));
			$help = '';
		}
		else
		{
			$heading_class = '';
			$display_state = array(
				'role' => $role == 'region' ? 'group' : ''
			);
		}

		if ($content)
		{
			$content =
				hed($label.popHelp($help), 3, array(
					'id'    => $id.'-label',
					'class' => $heading_class
				)).
				n.tag($content.n, 'div', $display_state).n;
		}

		return n.tag($content, 'section', array(
			'role'            => $role,
			'id'              => $id,
			'class'           => trim('txp-details '.$class),
			'aria-labelledby' => $content ? $id.'-label' : '',
		));
	}

/**
 * Wraps a region and group structure around content.
 *
 * @param  string $name    HTML id attribute for the group wrapper and ARIA label
 * @param  string $content Content to wrap
 * @param  string $label   L10n label name
 * @param  string $class   CSS class name to apply to wrapper
 * @param  string $help    Help text item
 * @return string HTML
 * @see    wrapRegion()
 * @since  4.6.0
 */

	function wrapGroup($id, $content, $label, $class = '', $help = '')
	{
		return wrapRegion($id, $content, '', $label, '', $class, 'region', $help);
	}

/**
 * Renders start of a layout &lt;table&gt; element.
 *
 * @return     string HTML
 * @deprecated in 4.4.0
 */

	function startSkelTable()
	{
		return
		'<table width="300" cellpadding="0" cellspacing="0" style="border:1px #ccc solid">';
	}

/**
 * Renders start of a layout &lt;table&gt; element.
 *
 * @param  string $id    HTML id attribute
 * @param  string $align HTML align attribute
 * @param  string $class HTML class attribute
 * @param  int    $p     HTML cellpadding attribute
 * @param  int    $w     HTML width atttribute
 * @return string HTML
 * @example
 * startTable().
 * tr(td('column') . td('column')).
 * tr(td('column') . td('column')).
 * endTable();
 */

	function startTable($id = '', $align = '', $class = '', $p = 0, $w = 0)
	{
		$atts = join_atts(array(
			'id'          => $id,
			'align'       => $align,
			'class'       => $class,
			'cellpadding' => (int) $p,
			'width'       => (int) $w,
		));

		return n.'<table'.$atts.'>';
	}

/**
 * Renders closing &lt;/table&gt; tag.
 *
 * @return string HTML
 */

	function endTable()
	{
		return n.'</table>';
	}

/**
 * Renders &lt;tr&gt; elements from input parameters.
 *
 * Takes a list of arguments containing each making a row.
 *
 * @return string HTML
 * @example
 * stackRows(
 * 	td('cell') . td('cell'),
 *  td('cell') . td('cell')
 * );
 */

	function stackRows()
	{
		foreach (func_get_args() as $a)
		{
			$o[] = tr($a);
		}

		return join('', $o);
	}

/**
 * Renders a &lt;td&gt; element.
 *
 * @param  string $content Cell content
 * @param  int    $width   HTML width attribute
 * @param  string $class   HTML class attribute
 * @param  string $id      HTML id attribute
 * @return string HTML
 */

	function td($content = '', $width = 0, $class = '', $id = '')
	{
		return tda($content, array(
			'width' => (int) $width,
			'class' => $class,
			'id'    => $id,
		));
	}

/**
 * Renders a &lt;td&gt; element with attributes.
 *
 * @param  string       $content Cell content
 * @param  string|array $atts    Cell attributes
 * @return string       HTML
 */

	function tda($content, $atts = '')
	{
		$content = ($content === '') ? sp : $content;
		return n.tag($content, 'td', $atts);
	}

/**
 * Renders a &lt;td&gt; element with attributes.
 *
 * This function is identical to tda().
 *
 * @param  string       $content Cell content
 * @param  string|array $atts    Cell attributes
 * @return string       HTML
 * @access private
 * @see    tda()
 */

	function tdtl($content, $atts = '')
	{
		return tda($content, $atts);
	}

/**
 * Renders a &lt;tr&gt; element with attributes.
 *
 * @param  string       $content Row content
 * @param  string|array $atts    Row attributes
 * @return string       HTML
 */

	function tr($content, $atts = '')
	{
		return n.tag($content, 'tr', $atts);
	}

/**
 * Renders a &lt;td&gt; element with top/left text orientation, colspan and other attributes.
 *
 * @param  string $content Cell content
 * @param  int    $span    Cell colspan attribute
 * @param  int    $width   Cell width attribute
 * @param  string $class   Cell class attribute
 * @return string HTML
 */

	function tdcs($content, $span, $width = 0, $class = '')
	{
		return tda($content, array(
			'colspan' => (int) $span,
			'width'   => (int) $width,
			'class'   => $class,
		));
	}

/**
 * Renders a &lt;td&gt; element with a rowspan attribute.
 *
 * @param  string $content Cell content
 * @param  int    $span    Cell rowspan attribute
 * @param  int    $width   Cell width attribute
 * @param  string $class   Cell class attribute 
 * @return string HTML
 */

	function tdrs($content, $span, $width = 0, $class = '')
	{
		return tda($content, array(
			'rowspan' => (int) $span,
			'width'   => (int) $width,
			'class'   => $class,
		));
	}

/**
 * Renders a form label inside a table cell.
 *
 * @param  string $text     Label text
 * @param  string $help     Help text
 * @param  string $label_id HTML "for" attribute, i.e. id of corresponding form element
 * @return string HTML
 */

	function fLabelCell($text, $help = '', $label_id = '')
	{
		$cell = gTxt($text).' '.popHelp($help);

		if ($label_id)
		{
			$cell = tag($cell, 'label', array('for' => $label_id));
		}

		return tda($cell, array('class' => 'cell-label'));
	}

/**
 * Renders a form input inside a table cell.
 *
 * @param  string $name     HTML name attribute
 * @param  string $var      Input value
 * @param  int    $tabindex HTML tabindex attribute
 * @param  int    $size     HTML size attribute
 * @param  bool   $help     TRUE to display help link
 * @param  string $id       HTML id attribute
 * @return string HTML
 */

	function fInputCell($name, $var = '', $tabindex = 0, $size = 0, $help = false, $id = '')
	{
		$pop = ($help) ? popHelp($name) : '';

		return tda(fInput('text', $name, $var, '', '', '', $size, $tabindex, $id).$pop);
	}

/**
 * Renders a name-value input control with label.
 *
 * The rendered input can be customised via the '{$event}_ui > inputlabel.{$name}'
 * pluggable UI callback event.
 *
 * @param  string $name        Input name
 * @param  string $input       Complete input control widget
 * @param  string $label       Label
 * @param  string $help        Help text item
 * @param  string $class       CSS class name to apply to wrapper
 * @param  string $wraptag_val Tag to wrap the value in, or empty string to omit
 * @return string HTML
 * @example
 * echo inputLabel('active', yesnoRadio('active'), 'Keep active?');
 */

	function inputLabel($name, $input, $label = '', $help = '', $class = '', $wraptag_val = 'span')
	{
		global $event;

		$arguments = compact('name', 'input', 'label', 'help', 'class', 'wraptag_val');

		if (!$class)
		{
			$class = 'edit-'.str_replace('_', '-', $name);
		}

		if ($label)
		{
			$label = tag(gTxt($label), 'label', array('for' => $name));
		}
		else
		{
			$label = gTxt($name);
		}

		if ($wraptag_val)
		{
			$input = tag($input, $wraptag_val, array('class' => 'edit-value'));
		}

		$out = graf(
			tag($label.popHelp($help), 'span', array('class' => 'edit-label')).
			n.$input
		, array('class' => $class));

		return pluggable_ui($event.'_ui', 'inputlabel.'.$name, $out, $arguments);
	}

/**
 * Renders anything as an XML element.
 *
 * @param  string       $content Enclosed content
 * @param  string       $tag     The tag without brackets
 * @param  string|array $atts    The element's HTML attributes
 * @return string       HTML
 * @example
 * echo tag('Link text', 'a', array('href' => '#', 'class' => 'warning'));
 */

	function tag($content, $tag, $atts = '')
	{
		return ('' !== $content) ? '<'.$tag.join_atts($atts).'>'.$content.'</'.$tag.'>' : '';
	}

/**
 * Renders anything as a HTML void element.
 *
 * @param  string       $tag  The tag without brackets
 * @param  string|array $atts HTML attributes
 * @return string       HTML
 * @since  4.6.0
 * @example
 * echo tag_void('input', array('name' => 'name', 'type' => 'text'));
 */

	function tag_void($tag, $atts = '')
	{
		return '<'.$tag.join_atts($atts).' />';
	}

/**
 * Renders anything as a HTML start tag.
 *
 * @param  string       $tag  The tag without brackets
 * @param  string|array $atts HTML attributes
 * @return string       A HTML start tag
 * @since  4.6.0
 * @example
 * echo tag_start('section', array('class' => 'myClass'));
 */

	function tag_start($tag, $atts = '')
	{
		return '<'.$tag.join_atts($atts).'>';
	}

/**
 * Renders anything as a HTML end tag.
 *
 * @param  string       $tag  The tag without brackets
 * @return string       A HTML end tag
 * @since  4.6.0
 * @example
 * echo tag_end('section');
 */

	function tag_end($tag)
	{
		return '</'.$tag.'>';
	}

/**
 * Renders a &lt;p&gt; element.
 *
 * @param  string       $item Enclosed content
 * @param  string|array $atts HTML attributes
 * @return string       HTML
 * @example
 * echo graf('This a paragraph.');
 */

	function graf($item, $atts = '')
	{
		return n.tag($item, 'p', $atts);
	}

/**
 * Renders a &lt;hx&gt; element.
 *
 * @param  string       $item  The Enclosed content
 * @param  int          $level Heading level 1...6
 * @param  string|array $atts  HTML attributes
 * @return string       HTML
 * @example
 * echo hed('Heading', 2);
 */

	function hed($item, $level, $atts = '')
	{
		return n.tag($item, 'h'.$level, $atts);
	}

/**
 * Renders an &lt;a&gt; element.
 *
 * @param  string       $item Enclosed content
 * @param  string|array $href The link target
 * @param  string|array $atts HTML attributes
 * @return string       HTML
 */

	function href($item, $href, $atts = '')
	{
		if (is_array($atts))
		{
			$atts['href'] = $href;
		}
		else
		{
			if (is_array($href))
			{
				$href = join_qs($href);
			}

			$atts .= ' href="'.$href.'"';
		}

		return tag($item, 'a', $atts);
	}

/**
 * Renders a &lt;strong&gt; element.
 *
 * @param  string       $item Enclosed content
 * @param  string|array $atts HTML attributes
 * @return string       HTML
 */

	function strong($item, $atts = '')
	{
		return tag($item, 'strong', $atts);
	}

/**
 * Renders a &lt;span&gt; element.
 *
 * @param  string       $item Enclosed content
 * @param  string|array $atts HTML attributes
 * @return string       HTML
 */

	function span($item, $atts = '')
	{
		return tag($item, 'span', $atts);
	}

/**
 * Renders a &lt;pre&gt; element.
 *
 * @param  string       $item The input string
 * @param  string|array $atts HTML attributes
 * @return string HTML
 * @example
 * echo htmlPre('&lt;?php echo "Hello World"; ?&gt;');
 */

	function htmlPre($item, $atts = '')
	{
		if (($item = tag($item, 'code')) === '')
		{
			$item = null;
		}

		return tag($item, 'pre', $atts);
	}

/**
 * Renders a HTML comment (&lt;!-- --&gt;) element.
 *
 * @param  string $item The input string
 * @return string HTML
 * @example
 * echo comment('Some HTML comment.');
 */

	function comment($item)
	{
		return '<!-- '.str_replace('--', '&shy;&shy;', $item).' -->';
	}

/**
 * Renders a &lt;small&gt element.
 *
 * @param  string       $item The input string
 * @param  string|array $atts HTML attributes
 * @return string       HTML
 */

	function small($item, $atts = '')
	{
		return tag($item, 'small', $atts);
	}

/**
 * Renders a table data row from an array of content => width pairs.
 *
 * @param  array        $array Array of content => width pairs
 * @param  string|array $atts  Table row atrributes
 * @return string       A HTML table row
 */

	function assRow($array, $atts = '')
	{
		$out = array();

		foreach ($array as $value => $width)
		{
			$out[] = tda($value, array('width' => (int) $width));
		}

		return tr(join('', $out), $atts);
	}

/**
 * Renders a table head row from an array of strings.
 *
 * Takes an argument list of head text strings. i18n is applied to the strings.
 *
 * @return string HTML
 */

	function assHead()
	{
		$array = func_get_args();
		$o = array();

		foreach ($array as $a)
		{
			$o[] = hCell(gTxt($a), '', ' scope="col"');
		}

		return tr(join('', $o));
	}

/**
 * Renders the ubiquitious popup help button.
 *
 * The rendered link can be customised via a 'admin_help > {$help_var}'
 * pluggable UI callback event.
 *
 * @param  string $help_var Help topic
 * @param  int    $width    Popup window width
 * @param  int    $height   Popup window height
 * @param  string $class    HTML class
 * @return string HTML
 */

	function popHelp($help_var, $width = 0, $height = 0, $class = 'pophelp')
	{
		if (!$help_var)
		{
			return '';
		}

		$ui = sp.href('?', HELP_URL.'?item='.urlencode($help_var).'&language='.urlencode(LANG), array(
			'role'       => 'button',
			'rel'        => 'help',
			'target'     => '_blank',
			'onclick'    => 'popWin(this.href, '.intval($width).', '.intval($height).'); return false;',
			'class'      => $class,
			'title'      => gTxt('help'),
			'aria-label' => gTxt('help'),
		));

		return pluggable_ui('admin_help', $help_var, $ui, compact('help_var', 'width', 'height', 'class'));
	}

/**
 * Renders the ubiquitious popup help button with a little less visual noise.
 *
 * The rendered link can be customised via a 'admin_help > {$help_var}'
 * pluggable UI callback event.
 *
 * @param  string $help_var Help topic
 * @param  int    $width    Popup window width
 * @param  int    $height   Popup window height
 * @return string HTML
 */

	function popHelpSubtle($help_var, $width = 0, $height = 0)
	{
		return popHelp($help_var, $width, $height, 'pophelpsubtle');
	}

/**
 * Renders a link that opens a popup tag help window.
 *
 * @param  string $var    Tag name
 * @param  string $text   Link text
 * @param  int    $width  Popup window width
 * @param  int    $height Popup window height
 * @return string HTML
 */

	function popTag($var, $text, $width = 0, $height = 0)
	{
		return href($text, array(
			'event'    => 'tag',
			'tag_name' => $var,
		), array(
			'target'  => '_blank',
			'onclick' => 'popWin(this.href, '.intval($width).', '.intval($height).'); return false;',
		));
	}

/**
 * Renders a list of tag builder links.
 *
 * @param  string $type Tag type
 * @return string HTML
 */

	function popTagLinks($type)
	{
		include txpath.'/lib/taglib.php';

		$arname = $type.'_tags';

		$out = array();

		foreach ($$arname as $a)
		{
			$out[] = tag(popTag($a, gTxt('tag_'.$a)), 'li');
		}

		return n.tag(n.join(n, $out).n, 'ul', array('class' => 'plain-list'));
	}

/**
 * Renders an admin-side message text.
 *
 * @param  string $thing    Subject
 * @param  string $thething Predicate (strong)
 * @param  string $action   Object
 * @return string HTML
 */

	function messenger($thing, $thething = '', $action = '')
	{
		return gTxt($thing).' '.strong($thething).' '.gTxt($action);
	}

/**
 * Renders a multi-edit form listing editing methods.
 *
 * @param  array   $options       array('value' => array( 'label' => '', 'html' => '' ),...)
 * @param  string  $event         Event
 * @param  string  $step          Step
 * @param  int     $page          Page number
 * @param  string  $sort          Column sorted by
 * @param  string  $dir           Sorting direction
 * @param  string  $crit          Search criterion
 * @param  string  $search_method Search method
 * @return string  HTML
 * @example
 * echo form(
 * 	multi_edit(array(
 * 		'feature' => array('label' => 'Feature', 'html' => yesnoRadio('is_featured', 1)),
 * 		'delete'  => array('label' => 'Delete'),
 * 	))
 * );
 */

	function multi_edit($options, $event = null, $step = null, $page = '', $sort = '', $dir = '', $crit = '', $search_method = '')
	{
		$html = $methods = array();
		$methods[''] = gTxt('with_selected_option');

		if ($event === null)
		{
			global $event;
		}

		if ($step === null)
		{
			$step = $event.'_multi_edit';
		}

		callback_event_ref($event.'_ui', 'multi_edit_options', 0, $options);

		foreach ($options as $value => $option)
		{
			if (is_array($option))
			{
				$methods[$value] = $option['label'];

				if (isset($option['html']))
				{
					$html[$value] = n.tag($option['html'], 'div', array(
						'class'             => 'multi-option',
						'data-multi-option' => $value,
					));
				}
			}
			else
			{
				$methods[$value] = $option;
			}
		}

		return n.tag(
			selectInput('edit_method', $methods, '').
			eInput($event).
			sInput($step).
			hInput('page', $page).
			($sort ? hInput('sort', $sort).hInput('dir', $dir) : '' ).
			($crit !== '' ? hInput('crit', $crit).hInput('search_method', $search_method) : '').
			join('', $html).
			fInput('submit', '', gTxt('go'))
			, 'div', array('class' => 'multi-edit'));
	}

/**
 * Renders a form to select various amounts to page lists by.
 *
 * @param  string      $event Event
 * @param  int         $val   Current setting
 * @param  string|null $step  Step
 * @return string      HTML
 */

	function pageby_form($event, $val, $step = null)
	{
		$vals = array(
			15  => 15,
			25  => 25,
			50  => 50,
			100 => 100,
		);

		if ($step === null)
		{
			$step = $event.'_change_pageby';
		}

		$select_page = selectInput('qty', $vals, $val, '', 1);

		// Proper localisation.
		$page = str_replace('{page}', $select_page, gTxt('view_per_page'));

		return form(
			graf(
				$page.
				eInput($event).
				sInput($step)
			)
		, '', '', 'post', 'pageby');
	}

/**
 * Renders a file upload form.
 *
 * The rendered form can be customised via the '{$event}_ui > upload_form'
 * pluggable UI callback event.
 *
 * @param  string $label         File name label. May be empty
 * @param  string $pophelp       Help item
 * @param  string $step          Step
 * @param  string $event         Event
 * @param  string $id            File id
 * @param  int    $max_file_size Maximum allowed file size
 * @param  string $label_id      HTML id attribute for the filename input element
 * @param  string $class         HTML class attribute for the form element
 * @return string HTML
 */

	function upload_form($label, $pophelp = '', $step, $event, $id = '', $max_file_size = 1000000, $label_id = '', $class = 'upload-form')
	{
		extract(gpsa(array('page', 'sort', 'dir', 'crit', 'search_method')));

		if (!$label_id)
		{
			$p_class = 'edit-'.$event.'-upload';
			$label_id = $event.'-upload';
		}
		else
		{
			$p_class = 'edit-'.str_replace('_', '-', $label_id);
		}

		$argv = func_get_args();
		return pluggable_ui($event.'_ui', 'upload_form',
			n.tag(

			(!empty($max_file_size) ? hInput('MAX_FILE_SIZE', $max_file_size) : '').
			eInput($event).
			sInput($step).
			hInput('id', $id).

			hInput('sort', $sort).
			hInput('dir', $dir).
			hInput('page', $page).
			hInput('search_method', $search_method).
			hInput('crit', $crit).

			graf(
				tag($label, 'label', array('for' => $label_id)).
				popHelp($pophelp).
				fInput('file', 'thefile', '', '', '', '', '', '', $label_id).
				fInput('submit', '', gTxt('upload'))
			, array('class' => $p_class)).

			tInput().n,

			'form', array(
				'class'   => $class,
				'method'  => 'post',
				'enctype' => 'multipart/form-data',
				'action'  => 'index.php',
			)), $argv);
	}

/**
 * Renders an admin-side search form.
 *
 * @param  string $event          Event
 * @param  string $step           Step
 * @param  string $crit           Search criterion
 * @param  array  $methods        Valid search methods
 * @param  string $method         Actual search method
 * @param  string $default_method Default search method
 * @return string HTML
 */

	function search_form($event, $step, $crit, $methods, $method, $default_method)
	{
		$method = ($method) ? $method : $default_method;

		return form(
			graf(
				tag(gTxt('search'), 'label', array('for' => $event.'-search')).
				selectInput('search_method', $methods, $method, '', '', $event.'-search').
				fInput('text', 'crit', $crit, 'input-medium', '', '', INPUT_MEDIUM).
				eInput($event).
				sInput($step).
				fInput('submit', 'search', gTxt('go'))
			)
		, '', '', 'get', 'search-form');
	}

/**
 * Renders a dropdown for selecting text filter method preferences.
 *
 * @param  string $name Element name
 * @param  string $val  Current value
 * @param  string $id   HTML id attribute for the select input element
 * @return string HTML
 */

	function pref_text($name, $val, $id = '')
	{
		$id = ($id) ? $id : $name;
		$vals = Textpattern_Textfilter_Set::map();
		return selectInput($name, $vals, $val, '', '', $id);
	}

/**
 * Attaches a HTML fragment to a DOM node.
 *
 * @param  string $id        Target DOM node's id
 * @param  string $content   HTML fragment
 * @param  string $noscript  Noscript alternative
 * @param  string $wraptag   Wrapping HTML element
 * @param  string $wraptagid Wrapping element's HTML id
 * @return string HTML/JS
 */

	function dom_attach($id, $content, $noscript = '', $wraptag = 'div', $wraptagid = '')
	{
		$content = escape_js($content);

		$js = <<<EOF
			$(document).ready(function ()
			{
				$('#{$id}').append($('<{$wraptag} />').attr('id', '{$wraptagid}').html('{$content}'));
			});
EOF;

		return script_js($js, (string) $noscript);
	}

/**
 * Renders a &lt:script&gt; element.
 *
 * @param  string     $js    JavaScript code
 * @param  int|string $flags Flags SCRIPT_URL | SCRIPT_ATTACH_VERSION, or noscript alternative if a string
 * @return string HTML with embedded script element
 * @example
 * echo script_js('/js/script.js', SCRIPT_URL);
 */

	function script_js($js, $flags = '')
	{
		if (is_int($flags))
		{
			if ($flags & SCRIPT_URL)
			{
				if ($flags & SCRIPT_ATTACH_VERSION && strpos(txp_version, '-dev') === false)
				{
					$ext = pathinfo($js, PATHINFO_EXTENSION);

					if ($ext)
					{
						$js = substr($js, 0, (strlen($ext)+1) * -1);
						$ext = '.'.$ext;
					}

					$js .= '.v'.txp_version.$ext;
				}

				return n.tag(null, 'script', array('src' => $js));
			}
		}

		$js = preg_replace('#<(/?)(script)#i', '\\x3c$1$2', $js);

		$out = n.tag(n.trim($js).n, 'script');

		if ($flags)
		{
			$out .= n.tag(n.trim($flags).n, 'noscript');
		}

		return $out;
	}

/**
 * Renders a "Details" toggle checkbox.
 *
 * @param  string $classname Unique identfier. The cookie's name will be derived from this value
 * @param  bool	  $form      Create as a stand-along &lt;form&gt; element
 * @return string HTML
 */

	function toggle_box($classname, $form = false)
	{
		$name = 'cb_toggle_'.$classname;
		$id = escape_js($name);
		$class = escape_js($classname);

		$out = checkbox($name, 1, cs('toggle_'.$classname), 0, $name).
			n.tag(gTxt('detail_toggle'), 'label', array('for' => $name));

		$js = <<<EOF
			$(document).ready(function ()
			{
				$('input')
					.filter(function ()
					{
						if ($(this).attr('id') === '{$id}')
						{
							setClassDisplay('{$class}', $(this).is(':checked'));
							return true;
						}
					})
					.change(function ()
					{
						toggleClassRemember('{$class}');
					});
			});
EOF;

		$out .= script_js($js);

		if ($form)
		{
			return form($out);
		}

		return $out;
	}

/**
 * Renders a checkbox to set/unset a browser cookie.
 *
 * @param  string $classname Label text. The cookie's name will be derived from this value
 * @param  bool   $form      Create as a stand-along &lt;form&gt; element
 * @return string HTML
 */

	function cookie_box($classname, $form = 1)
	{
		$name = 'cb_'.$classname;
		$val = cs('toggle_'.$classname) ? 1 : 0;

		$i =
			'<input type="checkbox" name="'.$name.'" id="'.$name.'" value="1" '.
			($val ? 'checked="checked" ' : '').
			'class="checkbox" onclick="setClassRemember(\''.$classname.'\','.(1-$val).');submit(this.form);" />'.
			' <label for="'.$name.'">'.gTxt($classname).'</label> ';

		if ($form)
		{
			$args = empty($_SERVER['QUERY_STRING']) ? '' : '?'.txpspecialchars($_SERVER['QUERY_STRING']);

			return '<form class="'.$name.'" method="post" action="index.php'.$args.'">'.$i.eInput(gps('event')).tInput().'</form>';
		}
		else
		{
			return n.$i;
		}
	}

/**
 * Renders a &lt;fieldset&gt; element.
 *
 * @param  string $content Enclosed content
 * @param  string $legend  Legend text
 * @param  string $id      HTML id attribute
 * @return string HTML
 */

	function fieldset($content, $legend = '', $id = '')
	{
		return tag(trim(tag($legend, 'legend').n.$content), 'fieldset', array('id' => $id));
	}

/**
 * Renders a link element to hook up txpAsyncHref() with request parameters.
 *
 * See this function's JavaScript companion, txpAsyncHref(), in textpattern.js.
 *
 * @param  string       $item  Link text
 * @param  array        $parms Request parameters; array keys are 'event', 'step', 'thing', 'property'
 * @param  string|array $atts  HTML attributes
 * @return string HTML
 * @since  4.5.0
 * @example
 * echo asyncHref('Disable', array(
 * 	'event'    => 'myEvent',
 * 	'step'     => 'myStep',
 * 	'thing'    => 'status',
 * 	'property' => 'disable',
 * ));
 */

	function asyncHref($item, $parms, $atts = '')
	{
		global $event, $step;

		$parms = lAtts(array(
			'event'    => $event,
			'step'     => $step,
			'thing'    => '',
			'property' => '',
		), $parms);

		$class = $parms['step'].' async';

		if (is_array($atts))
		{
			$atts['class'] = $class;
		}
		else
		{
			$atts .= ' class="'.txpspecialchars($class).'"';
		}

		return href($item, join_qs($parms), $atts);
	}

/**
 * Renders an array of items as a HTML list.
 *
 * This function is used for tag handler functions.
 * Creates a HTML list markup from an array of items.
 *
 * @param   array  $list
 * @param   string $wraptag    The HTML element
 * @param   string $break      The HTML break element
 * @param   string $class      Class applied to the wraptag
 * @param   string $breakclass Class applied to break tag
 * @param   string $atts       HTML attributes applied to the wraptag
 * @param   string $breakatts  HTML attributes applied to the break tag
 * @param   string $id         HTML id applied to the wraptag
 * @return  string HTML
 * @package HTML
 * @example
 * echo doWrap(array('item1', 'item2'), 'div', 'p');
 */

	function doWrap($list, $wraptag, $break, $class = '', $breakclass = '', $atts = '', $breakatts = '', $id = '')
	{
		if (!$list)
		{
			return '';
		}

		if ($id)
		{
			$atts .= ' id="'.txpspecialchars($id).'"';
		}

		if ($class)
		{
			$atts .= ' class="'.txpspecialchars($class).'"';
		}

		if ($breakclass)
		{
			$breakatts.= ' class="'.txpspecialchars($breakclass).'"';
		}

		// non-enclosing breaks
		if (!preg_match('/^\w+$/', $break) or $break == 'br' or $break == 'hr')
		{
			if ($break == 'br' or $break == 'hr')
			{
				$break = "<$break $breakatts/>".n;
			}

			return ($wraptag) ?	tag(join($break, $list), $wraptag, $atts) :	join($break, $list);
		}

		return ($wraptag)
			? tag(n.tag(join("</$break>".n."<{$break}{$breakatts}>", $list), $break, $breakatts).n, $wraptag, $atts) 
			: tag(n.join("</$break>".n."<{$break}{$breakatts}>".n, $list).n, $break, $breakatts);
	}

/**
 * Renders anything as a HTML tag.
 *
 * Used for tag handler functions.
 *
 * If $content is empty, renders a self-closing
 * tag.
 *
 * @param   string $content The wrapped item
 * @param   string $tag     The HTML tag
 * @param   string $class   HTML class
 * @param   string $atts    HTML attributes
 * @param   string $id      HTML id
 * @return  string HTML
 * @package HTML
 * @example
 * echo doTag('', 'meta', '', 'name="description" content="Some content"');
 */

	function doTag($content, $tag, $class = '', $atts = '', $id = '')
	{
		if ($id)
		{
			$atts .= ' id="'.txpspecialchars($id).'"';
		}

		if ($class)
		{
			$atts .= ' class="'.txpspecialchars($class).'"';
		}

		if (!$tag)
		{
			return $content;
		}

		return ($content) ? tag($content, $tag, $atts) : "<$tag $atts />";
	}

/**
 * Renders a label.
 *
 * This function is mostly used for rendering headings in tag
 * handler functions.
 *
 * If no $labeltag is given, label is separated from the
 * content with a &lt;br&gt;.
 *
 * @param   string $label    The label
 * @param   string $labeltag The HTML element
 * @return  string HTML
 * @package HTML
 * @example
 * echo doLabel('My label', 'h3');
 */

	function doLabel($label = '', $labeltag = '')
	{
		if ($label)
		{
			return (empty($labeltag) ? $label.'<br />' : tag($label, $labeltag));
		}

		return '';
	}
