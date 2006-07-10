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

	if (!defined('txpinterface'))
	{
		die('txpinterface is undefined.');
	}

	global $vars;

	if ($event == 'link')
	{	
		require_privs('link');		

		$vars = array('category', 'url', 'linkname', 'linksort', 'description', 'id');

		$available_steps = array(
			'link_list', 
			'link_edit', 
			'link_post', 
			'link_save', 
			'link_delete', 
			'link_change_pageby', 
			'link_multi_edit'
		);

		if (!$step or !function_exists($step) or !in_array($step, $available_steps))
		{
			link_edit();
		}

		else
		{
			$step();
		}
	}

// -------------------------------------------------------------

	function link_list($message = '') 
	{
		global $step, $link_list_pageby;

		extract(get_prefs());

		extract(gpsa(array('page', 'sort', 'dir', 'crit', 'search_method')));

		$dir = ($dir == 'desc') ? 'desc' : 'asc';

		switch ($sort)
		{
			case 'id':
				$sort_sql = 'id '.$dir;
			break;

			case 'name':
				$sort_sql = 'linksort '.$dir.', id asc';
			break;

			case 'description':
				$sort_sql = 'description '.$dir.', id asc';
			break;

			case 'category':
				$sort_sql = 'category '.$dir.', id asc';
			break;

			case 'date':
				$sort_sql = 'date '.$dir.', id asc';
			break;

			default:
				$dir = 'asc';
				$sort_sql = 'linksort asc';
			break;
		}

		$switch_dir = ($dir == 'desc') ? 'asc' : 'desc';

		$criteria = 1;

		if ($crit or $search_method)
		{
			$crit_escaped = doSlash($crit);

			$critsql = array(
				'id'			     => "id = '$crit_escaped'",
				'name'		     => "linkname like '%$crit_escaped%'",
				'description'	 => "description like '%$crit_escaped%'",
				'category'     => "category like '%$crit_escaped%'"
			);

			if (array_key_exists($search_method, $critsql))
			{
				$criteria = $critsql[$search_method];
				$limit = 500;
			}

			else
			{
				$search_method = '';
			}
		}

		$total = getCount('txp_link', "$criteria");  

		if ($total < 1)
		{
			if ($criteria != 1)
			{
				echo n.link_search_form($crit, $search_method).
					n.graf(gTxt('no_results_found'), ' style="text-align: center;"');
			}

			else
			{
				echo n.graf(gTxt('no_links_recorded'), ' style="text-align: center;"');
			}

			return;
		}

		$limit = max(@$link_list_pageby, 15);

		list($page, $offset, $numPages) = pager($total, $limit, $page);

		echo link_search_form($crit, $search_method);

		$rs = safe_rows_start('*, unix_timestamp(date) as uDate', 'txp_link', "$criteria order by $sort_sql limit $offset, $limit");

		if ($rs)
		{
			echo n.n.'<form action="index.php" method="post" name="longform" onsubmit="return verify(\''.gTxt('are_you_sure').'\')">',

				startTable('list').

				n.tr(
					column_head('ID', 'id', 'link', true, $switch_dir, $crit, $search_method).
					hCell().
					column_head('link_name', 'name', 'link', true, $switch_dir, $crit, $search_method).
					column_head('description', 'description', 'link', true, $switch_dir, $crit, $search_method).
					column_head('link_category', 'category', 'link', true, $switch_dir, $crit, $search_method).
					column_head('date', 'date', 'link', true, $switch_dir, $crit, $search_method).
					hCell()
				);

				while ($a = nextRow($rs))
				{
					extract($a);				

					$edit_url = '?event=link'.a.'step=link_edit'.a.'id='.$id.a.'sort='.$sort.
						a.'dir='.$dir.a.'page='.$page.a.'search_method='.$search_method.a.'crit='.$crit;

					echo tr(

						n.td($id, 20).

						td(
							n.'<ul>'.
							n.t.'<li>'.href(gTxt('edit'), $edit_url).'</li>'.
							n.t.'<li>'.href(gTxt('view'), $url).'</a></li>'.
							n.'</ul>'
						, 35).

						td(
							href($linkname, $edit_url)
						, 125).

						td(
							$description
						, 150).

						td(
							'<span title="'.fetch_category_title($category, 'link').'">'.$category.'</span>'
						, 125).

						td(
							safe_strftime('%d %b %Y %I:%M %p', $uDate)
						, 75).

						td(
							fInput('checkbox', 'selected[]', $id)
						)
					);
				}

			echo n.n.tr(
				tda(
					select_buttons().
					link_multiedit_form($page, $sort, $dir, $crit, $search_method)
				, ' colspan="7" style="text-align: right; border: none;"')
			).

			endTable().
			'</form>'.

			n.nav_form('link', $page, $numPages, $sort, $dir, $crit, $search_method).

			pageby_form('link', $link_list_pageby);
		}
	}

// -------------------------------------------------------------

	function link_search_form($crit, $method)
	{
		$methods =	array(
			'id'					=> gTxt('ID'),
			'name'				=> gTxt('link_name'),
			'description' => gTxt('description'),
			'category'		=> gTxt('link_category')
		);

		return search_form('link', 'link_edit', $crit, $methods, $method, 'name');
	}

// -------------------------------------------------------------

	function link_edit($message = '')
	{
		global $vars, $step;

		pagetop(gTxt('edit_links'), $message);

		extract(gpsa($vars));

		if ($id && $step == 'link_edit')
		{
			extract(safe_row('*', 'txp_link', "id = $id"));
		}
		
		if ($step == 'link_save' or $step == 'link_post')
		{
			foreach ($vars as $var)
			{
				$$var = '';
			}
		}

		echo form(

			startTable('edit') .

			tr(
				fLabelCell('title').
				fInputCell('linkname', $linkname, 1, 30)
			).

			tr(
				fLabelCell('sort_value').
				fInputCell('linksort', $linksort, 2, 15 )
			).

			tr(
				fLabelCell('url','link_url').
				fInputCell('url', $url, 3, 30)
			).

			tr(
				fLabelCell('link_category', 'link_category').

				td(
					linkcategory_popup($category).
					' ['.eLink('category', 'list', '', '', gTxt('edit')).']'
				)
			) .

			tr(
				tda(
					gTxt('description').' '.popHelp('link_description')
				,' style="text-align: right; vertical-align: top;"').

				td(
					'<textarea name="description" cols="40" rows="7" tabindex="4">'.$description.'</textarea>'
				)
			).

			tr(
				td().
				td(
					fInput('submit', '', gTxt('save'), 'publish')
				)
			).

			endTable().

			eInput('link').
			sInput( ($step == 'link_edit' ? 'link_save' : 'link_post') ).
			hInput('id', $id)
		, 'margin-bottom: 25px;');

		echo link_list();
	}

//--------------------------------------------------------------
	function linkcategory_popup($cat="") 
	{
		return event_category_popup("link", $cat);		
	}

// -------------------------------------------------------------
	function link_post()
	{
		global $txpcfg,$prefs,$vars;
		$varray = gpsa($vars);

		if($prefs['textile_links']) {

			include_once txpath.'/lib/classTextile.php';
			$textile = new Textile();
		
			$varray['linkname'] = $textile->TextileThis($varray['linkname'],'',1);
			$varray['description'] = $textile->TextileThis($varray['description'],1);
	
		}
	
		extract(doSlash($varray));

		if (!$linksort) $linksort = $linkname;

		$q = safe_insert("txp_link",
		   "category    = '$category',
			date        = now(),
			url         = '".trim($url)."',
			linkname    = '$linkname',
			linksort    = '$linksort',
			description = '$description'"
		);

		if ($q) {
			//update lastmod due to link feeds
			safe_update("txp_prefs", "val = now()", "name = 'lastmod'");
			
			link_edit(messenger('link',$linkname,'created'));			
		}
	}

// -------------------------------------------------------------
	function link_save() 
	{
		global $txpcfg,$prefs,$vars;
		$varray = gpsa($vars);

		if($prefs['textile_links']) {

			include_once txpath.'/lib/classTextile.php';
			$textile = new Textile();
			
			$varray['linkname'] = $textile->TextileThis($varray['linkname'],'',1);
			$varray['description'] = $textile->TextileThis($varray['description'],1);
		
		}
		
		extract(doSlash($varray));
		
		if (!$linksort) $linksort = $linkname;

		$rs = safe_update("txp_link",
		   "category    = '$category',
			url         = '".trim($url)."',
			linkname    = '$linkname',
			linksort    = '$linksort',
			description = '$description'",
		   "id = '$id'"
		);
		if ($rs) link_edit( messenger( 'link', doStrip($linkname), 'saved' ) );
	}

// -------------------------------------------------------------
	function link_delete() 
	{
		$id = ps('id');
		$rs = safe_delete("txp_link", "id=$id");
		if ($rs) link_edit(messenger('link', '', 'deleted'));
	}

// -------------------------------------------------------------
	function link_change_pageby() 
	{
		event_change_pageby('link');
		link_edit();
	}

// -------------------------------------------------------------

	function link_multiedit_form($page, $sort, $dir, $crit, $search_method) 
	{
		$methods = array(
			'delete' => gTxt('delete')
		);

		return event_multiedit_form('link', $methods, $page, $sort, $dir, $crit, $search_method);
	}

// -------------------------------------------------------------
	function link_multi_edit() 
	{
		$deleted = event_multi_edit('txp_link','id');
		if(!empty($deleted)) return link_edit(messenger('link',$deleted,'deleted'));
		return link_edit();
	}

?>
