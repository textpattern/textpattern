<?php
/*
	This is Textpattern
	Copyright 2004 by Dean Allen 
 	All rights reserved.

	Use of this software indicates acceptance of the Textpattern license agreement 
*/

//	dmp($_POST);


		$statuses = array(
			1 => gTxt('draft'),
			2 => gTxt('hidden'),
			3 => gTxt('pending'),
			4 => strong(gTxt('live'))
		);

		
	if(!$step or !function_exists($step)){
		list_list();
	} else $step();


//--------------------------------------------------------------
	function list_list($message="",$post='')
	{		

		extract(get_prefs());
		$lvars = array("page","sort","dir","crit",'method');
		extract(gpsa($lvars));
		global $statuses,$step;
		
		pagetop("Textpattern",$message);

		$total = getCount('textpattern',"1"); 
		$limit = ($article_list_pageby) ? $article_list_pageby : 25;
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
				'status'     => "Status rlike '$crit'"
			);
			$criteria = $critsql[$method];
			$limit = 500;
		} else $criteria = 1;
			

		$rs = safe_rows(
			"*, unix_timestamp(Posted) as uPosted", 
			"textpattern", 
			"$criteria order by $sort $dir limit $offset,$limit"
		);

		echo (!$crit) ? list_nav_form($page,$numPages,$sort,$dir) : '', list_searching_form($crit,$method);
		
		if ($rs) {
			echo '<form action="index.php" method="post" onsubmit="return verify(\''.gTxt('are_you_sure').'\')">',
			startTable('list'),
			'<tr>',
				column_head('posted', 'Posted', 'list', 1, $linkdir),
				column_head('title', 'Title', 'list', 1, $linkdir),
				($use_sections)
				?	column_head('section', 'Section', 'list', 1, $linkdir)
				:	'',
				($use_categories)
				?	column_head('category1', 'Category1', 'list', 1, $linkdir).
					column_head('category2', 'Category2', 'list', 1, $linkdir)
				:	'',
				hCell(gTxt('Author')),
				column_head(gTxt('status'), 'Status', 'list', 1, $linkdir),
				td(),
			'</tr>';
	
			foreach ($rs as $a) {
				extract($a);
				
				if($use_categories==1) { $cat1 = $Category1; $cat2 = $Category2; }
		
				$stat = (!empty($Status)) ? $statuses[$Status] : '';
				if($use_sections==1) $sect = $Section;
				$adate = date("d M y",$uPosted+$timeoffset);
		
				$alink = eLink('article','edit','ID',$ID,$adate);
				$tlink = eLink('article','edit','ID',$ID,$Title);
				$modbox = fInput('checkbox','selected[]',$ID);
				
				echo "<tr>".n,
					td($alink),
					td($tlink,200),
					($use_sections) ? td($sect,75) : '',
					($use_categories) ? td($cat1,75).td($cat2,75) : '',
					td($AuthorID),
					td($stat,45),
					td($modbox),
				'</tr>'.n;
			}
			
			echo tr(tda(list_multiedit_form(),' colspan="8" style="text-align:right;border:0px"'));
			
			echo "</table></form>";
			echo pageby_form('list',$article_list_pageby);
			unset($sort);
		}
	}

// -------------------------------------------------------------
	function list_change_pageby() 
	{
		$qty = gps('qty');
		safe_update('txp_prefs',"val=$qty","name='article_list_pageby'");
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
		$method = ps('method');
		$methods = array('delete'=>gTxt('delete'));
		return
			gTxt('with_selected').sp.selectInput('method',$methods,$method,1).
			eInput('list').sInput('list_multi_edit').fInput('submit','',gTxt('go'),'smallerbox');
	}

// -------------------------------------------------------------
	function list_multi_edit() 
	{
		$method = ps('method');
		$things = ps('selected');
		if ($things) {
			foreach($things as $ID) {
				if ($method == 'delete') {
					if (safe_delete('textpattern',"ID='$ID'")) {
						$ids[] = $ID;
					}
				}
			}
			list_list(messenger('article',join(', ',$ids),'deleted'));
		} else list_list();
	}

?>
