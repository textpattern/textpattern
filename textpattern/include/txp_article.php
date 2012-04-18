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

global $vars, $statuses;

$vars = array(
	'ID','Title','Body','Excerpt','textile_excerpt','Image',
	'textile_body', 'Keywords','Status','Posted','Expires','Section','Category1','Category2',
	'Annotate','AnnotateInvite','publish_now','reset_time','AuthorID','sPosted',
	'LastModID','sLastMod','override_form','from_view','year','month','day','hour',
	'minute','second','url_title','exp_year','exp_month','exp_day','exp_hour',
	'exp_minute','exp_second','sExpires'
);
$cfs = getCustomFields();
foreach($cfs as $i => $cf_name)
{
	$vars[] = "custom_$i";
}

$statuses = array(
		1 => gTxt('draft'),
		2 => gTxt('hidden'),
		3 => gTxt('pending'),
		4 => strong(gTxt('live')),
		5 => gTxt('sticky'),
);

if (!empty($event) and $event == 'article') {
	require_privs('article');


	$save = gps('save');
	if ($save) $step = 'save';

	$publish = gps('publish');
	if ($publish) $step = 'publish';

	bouncer($step,
		array(
			'create' 	=> true,
			'publish' 	=> true,
			'edit' 		=> false,
			'save' 		=> true,
			'save_pane_state' => true
		)
	);

	switch(strtolower($step)) {
		case "":         article_edit();    break;
		case "create":   article_edit();    break;
		case "publish":  article_post();    break;
		case "edit":     article_edit();    break;
		case "save":     article_save();    break;
		case "save_pane_state":     article_save_pane_state();    break;
		default:         article_edit();    break;
	}
}

//--------------------------------------------------------------

	function article_post()
	{
		global $txp_user, $vars, $prefs;

		extract($prefs);

		$incoming = doSlash(textile_main_fields(array_map('assert_string', psa($vars))));
		extract($incoming);
		extract(array_map('assert_int', psa(array( 'Status', 'textile_body', 'textile_excerpt'))));
		// comments my be on, off, or disabled.
		$Annotate = (int) $Annotate;
		// set and validate article timestamp
		if ($publish_now == 1) {
			$when = 'now()';
			$when_ts = time();
		} else {
			if (!is_numeric($year) || !is_numeric($month) || !is_numeric($day) || !is_numeric($hour)  || !is_numeric($minute) || !is_numeric($second) ) {
				$ts = false;
			} else {
				$ts = strtotime($year.'-'.$month.'-'.$day.' '.$hour.':'.$minute.':'.$second);
			}

			if ($ts === false || $ts < 0) { // Tracking the PHP meanders on how to return an error
				article_edit(array(gTxt('invalid_postdate'), E_ERROR));
				return;
			}

			$when_ts = $ts - tz_offset($ts);
			$when = "from_unixtime($when_ts)";
		}

		// set and validate expiry timestamp
		if (empty($exp_year)) {
			$expires = 0;
		} else {
			if(empty($exp_month)) $exp_month=1;
			if(empty($exp_day)) $exp_day=1;
			if(empty($exp_hour)) $exp_hour=0;
			if(empty($exp_minute)) $exp_minute=0;
			if(empty($exp_second)) $exp_second=0;

			$ts = strtotime($exp_year.'-'.$exp_month.'-'.$exp_day.' '.$exp_hour.':'.$exp_minute.':'.$exp_second);
			if ($ts === false || $ts < 0) {
				article_edit(array(gTxt('invalid_expirydate'), E_ERROR));
				return;
			} else {
				$expires = $ts - tz_offset($ts);
			}
		}

		if ($expires && ($expires <= $when_ts)) {
			article_edit(array(gTxt('article_expires_before_postdate'), E_ERROR));
			return;
		}

		if ($expires) {
			$whenexpires = "Expires=from_unixtime($expires)";
		} else {
			$whenexpires = "Expires=".NULLDATETIME;
		}

		$user = doSlash($txp_user);
		$Keywords = doSlash(trim(preg_replace('/( ?[\r\n\t,])+ ?/s', ',', preg_replace('/ +/', ' ', ps('Keywords'))), ', '));
		$msg = '';

		if ($Title or $Body or $Excerpt) {

			if (!has_privs('article.publish') && $Status>=4) $Status = 3;
			if (empty($url_title)) $url_title = stripSpace($Title_plain, 1);

			$cfq = array();
			$cfs = getCustomFields();
			foreach($cfs as $i => $cf_name)
			{
				$custom_x = "custom_{$i}";
				$cfq[] = "custom_$i = '".$$custom_x."'";
			}
			$cfq = join(', ', $cfq);

			if (article_validate(compact($vars), $msg)) {
				if (safe_insert(
				   "textpattern",
				   "Title           = '$Title',
					Body            = '$Body',
					Body_html       = '$Body_html',
					Excerpt         = '$Excerpt',
					Excerpt_html    = '$Excerpt_html',
					Image           = '$Image',
					Keywords        = '$Keywords',
					Status          =  $Status,
					Posted          =  $when,
					Expires         =  $whenexpires,
					AuthorID        = '$user',
					LastMod         =  $when,
					LastModID       = '$user',
					Section         = '$Section',
					Category1       = '$Category1',
					Category2       = '$Category2',
					textile_body    =  $textile_body,
					textile_excerpt =  $textile_excerpt,
					Annotate        =  $Annotate,
					override_form   = '$override_form',
					url_title       = '$url_title',
					AnnotateInvite  = '$AnnotateInvite',"
					.(($cfs) ? $cfq.',' : '').
					"uid             = '".md5(uniqid(rand(),true))."',
					feed_time       = now()"
				)) {

					$GLOBALS['ID'] = mysql_insert_id();

					if ($Status>=4) {
						do_pings();
						update_lastmod();
					}
					$s = check_url_title($url_title);
					$msg = array(get_status_message($Status).' '.$s, ($s ? E_WARNING : 0));
				} else {
					unset($GLOBALS['ID']);
					$msg = array(gTxt('article_save_error'), E_ERROR);
				}
			}
		}
		article_edit($msg);
	}

//--------------------------------------------------------------

	function article_save()
	{
		global $txp_user, $vars, $prefs, $statuses;

		extract($prefs);

		$incoming = array_map('assert_string', psa($vars));

		$oldArticle = safe_row('Status, url_title, Title, '.
			'unix_timestamp(LastMod) as sLastMod, LastModID, '.
			'unix_timestamp(Posted) as sPosted, '.
			'unix_timestamp(Expires) as sExpires',
			'textpattern', 'ID = '.(int)$incoming['ID']);

		if (! (    ($oldArticle['Status'] >= 4 and has_privs('article.edit.published'))
				or ($oldArticle['Status'] >= 4 and $incoming['AuthorID']==$txp_user and has_privs('article.edit.own.published'))
		    	or ($oldArticle['Status'] < 4 and has_privs('article.edit'))
				or ($oldArticle['Status'] < 4 and $incoming['AuthorID']==$txp_user and has_privs('article.edit.own'))))
		{
				// Not allowed, you silly rabbit, you shouldn't even be here.
				// Show default editing screen.
			article_edit();
			return;
		}

		if ($oldArticle['sLastMod'] != $incoming['sLastMod'])
		{
			article_edit(array(gTxt('concurrent_edit_by', array('{author}' => htmlspecialchars($oldArticle['LastModID']))), E_ERROR), TRUE , !AJAXALLY_CHALLENGED );
			return;
		}

		$incoming = textile_main_fields($incoming);

		extract(doSlash($incoming));
		extract(array_map('assert_int', psa(array('ID', 'Status', 'textile_body', 'textile_excerpt'))));
		// comments my be on, off, or disabled.
		$Annotate = (int) $Annotate;

		if (!has_privs('article.publish') && $Status>=4) $Status = 3;

		// set and validate article timestamp
		if ($reset_time) {
			$whenposted = "Posted=now()";
			$when_ts = time();
		} else {
			if (!is_numeric($year) || !is_numeric($month) || !is_numeric($day) || !is_numeric($hour)  || !is_numeric($minute) || !is_numeric($second) ) {
				$ts = false;
			} else {
				$ts = strtotime($year.'-'.$month.'-'.$day.' '.$hour.':'.$minute.':'.$second);
			}

			if ($ts === false || $ts < 0) {
				$when = $when_ts = $oldArticle['sPosted'];
				$msg = array(gTxt('invalid_postdate'), E_ERROR);
			} else {
				$when = $when_ts = $ts - tz_offset($ts);
			}

			$whenposted = "Posted=from_unixtime($when)";
		}

		// set and validate expiry timestamp
		if (empty($exp_year)) {
			$expires = 0;
		} else {
			if(empty($exp_month)) $exp_month=1;
			if(empty($exp_day)) $exp_day=1;
			if(empty($exp_hour)) $exp_hour=0;
			if(empty($exp_minute)) $exp_minute=0;
			if(empty($exp_second)) $exp_second=0;

			$ts = strtotime($exp_year.'-'.$exp_month.'-'.$exp_day.' '.$exp_hour.':'.$exp_minute.':'.$exp_second);
			if ($ts === false || $ts < 0) {
				$expires = $oldArticle['sExpires'];
				$msg = array(gTxt('invalid_expirydate'), E_ERROR);
			} else {
				$expires = $ts - tz_offset($ts);
			}
		}

		if ($expires && ($expires <= $when_ts)) {
			$expires = $oldArticle['sExpires'];
			$msg = array(gTxt('article_expires_before_postdate'), E_ERROR);
		}

		if ($expires) {
			$whenexpires = "Expires=from_unixtime($expires)";
		} else {
			$whenexpires = "Expires=".NULLDATETIME;
		}

		//Auto-Update custom-titles according to Title, as long as unpublished and NOT customized
		if ( empty($url_title)
			  || ( ($oldArticle['Status'] < 4)
					&& ($oldArticle['url_title'] == $url_title )
					&& ($oldArticle['url_title'] == stripSpace($oldArticle['Title'],1))
					&& ($oldArticle['Title'] != $Title)
				 )
		   )
		{
			$url_title = stripSpace($Title_plain, 1);
		}

		$Keywords = doSlash(trim(preg_replace('/( ?[\r\n\t,])+ ?/s', ',', preg_replace('/ +/', ' ', ps('Keywords'))), ', '));

		$user = doSlash($txp_user);

		$cfq = array();
		$cfs = getCustomFields();
		foreach($cfs as $i => $cf_name)
		{
			$custom_x = "custom_{$i}";
			$cfq[] = "custom_$i = '".$$custom_x."'";
		}
		$cfq = join(', ', $cfq);

		if (article_validate(compact($vars), $msg)) {
			if (safe_update("textpattern",
			   "Title           = '$Title',
				Body            = '$Body',
				Body_html       = '$Body_html',
				Excerpt         = '$Excerpt',
				Excerpt_html    = '$Excerpt_html',
				Keywords        = '$Keywords',
				Image           = '$Image',
				Status          =  $Status,
				LastMod         =  now(),
				LastModID       = '$user',
				Section         = '$Section',
				Category1       = '$Category1',
				Category2       = '$Category2',
				Annotate        =  $Annotate,
				textile_body    =  $textile_body,
				textile_excerpt =  $textile_excerpt,
				override_form   = '$override_form',
				url_title       = '$url_title',
				AnnotateInvite  = '$AnnotateInvite',"
				.(($cfs) ? $cfq.',' : '').
				"$whenposted,
				$whenexpires",
				"ID = $ID"
			)) {
				if ($Status >= 4) {
					if ($oldArticle['Status'] < 4) {
						do_pings();
					}
					update_lastmod();
				}

				if (empty($msg)) {
					$s = check_url_title($url_title);
					$msg = array(get_status_message($Status).' '.$s, $s ? E_WARNING : 0);
				}
			} else {
				$msg = array(gTxt('article_save_error'), E_ERROR);
			}
		}
		article_edit($msg, FALSE, !AJAXALLY_CHALLENGED);
	}

//--------------------------------------------------------------

	function article_edit($message = '', $concurrent = FALSE, $refresh_partials = FALSE)
	{
		global $vars, $txp_user, $comments_disabled_after, $prefs, $event;

		extract($prefs);

		$partials = array(
			'sLastMod'      => array('mode' => PARTIAL_VOLATILE_VALUE,  'selector' => '[name=sLastMod]'),
			'sPosted'       => array('mode' => PARTIAL_VOLATILE_VALUE,  'selector' => '[name=sPosted]'),
			'custom_fields' => array('mode' => PARTIAL_STATIC,      'selector' => false),
			'image'         => array('mode' => PARTIAL_STATIC,      'selector' => false),
			'keywords'      => array('mode' => PARTIAL_STATIC,      'selector' => false),
			'keywords_value'    => array('mode' => PARTIAL_VOLATILE_VALUE,    'selector' => '#keywords'),
			'url_title'     => array('mode' => PARTIAL_STATIC,      'selector' => false),
			'url_title_value'   => array('mode' => PARTIAL_VOLATILE_VALUE,    'selector' => '#url-title'),
			'recent_articles'=> array('mode' => PARTIAL_VOLATILE,   'selector' => '#recent_group .recent'),
			'title'         => array('mode' => PARTIAL_STATIC,      'selector' => false),
			'title_value'   => array('mode' => PARTIAL_VOLATILE_VALUE,      'selector' => '#title'),
			'article_view'  => array('mode' => PARTIAL_VOLATILE,    'selector' => '#article_partial_article_view'),
			'body'          => array('mode' => PARTIAL_STATIC,      'selector' => false),
			'excerpt'       => array('mode' => PARTIAL_STATIC,      'selector' => false),
			'author'        => array('mode' => PARTIAL_VOLATILE,    'selector' => 'p.author'),
			'article_nav'   => array('mode' => PARTIAL_VOLATILE,    'selector' => 'p.article-nav'),
			'status'        => array('mode' => PARTIAL_VOLATILE,    'selector' => '#write-status'),
			'categories'    => array('mode' => PARTIAL_STATIC,      'selector' => false),
			'section'       => array('mode' => PARTIAL_STATIC,      'selector' => false),
			'posted'        => array('mode' => PARTIAL_VOLATILE,    'selector' => '#write-timestamp'),
			'expires'       => array('mode' => PARTIAL_VOLATILE,    'selector' => '#write-expires'),
		);

		extract(gpsa(array('view','from_view','step')));

		if(!empty($GLOBALS['ID'])) { // newly-saved article
			$ID = $GLOBALS['ID'];
			$step = 'edit';
		} else {
			$ID = gps('ID');
		}

		include_once txpath.'/lib/classTextile.php';
		$textile = new Textile($doctype);

		// switch to 'text' view upon page load and after article post
		if(!$view || gps('save') || gps('publish')) {
			$view = 'text';
		}

		if (!$step) $step = "create";

		if ($step == "edit"
			&& $view=="text"
			&& !empty($ID)
			&& $from_view != 'preview'
			&& $from_view != 'html'
			&& !$concurrent)
		{
			$pull = true;          //-- it's an existing article - off we go to the db
			$ID = assert_int($ID);

			$rs = safe_row(
				"*, unix_timestamp(Posted) as sPosted,
				unix_timestamp(Expires) as sExpires,
				unix_timestamp(LastMod) as sLastMod",
				"textpattern",
				"ID=$ID"
			);
			if (empty($rs)) return;

			$rs['reset_time'] = $rs['publish_now'] = false;

		} else {

			$pull = false;         //-- assume they came from post

			if ($from_view=='preview' or $from_view=='html')
			{
				$store_out = array();
				$store = unserialize(base64_decode(ps('store')));

				foreach($vars as $var)
				{
					if (isset($store[$var])) $store_out[$var] = $store[$var];
				}
			}

			else
			{
				$store_out = gpsa($vars);

				if ($concurrent)
				{
					$store_out['sLastMod'] = safe_field('unix_timestamp(LastMod) as sLastMod', 'textpattern', 'ID='.$ID);
				}
			}

			$rs = textile_main_fields($store_out);

			if (!empty($rs['exp_year']))
			{
				if(empty($rs['exp_month'])) $rs['exp_month']=1;
				if(empty($rs['exp_day'])) $rs['exp_day']=1;
				if(empty($rs['exp_hour'])) $rs['exp_hour']=0;
				if(empty($rs['exp_minute'])) $rs['exp_minute']=0;
				if(empty($rs['exp_second'])) $rs['exp_second']=0;
				$rs['sExpires'] = safe_strtotime($rs['exp_year'].'-'.$rs['exp_month'].'-'.$rs['exp_day'].' '.
					$rs['exp_hour'].':'.$rs['exp_minute'].':'.$rs['exp_second']);
			}

			if (!empty($rs['year'])) {
				$rs['sPosted'] = safe_strtotime($rs['year'].'-'.$rs['month'].'-'.$rs['day'].' '.
					$rs['hour'].':'.$rs['minute'].':'.$rs['second']);
			}
		}

		if (!$from_view && !$pull) {
			$rs['Section'] = getDefaultSection();
		}

		extract($rs);

		$GLOBALS['step'] = $step;

		if ($step == 'create')
		{
			$textile_body = $use_textile;
			$textile_excerpt = $use_textile;
		}

		if ($step != 'create' && isset($sPosted)) {

			// Previous record?
			$rs['prev_id'] = checkIfNeighbour('prev',$sPosted);

			// Next record?
			$rs['next_id'] = checkIfNeighbour('next',$sPosted);
		} else {
			$rs['prev_id'] = $rs['next_id'] = 0;
		}

		// let plugins chime in on partials
		callback_event_ref('article_ui', 'partials_meta', 0, $rs, $partials);

		// get content for volatile partials
		foreach ($partials as $key => $value) {
			if ($value['mode'] == PARTIAL_VOLATILE || $value['mode'] == PARTIAL_VOLATILE_VALUE) {
				$f = "article_partial_$key";
				$partials[$key]['html'] = $f($rs);
			}
		}

		if ($refresh_partials) {
			global $theme;
			$response[] = $theme->announce_async($message);

			// Update the volatile partials
			foreach ($partials as $p) {
				if ($p['mode'] == PARTIAL_VOLATILE) {
					$response[] = '$("'.$p['selector'].'").replaceWith("'.escape_js($p['html']).'")';
				} elseif ($p['mode'] == PARTIAL_VOLATILE_VALUE) {
					$response[] = '$("'.$p['selector'].'").val("'.escape_js($p['html']).'")';
				}
			}
			send_script_response(join(";\n", $response));

			// bail out
			return;
		}

		foreach ($partials as $key => $value) {
			if ($value['mode'] == PARTIAL_STATIC) {
				$f = "article_partial_$key";
				$partials[$key]['html'] = $f($rs);
			}
		}

		$page_title = ($Title) ? $Title : gTxt('write');

		pagetop($page_title, $message);

		echo n.'<div id="'.$event.'_container" class="txp-container txp-edit">';
		echo n.n.'<form id="article_form" name="article_form" method="post" action="index.php" '. ($step=='create' ? '>' : ' class="async">');

		if (!empty($store_out))
		{
			echo hInput('store', base64_encode(serialize($store_out)));
		}

		echo hInput('ID', $ID).
			n.eInput('article').
			n.sInput($step).
			n.hInput('sPosted', $sPosted).
			n.hInput('sLastMod', $sLastMod).
			n.hInput('AuthorID', $AuthorID).
			n.hInput('LastModID', $LastModID).
			'<input type="hidden" name="view" />'.

			startTable('edit').

  		'<tr>'.n.
				'<td id="article-col-1"><div id="configuration_content">';

		if ($view == 'text')
		{

		//-- markup help --------------

			echo pluggable_ui('article_ui', 'sidehelp', side_help($textile_body, $textile_excerpt), $rs);

		//-- custom menu entries --------------

			echo pluggable_ui('article_ui', 'extend_col_1', '', $rs);

		//-- advanced --------------

			echo '<div id="advanced_group"><h3 class="plain lever'.(get_pref('pane_article_advanced_visible') ? ' expanded' : '').'"><a href="#advanced">'.gTxt('advanced_options').'</a></h3>'.
				'<div id="advanced" class="toggle" style="display:'.(get_pref('pane_article_advanced_visible') ? 'block' : 'none').'">';

			// markup selection
			echo pluggable_ui('article_ui', 'markup',
				n.graf('<label for="markup-body">'.gTxt('article_markup').'</label>'.br.
					pref_text('textile_body', $textile_body, 'markup-body'), ' class="markup markup-body"').
				n.graf('<label for="markup-excerpt">'.gTxt('excerpt_markup').'</label>'.br.
					pref_text('textile_excerpt', $textile_excerpt, 'markup-excerpt'), ' class="markup markup-excerpt"'),
				$rs);

			// form override
			echo ($allow_form_override)
				? pluggable_ui('article_ui', 'override', graf('<label for="override-form">'.gTxt('override_default_form').'</label>'.sp.popHelp('override_form').br.
					form_pop($override_form, 'override-form'), ' class="override-form"'), $rs)
				: '';
			echo '</div></div>'.n;

		//-- custom fields --------------

			echo $partials['custom_fields']['html'];

		//-- article image --------------

			echo $partials['image']['html'];

		//-- meta info --------------

			echo '<div id="meta_group"><h3 class="plain lever'.(get_pref('pane_article_meta_visible') ? ' expanded' : '').'"><a href="#meta">'.gTxt('meta').'</a></h3>'.
				'<div id="meta" class="toggle" style="display:'.(get_pref('pane_article_meta_visible') ? 'block' : 'none').'">';
			// keywords
			echo $partials['keywords']['html'];
			// url title
			echo $partials['url_title']['html'];
			echo '</div></div>'.n;

		//-- recent articles --------------

			echo '<div id="recent_group"><h3 class="plain lever'.(get_pref('pane_article_recent_visible') ? ' expanded' : '').'"><a href="#recent">'.gTxt('recent_articles').'</a>'.'</h3>'.
				'<div id="recent" class="toggle" style="display:'.(get_pref('pane_article_recent_visible') ? 'block' : 'none').'">';
			echo $partials['recent_articles']['html'];
			echo '</div></div>';
		}

		else
		{
			echo sp;
		}

		echo '</div></td>'.n.'<td id="article-main"><div id="main_content">';

	//-- title input --------------

		if ($view == 'preview')
		{
			echo '<div class="preview">'.hed(gTxt('preview'), 2).hed($Title, 1, ' class="title"');
		}

		elseif ($view == 'html')
		{
			echo '<div class="html">'.hed('HTML', 2).hed($Title, 1, ' class="title"');
		}

		elseif ($view == 'text')
		{
			echo '<div class="text">'.n.
				'<p class="title">'.$partials['title']['html'];

			if ($step != 'create')
			{
				echo $partials['article_view']['html'];
			}

			echo '</p>';
		}

	//-- body --------------------

		if ($view == 'preview')
		{
			echo '<div class="body">'.$Body_html.'</div>';
		}

		elseif ($view == 'html')
		{
			echo tag(str_replace(array(n,t), array(br,sp.sp.sp.sp), htmlspecialchars($Body_html)), 'code', ' class="body"');
		}

		else
		{
			echo $partials['body']['html'];
		}

	//-- excerpt --------------------

		if ($articles_use_excerpts)
		{
			if ($view == 'text')
			{
				echo $partials['excerpt']['html'];
			}

			else
			{
				echo n.'<hr width="50%" />';

				echo '<div class="excerpt">';
				echo ($textile_excerpt == USE_TEXTILE)
				?	($view=='preview')
					?	graf($textile->textileThis($Excerpt))
					:	tag(str_replace(array(n,t),
							array(br,sp.sp.sp.sp),htmlspecialchars(
								$textile->TextileThis($Excerpt))),'code', ' class="excerpt"')
				:	graf($Excerpt);
				echo '</div>';
			}
		}


	//-- author --------------

		if ($view=="text" && $step != "create")
		{
			echo $partials['author']['html'];
		}

		echo hInput('from_view',$view),
		'</div></div></td>';

	//-- layer tabs -------------------

		echo '<td id="article-tabs"><div id="view_modes">';

		echo pluggable_ui('article_ui', 'view',
			($use_textile == USE_TEXTILE || $textile_body == USE_TEXTILE)
			? tag((tab('text',$view).tab('html',$view).tab('preview',$view)), 'ul')
			: '&#160;',
			$rs);
		echo '</div></td>';

		echo '<td id="article-col-2"><div id="supporting_content">';

		if ($view == 'text')
		{
			if ($step != 'create')
			{
				echo n.graf(href(gtxt('create_new'), 'index.php?event=article'), ' class="action-create"');
			}

		//-- prev/next article links --

			if ($step!='create' and ($rs['prev_id'] or $rs['next_id'])) {
				echo $partials['article_nav']['html'];
			}

		//-- status radios --------------

			echo $partials['status']['html'];

		//-- sort and display  -----------

			echo pluggable_ui('article_ui', 'sort_display',
				n.n.tag(
					n.'<legend>'.gTxt('sort_display').'</legend>'.
					//-- category selects -----------
					$partials['categories']['html'].
					//-- section select --------------
					$partials['section']['html'].
					n,
					'fieldset', ' id="write-sort"'),
				$rs);

		//-- "More" section
			echo n.n.'<div id="more_group"><h3 class="plain lever'.(get_pref('pane_article_more_visible') ? ' expanded' : '').'"><a href="#more">'.gTxt('more').'</a></h3>',
				'<div id="more" class="toggle" style="display:'.(get_pref('pane_article_more_visible') ? 'block' : 'none').'">';

		//-- comments stuff --------------

			if($step=="create") {
				//Avoiding invite disappear when previewing
				$AnnotateInvite = (!empty($store_out['AnnotateInvite']))? $store_out['AnnotateInvite'] : $comments_default_invite;
				if ($comments_on_default==1) { $Annotate = 1; }
			}

			if ($use_comments == 1)
			{
				$invite[] = n.n.'<fieldset id="write-comments">'.
					n.'<legend>'.gTxt('comments').'</legend>';

				$comments_expired = false;

				if ($step != 'create' && $comments_disabled_after)
				{
					$lifespan = $comments_disabled_after * 86400;
					$time_since = time() - $sPosted;

					if ($time_since > $lifespan)
					{
						$comments_expired = true;
					}
				}

				if ($comments_expired)
				{
					$invite[] = n.n.graf(gTxt('expired'), ' class="comment-annotate"');
				}

				else
				{
					$invite[] = n.n.graf(
						onoffRadio('Annotate', $Annotate)
					, ' class="comment-annotate"').

					n.n.graf(
						'<label for="comment-invite">'.gTxt('comment_invitation').'</label>'.br.
						fInput('text', 'AnnotateInvite', $AnnotateInvite, 'edit', '', '', '', '', 'comment-invite')
					, ' class="comment-invite"');
				}

				$invite[] = n.n.'</fieldset>';
				echo pluggable_ui('article_ui', 'annotate_invite', join('', $invite), $rs);

			}

			if ($step == "create" and empty($GLOBALS['ID']))
			{
		//-- timestamp -------------------

				//Avoiding modified date to disappear
				$persist_timestamp = (!empty($store_out['year']))?
					safe_strtotime($store_out['year'].'-'.$store_out['month'].'-'.$store_out['day'].' '.$store_out['hour'].':'.$store_out['minute'].':'.$store_out['second'])
					: time();

				echo pluggable_ui('article_ui', 'timestamp',
					n.n.'<fieldset id="write-timestamp">'.
					n.'<legend>'.gTxt('timestamp').'</legend>'.

					n.graf(checkbox('publish_now', '1', $publish_now, '', 'publish_now').'<label for="publish_now">'.gTxt('set_to_now').'</label>', ' class="publish-now"').

					n.graf(gTxt('or_publish_at').sp.popHelp('timestamp'), ' class="publish-at"').

					n.graf('<span class="label">'.gtxt('date').'</span>'.sp.
						tsi('year', '%Y', $persist_timestamp).' / '.
						tsi('month', '%m', $persist_timestamp).' / '.
						tsi('day', '%d', $persist_timestamp)
					, ' class="date posted created"'
					).

					n.graf('<span class="label">'.gTxt('time').'</span>'.sp.
						tsi('hour', '%H', $persist_timestamp).' : '.
						tsi('minute', '%M', $persist_timestamp).' : '.
						tsi('second', '%S', $persist_timestamp)
					, ' class="time posted created"'
					).

				n.'</fieldset>',
				array('sPosted' => $persist_timestamp) + $rs);

		//-- expires -------------------

				$persist_timestamp = (!empty($store_out['exp_year']))?
					safe_strtotime($store_out['exp_year'].'-'.$store_out['exp_month'].'-'.$store_out['exp_day'].' '.$store_out['exp_hour'].':'.$store_out['exp_minute'].':'.$store_out['second'])
					: NULLDATETIME;

				echo pluggable_ui('article_ui', 'expires',
					n.n.'<fieldset id="write-expires">'.
					n.'<legend>'.gTxt('expires').'</legend>'.

					n.graf('<span class="label">'.gtxt('date').'</span>'.sp.
						tsi('exp_year', '%Y', $persist_timestamp).' / '.
						tsi('exp_month', '%m', $persist_timestamp).' / '.
						tsi('exp_day', '%d', $persist_timestamp)
					, ' class="date expires"'
					).

					n.graf('<span class="label">'.gTxt('time').'</span>'.sp.
						tsi('exp_hour', '%H', $persist_timestamp).' : '.
						tsi('exp_minute', '%M', $persist_timestamp).' : '.
						tsi('exp_second', '%S', $persist_timestamp)
					, ' class="time expires"'
					).

				n.'</fieldset>',
				$rs);

				// end "More" section
				echo n.n.'</div></div>';

		//-- publish button --------------

				echo graf(
					(has_privs('article.publish')) ?
					fInput('submit','publish',gTxt('publish'),"publish", '', '', '', 4) :
					fInput('submit','publish',gTxt('save'),"publish", '', '', '', 4)
				, ' id="write-publish"');
			}

			else
			{

			//-- timestamp -------------------

				echo $partials['posted']['html'];

			//-- expires -------------------

				echo $partials['expires']['html'];;

				// end "More" section
				echo n.n.'</div></div>';

		//-- save button --------------

				if (   ($Status >= 4 and has_privs('article.edit.published'))
					or ($Status >= 4 and $AuthorID==$txp_user and has_privs('article.edit.own.published'))
				    or ($Status <  4 and has_privs('article.edit'))
					or ($Status <  4 and $AuthorID==$txp_user and has_privs('article.edit.own')))
					echo graf(fInput('submit','save',gTxt('save'),"publish", '', '', '', 4), ' id="write-save"');
			}
		}

		echo '</div></td></tr></table>'.n.
			tInput().n.
			'</form></div>'.n;
		// Assume users would not change the timestamp if they wanted to "publish now"/"reset time"
		echo script_js( <<<EOS
		$('#write-timestamp input.edit').change(
			function() {
				$('#publish_now').prop('checked', false);
				$('#reset_time').prop('checked', false);
			});
EOS
);


	}

// -------------------------------------------------------------

	function custField($num, $field, $content)
	{
		return n.n.graf('<label for="custom-'.$num.'">'.$field.'</label>'.br.
			n.fInput('text', 'custom_'.$num, $content, 'edit', '', '', 22, '', 'custom-'.$num), ' class="custom-field custom-'.$num.'"');
	}

// -------------------------------------------------------------
	function checkIfNeighbour($whichway,$sPosted)
	{
		$sPosted = assert_int($sPosted);
		$dir = ($whichway == 'prev') ? '<' : '>';
		$ord = ($whichway == 'prev') ? 'desc' : 'asc';

		return safe_field("ID", "textpattern",
			"Posted $dir from_unixtime($sPosted) order by Posted $ord limit 1");
	}

//--------------------------------------------------------------
// remember to show markup help for both body and excerpt
// if they are different

	function side_help($textile_body, $textile_excerpt)
	{
		if ($textile_body == USE_TEXTILE or $textile_excerpt == USE_TEXTILE)
		{
			return n.
				'<div id="textile_group">'.
				hed(
				'<a href="#textile_help">'.gTxt('textile_help').'</a>'
			, 3, ' class="plain lever'.(get_pref('pane_article_textile_help_visible') ? ' expanded' : '').'"').

				n.'<div id="textile_help" class="toggle" style="display:'.(get_pref('pane_article_textile_help_visible') ? 'block' : 'none').'">'.

				n.'<ul class="textile plain-list small">'.
					n.t.'<li>'.gTxt('header').': <strong>h<em>n</em>.</strong>'.sp.
						popHelpSubtle('header', 400, 400).'</li>'.
					n.t.'<li>'.gTxt('blockquote').': <strong>bq.</strong>'.sp.
						popHelpSubtle('blockquote',400,400).'</li>'.
					n.t.'<li>'.gTxt('numeric_list').': <strong>#</strong>'.sp.
						popHelpSubtle('numeric', 400, 400).'</li>'.
					n.t.'<li>'.gTxt('bulleted_list').': <strong>*</strong>'.sp.
						popHelpSubtle('bulleted', 400, 400).'</li>'.
				n.'</ul>'.

				n.'<ul class="textile plain-list small">'.
					n.t.'<li>'.'_<em>'.gTxt('emphasis').'</em>_'.sp.
						popHelpSubtle('italic', 400, 400).'</li>'.
					n.t.'<li>'.'*<strong>'.gTxt('strong').'</strong>*'.sp.
						popHelpSubtle('bold', 400, 400).'</li>'.
					n.t.'<li>'.'??<cite>'.gTxt('citation').'</cite>??'.sp.
						popHelpSubtle('cite', 500, 300).'</li>'.
					n.t.'<li>'.'-'.gTxt('deleted_text').'-'.sp.
						popHelpSubtle('delete', 400, 300).'</li>'.
					n.t.'<li>'.'+'.gTxt('inserted_text').'+'.sp.
						popHelpSubtle('insert', 400, 300).'</li>'.
					n.t.'<li>'.'^'.gTxt('superscript').'^'.sp.
						popHelpSubtle('super', 400, 300).'</li>'.
					n.t.'<li>'.'~'.gTxt('subscript').'~'.sp.
						popHelpSubtle('subscript', 400, 400).'</li>'.
				n.'</ul>'.

				n.graf(
					'"'.gTxt('linktext').'":url'.sp.popHelpSubtle('link', 400, 500)
				, ' class="textile small"').

				n.graf(
					'!'.gTxt('imageurl').'!'.sp.popHelpSubtle('image', 500, 500)
				, ' class="textile small"').

				n.graf(
					'<a id="textile-docs-link" href="http://textpattern.com/textile-sandbox" target="_blank">'.gTxt('More').'</a>').

				n.'</div></div>';
		}
	}

//--------------------------------------------------------------

	function status_radio($Status)
	{
		global $statuses;

		$Status = (!$Status) ? 4 : $Status;

		foreach ($statuses as $a => $b)
		{
			$out[] = n.t.'<li class="status-'.$a.($Status == $a ? ' active' : '').'">'.radio('Status', $a, ($Status == $a) ? 1 : 0, 'status-'.$a).
				'<label for="status-'.$a.'">'.$b.'</label></li>';
		}

		return '<ul class="status plain-list">'.join('', $out).n.'</ul>';
	}

//--------------------------------------------------------------

	function category_popup($name, $val, $id)
	{
		$rs = getTree('root', 'article');

		if ($rs)
		{
			return treeSelectInput($name,$rs,$val, $id, 35);
		}

		return false;
	}

//--------------------------------------------------------------

	function section_popup($Section, $id)
	{
		$rs = safe_column('name', 'txp_section', "name != 'default'");

		if ($rs)
		{
			return selectInput('Section', $rs, $Section, false, '', $id);
		}

		return false;
	}

//--------------------------------------------------------------
	function tab($tabevent,$view)
	{
		$state = ($view==$tabevent) ? 'up' : 'down';
		$out = '<li class="view-mode '.$tabevent.'" id="tab-'.$tabevent.$state.'">';
		$out.=($tabevent!=$view) ? '<a href="javascript:document.article_form.view.value=\''.$tabevent.'\';document.article_form.submit();">'.gTxt($tabevent).'</a>' : gTxt($tabevent);
		$out.='</li>';
		return $out;
	}

//--------------------------------------------------------------
	function getDefaultSection()
	{
		return safe_field("name", "txp_section","is_default=1");
	}

// -------------------------------------------------------------

	function form_pop($form, $id)
	{
		$arr = array(' ');

		$rs = safe_column('name', 'txp_form', "type = 'article' and name != 'default' order by name");

		if ($rs)
		{
			return selectInput('override_form', $rs, $form, true, '', $id);
		}
	}

// -------------------------------------------------------------

	function check_url_title($url_title)
	{
		// Check for blank or previously used identical url-titles
		if (strlen($url_title) === 0)
		{
			return gTxt('url_title_is_blank');
		}

		else
		{
			$url_title_count = safe_count('textpattern', "url_title = '$url_title'");

			if ($url_title_count > 1)
			{
				return gTxt('url_title_is_multiple', array('{count}' => $url_title_count));
			}
		}

		return '';
	}
// -------------------------------------------------------------
	function get_status_message($Status)
	{
		switch ($Status){
			case 3: return gTxt("article_saved_pending");
			case 2: return gTxt("article_saved_hidden");
			case 1: return gTxt("article_saved_draft");
			default: return gTxt('article_posted');
		}
	}
// -------------------------------------------------------------
	function textile_main_fields($incoming)
	{
		global $prefs;

		include_once txpath.'/lib/classTextile.php';
		$textile = new Textile($prefs['doctype']);

		$incoming['Title_plain'] = $incoming['Title'];
		$incoming['Title_html'] = ''; // not used

		if ($incoming['textile_body'] == LEAVE_TEXT_UNTOUCHED) {

			$incoming['Body_html'] = trim($incoming['Body']);

		}elseif ($incoming['textile_body'] == USE_TEXTILE){

			$incoming['Body_html'] = $textile->TextileThis($incoming['Body']);
			$incoming['Title'] = $textile->TextileThis($incoming['Title'],'',1);

		}elseif ($incoming['textile_body'] == CONVERT_LINEBREAKS){

			$incoming['Body_html'] = nl2br(trim($incoming['Body']));
		}

		if ($incoming['textile_excerpt'] == LEAVE_TEXT_UNTOUCHED) {

			$incoming['Excerpt_html'] = trim($incoming['Excerpt']);

		}elseif ($incoming['textile_excerpt'] == USE_TEXTILE){

			$incoming['Excerpt_html'] = $textile->TextileThis($incoming['Excerpt']);

		}elseif ($incoming['textile_excerpt'] == CONVERT_LINEBREAKS){

			$incoming['Excerpt_html'] = nl2br(trim($incoming['Excerpt']));
		}

		return $incoming;
	}
// -------------------------------------------------------------
	function do_pings()
	{
		global $prefs, $production_status;

		# only ping for Live sites
		if ($production_status !== 'live')
			return;

		include_once txpath.'/lib/IXRClass.php';

		callback_event('ping');

		if ($prefs['ping_textpattern_com']) {
			$tx_client = new IXR_Client('http://textpattern.com/xmlrpc/');
			$tx_client->query('ping.Textpattern', $prefs['sitename'], hu);
		}

		if ($prefs['ping_weblogsdotcom']==1) {
			$wl_client = new IXR_Client('http://rpc.pingomatic.com/');
			$wl_client->query('weblogUpdates.ping', $prefs['sitename'], hu);
		}
	}

// -------------------------------------------------------------
	function article_save_pane_state()
	{
		global $event;
		$panes = array('textile_help', 'advanced', 'custom_field', 'image', 'meta', 'recent', 'more');
		$pane = gps('pane');
		if (in_array($pane, $panes))
		{
			set_pref("pane_{$event}_{$pane}_visible", (gps('visible') == 'true' ? '1' : '0'), $event, PREF_HIDDEN, 'yesnoradio', 0, PREF_PRIVATE);
			send_xml_response();
		} else {
			send_xml_response(array('http-status' => '400 Bad Request'));
		}
	}

// -------------------------------------------------------------
	function article_partial_title($rs)
	{
		return pluggable_ui('article_ui', 'title',
			'<label for="title">'.gTxt('title').'</label>'.sp.popHelp('title').br.
				'<input type="text" id="title" name="Title" value="'.escape_title(article_partial_title_value($rs)).'" class="edit" size="40" tabindex="1" />',
			$rs);
	}

// -------------------------------------------------------------
	function article_partial_title_value($rs)
	{
		return $rs['Title'];
	}

// -------------------------------------------------------------
	function article_partial_author($rs)
	{
		extract($rs);
		$out = '<p class="author small">'.gTxt('posted_by').': '.htmlspecialchars($AuthorID).' &#183; '.safe_strftime('%d %b %Y &#183; %X',$sPosted);
		if($sPosted != $sLastMod) {
			$out .= br.gTxt('modified_by').': '.htmlspecialchars($LastModID).' &#183; '.safe_strftime('%d %b %Y &#183; %X',$sLastMod);
		}
		return pluggable_ui('article_ui', 'author', $out.'</p>', $rs);
	}

// -------------------------------------------------------------
	function article_partial_custom_fields($rs)
	{
		global $prefs;
		extract ($prefs);
		extract($rs);
		$cf = '';
		$cfs = getCustomFields();
		$out = '<div id="custom_field_group"'.(($cfs) ? '' : ' class="empty"').'><h3 class="plain lever'.(get_pref('pane_article_custom_field_visible') ? ' expanded' : '').'"><a href="#custom_field">'.gTxt('custom').'</a></h3>'.
			'<div id="custom_field" class="toggle" style="display:'.(get_pref('pane_article_custom_field_visible') ? 'block' : 'none').'">';

		foreach($cfs as $i => $cf_name)
		{
			$custom_x_set = "custom_{$i}_set";
			$custom_x = "custom_{$i}";
			$cf .= ($$custom_x_set !== '' ? custField( $i, $$custom_x_set,  $$custom_x ): '');
		}
		$out .= pluggable_ui('article_ui', 'custom_fields', $cf, $rs);
		return $out.'</div></div>'.n;

	}

// -------------------------------------------------------------
	function article_partial_image($rs)
	{
		$out = '<div id="image_group"><h3 class="plain lever'.(get_pref('pane_article_image_visible') ? ' expanded' : '').'"><a href="#image">'.gTxt('article_image').'</a></h3>'.
			'<div id="image" class="toggle" style="display:'.(get_pref('pane_article_image_visible') ? 'block' : 'none').'">';

		$out .= pluggable_ui('article_ui', 'article_image',
			n.graf('<label for="article-image">'.gTxt('article_image').'</label>'.sp.popHelp('article_image').br.
				fInput('text', 'Image', $rs['Image'], 'edit', '', '', 22, '', 'article-image'), ' class="article-image"'),
			$rs);
		return $out.'</div></div>'.n;
	}

// -------------------------------------------------------------
	function article_partial_keywords($rs)
	{
		return pluggable_ui('article_ui', 'keywords',
			n.graf('<label for="keywords">'.gTxt('keywords').'</label>'.sp.popHelp('keywords').br.
				n.'<textarea id="keywords" name="Keywords" cols="18" rows="5">'.htmlspecialchars(article_partial_keywords_value($rs)).'</textarea>', ' class="keywords"'),
			$rs);
	}

// -------------------------------------------------------------
	function article_partial_keywords_value($rs)
	{
		return str_replace(',', ', ', $rs['Keywords']);
	}

// -------------------------------------------------------------
	function article_partial_url_title($rs)
	{
		return pluggable_ui('article_ui', 'url_title',
			n.graf('<label for="url-title">'.gTxt('url_title').'</label>'.sp.popHelp('url_title').br.
				fInput('text', 'url_title', article_partial_url_title_value($rs), 'edit', '', '', 22, '', 'url-title'), ' class="url-title"'),
			$rs);
	}

// -------------------------------------------------------------
	function article_partial_url_title_value($rs)
	{
		return $rs['url_title'];
	}

// -------------------------------------------------------------
	function article_partial_recent_articles($rs)
	{
		$recents = safe_rows_start("Title, ID",'textpattern',"1=1 order by LastMod desc limit 10");
		$ra = '';

		if ($recents)
		{
			$ra = '<ul class="recent plain-list">';

			while($recent = nextRow($recents))
			{
				if (!$recent['Title'])
				{
					$recent['Title'] = gTxt('untitled').sp.$recent['ID'];
				}

				$ra .= n.t.'<li class="recent-article"><a href="?event=article'.a.'step=edit'.a.'ID='.$recent['ID'].'">'.escape_title($recent['Title']).'</a></li>';
			}

			$ra .= '</ul>';
		}
		return pluggable_ui('article_ui', 'recent_articles', $ra, $rs);
	}

// -------------------------------------------------------------
	function article_partial_article_view($rs)
	{
		extract($rs);
		if ($Status != 4 and $Status != 5)
		{
			$url = '?txpreview='.intval($ID).'.'.time(); // article ID plus cachebuster
		}
		else
		{
			include_once txpath.'/publish/taghandlers.php';
			$url = permlinkurl_id($ID);
		}
		return '<span id="article_partial_article_view">'.sp.sp.'<a href="'.$url.'" class="article-view">'.gTxt('view').'</a></span>';
	}

// -------------------------------------------------------------
	function article_partial_body($rs)
	{
		return pluggable_ui('article_ui', 'body',
			n.graf('<label for="body">'.gTxt('body').'</label>'.sp.popHelp('body').br.
				'<textarea id="body" name="Body" cols="55" rows="31" tabindex="2">'.htmlspecialchars($rs['Body']).'</textarea>', ' class="body"'),
			$rs);
	}

// -------------------------------------------------------------
	function article_partial_excerpt($rs)
	{
		return pluggable_ui('article_ui', 'excerpt',
			n.graf('<label for="excerpt">'.gTxt('excerpt').'</label>'.sp.popHelp('excerpt').br.
				'<textarea id="excerpt" name="Excerpt" cols="55" rows="5" tabindex="3">'.htmlspecialchars($rs['Excerpt']).'</textarea>', ' class="excerpt"'),
			$rs);
	}

// -------------------------------------------------------------
	function article_partial_article_nav($rs)
	{
		return '<p class="article-nav">'.
		($rs['prev_id']
			?	prevnext_link('&#8249;'.gTxt('prev'),'article','edit',
				$rs['prev_id'],gTxt('prev'))
			:	'').n.
		($rs['next_id']
			?	prevnext_link(gTxt('next').'&#8250;','article','edit',
				$rs['next_id'],gTxt('next'))
			:	'').n.
		'</p>';
	}

// -------------------------------------------------------------
	function article_partial_status($rs)
	{
		return pluggable_ui('article_ui', 'status',
			n.n.'<fieldset id="write-status">'.
				n.'<legend>'.gTxt('status').'</legend>'.
				n.status_radio($rs['Status']).
				n.'</fieldset>',
			$rs);
	}

// -------------------------------------------------------------
	function article_partial_categories($rs)
	{
		return pluggable_ui('article_ui', 'categories',
			n.graf('<label for="category-1">'.gTxt('category1').'</label> '.
			'<span class="edit category-edit small">['.eLink('category', '', '', '', gTxt('edit')).']</span>'.br.
			n.category_popup('Category1', $rs['Category1'], 'category-1'), ' class="category category-1"').

			n.graf('<label for="category-2">'.gTxt('category2').'</label>'.br.
			n.category_popup('Category2', $rs['Category2'], 'category-2'), ' class="category category-2"'),
		$rs);
	}

// -------------------------------------------------------------
	function article_partial_section($rs)
	{
		return pluggable_ui('article_ui', 'section',
			n.graf('<label for="section">'.gTxt('section').'</label> '.
				'<span class="edit section-edit small">['.eLink('section', '', '', '', gTxt('edit')).']</span>'.br.
				section_popup($rs['Section'], 'section'), ' class="section"'),
			$rs);
	}

// -------------------------------------------------------------
	function article_partial_posted($rs)
	{
		extract($rs);
		return pluggable_ui('article_ui', 'timestamp',
			n.n.'<fieldset id="write-timestamp">'.
				n.'<legend>'.gTxt('timestamp').'</legend>'.

				n.graf(checkbox('reset_time', '1', $reset_time, '', 'reset_time').'<label for="reset_time">'.gTxt('reset_time').'</label>', ' class="reset-time"').

				n.graf(gTxt('published_at').sp.popHelp('timestamp'), ' class="publish-at"').

				n.graf('<span class="label">'.gtxt('date').'</span>'.sp.
					tsi('year', '%Y', $sPosted).' / '.
					tsi('month', '%m', $sPosted).' / '.
					tsi('day', '%d', $sPosted)
				, ' class="date posted created"'
			).

				n.graf('<span class="label">'.gTxt('time').'</span>'.sp.
					tsi('hour', '%H', $sPosted).' : ' .
					tsi('minute', '%M', $sPosted).' : '.
					tsi('second', '%S', $sPosted)
				, ' class="time posted created"'
			).
			n.'</fieldset>',
			$rs);
	}

// -------------------------------------------------------------
	function article_partial_expires($rs)
	{
		extract($rs);
		return pluggable_ui('article_ui', 'expires',
			n.n.'<fieldset id="write-expires">'.
				n.'<legend>'.gTxt('expires').'</legend>'.

				n.graf('<span class="label">'.gtxt('date').'</span>'.sp.
					tsi('exp_year', '%Y', $sExpires).' / '.
					tsi('exp_month', '%m', $sExpires).' / '.
					tsi('exp_day', '%d', $sExpires)
				, ' class="date expires"'
			).

				n.graf('<span class="label">'.gTxt('time').'</span>'.sp.
					tsi('exp_hour', '%H', $sExpires).' : '.
					tsi('exp_minute', '%M', $sExpires).' : '.
					tsi('exp_second', '%S', $sExpires)
				, ' class="time expires"'
			).
				n.hInput('sExpires', $sExpires).

				n.'</fieldset>',
			$rs);
	}

// -------------------------------------------------------------
	function article_partial_sLastMod($rs)
	{
		return($rs['sLastMod']);
	}

// -------------------------------------------------------------
	function article_partial_sPosted($rs)
	{
		return($rs['sPosted']);
	}

// -------------------------------------------------------------
	function article_validate($rs, &$msg)
	{
		global $prefs, $step, $statuses;

		$constraints = array(
			'Status' => new ChoiceConstraint($rs['Status'], array('choices' => array_keys($statuses), 'message' => 'invalid_status')),
			'Section' => new SectionConstraint($rs['Section']),
			'Category1' => new CategoryConstraint($rs['Category1'], array('type' => 'article')),
			'Category2' => new CategoryConstraint($rs['Category2'], array('type' => 'article')),
		);

		if (!$prefs['articles_use_excerpts']) {
			$constraints['excerpt_blank'] = new BlankConstraint($rs['Excerpt'], array('message' => 'excerpt_not_blank'));
		}

		if (!$prefs['use_comments']) {
			$constraints['annotate_invite_blank'] = new BlankConstraint($rs['AnnotateInvite'], array('message' => 'invite_not_blank'));
			$constraints['annotate_false'] = new FalseConstraint($rs['Annotate'], array('message' => 'comments_are_on'));
		}

		if ($prefs['allow_form_override']) {
			$constraints['override_form'] = new FormConstraint($rs['override_form'], array('type' => 'article'));
		} else {
			$constraints['override_form'] = new BlankConstraint($rs['override_form'], array('message' => 'override_form_not_blank'));
		}

		callback_event_ref('article_ui', "validate_$step", 0, $rs, $constraints);

		$validator = new Validator($constraints);
		if ($validator->validate()) {
			$msg = '';
			return true;
		} else {
			$msg = doArray($validator->getMessages(), 'gTxt');
			$msg = array(join(', ', $msg), E_ERROR);
			return false;
		}
	}
?>
