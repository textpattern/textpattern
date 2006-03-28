<?php
/*
	This is Textpattern
	Copyright 2005 by Dean Allen 
 	All rights reserved.

	Use of this software indicates acceptance of the Textpattern license agreement 

$HeadURL$
$LastChangedRevision$

*/

	if (!defined('txpinterface')) die('txpinterface is undefined.');

	global $statuses;
	$statuses = array(
		1 => gTxt('draft'),
		2 => gTxt('hidden'),
		3 => gTxt('pending'),
		4 => gTxt('live'),
		5 => gTxt('sticky'),
	);

	if ($event=='list') {
		require_privs('article');

		if(!$step or !in_array($step, array('list_change_pageby','list_list','list_multi_edit','list_list'))){
			list_list();
		} else $step();
	}

//--------------------------------------------------------------
	function list_list($message="",$post='')
	{		

		extract(get_prefs());
		$lvars = array("page","sort","dir","crit",'method');
		extract(gpsa($lvars));
		global $statuses,$step;
		$sesutats = array_flip($statuses);
		
		pagetop(gTxt('tab_list'),$message);

		$total = getCount('textpattern',"1"); 
		$limit = max(@$article_list_pageby, 25);
		$numPages = ceil($total/$limit);  
		$page = (!$page) ? 1 : $page;

		$offset = ($page - 1) * $limit;

		if (!$sort) $sort = "Posted";
		if (!$dir) $dir = "desc";
		if ($dir == "desc") { $linkdir = "asc"; } else { $linkdir = "desc"; }

		if ($crit) {	
			$critsql = array(
				'title_body' => "Title rlike '$crit' or Body rlike '$crit'",
				'author'     => "AuthorID rlike '$crit'",
				'categories' => "Category1 rlike '$crit' or Category2 rlike '$crit'",
				'section'    => "Section rlike '$crit'",
				'status'     => "Status = '".(@$sesutats[$crit])."'"
			);
			$criteria = $critsql[$method];
			$limit = 500;
		} else $criteria = 1;
			

		$rs = safe_rows_start(
			"*, unix_timestamp(Posted) as uPosted", 
			"textpattern", 
			"$criteria order by $sort $dir limit $offset, $limit"
		);

		echo (!$crit) ? list_nav_form($page,$numPages,$sort,$dir) : '', list_searching_form($crit,$method);
		
		if ($rs) {
			echo '<form action="index.php" method="post" name="longform" onsubmit="return verify(\''.gTxt('are_you_sure').'\')">',
			startTable('list'),
			'<tr>',
				column_head('posted', 'posted', 'list', 1, $linkdir),
				column_head('title', 'title', 'list', 1, $linkdir),
				column_head('section', 'section', 'list', 1, $linkdir),
				column_head('category1', 'category1', 'list', 1, $linkdir).
				column_head('category2', 'category2', 'list', 1, $linkdir),
				hCell(gTxt('author')),
				column_head(gTxt('status'), 'Status', 'list', 1, $linkdir),
				td(),
			'</tr>';
	
			while ($a = nextRow($rs)) {
				extract($a);
						
				$stat = (!empty($Status)) ? $statuses[$Status] : '';
				$adate = safe_strftime('%d %b %Y', $uPosted);
		
				$alink = eLink('article','edit','ID',$ID,$adate);
				$tlink = eLink('article','edit','ID',$ID,$Title);
				$modbox = fInput('checkbox','selected[]',$ID,'','','','','',$ID);
				
				echo "<tr>".n,
					td($alink),
					td($tlink,200),
					td($Section,75),
					td($Category1,75).td($Category2,75),
					td($AuthorID),
					td($stat,45),
					td($modbox),
				'</tr>'.n;
			}
			
			echo tr(tda(select_buttons().
			list_multiedit_form(),' colspan="8" style="text-align:right;border:0px"'));
			
			echo "</table></form>";
			echo pageby_form('list',$article_list_pageby);
			unset($sort);
		}
	}

// -------------------------------------------------------------
	function list_change_pageby() 
	{
		event_change_pageby('article');
		list_list();
	}

// -------------------------------------------------------------
	function list_searching_form($crit,$method) 
	{
		$methods = 	array(
			'title_body' => gTxt('title_body'),
			'author' => gTxt('author'),
			'section' => gTxt('section'),
			'categories' => gTxt('categories'),
			'status' => gTxt('status')
		);
	
		return
		form(
			graf(gTxt('Search').sp.selectInput('method',$methods,$method).
				fInput('text','crit',$crit,'edit','','','15').
				eInput("list").sInput('list').
				fInput("submit","search",gTxt('go'),"smallerbox"),' align="center"')
		);

	}

// -------------------------------------------------------------
	function list_nav_form($page, $numPages, $sort, $dir) 
	{
		$nav[] = ($page > 1) 
		?	PrevNextLink("list",$page-1,gTxt('prev'),'prev',$sort, $dir)
		:	'';

		$nav[] = sp.small($page. '/'.$numPages).sp;

		$nav[] = ($page != $numPages) 
		?	PrevNextLink("list",$page+1,gTxt('next'),'next',$sort, $dir)
		:	'';

		if ($nav) return graf(join('',$nav),' align="center"');
	
	}


// -------------------------------------------------------------
	function list_multiedit_form() 
	{
		return event_multiedit_form('list');
	}

// -------------------------------------------------------------
	function list_multi_edit() 
	{
		global $txp_user;

		if (ps('selected') and !has_privs('article.delete')) {
			$ids = array();
			if (has_privs('article.delete.own')) {
				foreach (ps('selected') as $id) {
					$author = safe_field('AuthorID', 'textpattern', "ID='".doSlash($id)."'");
					if ($author == $txp_user)
						$ids[] = $id;
				}
			}
			$_POST['selected'] = $ids;
		}

		$deleted = event_multi_edit('textpattern','ID');
		if(!empty($deleted)){
			$method = ps('method');
			return list_list(messenger('article',$deleted,(($method == 'delete')?'deleted':'modified')));
		}
		return list_list();
	}

?>
