<?php

/*
	This is Textpattern
	Copyright 2005 by Dean Allen - all rights reserved.

	Use of this software denotes acceptance of the Textpattern license agreement 

$HeadURL$
$LastChangedRevision$

*/

// -------------------------------------------------------------
	function page_title($atts) 
	{
		global $sitename,$id,$c,$q,$parentid,$pg;
		extract(lAtts(array('separator' => ': '),$atts));
		$s = $sitename;
		$sep = $separator;

		$out = $sitename;
		if ($c)        $out = $s.$sep.fetch_category_title($c);
		if ($q)        $out = $s.$sep.gTxt('search_results').$sep.' '.$q;
		if ($pg)       $out = $s.$sep.gTxt('page').' '.$pg;
		if ($id)       $out = $s.$sep.safe_field('Title','textpattern',"ID = $id");
		if ($parentid) $out = $s.$sep.gTxt('comments_on').' '.
			safe_field('Title','textpattern',"ID = '$parentid'");
		return escape_title($out);
	}

// -------------------------------------------------------------
	function css($atts) 	// generates the css src in <head>
	{
		global $s;
		extract(lAtts(array('n' => ''),$atts));
		if ($n) return hu.'textpattern/css.php?n='.$n;
		return hu.'textpattern/css.php?s='.$s;
	}

// -------------------------------------------------------------
	function image($atts) 
	{
		global $img_dir;
		static $cache = array();
		extract(lAtts(array(
			'id'    => '',
			'name'  => '',
			'style' => '',
			'align' => '',
			'class' => ''
		),$atts));
		
		if ($name) {
			if (isset($cache['n'][$name]))
			{
				$rs = $cache['n'][$name];
			} else {
				$name = doSlash($name);
				$rs = safe_row("*", "txp_image", "name='$name' limit 1");
				$cache['n'][$name] = $rs;
			}
		} elseif ($id) {
			if (isset($cache['i'][$id]))
			{
				$rs = $cache['i'][$id];
			} else {
				$id = intval($id);
				$rs = safe_row("*", "txp_image", "id='$id' limit 1");
				$cache['i'][$id] = $rs;
			}
		} else return;
		
		if ($rs) {
			extract($rs);
			$out = array(
				'<img',
				'src="'.hu.$img_dir.'/'.$id.$ext.'"',
				'height="'.$h.'" width="'.$w.'" alt="'.$alt.'"',				
				($style) ? 'style="'.$style.'"' : '',
				($align) ? 'align="'.$align.'"' : '',
				($class) ? 'class="'.$class.'"' : '',
				'/>'
			);
			
			return join(' ',$out);
		}
		return '<txp:notice message="malformed image tag" />';
	}

// -------------------------------------------------------------
    function thumbnail($atts) 
    {
        global $img_dir;
		extract(lAtts(array(
			'id'        => '',
			'name'      => '',
			'thumbnail' => '',
			'poplink'   => '',
			'style'     => '',
			'align'     => ''
		),$atts));
		
        if (!empty($name)) {
            $name = doSlash($name);
            $rs = safe_row("*", "txp_image", "name='$name' limit 1");
        } elseif (!empty($id)) {
            $rs = safe_row("*", "txp_image", "id='$id' limit 1");
        } else return;

        if ($rs) {
            extract($rs);
            if($thumbnail) {
                $out = array(
                    ($poplink)
                    ?   '<a href="'.hu.$img_dir.'/'.$id.$ext.
                            '" onclick="window.open(this.href, \'popupwindow\', \'width='.
                            $w.',height='.$h.',scrollbars,resizable\'); return false;">'
                    :   '',
                    '<img src="'.hu.$img_dir.'/'.$id.'t'.$ext.'"',
                    ' alt="'.$alt.'"',
                    ($style) ? 'style="'.$style.'"' : '',
                    ($align) ? 'align="'.$align.'"' : '',
                    '/>',
                    ($poplink) ? '</a>' : ''
                );
                return join(' ',$out);
            }
        }
    }

// -------------------------------------------------------------
	function output_form($atts) 
	{
		extract(lAtts(array(
			'form' => '',
		), $atts));

		if (!$form)
			trigger_error(gTxt('form_not_specified'));
		else
			return parse(fetch_form($form));

	}

// -------------------------------------------------------------
	function feed_link($atts) // simple link to rss or atom feed
	{
		extract(lAtts(array(
			'label'    => '',
			'break'    => br,
			'wraptag'  => '',
			'category' => '',
			'section'  => '',
			'limit'    => '',
			'flavor'   => 'rss',
			'title'    => gTxt('xml_feed_title'),
		),$atts));
		
		$url = pagelinkurl(array('category'=>$category, 'section'=>$section, 'limit'=>$limit ,$flavor=>'1'));

		$out = '<a href="'.$url.'" title="'.$title.'">'.$label.'</a>';
		return ($wraptag) ? tag($out,$wraptag) : $out;
	}

// -------------------------------------------------------------
	function link_feed_link($atts) // rss or atom feed of links
	{
		extract(lAtts(array(
			'label'    => '',
			'break'    => br,
			'wraptag'  => '',
			'category' => '',
			'flavor'   => 'rss',
			'title'    => gTxt('xml_feed_title'),
		),$atts));
	
		$url = pagelinkurl(array('c'=>$category, $flavor=>'1', 'area'=>'link'));

		$out = '<a href="'.$url.'" title="'.$title.'">'.$label.'</a>';
		
		return ($wraptag) ? tag($out,$wraptag) : $out;
	}

// -------------------------------------------------------------
	function linklist($atts) 
	{
		global $thislink;
		extract(lAtts(array(
			'form'     => 'plainlinks',
			'sort'     => 'linksort',
			'label'    => '',
			'break'    => '',
			'limit'    => '',
			'wraptag'  => '',
			'category' => '',
			'class'    => __FUNCTION__,
			'labeltag' => '',
		),$atts));
	
		$Form = fetch_form($form);
		
		$qparts = array(
			($category) ? "category='$category'" : '1',
			"order by",
			$sort,
			($limit) ? "limit $limit" : ''
		);
		
		$rs = safe_rows_start("*","txp_link",join(' ',$qparts));
	
		if ($rs) {
		
			while ($a = nextRow($rs)) {
				extract($a);
				$linkname = str_replace("& ","&#38; ", $linkname);
				$link = '<a href="'.doSpecial($url).'">'.$linkname.'</a>';
				$linkdesctitle = '<a href="'.doSpecial($url).'" title="'.$description.'">'.$linkname.'</a>';
				$thislink = $a;

				$out = str_replace("<txp:link />", $link, $Form);
				$out = str_replace("<txp:linkdesctitle />", $linkdesctitle, $out);
				$out = str_replace("<txp:link_description />", $description, $out);
			
				$outlist[] = parse($out);
			}
			
			if (!empty($outlist)) {
				return doLabel($label, $labeltag).doWrap($outlist, $wraptag, $break, $class);
			}
		}
		return false;
	}


// -------------------------------------------------------------
	function eE($txt) // convert email address into unicode entities
	{
		 for ($i=0;$i<strlen($txt);$i++) { 
			  $ent[] = "&#".ord(substr($txt,$i,1)).";"; 
		 } 
		 if (!empty($ent)) return join('',$ent); 
	}

// -------------------------------------------------------------
	function email($atts) // simple contact link
	{
		extract(lAtts(array(
			'email'    => '',
			'linktext' => gTxt('contact'),
			'title'    => ''
		),$atts));

		if($email) {
			$out  = array(
				'<a href="'.eE('mailto:'.$email).'"',
				($title) ? ' title="'.$title.'"' : '',
				'>',
				$linktext,
				'</a>'
			);
			return join('',$out);
		}
		return '<txp:notice message="malformed email tag />"';
	}
	
// -------------------------------------------------------------
	function password_protect($atts)
	{
		ob_start();

		extract(lAtts(array(
			'login' => '',
			'pass'  => ''
		),$atts));

		$au = serverSet('PHP_AUTH_USER');
		$ap = serverSet('PHP_AUTH_PW');
		if ($login && $pass) {
			if (!$au || !$ap || $au!= $login || $ap!= $pass) {
				header('WWW-Authenticate: Basic realm="Private"'); 
				txp_die(gTxt('auth_required'), '401');
			}
		}
	}

// -------------------------------------------------------------
	function recent_articles($atts)
	{
		extract(lAtts(array(
			'label'    => '',
			'break'    => br,
			'wraptag'  => '',
			'limit'    => 10,
			'category' => '',
			'sortby'   => 'Posted',
			'sortdir'  => 'desc',
			'class'    => __FUNCTION__,
			'labeltag' => '',
		),$atts));

		$catq = ($category) ? "and (Category1='".doSlash($category)."' 
			or Category2='".doSlash($category)."')" : '';

		$rs = safe_rows_start(
			"*, id as thisid, unix_timestamp(Posted) as posted", 
			"textpattern", 
			"Status = 4 and Posted <= now() $catq order by $sortby $sortdir limit 0,$limit"
		);
		
		if ($rs) {
			$out = array();
			while ($a = nextRow($rs)) {
				extract($a);
				$out[] = href(escape_title($Title),permlinkurl($a));
			}
			if (count($out)) {
				return doLabel($label, $labeltag).doWrap($out, $wraptag, $break, $class);
			}
		}
		return '';
	}

// -------------------------------------------------------------
	function recent_comments($atts)
	{
		extract(lAtts(array(
			'label'    => '',
			'break'    => br,
			'wraptag'  => '',
			'limit'    => 10,
			'class'    => __FUNCTION__,
			'labeltag' => ''
		),$atts));

		$rs = safe_rows_start("*",'txp_discuss',"visible=".VISIBLE." order by posted desc limit 0,$limit");

		if ($rs) {
        	while ($a = nextRow($rs)) {
				extract($a);
				extract(safe_row("Title, Status",'textpattern',"ID=$parentid"));
				If ($Status >= 4)
					$out[] = href($name.' ('.escape_title($Title).')', permlinkurl_id($parentid).'#c'.$discussid);
			}
			if (!empty($out)) {
				return doLabel($label, $labeltag).doWrap($out, $wraptag, $break, $class);
			}
		}
		return '';
	}

// -------------------------------------------------------------
	function related_articles($atts)
	{
		extract(lAtts(array(
			'label'    => '',
			'break'    => br,
			'wraptag'  => '',
			'limit'    => 10,
			'class'    => __FUNCTION__,
			'labeltag' => '',
		),$atts));
		
		global $id,$thisarticle;
		assert_article();
		
		$cats = doSlash(safe_row("Category1,Category2","textpattern", "ID='$id' limit 1"));

		if (!empty($cats['Category1']) or !empty($cats['Category2'])) {
			extract($cats);
			$cat_condition = array();
			if (!empty($Category1)) array_push($cat_condition, "(Category1='$Category1')","(Category2='$Category1')");
			if (!empty($Category2)) array_push($cat_condition, "(Category1='$Category2')","(Category2='$Category2')");
			$cat_condition = (count($cat_condition)) ? join(' or ',$cat_condition) : '';

			$q = array("select *, id as thisid, unix_timestamp(Posted) as posted from `".PFX."textpattern` where Status=4",
				($cat_condition) ? "and (". $cat_condition. ")" :'',
				"and Posted <= now() order by Posted desc limit 0,$limit");

			$rs = getRows(join(' ',$q));
	
			if ($rs) {
				$out = array();
				foreach($rs as $a) {
					extract($a);
					if ($thisid == $id) continue;
					$out[] = href(escape_title($Title),permlinkurl($a));
				}
				if (count($out)) {
					return doLabel($label, $labeltag).doWrap($out, $wraptag, $break, $class);
				}
			}
		}
		return '';
	}

// -------------------------------------------------------------
	function popup($atts)
	{
		global $pretext;
		
		$gc = $pretext['c'];
		$gs = $pretext['s'];
		
		extract(lAtts(array(
			'label'   => '',
			'wraptag' => '',
			'type'    => ''
		),$atts));

		$thetable = ($type=='s') ? 'section' : 'category';
		$out ='<select name="'.$type.'" onchange="submit(this.form)">'.n.
		t.'<option value=""></option>'.n;
		$q[] = "select name,title from `".PFX."txp_".$thetable."` where name != 'default'";
		$q[] = ($thetable=='category') ? "and type='article'" : '';
		$q[] = "order by name";

		$rs = getRows(join(' ',$q));
		if ($rs) {
			foreach ($rs as $a) {
				extract($a);
				if ($name=='root') continue;
				$sel = ($gc==$name or $gs==$name) ? ' selected="selected"' : '';
				$out .= t.t.'<option value="'.urlencode($name).'"'.$sel.'>'.
				$title.'</option>'.n;
				unset($selected);
			}
			$out.= '</select>';
			$out = ($label) ? $label.br.$out : $out;
			$out = ($wraptag) ? tag($out,$wraptag) : $out;
			$out.= '<noscript><input type="submit" value="go" /></noscript>';
			return '<form action="'.hu.'" method="get">'.n.$out.'</form>';
		}
	}

// -------------------------------------------------------------
	function category_list($atts) // output href list of site categories
	{
		extract(lAtts(array(
			'label'    => '',
			'break'    => br,
			'wraptag'  => '',
			'parent'   => '',
			'type'    => 'article',
			'class'    => __FUNCTION__,
			'labeltag' => '',
		),$atts));

		if ($parent) {
			$qs = safe_row("lft,rgt",'txp_category',"name='$parent'");
			if($qs) {
				extract($qs);
				$rs = safe_rows_start(
				  "name,title", 
				  "txp_category","name != 'default' and type='$type' and (lft between $lft and $rgt) order by lft asc"
				);
			}
		} else {
			$rs = safe_rows_start(
			  "name,title", 
			  "txp_category",
			  "name != 'default' and type='$type' order by name"
			);
		}

		if ($rs) {
			$out = array();
			while ($a = nextRow($rs)) {
				extract($a);
				if ($name=='root') continue;
				if($name) $out[] = tag(str_replace("& ","&#38; ", $title),'a',' href="'.pagelinkurl(array('c'=>$name)).'"');
			}
			if (count($out)) {
				return doLabel($label, $labeltag).doWrap($out, $wraptag, $break, $class);
			}			
		}
		return '';
	}

// -------------------------------------------------------------
	function section_list($atts) // output href list of site sections
	{
		global $sitename;
		
		extract(lAtts(array(
			'label'   => '',
			'break'   => br,
			'wraptag' => '',
			'class'    => __FUNCTION__,
			'labeltag' => '',
			'include_default' => '',
		),$atts));
		
		$rs = safe_rows_start("name,title","txp_section","name != 'default' order by name");
		
		if ($rs) {
			$out = array();
			while ($a = nextRow($rs)) {
				extract($a);
				$url = pagelinkurl(array('s'=>$name));
				$out[] = tag($title, 'a', ' href="'.$url.'"');
			}
			if (count($out)) {
				if ($include_default) $out = array_merge(array(tag($sitename,'a', ' href="'.hu.'"')),$out);
				return doLabel($label, $labeltag).doWrap($out, $wraptag, $break, $class);
			}
		}
		return '';
	}

// -------------------------------------------------------------
	function search_input($atts) // input form for search queries
	{
		global $q, $permlink_mode;
		extract(lAtts(array(
			'form'    => 'search_input',
			'wraptag' => 'p',
			'size'    => '15',
			'label'   => 'Search',
			'button'  => '',
			'section' => '',
		),$atts));	

		if ($form) {
			$rs = fetch('form','txp_form','name',$form);
			if ($rs) {
				return $rs;
			}
		}

		$sub = (!empty($button)) ? '<input type="submit" value="'.$button.'" />' : '';
		$out = fInput('text','q',$q,'','','',$size);
		$out = (!empty($label)) ? $label.br.$out.$sub : $out.$sub;
		$out = ($wraptag) ? tag($out,$wraptag) : $out;
	
		if (!$section)
			return '<form action="'.hu.'" method="get">'.$out.'</form>';

		$url = pagelinkurl(array('s'=>$section));	
		return '<form action="'.$url.'" method="get">'.$out.'</form>';
	}

// -------------------------------------------------------------
	function link_to_next($atts, $thing) // link to next article, if it exists
	{
		global $thisarticle, $id;
		global $next_id, $next_title, $next_utitle, $next_posted;
		global $prev_id, $prev_title, $prev_utitle, $prev_posted;
		extract(lAtts(array(
			'showalways'   => 0,
		),$atts));

		if(!is_numeric(@$id)) {
			extract(getNextPrev(@$thisarticle['thisid'], @strftime('%Y-%m-%d %H:%M:%S', $thisarticle['posted']), @$GLOBALS['s']));
		}

		return ($next_id) ? href(parse($thing),permlinkurl_id($next_id)) : ($showalways ? parse($thing) : '');
	}
		
// -------------------------------------------------------------
	function link_to_prev($atts, $thing) // link to next article, if it exists
	{
		global $thisarticle, $id;
		global $next_id, $next_title, $next_utitle, $next_posted;
		global $prev_id, $prev_title, $prev_utitle, $prev_posted;
		extract(lAtts(array(
			'showalways'   => 0,
		),$atts));

		if(!is_numeric(@$id)) {
			extract(getNextPrev($thisarticle['thisid'], @strftime('%Y-%m-%d %H:%M:%S', $thisarticle['posted']), @$GLOBALS['s']));
		}

		return ($prev_id) ? href(parse($thing),permlinkurl_id($prev_id)) : ($showalways ? parse($thing) : '');
	}

// -------------------------------------------------------------
	function next_title()
	{
		return $GLOBALS['next_title'];
	}

// -------------------------------------------------------------
	function prev_title()
	{
		return $GLOBALS['prev_title'];
	}

// -------------------------------------------------------------
	function site_slogan()
	{
		return $GLOBALS['site_slogan'];
	}

// -------------------------------------------------------------
	function link_to_home($atts, $thing = false) 
	{
		extract(lAtts(array(
			'class' => false
		), $atts));

		if ($thing)
		{
			$class = ($class) ? ' class="'.$class.'"' : '';
			return '<a href="'.hu.'"'.$class.'>'.parse($thing).'</a>';
		}

		return hu;
	}

// -------------------------------------------------------------
	function newer($atts, $thing = false, $match='') 
	{
		global $thispage, $permlink_mode, $pretext;

		extract($pretext);

		if (is_array($atts))
		{
			extract($atts);
		}

		if (is_array($thispage))
		{
			extract($thispage);
		}

		if ($numPages > 1 && $pg > 1)
		{
			$nextpg = ($pg - 1 == 1) ? 0 : ($pg - 1);

			$url = pagelinkurl(array(
				'pg' => $nextpg, 
				's' => @$pretext['s'], 
				'c' => @$pretext['c'], 
				'q' => @$pretext['q'], 
				'a' => @$pretext['a']
			));

			if ($thing)
			{
				return '<a href="'.$url.'"'.
				(empty($title) ? '' : ' title="'.$title.'"').
				'>'.$thing.'</a>';
			}

			return $url;
		}

		return;
	}

// -------------------------------------------------------------
	function older($atts, $thing = false, $match = '') 
	{
		global $thispage, $permlink_mode, $pretext;

		extract($pretext);

		if (is_array($atts))
		{
			extract($atts);
		}

		if (is_array($thispage))
		{
			extract($thispage);
		}
		
		if ($numPages > 1 && $pg != $numPages)
		{
			$nextpg = $pg + 1;
			$url = pagelinkurl(array('pg' => $nextpg, 's' => @$pretext['s'], 'c' => @$pretext['c'], 'q' => @$pretext['q'], 'a' => @$pretext['a']));

			if ($thing)
			{
				return '<a href="'.$url.'"'.
				(empty($title) ? '' : ' title="'.$title.'"').
				'>'.$thing.'</a>';
			}

			return $url;
		}

		return;
	}

// -------------------------------------------------------------
	function text($atts) 
	{
		extract(lAtts(array('item' => ''),$atts));
		return ($item) ? gTxt($item) : '';
	}

// -------------------------------------------------------------
	function article_id() 
	{
		global $thisarticle;
		assert_article();

		return $thisarticle['thisid'];
	}

// -------------------------------------------------------------
	function posted($atts) 
	{
		global $dateformat,$archive_dateformat,
				$pg,$c,$thisarticle,$id,$txpcfg;

		assert_article();
		
		$date_offset = $thisarticle['posted'];

		extract(lAtts(array(
			'format' => '',
			'lang'   => '',
			'gmt'    => '',
		),$atts));	

		if($format) {

			$date_out = safe_strftime($format,$date_offset,$gmt,$lang);

		} else {
		
			if ($pg or $id or $c) { 	
				$dateformat = $archive_dateformat; 
			}

			$date_out = safe_strftime($dateformat,$date_offset); 
		}

		if(!empty($wraptag)) $date_out = tag($date_out,$wraptag);

		return $date_out;
	}

// -------------------------------------------------------------
	function comments_count($atts) 
	{
		global $thisarticle;

		assert_article();
		
		$com_count = $thisarticle['comments_count'];
		return ($com_count > 0) ? $com_count : '';
	}

// -------------------------------------------------------------
	function comments_invite($atts) 
	{
		global $thisarticle,$is_article_list;

		assert_article();
		
		extract($thisarticle);
		global $comments_mode;

		if (!$comments_invite)
			$comments_invite = @$GLOBALS['prefs']['comments_default_invite'];

		extract(lAtts(array(
			'class'		=> __FUNCTION__,
			'showcount'	=> true,
			'textonly'	=> false,
			'showalways'=> false,  //FIXME in crockery. This is only for BC.
			'wraptag'   => '',
		), $atts));

		$invite_return = '';
		if (($annotate or $comments_count) && ($showalways or $is_article_list) ) {

			$ccount = ($comments_count && $showcount) ?  ' ['.$comments_count.']' : '';
			if ($textonly)
				$invite_return = $comments_invite.$ccount;
			else
			{
				if (!$comments_mode) {
					$invite_return = doTag($comments_invite, 'a', $class, ' href="'.permlinkurl($thisarticle).'#'.gTxt('comment').'" '). $ccount;
				} else {
					$invite_return = "<a href=\"".hu."?parentid=$thisid\" onclick=\"window.open(this.href, 'popupwindow', 'width=500,height=500,scrollbars,resizable,status'); return false;\"".(($class) ? ' class="'.$class.'"' : '').'>'.$comments_invite.'</a> '.$ccount;
				}
			}
			if ($wraptag) $invite_return = doTag($invite_return, $wraptag, $class);
		}

		return $invite_return;
	}
// -------------------------------------------------------------
	function comments_form($atts)
	{
		global $thisarticle, $comment_preview, $pretext;

		extract(lAtts(array(
			'id'		   => @$pretext['id'],
			'class'		=> __FUNCTION__,
			'preview'   => false,
			'form'		=> 'comment_form',
			'wraptag'	=> ''
		),$atts));

		# don't display the comment form at the bottom, since it's
		# already shown at the top
		if (ps('preview') and empty($comment_preview) and !$preview)
			return '';

		assert_article();
		
		if (is_array($thisarticle)) extract($thisarticle);

		if (@$thisid) $id = $thisid;

		if ($id) {
			if (!checkCommentsAllowed($id)) {
				$out = graf(gTxt("comments_closed"));
			} elseif (gps('commented')!=='') {
				$out = gTxt("comment_posted");
				if (gps('commented')==='0')
					$out .= " ". gTxt("comment_moderated");
				$out = graf($out, ' id="txpCommentInputForm"');
			} else {
				$out = commentForm($id,$atts);
			}

			return (!$wraptag ? $out : doTag($out,$wraptag,$class) );
		}
	}

// -------------------------------------------------------------
	function comments_error($atts)
	{
		extract(lAtts(array(
			'class'		=> __FUNCTION__,
			'break'		=> 'br',
			'wraptag'	=> 'div'
		),$atts));

		$evaluator =& get_comment_evaluator();
		return doWrap($evaluator -> get_result_message(), $wraptag, $break, $class);
	}

// -------------------------------------------------------------
	function if_comments_error($atts, $thing)
	{
		$evaluator =& get_comment_evaluator();
		return parse(EvalElse($thing,(count($evaluator -> get_result_message()) > 0)));
	}

// -------------------------------------------------------------
	# DEPRECATED - provided only for backwards compatibility
	# this functionality will be merged into comments_invite
	# no point in having two tags for one functionality
	function comments_annotateinvite($atts,$thing=NULL)
	{
		global $thisarticle, $pretext;

		extract(lAtts(array(
			'id'		   => @$pretext['id'],
			'class'		=> __FUNCTION__,
			'wraptag'	=> 'h3',
		),$atts));

		assert_article();
		
		if (is_array($thisarticle)) extract($thisarticle);

		if (@$thisid) $id = $thisid;

		if ($id) {
			extract(
				safe_row(
					"Annotate,AnnotateInvite,unix_timestamp(Posted) as uPosted",
						"textpattern", "ID='{$id}'"
				)
			);

			if (!$thing)
				$thing = $AnnotateInvite;

			return (!$Annotate) ? '' : doTag($thing,$wraptag,$class,' id="'.gTxt('comment').'"');
		}
	}

// -------------------------------------------------------------
	function comments($atts)
	{
		global $thisarticle, $prefs, $comment_preview, $pretext;
		extract($prefs);

		extract(lAtts(array(
			'id'		   => @$pretext['id'],
			'form'		=> 'comments',
			'wraptag'	=> ($comments_are_ol ? 'ol' : ''),
			'break'		=> ($comments_are_ol ? 'li' : 'div'),
			'class'		=> __FUNCTION__,
			'breakclass'=> '',
		),$atts));	

		assert_article();
		
		if (is_array($thisarticle)) extract($thisarticle);

		if (@$thisid) $id = $thisid;

		$Form = fetch_form($form);

		if (!empty($comment_preview)) {
			$preview = psas(array('name','email','web','message','parentid','remember'));
			$preview['time'] = time();
			$preview['discussid'] = 0;
			$preview['message'] = markup_comment($preview['message']);
			$GLOBALS['thiscomment'] = $preview;
			$comments[] = parse($Form).n;
			unset($GLOBALS['thiscomment']);
			$out = doWrap($comments,$wraptag,$break,$class,$breakclass);
		}
		else {
			$rs = safe_rows_start("*, unix_timestamp(posted) as time", "txp_discuss",
				"parentid='$id' and visible=".VISIBLE." order by posted asc");
							
			$out = '';

			if ($rs) {
				$comments = array();

				while($vars = nextRow($rs)) {
					$GLOBALS['thiscomment'] = $vars;
					$comments[] = parse($Form).n;
					unset($GLOBALS['thiscomment']);
				}

				$out .= doWrap($comments,$wraptag,$break,$class,$breakclass);
			}
		}

		return $out;
	}
	
// -------------------------------------------------------------
	function comments_preview($atts, $thing='', $me='')
	{
		global $thisarticle;
		if (!ps('preview'))
			return;

		
		extract(lAtts(array(
			'id'		   => @$pretext['id'],
			'form'		=> 'comments',
			'bc'		=> false,  // backwards-compatibility; only internally for old preview behaviour
			'wraptag'	=> '',
			'class'		=> __FUNCTION__,
		),$atts));	

		//FIXME for crockery. This emulates the old hardcoded preview behaviour.
		if ($bc)
		{
			if (@$GLOBALS['pretext']['secondpass'] == false)
				return $me;
			if (@$GLOBALS['pretext']['comments_preview_shown'])
				return '';
			else
				return '<a id="cpreview"></a>'.discuss($id);
		}
		$GLOBALS['pretext']['comments_preview_shown'] = true;

		assert_article();
		
		if (is_array($thisarticle)) extract($thisarticle);

		if (@$thisid) $id = $thisid;

		$Form = fetch_form($form);

		$preview = psas(array('name','email','web','message','parentid','remember'));
		$preview['time'] = time();
		$preview['discussid'] = 0;
		$preview['message'] = markup_comment($preview['message']);

		$GLOBALS['thiscomment'] = $preview;
		$comments = parse($Form).n;
		unset($GLOBALS['thiscomment']);
		$out = doTag($comments,$wraptag,$class);

		return $out;
	}
	
// -------------------------------------------------------------
	function if_comments_preview($atts, $thing)	
	{
		return parse(EvalElse($thing, ps('preview') && checkCommentsAllowed(gps('parentid')) ));
	}

// -------------------------------------------------------------
	function comment_permlink($atts,$thing) 
	{
		global $thisarticle, $thiscomment;

		assert_article();
		
		extract($thiscomment);
		extract(lAtts(array(
			'anchor' => empty($thiscomment['has_anchor_tag']),
		),$atts));

		$dlink = permlinkurl($thisarticle).'#c'.$discussid;
		
		$thing = parse($thing);

		$name = ($anchor ? ' id="c'.$discussid.'"' : '');
	
		return tag($thing,'a',' href="'.$dlink.'"'.$name);
	}

// -------------------------------------------------------------
	function comment_id($atts) 
	{
		global $thiscomment;

		assert_comment();
		
		return $thiscomment['discussid'];
	}

// -------------------------------------------------------------
	function comment_name($atts) 
	{
		global $thiscomment, $prefs;

		assert_comment();
		
		extract($prefs);
		extract($thiscomment);
		$web = str_replace("http://", "", $web);

		if ($email && !$web && !$never_display_email)
			$name = '<a href="'.eE('mailto:'.$email).'"'.(@$comment_nofollow ? ' rel="nofollow"' : '').'>'.$name.'</a>';

		if ($web)
			$name = '<a href="http://'.$web.'" title="'.$web.'"'.(@$comment_nofollow ? ' rel="nofollow"' : '').'>'.$name.'</a>';

		return $name;
	}

// -------------------------------------------------------------
	function comment_email($atts) 
	{
		global $thiscomment;

		assert_comment();
		
		return $thiscomment['email'];
	}

// -------------------------------------------------------------
	function comment_web($atts) 
	{
		global $thiscomment;
		assert_comment();
		
		return $thiscomment['web'];
	}

// -------------------------------------------------------------
	function comment_time($atts) 
	{
		global $thiscomment, $comments_dateformat;

		extract(lAtts(array(
			'format' => $comments_dateformat,
			'gmt'    => '',
			'lang'   => '',
		), $atts));

		assert_comment();
		
		$comment_time = safe_strftime($format, $thiscomment['time'], $gmt, $lang);
		return $comment_time;
	}

// -------------------------------------------------------------
	function comment_message($atts) 
	{
		global $thiscomment;
		assert_comment();
		
		return $thiscomment['message'];
	}

// -------------------------------------------------------------
	function comment_anchor($atts) 
	{
		global $thiscomment;

		assert_comment();
		
		$thiscomment['has_anchor_tag'] = 1;
		return '<a id="c'.$thiscomment['discussid'].'"></a>';
	}

// -------------------------------------------------------------
// DEPRECATED: the old comment message body tag
	function message($atts) 
	{
		return comment_message($atts);
	}

// -------------------------------------------------------------
	function author($atts) 
	{
		global $thisarticle;		

		assert_article();
		
		extract(lAtts(array('link' => ''),$atts));
		$author_name = get_author_name($thisarticle['authorid']);
		return (empty($link))
			? $author_name 
			: tag($author_name, 'a', ' href="'.pagelinkurl(array('author'=>$author_name)).'"');
	}
	
// -------------------------------------------------------------

	function if_author($atts, $thing)
	{
		global $author;		

		extract(lAtts(array(
			'name' => ''
		), $atts));

		if ($name)
		{
			return parse(EvalElse($thing, in_list($author, $name)));
		}

		return parse(EvalElse($thing, !empty($author)));
	}

// -------------------------------------------------------------

	function if_article_author($atts, $thing)
	{
		global $thisarticle;

		extract(lAtts(array(
			'name' => ''
		), $atts));

		$author = $thisarticle['authorid'];

		if ($name)
		{
			return parse(EvalElse($thing, in_list($author, $name)));
		}

		return parse(EvalElse($thing, !empty($author)));
	}
	
// -------------------------------------------------------------

function body($atts) 
	{
		global $thisarticle;

		assert_article();
		
		return $thisarticle['body'];
	}	
	
// -------------------------------------------------------------
	function title($atts) 
	{
		global $thisarticle;
		assert_article();
		
		return escape_title($thisarticle['title']);
	}

// -------------------------------------------------------------
	function excerpt($atts) 
	{
		global $thisarticle;
		assert_article();

		return $thisarticle['excerpt'];	
	}

// -------------------------------------------------------------
	function category1($atts) 
	{
		global $thisarticle;
		assert_article();

		extract(lAtts(array(
			'link' => 0,
			'title' => 0,
		),$atts));
		if ($thisarticle['category1']) {
			$cat_title = ($title ? fetch_category_title($thisarticle['category1']) : $thisarticle['category1']);
			if (!empty($link)) 
				return '<a href="'.pagelinkurl(array('c'=>$thisarticle['category1'])).'">'.
					$cat_title.'</a>';
			return $cat_title;
		}
	}
	
// -------------------------------------------------------------
	function category2($atts) 
	{
		global $thisarticle;
		assert_article();

		extract(lAtts(array(
			'link' => 0,
			'title' => 0,
		),$atts));
		if ($thisarticle['category2']) {
			$cat_title = ($title ? fetch_category_title($thisarticle['category2']) : $thisarticle['category2']);
			if (!empty($link)) 
				return '<a href="'.pagelinkurl(array('c'=>$thisarticle['category2'])).'">'.
					$cat_title.'</a>';
			return $cat_title;
		}
	}
	
// -------------------------------------------------------------
	function category($atts) 
	{
		global $pretext;
		extract(lAtts(array(
			'link' => 0,
			'title' => 0,
			'name' => '',
			'wraptag' => '',
			'section' => @$pretext['s'],
		),$atts));

		if ($name) $cat = $name;
		else $cat = @$pretext['c'];

		if ($cat) {
			$cat_title = ($title ? fetch_category_title($cat) : $cat);
			if ($link) 
				$out = '<a href="'.pagelinkurl(array('c'=>$cat, 's'=>$section)).'">'.
					$cat_title.'</a>';
			else
				$out = $cat_title;

			return doTag($out, $wraptag);
		}
	}

// -------------------------------------------------------------
	function section($atts) 
	{
		global $thisarticle, $pretext;
		extract(lAtts(array(
			'link' => 0,
			'title' => 0,
			'name' => '',
			'wraptag' => '',
		),$atts));

		if ($name) $sec = $name;
		elseif (!empty($thisarticle['section'])) $sec = $thisarticle['section'];
		else $sec = @$pretext['s'];

		if ($sec) {
			$sec_title = ($title ? fetch_section_title($sec) : $sec);
			if (!empty($link)) 
				$out = '<a href="'.pagelinkurl(array('s'=>$sec)).'">'.
					$sec_title.'</a>';
			else
				$out = $sec_title;

			return doTag($out, $wraptag);
		}
	}

// -------------------------------------------------------------
	function keywords($atts) 
	{
		global $thisarticle;
		assert_article();

		return ($thisarticle['keywords']) ? $thisarticle['keywords'] : '';
	}

// -------------------------------------------------------------
	function article_image($atts)
	{
		global $thisarticle, $img_dir;

		assert_article();

		extract(lAtts(array(
			'style' 	=> '',
			'align' 	=> '',
			'thumbnail' => 0
		),$atts));	

		$image = ($thisarticle['article_image']) ? $thisarticle['article_image'] : '';

		if ($image)
		{
			if (!is_numeric($image))
			{
				return '<img src="'.$image.'" alt="" />';
			}
			else			
			{
				$rs = safe_row('*', 'txp_image', "id = '$image'");

				if ($rs)
				{
					if ($thumbnail)
					{
						if ($rs['thumbnail'])
						{
							extract($rs);

							$out = array(
								'<img src="'.hu.$img_dir.'/'.$id.'t'.$ext.'" alt="'.$alt.'"'.
								(!empty($style) ? 'style="'.$style.'"' : '').
								(!empty($align) ? 'align="'.$align.'"' : '').
								' />'
							);

							return join(' ', $out);
						}
					}

					else
					{
						extract($rs);

						$out = array(
							'<img src="'.hu.$img_dir.'/'.$id.$ext.'" width="'.$w.'" height="'.$h.'" alt="'.$alt.'"'.
							(!empty($style) ? 'style="'.$style.'"' : '').
							(!empty($align) ? 'align="'.$align.'"' : '').
							' />'
						);

						return join(' ', $out);
					}
				} //if ($rs)
			}
		} //if ($image)

		return '';
	}

// -------------------------------------------------------------
	function search_result_title($atts) 
	{
		return permlink($atts, '<txp:title />');
	}

// -------------------------------------------------------------
	function search_result_excerpt($atts) 
	{
		global $thisarticle, $pretext;
		extract(lAtts(array(
			'hilight'     => 'strong',
			'limit'       => 5,
		),$atts));
	
		assert_article();	
		extract($pretext);
		extract($thisarticle);
		
		$result = preg_replace("/>\s*</","> <",$body);
		preg_match_all("/\s.{1,50}".preg_quote($q).".{1,50}\s/iu",$result,$concat);

		$r = array();
		for ($i=0; $i < min($limit, count($concat[0])); $i++)
			$r[] = trim($concat[0][$i]);
		$concat = join(" ...\n", $r);

		$concat = strip_tags($concat);
		$concat = preg_replace('/^[^>]+>/U',"",$concat);
		$concat = preg_replace("/(".preg_quote($q).")/i","<$hilight>$1</$hilight>",$concat);
		return ($concat) ? "... ".$concat." ..." : '';
	}

// -------------------------------------------------------------
	function search_result_url($atts) 
	{
		global $thisarticle;
		assert_article();
		
		$l = permlinkurl($thisarticle);
		return permlink($atts, $l);
	}

// -------------------------------------------------------------
	function search_result_date($atts) 
	{
		assert_article();
		return posted($atts);
	}

// -------------------------------------------------------------
	function search_result_count($atts)
	{
		global $thispage;
		$t = @$thispage['grand_total'];
		extract(lAtts(array(
			'text'     => ($t == 1 ? gTxt('article_found') : gTxt('articles_found')),
		),$atts));
		
		return $t . ($text ? ' ' . $text : '');
	}

// -------------------------------------------------------------
	function image_index($atts)
	{
		global $permlink_mode,$s,$c,$p,$txpcfg,$img_dir,$path_to_site;
		extract(lAtts(array(
			'label'    => '',
			'break'    => br,
			'wraptag'  => '',
			'parent'   => '',
			'type'    => 'article',
			'class'    => __FUNCTION__,
			'labeltag' => '',
			'c' => doSlash($c) // Keep the option to override categories due to backward compatiblity
		),$atts));
		$c = doSlash($c);
		
		$rs = safe_rows_start("*", "txp_image","category='$c' and thumbnail=1 order by name");

		if ($rs) {
			$out = array();
			while ($a = nextRow($rs)) {
				extract($a);
				$impath = $img_dir.'/'.$id.'t'.$ext;
				$imginfo = getimagesize($path_to_site.'/'.$impath);
				$dims = (!empty($imginfo[3])) ? ' '.$imginfo[3] : '';
				$url = pagelinkurl(array('c'=>$c, 's'=>$s, 'p'=>$id));
				$out[] = '<a href="'.$url.'">'.
               '<img src="'.hu.$impath.'"'.$dims.' alt="'.$alt.'" />'.'</a>';

			}
			if (count($out)) {
				return doLabel($label, $labeltag).doWrap($out, $wraptag, $break, $class);
			}	
		}
		return '';
	}

// -------------------------------------------------------------
	function image_display($atts) 
	{
		if (is_array($atts)) extract($atts);
		global $s,$c,$p,$img_dir;
		if($p) {
			$rs = safe_row("*", "txp_image", "id='$p' limit 1");
			if ($rs) {
				extract($rs);
				$impath = hu.$img_dir.'/'.$id.$ext;
				return '<img src="'.$impath.
					'" style="height:'.$h.'px;width:'.$w.'px" alt="'.$alt.'" />';
			}
		}
	}

// -------------------------------------------------------------
	function if_comments($atts, $thing)	
	{
		global $thisarticle;
		assert_article();

		return parse(EvalElse($thing, ($thisarticle['comments_count'] > 0)));
	}

// -------------------------------------------------------------
	function if_comments_allowed($atts, $thing)
	{
		global $thisarticle, $pretext;

		$id = gAtt($atts,'id',gps('id'));
		if ($thisarticle['thisid']) $id = $thisarticle['thisid'];
		if (!$id && @$pretext['id']) $id = $pretext['id'];
		return parse(EvalElse($thing, checkCommentsAllowed($id)));
	}

// -------------------------------------------------------------
	function if_comments_disallowed($atts, $thing)
	{
		global $thisarticle, $pretext;

		$id = gAtt($atts,'id',gps('id'));
		if ($thisarticle['thisid']) $id = $thisarticle['thisid'];
		if (!$id && @$pretext['id']) $id = $pretext['id'];
		return parse(EvalElse($thing, !checkCommentsAllowed($id)));
	}

// -------------------------------------------------------------
	function if_individual_article($atts, $thing)	
	{
		global $is_article_list;
		return parse(EvalElse($thing, ($is_article_list == false)));
	}

// -------------------------------------------------------------
	function if_article_list($atts, $thing)	
	{
		global $is_article_list;
		return parse(EvalElse($thing, ($is_article_list == true)));
	}

// -------------------------------------------------------------
	function meta_keywords() 
	{
		global $id_keywords;
		return ($id_keywords)
		?	'<meta name="keywords" content="'.$id_keywords.'" />'
		:	'';
	}

// -------------------------------------------------------------
	function meta_author() 
	{
		global $id_author;
		return ($id_author)
		?	'<meta name="author" content="'.$id_author.'" />'
		:	'';
	}

// -------------------------------------------------------------
	function doWrap($list, $wraptag, $break, $class='', $breakclass='', $atts='')
	{
		$atts = ($class ? $atts.' class="'.$class.'"' : $atts);
		$breakatts = ($breakclass ? ' class="'.$breakclass.'"' : '');

		// non-enclosing breaks
		if (!preg_match('/^\w+$/', $break) or $break == 'br' or $break == 'hr') {
			if ($break == 'br' or $break == 'hr')
				$break = "<$break $breakatts/>";
			return ($wraptag) 
			?	tag(join($break.n,$list),$wraptag,$atts) 
			:	join($break.n,$list);
		}	

		// enclosing breaks should be specified by name only, no '<' or '>'
		if (($wraptag == 'ul' or $wraptag == 'ol') and empty($break))
			$break = 'li';
			
		return ($wraptag)
		? tag(tag(join("</$break>".n."<{$break}{$breakatts}>",$list),$break,$breakatts),$wraptag,$atts)
		: tag(join("</$break>".n."<{$break}{$breakatts}>",$list),$break,$breakatts);
	}

// -------------------------------------------------------------
	function doTag($content, $tag, $class='', $atts='')
	{
		$atts = ($class ? $atts.' class="'.$class.'"' : $atts);

		if (!$tag)
			return $content;
			
		return ($content)
		? tag($content, $tag, $atts)
		: "<$tag $atts />";
	}


// -------------------------------------------------------------
	function doLabel($label='', $labeltag='')
	{
		if ($label) {
			return (empty($labeltag)? $label.'<br />' : tag($label, $labeltag));
		}
		return '';
	}
	
// -------------------------------------------------------------
	function permlink($atts,$thing=NULL)
	{
		global $thisarticle;
		assert_article();

		extract(lAtts(array(
			'style' => '',
			'class' => ''
		),$atts));
		
		$url = permlinkurl($thisarticle);

		if ($thing === NULL)
			return $url;
		
		return tag(parse($thing),'a',' href="'.$url.'" title="'.gTxt('permanent_link').'"'. 
							(($style) ? ' style="'.$style.'"' : '').
							(($class) ? ' class="'.$class.'"' : '')
 			 );
	}

// -------------------------------------------------------------
	function permlinkurl_id($ID)
	{
		$article = safe_row(
			"*,ID as thisid, unix_timestamp(Posted) as posted",
			"textpattern",
			"ID=$ID");
		
		return permlinkurl($article);
	}

// -------------------------------------------------------------
	function permlinkurl($article_array) 
	{
		global $permlink_mode, $prefs;

		if (isset($prefs['custom_url_func']) and is_callable($prefs['custom_url_func']))
			return call_user_func($prefs['custom_url_func'], $article_array);

		if (empty($article_array)) return;
		
		extract($article_array);
		
		if (!isset($title)) $title = $Title;
		if (empty($url_title)) $url_title = stripSpace($title);
		if (empty($section)) $section = $Section; // lame, huh?
		if (empty($posted)) $posted = $Posted;
		if (empty($thisid)) $thisid = $ID;
		
		switch($permlink_mode) {
			case 'section_id_title':
				if ($prefs['attach_titles_to_permalinks'])
				{
					return hu."$section/$thisid/$url_title";
				}else{
					return hu."$section/$thisid/";
				}
			case 'year_month_day_title':
				list($y,$m,$d) = explode("-",date("Y-m-d",$posted));
				return hu."$y/$m/$d/$url_title";
			case 'id_title':
				if ($prefs['attach_titles_to_permalinks'])
				{
					return hu."$thisid/$url_title";
				}else{
					return hu."$thisid/";
				}
			case 'section_title':
				return hu."$section/$url_title";
			case 'title_only':
				return hu."$url_title";	
			case 'messy':
				return hu."index.php?id=$thisid";	
		}
	}
	
// -------------------------------------------------------------	
	function lang($atts)
	{
		return LANG;
	}

// -------------------------------------------------------------
	# DEPRECATED - provided only for backwards compatibility
	function formatPermLink($ID,$Section)
	{
		return permlinkurl_id($ID);
	}

// -------------------------------------------------------------
	# DEPRECATED - provided only for backwards compatibility
	function formatCommentsInvite($AnnotateInvite,$Section,$ID)
	{
		global $comments_mode;

		$dc = safe_count('txp_discuss',"parentid='$ID' and visible=".VISIBLE);

		$ccount = ($dc) ?  '['.$dc.']' : '';
		if (!$comments_mode) {
			return '<a href="'.permlinkurl_id($ID).'/#'.gTxt('comment').
				'">'.$AnnotateInvite.'</a>'. $ccount;
		} else {
			return "<a href=\"".hu."?parentid=$ID\" onclick=\"window.open(this.href, 'popupwindow', 'width=500,height=500,scrollbars,resizable,status'); return false;\">".$AnnotateInvite.'</a> '.$ccount;
		}

	}
// -------------------------------------------------------------
	# DEPRECATED - provided only for backwards compatibility
   function doPermlink($text, $plink, $Title, $url_title)
	{
		global $url_mode;
		$Title = ($url_title) ? $url_title : stripSpace($Title);
		$Title = ($url_mode) ? $Title : '';
		return preg_replace("/<(txp:permlink)>(.*)<\/\\1>/sU",
			"<a href=\"".$plink.$Title."\" title=\"".gTxt('permanent_link')."\">$2</a>",$text);
	}

// -------------------------------------------------------------
	# DEPRECATED - provided only for backwards compatibility
	function doArticleHref($ID,$Title,$url_title,$Section)
	{
		$conTitle = ($url_title) ? $url_title : stripSpace($Title);	
		return ($GLOBALS['url_mode'])
		?	tag($Title,'a',' href="'.hu.$Section.'/'.$ID.'/'.$conTitle.'"')
		:	tag($Title,'a',' href="'.hu.'index.php?id='.$ID.'"');
	}

// -------------------------------------------------------------
// Testing breadcrumbs
	function breadcrumb($atts)
	{
		global $pretext,$thisarticle,$sitename;
		
		extract(lAtts(array(
			'wraptag' => 'p',
			'sep' => '&#160;&#187;&#160;',
			'link' => 'y',
			'label' => $sitename,
			'title' => '',
			'class' => '',
			'linkclass' => 'noline',
		),$atts));
		$linked = ($link == 'y')? true: false; 		
		if ($linked) $label = doTag($label,'a',$linkclass,' href="'.hu.'"');
		
		$content = array();
		extract($pretext);
		if(!empty($s) && $s!= 'default')
		{ 
			$section_title = ($title) ? fetch_section_title($s) : $s;
			$section_title_html = escape_title($section_title);
			$content[] = ($linked)? (
					doTag($section_title_html,'a',$linkclass,' href="'.pagelinkurl(array('s'=>$s)).'"')
				):$section_title_html;
		}
		
		$category = empty($c)? '': $c;

		foreach (getTreePath($category, 'article') as $cat) {
			if ($cat['name'] != 'root') {
				$category_title_html = $title ? escape_title($cat['title']) : $cat['name'];
				$content[] = ($linked)? 
					doTag($category_title_html,'a',$linkclass,' href="'.pagelinkurl(array('c'=>$cat['name'])).'"')
						:$category_title_html;
			}
		}

		//Add the label at the end, to prevent breadcrumb for home page
		if (!empty($content)) $content = array_merge(array($label),$content);
		//Add article title without link if we're on an individual archive page?
		return doTag(join($sep, $content), $wraptag, $class);
	}


//------------------------------------------------------------------------

	function if_excerpt($atts, $thing)
	{
	        global $thisarticle;
           assert_article();
	        # eval condition here. example for article excerpt
	        $excerpt = trim($thisarticle['excerpt']);
	        $condition = (!empty($excerpt))? true : false;
	        return parse(EvalElse($thing, $condition));
	}

//--------------------------------------------------------------------------
// Searches use default page. This tag allows you to use different templates if searching
//--------------------------------------------------------------------------

	function if_search($atts, $thing)
	{
		$searching = gps('q');
		$condition = (!empty($searching))? true : false;
		return parse(EvalElse($thing, $condition));
	}

//--------------------------------------------------------------------------
	function if_category($atts, $thing)
	{
		global $c;

		extract(lAtts(array(
			'name' => '',
		),$atts));

		if (trim($name)) {
			return parse(EvalElse($thing, in_list($c, $name)));
		}

		return parse(EvalElse($thing, !empty($c)));
	}

//--------------------------------------------------------------------------
	function if_article_category($atts, $thing)
	{
		global $thisarticle;
		assert_article();

		extract(lAtts(array(
			'name' => '',
			'number' => '',
		),$atts));

		if ($number)
			$cats = array($thisarticle['category' . $number]);
		else
			$cats = array_unique(array($thisarticle['category1'], $thisarticle['category2']));

		sort($cats);
		if ($name)
			return parse(EvalElse($thing, (in_array($name, $cats))));

		return parse(EvalElse($thing, (array_shift($cats) != '')));
	}

//--------------------------------------------------------------------------
	function if_section($atts, $thing)
	{
		global $pretext;
		extract($pretext);

		extract(lAtts(array(
			'name' => '',
		),$atts));

		$section = ($s == 'default' ? '' : $s);

		return parse(EvalElse($thing, in_list($section, $name)));

	}

//--------------------------------------------------------------------------
	function if_article_section($atts, $thing)
	{
		global $thisarticle;
		assert_article();

		extract(lAtts(array(
			'name' => '',
		),$atts));

		$section = $thisarticle['section'];

		return parse(EvalElse($thing, in_list($section, $name)));
	}

//--------------------------------------------------------------------------
	function php($atts, $thing)
	{
		global $is_article_body, $thisarticle, $prefs;

		ob_start();
		if (empty($is_article_body)) {
			if (!empty($prefs['allow_page_php_scripting']))
				eval($thing);
		}
		else {
			if (!empty($prefs['allow_article_php_scripting'])
				and has_privs('article.php', $thisarticle['authorid']))
				eval($thing);
		}
		return ob_get_clean();
	}
	
//--------------------------------------------------------------------------
	function custom_field($atts)
	{
		global $thisarticle, $prefs;
		assert_article();
		
		extract(lAtts(array(
			'name' => @$prefs['custom_1_set'],
			'escape' => '',
			'default' => '',
		),$atts));

		if (!empty($thisarticle[$name]))
			$out = $thisarticle[$name];
		else
			$out = $default;

		return ($escape == 'html' ? escape_output($out) : $out);
	}	
	
//--------------------------------------------------------------------------
	function if_custom_field($atts, $thing)
	{
		global $thisarticle, $prefs;
		assert_article();
		
		extract(lAtts(array(
			'name' => @$prefs['custom_1_set'],
			'val' => NULL,
		),$atts));

		if ($val !== NULL)
			$cond = (@$thisarticle[$name] == $val);
		else
			$cond = !empty($thisarticle[$name]);

		return parse(EvalElse($thing, $cond));
	}	

// -------------------------------------------------------------
	function site_url($atts) 
	{
		return hu;
	}

// -------------------------------------------------------------
	function img($atts) 
	{
		extract(lAtts(array(
			'src' => '',
		), $atts));

		$img = rtrim(hu, '/').'/'.ltrim($src, '/');

		$out = '<img src="'.$img.'" />';

		return $out;
	}

// -------------------------------------------------------------
	function error_message($atts) 
	{
		return @$GLOBALS['txp_error_message'];
	}

// -------------------------------------------------------------
	function error_status($atts) 
	{
		return @$GLOBALS['txp_error_status'];
	}

// -------------------------------------------------------------
	function if_status($atts, $thing='') 
	{
		global $pretext;

		extract(lAtts(array(
			'status' => '200',
		), $atts));

		$page_status = !empty($GLOBALS['txp_error_code']) 
			? $GLOBALS['txp_error_code'] 
			: $pretext['status'];

		return parse(EvalElse($thing, $status == $page_status));
	}

// -------------------------------------------------------------
	function page_url($atts) 
	{
		global $pretext;

		extract(lAtts(array(
			'type' => 'request_uri',
		), $atts));

		return @htmlspecialchars($pretext[$type]);	
	}

// -------------------------------------------------------------
	function if_different($atts, $thing) 
	{
		static $last;

		$key = md5($thing);

		$cond = EvalElse($thing, 1);

		$out = parse($cond);
		if (empty($last[$key]) or $out != $last[$key]) {
			return $last[$key] = $out;
		}
		else
			return parse(EvalElse($thing, 0));
	}

// -------------------------------------------------------------
	function if_first_article($atts, $thing) 
	{
		global $thisarticle;
		assert_article();
		return parse(EvalElse($thing, !empty($thisarticle['is_first'])));
	}

// -------------------------------------------------------------
	function if_last_article($atts, $thing) 
	{
		global $thisarticle;
		assert_article();
		return parse(EvalElse($thing, !empty($thisarticle['is_last'])));
	}

// -------------------------------------------------------------
	function if_plugin($atts, $thing) 
	{
		global $plugins, $plugins_ver;
		extract(lAtts(array(
			'name'    => '',
			'ver'     => '',
		),$atts));	

		return parse(EvalElse($thing, @in_array($name, $plugins) and (!$ver or version_compare($plugins_ver[$name], $ver) >= 0)));
	}

//--------------------------------------------------------------------------
//File tags functions. 
//--------------------------------------------------------------------------

	function file_download_list($atts)
	{
		global $thisfile;
		
		extract(lAtts(array(
			'form'     => 'files',
			'sort'     => 'filename',
			'label'    => '',
			'break'    => br,
			'limit'    => '10',
			'offset'   => '0',
			'wraptag'  => '',
			'category' => '',
			'class'    => __FUNCTION__,
			'labeltag' => '',
		),$atts));	
		
		$qparts = array(
			($category) ? "category='$category'" : '1',
			"order by",
			$sort,
			($limit) ? "limit $offset, $limit" : ''
		);
		
		$rs = safe_rows_start("*","txp_file",join(' ',$qparts));
	
		if ($rs) {
		
			while ($a = nextRow($rs)) {				
				$thisfile = fileDownloadFetchInfo("id='$a[id]'");
				$outlist[] = file_download(
					array('id'=>$a['id'],'filename'=>$a['filename'],'form'=>$form)
				);
			}
			
			if (!empty($outlist)) {
				if ($wraptag == 'ul' or $wraptag == 'ol') {
					return doLabel($label, $labeltag).doWrap($outlist, $wraptag, $break, $class);
				}	
				
				return ($wraptag) ? tag(join($break,$outlist),$wraptag) : join(n,$outlist);
			}
		}				
		return '';
	}

//--------------------------------------------------------------------------
	function file_download($atts)
	{
		global $thisfile;
		
		extract(lAtts(array(
			'form'=>'files',
			'id'=>'',
			'filename'=>'',
		),$atts));
		
		$where = (!empty($id) && $id != 0)? "id='$id'" : ((!empty($filename))? "filename='$filename'" : '');
		
		if (!empty($id) || !empty($filename)) {
			$thisfile = fileDownloadFetchInfo($where);
		}				
		
		$thing = fetch_form($form);

		return parse($thing);		
	}
	
//--------------------------------------------------------------------------
	function file_download_link($atts,$thing)
	{
		global $permlink_mode, $thisfile;
		extract(lAtts(array(
			'id'=>'',
			'filename'=>'',
		),$atts));
		
		$where = (!empty($id) && $id != 0)? "id='$id'" : ((!empty($filename))? "filename='$filename'" : '');
		
		if (!empty($id) || !empty($filename)) {
			$thisfile = fileDownloadFetchInfo($where);
		}
		
		$out = ($permlink_mode == 'messy') ?
					'<a href="'.hu.'index.php?s=file_download&amp;id='.$thisfile['id'].'">'.parse($thing).'</a>':
					'<a href="'.hu.gTxt('file_download').'/'.$thisfile['id'].'">'.parse($thing).'</a>';								
		return $out;
	}	
//--------------------------------------------------------------------------
	function fileDownloadFetchInfo($where)
	{
		global $file_base_path;		

		$result = array(
				'id' => 0,
				'filename' => '',
				'category' => '',
				'description' => '',
				'downloads' => 0,
				'size' => 0,
				'created' => 0,
				'modified' => 0
			);

		$rs = safe_row('*','txp_file',$where);

		if ($rs) {
			extract($rs);

			$result['id'] = $id;
			$result['filename'] = $filename;
			$result['category'] = $category;
			$result['description'] = $description;
			$result['downloads'] = $downloads;

			// get filesystem info
			$filepath = build_file_path($file_base_path , $filename);

			if (file_exists($filepath)) {
				$filesize = filesize($filepath);
				if ($filesize !== false)
					$result['size'] = $filesize;

				$created = filectime($filepath);
				if ($created !== false)
					$result['created'] = $created;

				$modified = filemtime($filepath);
				if ($modified !== false)
					$result['modified'] = $modified;
			}
		}

		return $result;
	}	
//--------------------------------------------------------------------------
	function file_download_size($atts)
	{
		global $thisfile;		
		
		extract(lAtts(array(
			'decimals' => 2,
			'format' => ''			
		), $atts));
		
		if (is_numeric($decimals) and $decimals >= 0) {
			$decimals = intval($decimals);			
		} else {
			$decimals = 2;
		}
		$t = $thisfile['size'];
		if (!empty($thisfile['size']) && !empty($format)) {
			switch(strtoupper(trim($format))) {
				default:
					$divs = 0;
					while ($t > 1024) {
						$t /= 1024;
						$divs++;
					}
					if ($divs==0) $format = ' b';
					elseif ($divs==1) $format = 'kb';
					elseif ($divs==2) $format = 'mb';
					elseif ($divs==3) $format = 'gb';
					elseif ($divs==4) $format = 'pb';
					break;
				case 'B':
					// do nothing
					break;
				case 'KB':
					$t /= 1024;
					break;
				case 'MB':
					$t /= (1024*1024);
					break;
				case 'GB':
					$t /= (1024*1024*1024);
					break;
				case 'PB':
					$t /= (1024*1024*1024);
				break;
			}
			return number_format($t,$decimals) . $format;
		}
		
		return (!empty($thisfile['size']))? $thisfile['size'] : '';
	}

//--------------------------------------------------------------------------
	function file_download_created($atts)
	{
		global $thisfile;		
		extract(lAtts(array('format'=>''),$atts));		
		return fileDownloadFormatTime(array('ftime'=>$thisfile['created'], 'format' => $format));
	}
//--------------------------------------------------------------------------
	function file_download_modified($atts)
	{
		global $thisfile;		
		extract(lAtts(array('format'=>''),$atts));		
		return fileDownloadFormatTime(array('ftime'=>$thisfile['modified'], 'format' => $format));
	}
//-------------------------------------------------------------------------
	//All the time related file_download tags in one
	//One Rule to rule them all ... now using safe formats
	function fileDownloadFormatTime($params)
	{
		global $prefs;
		extract($params);
		if (!empty($ftime)) {
			return  (!empty($format))? safe_strftime($format,$ftime) : safe_strftime($prefs['archive_dateformat'],$ftime);
		}
		return '';
	}

	function file_download_id($atts)
	{
		global $thisfile;		
		return $thisfile['id'];
	}
	function file_download_name($atts)
	{
		global $thisfile;		
		return $thisfile['filename'];
	} 
	function file_download_category($atts)
	{
		global $thisfile;		
		return $thisfile['category'];
	}
	function file_download_downloads($atts)
	{
		global $thisfile;		
		return $thisfile['downloads'];
	}
	function file_download_description($atts)
	{
		global $thisfile;		
		return $thisfile['description'];
	}	


?>
