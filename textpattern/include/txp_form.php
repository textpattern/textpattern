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

	global $vars;
	$vars = array('Form','type','name','savenew','oldname');

	if ($event == 'form') {
		require_privs('form');

		if(!$step or !in_array($step, array('form_list','form_create','form_delete','form_edit','form_multi_edit','form_save'))){
			form_edit();
		} else $step();
	}
	
// -------------------------------------------------------------
	function form_list($curname)
	{
		global $step;
		$out[] = startTable('list');
		$out[] = tr(tda(sLink('form','form_create',gTxt('create_new_form')),' colspan="3" style="height:30px"'));

		$out[] = assHead('form','type','');
		
		$methods = array('delete'=>gTxt('delete'));


		$rs = safe_rows_start("*", "txp_form", "1 order by name");

		if ($rs) {
			while ($a = nextRow($rs)){
				extract($a);
					$editlink = ($curname!=$name) 
					?	eLink('form','form_edit','name',$name,$name)
					:	$name;
					$modbox = ($name!='comments' 
								&& $name!='comment_form' 
								&& $name!='default' 
								&& $name!='Links'
								&& $name!='files') 
					?	'<input type="checkbox" name="selected_forms[]" value="'.$name.'" />'
					:	sp;
				$out[] = tr(td($editlink).td(small($type)).td($modbox));
			}

			$out[] = endTable();
			$out[] = eInput('form').sInput('form_multi_edit');
			$out[] = graf(selectInput('method',$methods,'',1).sp.gTxt('selected').sp.
				fInput('submit','form_multi_edit',gTxt('go'),'smallerbox')
				, ' align="right"');

			return form( join('',$out),'',"verify('".gTxt('are_you_sure')."')" );
		}
	}

// -------------------------------------------------------------
	function form_multi_edit() 
	{
		$method = ps('method');
		$forms = ps('selected_forms');

		if (is_array($forms)) {
			if ($method == 'delete') {
				foreach($forms as $name) {
					if (form_delete($name)) {
						$deleted[] = $name;
					}
				}
			form_edit(messenger('form',join(', ',$deleted),'deleted'));
			}
		} else form_edit('nothing to delete');
	}


// -------------------------------------------------------------
	function form_create() 
	{
		form_edit();
	}

// -------------------------------------------------------------
	function form_edit($message='')
	{
		global $step;
		pagetop(gTxt('edit_forms'),$message);

		extract(gpsa(array('Form','name','type')));

		if ($step=='form_create') {
			$Form=''; $name=''; $type='';
			$inputs = fInput('submit','savenew',gTxt('save_new'),'publish').
				eInput("form").sInput('form_save');
		} else {
			$name = (!$name or $step=='form_delete') ? 'default' : $name;
			$rs = safe_row("*", "txp_form", "name='$name'");
			if ($rs) {
				extract($rs);
				$inputs = fInput('submit','save',gTxt('save'),'publish').
					eInput("form").sInput('form_save').hInput('oldname',$name);
			}
		}

		$out = 
			startTable('edit').
			tr(
				tdtl(
					hed(gTxt('useful_tags'),2).
					graf(gTxt('articles').sp.popHelp('form_place_article').br.
						popTagLinks('article')).
					graf(gTxt('links').sp.popHelp('form_place_link').br.
						popTagLinks('link')).
					graf(gTxt('displayed_comments').sp.popHelp('form_place_comment').br.
						popTagLinks('comment')).
					graf(gTxt('comment_form').sp.popHelp('form_place_input').br.
						popTagLinks('comment_form')).
					graf(gTxt('search_input_form').sp.popHelp('form_place_search_input').br.
						popTagLinks('search_input')).
					graf(gTxt('search_results_form').
						sp.popHelp('form_place_search_results').br.
						popTagLinks('search_result')).
					graf(
						tag('<strong>'.gTxt('file_download_tags').'</strong>','a',' href="#" onclick="toggleDisplay(\'downloadtags\');"').sp.popHelp('form_file_download_tags')).
						graf(popTagLinks('file_download'), ' style="display:none;" id="downloadtags"')
				).
				tdtl(
					'<form action="index.php" method="post">'.
					input_textarea($Form).

					graf(gTxt('form_name').br.
						fInput('text','name',$name,'edit','','',15)).
					graf(gTxt('form_type').br.
						formtypes($type)).
					graf(gTxt('only_articles_can_be_previewed')).
					fInput('submit','preview',gTxt('preview'),'smallbox').
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
		global $vars;
		extract(doSlash(gpsa($vars)));
		if ($savenew) {
			if (safe_insert("txp_form", "Form='$Form', type='$type', name='$name'")) {
				form_edit(messenger('form',$name,'created'));
			} else form_edit(messenger('form',$name,'already_exists'));
		} else {
			safe_update(
				"txp_form", 
				"Form='$Form',type='$type',name='$name'",
				"name='$oldname'"
			);
			form_edit(messenger('form',$name,'updated'));		
		}
	}

// -------------------------------------------------------------
	function form_delete($name)
	{
		$name = doSlash($name);
		if (safe_delete("txp_form","name='$name'")) {
			return true;
		}
		return false;
	}
	
// -------------------------------------------------------------
	function formTypes($type) 
	{
	 	$types = array(''=>'','article'=>'article','comment'=>'comment','link'=>'link','misc'=>'misc','file'=>'file'); 
		return selectInput('type',$types,$type);
	}

// -------------------------------------------------------------
	function input_textarea($Form) 
	{
		return 
		'<textarea name="Form" rows="20" cols="60">'.htmlspecialchars($Form).'</textarea>';
	}
?>
