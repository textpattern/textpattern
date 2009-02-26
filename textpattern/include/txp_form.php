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

	global $vars;
	$vars = array('Form','type','name','savenew','oldname');
	$essential_forms = array('comments','comments_display','comment_form','default','Links','files');

	if ($event == 'form') {
		require_privs('form');

		if(!$step or !in_array($step, array('form_list','form_create','form_delete','form_edit','form_multi_edit','form_save'))){
			form_edit();
		} else $step();
	}

// -------------------------------------------------------------
	function form_list($curname)
	{
		global $step,$essential_forms;
		$out[] = startTable('list');
		$out[] = tr(tda(sLink('form','form_create',gTxt('create_new_form')),' colspan="3" style="height:30px"'));

		$out[] = assHead('form','type','');

		$methods = array('delete'=>gTxt('delete'));


		$rs = safe_rows_start("*", "txp_form", "1 order by type asc, name asc");

		if ($rs) {
			while ($a = nextRow($rs)){
				extract($a);
					$editlink = ($curname!=$name)
					?	eLink('form','form_edit','name',$name,$name)
					:	htmlspecialchars($name);
					$modbox = (!in_array($name, $essential_forms))
					?	'<input type="checkbox" name="selected_forms[]" value="'.$name.'" />'
					:	sp;
				$out[] = tr(td($editlink).td(small($type)).td($modbox));
			}

			$out[] = endTable();
			$out[] = eInput('form').sInput('form_multi_edit');
			$out[] = graf(selectInput('edit_method',$methods,'',1).sp.gTxt('selected').sp.
				fInput('submit','form_multi_edit',gTxt('go'),'smallerbox')
				, ' align="right"');

			return form( join('',$out),'',"verify('".gTxt('are_you_sure')."')" );
		}
	}

// -------------------------------------------------------------

	function form_multi_edit()
	{
		global $essential_forms;

		$method = ps('edit_method');
		$forms = ps('selected_forms');

		if ($forms and is_array($forms))
		{
			if ($method == 'delete')
			{
				foreach ($forms as $name)
				{
					if (!in_array($name, $essential_forms) && form_delete($name))
					{
						$deleted[] = $name;
					}
				}

				$message = gTxt('forms_deleted', array('{list}' => join(', ', $deleted)));

				form_edit($message);
			}
		}

		else
		{
			form_edit();
		}
	}

// -------------------------------------------------------------
	function form_create()
	{
		form_edit();
	}

// -------------------------------------------------------------
	function form_edit($message='')
	{
		global $step,$essential_forms;
		pagetop(gTxt('edit_forms'),$message);

		extract(gpsa(array('Form','name','type')));
		$name = trim(preg_replace('/[<>&"\']/', '', $name));

		if ($step=='form_create') {
			$inputs = fInput('submit','savenew',gTxt('save_new'),'publish').
				eInput("form").sInput('form_save');
		} else {
			$name = (!$name or $step=='form_delete') ? 'default' : $name;
			$rs = safe_row("*", "txp_form", "name='".doSlash($name)."'");
//			if ($rs)
 {
				extract($rs);
				$inputs = fInput('submit','save',gTxt('save'),'publish').
					eInput("form").sInput('form_save').hInput('oldname',$name);
			}
		}

		if (!in_array($name, $essential_forms))
			$changename = graf(gTxt('form_name').br.fInput('text','name',$name,'edit','','',15));
		else
			$changename = graf(gTxt('form_name').br.tag($name, 'em').hInput('name',$name));

		$out =
			startTable('edit').
			tr(
				tdtl(
					hed(gTxt('tagbuilder'), 2).

					hed('<a href="#article-tags" onclick="toggleDisplay(\'article-tags\'); return false;">'.gTxt('articles').'</a>'.
						sp.popHelp('form_articles'), 3, ' class="plain"').
						'<div id="article-tags" class="toggle on" style="display:block">'.popTagLinks('article').'</div>'.

					hed('<a href="#link-tags" onclick="toggleDisplay(\'link-tags\'); return false;">'.gTxt('links').'</a>'.
						sp.popHelp('form_place_link'), 3, ' class="plain"').
						'<div id="link-tags" class="toggle" style="display:none">'.popTagLinks('link').'</div>'.

					hed('<a href="#comment-tags" onclick="toggleDisplay(\'comment-tags\'); return false;">'.gTxt('comments').'</a>'.
						sp.popHelp('form_comments'), 3, ' class="plain"').
						'<div id="comment-tags" class="toggle" style="display:none">'.popTagLinks('comment').'</div>'.

					hed('<a href="#comment-detail-tags" onclick="toggleDisplay(\'comment-detail-tags\'); return false;">'.gTxt('comment_details').'</a>'.
						sp.popHelp('form_comment_details'), 3, ' class="plain"').
						'<div id="comment-detail-tags" class="toggle" style="display:none">'.popTagLinks('comment_details').'</div>'.

					hed('<a href="#comment-form-tags" onclick="toggleDisplay(\'comment-form-tags\'); return false;">'.gTxt('comment_form').'</a>'.
						sp.popHelp('form_comment_form'), 3, ' class="plain"').
						'<div id="comment-form-tags" class="toggle" style="display:none">'.popTagLinks('comment_form').'</div>'.

					hed('<a href="#search-result-tags" onclick="toggleDisplay(\'search-result-tags\'); return false;">'.gTxt('search_results_form').'</a>'.
						sp.popHelp('form_search_results'), 3, ' class="plain"').
						'<div id="search-result-tags" class="toggle" style="display:none">'.popTagLinks('search_result').'</div>'.

					hed('<a href="#file-tags" onclick="toggleDisplay(\'file-tags\'); return false;">'.gTxt('file_download_tags').'</a>'.
						sp.popHelp('form_file_download_tags'), 3, ' class="plain"').
						'<div id="file-tags" class="toggle" style="display:none">'.popTagLinks('file_download').'</div>'.

					hed('<a href="#category-tags" onclick="toggleDisplay(\'category-tags\'); return false;">'.gTxt('category_tags').'</a>'.
						sp.popHelp('form_category_tags'), 3, ' class="plain"').
						'<div id="category-tags" class="toggle" style="display:none">'.popTagLinks('category').'</div>'.

					hed('<a href="#section-tags" onclick="toggleDisplay(\'section-tags\'); return false;">'.gTxt('section_tags').'</a>'.
						sp.popHelp('form_section_tags'), 3, ' class="plain"').
						'<div id="section-tags" class="toggle" style="display:none">'.popTagLinks('section').'</div>'
						).
				tdtl(
					'<form action="index.php" method="post">'.
						'<textarea id="form" class="code" name="Form" cols="60" rows="20">'.htmlspecialchars($Form).'</textarea>'.

					$changename.

					graf(gTxt('form_type').br.
						formtypes($type)).
					graf(gTxt('only_articles_can_be_previewed')).
					fInput('submit','form_preview',gTxt('preview'),'smallbox').
					graf($inputs).
					'</form>'

				).
				tdtl(
					form_list($name)
				)
			).endTable();

		echo $out;
	}

// -------------------------------------------------------------

	function form_save()
	{
		global $vars, $step, $essential_forms;

		extract(doSlash(gpsa($vars)));
		$name = doSlash(trim(preg_replace('/[<>&"\']/', '', gps('name'))));

		if (!$name)
		{
			$step = 'form_create';
			$message = gTxt('form_name_invalid');

			return form_edit(array($message, E_ERROR));
		}

		if (!in_array($type, array('article','category','comment','file','link','misc','section')))
		{
			$step = 'form_create';
			$message = gTxt('form_type_missing');

			return form_edit(array($message, E_ERROR));
		}

		if ($savenew)
		{
			$exists = safe_field('name', 'txp_form', "name = '$name'");

			if ($exists)
			{
				$step = 'form_create';
				$message = gTxt('form_already_exists', array('{name}' => $name));

			return form_edit(array($message, E_ERROR));
			}

			safe_insert('txp_form', "Form = '$Form', type = '$type', name = '$name'");

			update_lastmod();

			$message = gTxt('form_created', array('{name}' => $name));

			return form_edit($message);
		}

		safe_update('txp_form', "Form = '$Form', type = '$type', name = '$name'", "name = '$oldname'");

		update_lastmod();

		$message = gTxt('form_updated', array('{name}' => $name));

		form_edit($message);
	}

// -------------------------------------------------------------
	function form_delete($name)
	{
		global $essential_forms;
		if (in_array($name, $essential_forms)) return false;
		$name = doSlash($name);
		if (safe_delete("txp_form","name='$name'")) {
			return true;
		}
		return false;
	}

// -------------------------------------------------------------
	function formTypes($type)
	{
	 	$types = array(''=>'','article'=>'article','category'=>'category','comment'=>'comment',
	 		'file'=>'file','link'=>'link','misc'=>'misc','section'=>'section');
		return selectInput('type',$types,$type);
	}

?>
