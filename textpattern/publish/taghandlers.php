<?php

/*
	This is Textpattern
	Copyright 2005 by Dean Allen - all rights reserved.

	Use of this software denotes acceptance of the Textpattern license agreement 

*/

// -------------------------------------------------------------
	function page_title($atts) 
	{
		global $sitename,$id,$c,$q,$parentid,$pg;
		extract(lAtts(array('separator' => ': '),$atts));
		$s = $sitename;
		$sep = $separator;
		if ($id)       return $s.$sep.safe_field('Title','textpattern',"ID = $id");
		if ($c)        return $s.$sep.$c;
		if ($q)        return $s.$sep.gTxt('search_results').$sep.' '.$q;
		if ($pg)       return $s.$sep.gTxt('page').' '.$pg;
		if ($parentid) return $s.$sep.gTxt('comments_on').' '.
			safe_field('Title','textpattern',"ID = '$parentid'");
		return $sitename;
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
		extract(lAtts(array(
			'id'    => '',
			'name'  => '',
			'style' => '',
			'align' => ''
		),$atts));
		
		if ($name) {
			$name = doSlash($name);
			$rs = safe_row("*", "txp_image", "name='$name' limit 1");
		} elseif ($id) {
			$rs = safe_row("*", "txp_image", "id='$id' limit 1");
		} else return;
		
		if ($rs) {
			extract($rs);
			$out = array(
				'<img',
				'src="'.hu.$img_dir.'/'.$id.$ext.'"',
				'height="'.$h.'" width="'.$w.'" alt="'.$alt.'"',				
				($style) ? 'style="'.$style.'"' : '',
				($align) ? 'align="'.$align.'"' : '',
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
		extract(lAtts(array('form' => ''),$atts));
		return ($form) ? parse(fetch('form','txp_form','name',doSlash($form))) : '';
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
			'flavor'   => 'rss'
		),$atts));
		
		$category = (!$category) ? '' : a.'category='.urlencode($category);
		$section = (!$section) ? '' : a.'section='.urlencode($section);

		$out = '<a href="'.hu.'?'.$flavor.'=1'.
			$category.$section.'" title="XML feed">'.$label.'</a>';
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
			'flavor'   => 'rss'
		),$atts));
	
		$category = (!$category) ? '' : a.'category='.urlencode($category);

		$out = '<a href="'.hu.'?'.$flavor.'=1'.a.'area=link'.$category.
			'" title="XML feed">'.$label.'</a>';
		
		return ($wraptag) ? tag($out,$wraptag) : $out;
	}

// -------------------------------------------------------------
	function linklist($atts) 
	{
		extract(lAtts(array(
			'form'     => 'plainlinks',
			'sort'     => 'linksort',
			'label'    => '',
			'break'    => br,
			'limit'    => '',
			'wraptag'  => '',
			'category' => ''
		),$atts));
	
		$Form = fetch('Form','txp_form','name',$form);
		
		$qparts = array(
			($category) ? "category='$category'" : '1',
			"order by",
			$sort,
			($limit) ? "limit $limit" : ''
		);
		
		$rs = safe_rows_start("*","txp_link",join(' ',$qparts));
	
		if ($rs) {
			if ($label)
				$outlist[] = $label;
		
			while ($a = nextRow($rs)) {
				extract($a);
				$linkname = str_replace("& ","&#38; ", $linkname);
				$link = '<a href="'.doSpecial($url).'">'.$linkname.'</a>';
				$linkdesctitle = '<a href="'.doSpecial($url).'" title="'.$description.'">'.$linkname.'</a>';

				$out = str_replace("<txp:link />", $link, $Form);
				$out = str_replace("<txp:linkdesctitle />", $linkdesctitle, $out);
				$out = str_replace("<txp:link_description />", $description, $out);
			
				$outlist[] = $out;
			}
			
			if (!empty($outlist)) {
				if ($wraptag == 'ul' or $wraptag == 'ol') {
					return doWrap($outlist, $wraptag, $break);
				}	
				
				return ($wraptag) ? tag(join($break,$outlist),$wraptag) : join(n,$outlist);
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
				header('HTTP/1.0 401 Unauthorized');  
				exit(gTxt('auth_required'));
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
			'sortdir'  => 'desc'
		),$atts));

		$catq = ($category) ? "and (Category1='".doSlash($category)."' 
			or Category2='".doSlash($category)."')" : '';

		$rs = safe_rows_start(
			"*, id as thisid, unix_timestamp(Posted) as posted", 
			"textpattern", 
			"Status = 4 and Posted <= now() $catq order by $sortby $sortdir limit 0,$limit"
		);
		
		if ($rs) {
			if ($label) $out[] = $label;
			while ($a = nextRow($rs)) {
				extract($a);
				$out[] = href($Title,permlinkurl($a));
			}
			if (is_array($out)) {
				return doWrap($out, $wraptag, $break);
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
			'limit'    => 10
		),$atts));

		$rs = safe_rows_start("*",'txp_discuss',"visible=1 order by posted desc limit 0,$limit");

		if ($rs) {
			if ($label) $out[] = $label;
        	while ($a = nextRow($rs)) {
				extract($a);
				$Title = safe_field("Title",'textpattern',"ID=$parentid");
				$out[] = href($name.' ('.$Title.')', permlinkurl_id($parentid).'#c'.$discussid);
			}
			if (is_array($out)) {
				return doWrap($out, $wraptag, $break);
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
			'limit'    => 10
		),$atts));
		
		global $thisid,$thisarticle;
		
		if($thisid) $id = $thisid;

		$cats = doSlash(safe_row("Category1,Category2","textpattern", "ID='$id' limit 1"));

		if (!empty($cats[0]) or !empty($cats[1])) {

			$q = array("select *, id as thisid, unix_timestamp(Posted) as posted from ".PFX."textpattern where Status = 4 and ID!='$id'",
				(!empty($cats[0])) ? "and ((Category1='$cats[0]') or (Category2='$cats[0]'))" :'',
				(!empty($cats[1])) ? "or ((Category1='$cats[1]') or (Category2='$cats[1]'))" :'',
				"and Status=4 and Posted <= now() order by Posted desc limit 0,$limit");

			$rs = getRows(join(' ',$q));
	
			if ($rs) {
				if ($label) $out[] = $label;
				foreach($rs as $a) {
					extract($a);
					$out[] = href($Title,permlinkurl($a));
				}
				if (is_array($out)) {
					return doWrap($out, $wraptag, $break);
				}
			}
		}
		return '';
		unset($GLOBALS['thisid']);
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
		$q[] = "select name from ".PFX."txp_".$thetable." where name != 'default'";
		$q[] = ($thetable=='category') ? "and type='article'" : '';
		$q[] = "order by name";

		$rs = getRows(join(' ',$q));
		if ($rs) {
			foreach ($rs as $a) {
				extract($a);
				if ($name=='root') continue;
				$sel = ($gc==$name or $gs==$name) ? 'selected="selected"' : '';
				$out .= t.t.'<option value="'.urlencode($name).'"'.$sel.'>'.
				htmlspecialchars($name).'</option>'.n;
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
			'type'    => 'article'
		),$atts));

		if ($parent) {
			$qs = safe_row("lft,rgt",'txp_category',"name='$parent'");
			if($qs) {
				extract($qs);
				$rs = safe_column(
					'name',
					'txp_category',
					"name != 'default' and type='$type' and (lft between $lft and $rgt) order by lft asc"			
				);
			}
		} else {
			$rs = safe_column(
				"name", 
				"txp_category",
				"name != 'default' and type='$type' order by name"
			);
		}

		if ($rs) {
			if ($label) $out[] = $label;
			foreach ($rs as $a) {
				if ($a=='root') continue;
				if($a) $out[] = tag(str_replace("& ","&#38; ", $a),'a',' href="'.hu.'?c='.urlencode($a).'"');
			}
			if (is_array($out)) {
				return doWrap($out, $wraptag, $break);
			}
		}
		return '';
	}

// -------------------------------------------------------------
	function section_list($atts) // output href list of site sections
	{
		extract(lAtts(array(
			'label'   => '',
			'break'   => br,
			'wraptag' => ''
		),$atts));
		
		$rs = safe_column("name","txp_section","name != 'default' order by name");
		
		if ($rs) {
			if ($label) $out[] = $label;
			foreach ($rs as $a) {
				if($a) {
					if($GLOBALS['permlink_mode'] == 'messy') {
						$out[] = tag(htmlspecialchars($a),'a',' href="'.hu.'?s='.urlencode($a).'"');
					} else {
						$out[] = tag(htmlspecialchars($a),'a',' href="'.hu.urlencode($a).'/"');
					}
				}
			}
			if (is_array($out)) {
				return doWrap($out, $wraptag, $break);
			}
		}
		return '';
	}

// -------------------------------------------------------------
	function search_input($atts) // input form for search queries
	{
		global $q;
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
	
		return $permlink_mode == 'messy' 
			? '<form action="'.hu.'?section='.$section.'" method="get">'.$out.'</form>'
			: '<form action="'.hu.$section.'/" method="get">'.$out.'</form>';
	}

// -------------------------------------------------------------
	function link_to_next($atts, $thing) // link to next article, if it exists
	{
		global $next_id;		
		return ($next_id) ? href(parse($thing),permlinkurl_id($next_id)) : '';
	}
		
// -------------------------------------------------------------
	function link_to_prev($atts, $thing) // link to next article, if it exists
	{
		global $prev_id;		
		return ($prev_id) ? href(parse($thing),permlinkurl_id($prev_id)) : '';
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
	function link_to_home($atts, $thing) 
	{
		if (!empty($thing)) {
			return '<a href="'.hu.'" class="noline">'.parse($thing).'</a>';
		}
	}

// -------------------------------------------------------------
	function newer($atts, $thing, $match='') 
	{
		global $thispage,$permlink_mode, $pretext;
		extract($pretext);
				
		if (is_array($atts)) extract($atts);
		if (is_array($thispage)) { 
			extract($thispage);
		} else { 
			//Exclude search results
			return (!$q)?$match:''; 
		}

		//Fix a problem when paging /author/author_name and simmilar url schemas
		//chopUrl explodes by slashes
		$req_uri = $_SERVER['REQUEST_URI'];
		if (!empty($_SERVER['QUERY_STRING'])) 
			$req_uri = str_replace('?'.$_SERVER['QUERY_STRING'], '', $req_uri);
		if(strlen(strrchr($req_uri,'/')) != 1) $req_uri.='/';
		
		if ($pg > 1) {
			$out = array(
				'<a href="'.$req_uri.'?pg='.($pg - 1),
				($c) ? a.'c='.urlencode($c) : '',
				($s && $permlink_mode=='messy') ? a.'s='.urlencode($s) : '',
				'"',
				(empty($title)) ? '' : ' title="'.$title.'"',
				'>',
				$thing,
				'</a>');
			return join('',$out);
		} else return;
	}

// -------------------------------------------------------------
	function older($atts, $thing, $match='') 
	{
		global $thispage,$permlink_mode, $pretext;
		extract($pretext);
		if (is_array($atts)) extract($atts);
		if (is_array($thispage)) {
			extract($thispage); 
		} else { 
			//Exclude search results
			return (!$q)?$match:''; 
		}
		
		//Fix a problem when paging /author/author_name and simmilar url schemas
		//chopUrl explodes by slashes
		$req_uri = $_SERVER['REQUEST_URI'];
		if (!empty($_SERVER['QUERY_STRING'])) 
			$req_uri = str_replace('?'.$_SERVER['QUERY_STRING'], '', $req_uri);
		if(strlen(strrchr($req_uri,'/')) != 1) $req_uri.='/';
		
		if ($pg != $numPages) {
			$out = array(
				'<a href="'.$req_uri.'?pg='.($pg + 1),
				($c) ? a.'c='.urlencode($c) : '',
				($s && $permlink_mode == 'messy') ? a.'s='.urlencode($s) : '',
				'"',
				(empty($title)) ? '' : ' title="'.$title.'"',
				'>',
				$thing,
				'</a>');
			return join('',$out);
		} else return;
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
		return $thisarticle['thisid'];
	}

// -------------------------------------------------------------
	function posted($atts) 
	{
		global $dateformat,$archive_dateformat,$timeoffset,
				$pg,$c,$thisarticle,$id,$txpcfg;

		$date_offset = $thisarticle['posted'];

		extract(lAtts(array(
			'format' => '',
			'lang'   => ''
		),$atts));	

		if($format) {
			if($format=='since') {
				$date_out = since($thisarticle['posted']);
			} else {
				$date_out = safe_strftime($format,$date_offset);
			}

		} else {
		
			if ($pg or $id or $c) { 	
				$dateformat = $archive_dateformat; 
			}

			if($dateformat == "since") { 
				$date_out = since($thisarticle['posted']); 
			} else { 
				$date_out = safe_strftime($dateformat,$date_offset); 
			}
		}

		if(!empty($wraptag)) $date_out = tag($date_out,$wraptag);

		return $date_out;
	}

// -------------------------------------------------------------
	function comments_count($atts) 
	{
		global $thisarticle;
		return ($thisarticle['comments_count'] > 0) ? $thisarticle['comments_count'] : '';
	}

// -------------------------------------------------------------
	function comments_invite($atts) 
	{
		global $thisarticle,$is_article_list;
		extract($thisarticle);
		global $comments_mode;

		if ($if_comments  && $is_article_list) {

			$ccount = ($comments_count) ?  ' ['.$comments_count.']' : '';
	
			if (!$comments_mode) {
				return '<a href="'.permlinkurl($thisarticle).'#'.gTxt('comment').
					'">'.$comments_invite.'</a>'. $ccount;
			} else {
				return "<a href=\"".hu."?parentid=$thisid\" onclick=\"window.open(this.href, 'popupwindow', 'width=500,height=500,scrollbars,resizable,status'); return false;\">".$comments_invite.'</a> '.$ccount;
	
			}
		}
	}

// -------------------------------------------------------------
	function author($atts) 
	{
		global $thisarticle;
		return $thisarticle['author'];	
	}
	
// -------------------------------------------------------------
	function body($atts) 
	{
		global $thisarticle;
		return $thisarticle['body'];
	}	
	
// -------------------------------------------------------------
	function title($atts) 
	{
		global $thisarticle;
		return $thisarticle['title'];	
	}

// -------------------------------------------------------------
	function excerpt($atts) 
	{
		global $thisarticle;
		return $thisarticle['excerpt'];	
	}

// -------------------------------------------------------------
	function category1($atts) 
	{
		global $thisarticle;
		extract(lAtts(array('link' => ''),$atts));
		if ($thisarticle['category1']) {
			if (!empty($link)) 
				return '<a href="'.hu.strtolower(gTxt('category')).'/'.
					strtolower(urlencode($thisarticle['category1'])).'">'.
					$thisarticle['category1'].'</a>';
			return $thisarticle['category1'];
		}
	}
	
// -------------------------------------------------------------
	function category2($atts) 
	{
		global $thisarticle;
		extract(lAtts(array('link' => ''),$atts));
		if ($thisarticle['category2']) {
			if (!empty($link)) 
				return '<a href="'.hu.strtolower(gTxt('category')).'/'.
					strtolower(urlencode($thisarticle['category2'])).'">'.
					$thisarticle['category2'].'</a>';
			return $thisarticle['category2'];
		}
	}

// -------------------------------------------------------------
	function section($atts) 
	{
		global $thisarticle;
		extract(lAtts(array('link' => ''),$atts));
		if ($thisarticle['section']) {
			if (!empty($link)) 
				return '<a href="'.hu.strtolower(gTxt('section')).'/'.
					strtolower(urlencode($thisarticle['section'])).'/">'.
					$thisarticle['section'].'</a>';
			return $thisarticle['section'];
		}
	}

// -------------------------------------------------------------
	function keywords($atts) 
	{
		global $thisarticle;
		return ($thisarticle['keywords']) ? $thisarticle['keywords'] : '';
	}

// -------------------------------------------------------------
	function article_image($atts) 
	{
		global $thisarticle,$img_dir;
		extract(lAtts(array(
			'style' => '',
			'align' => ''
		),$atts));	

		$theimage = ($thisarticle['article_image']) ? $thisarticle['article_image'] : '';
		
		if ($theimage) {
		
			if (is_numeric($theimage)) {
				$rs = safe_row("*",'txp_image',"id='$theimage'");
				if ($rs) {
					extract($rs);
					$out = array(
						'<img',
						'src="'.hu.$img_dir.'/'.$id.$ext.'"',
						'height="'.$h.'" width="'.$w.'" alt="'.$alt.'"',
						(!empty($style)) ? 'style="'.$style.'"' : '',
						(!empty($align)) ? 'align="'.$align.'"' : '',
						' />'
					);			
					return join(' ',$out);
				}
			} else {
				return '<img src="'.$theimage.'" alt="" />';
			}
		}
	}

// -------------------------------------------------------------
	function search_result_title() 
	{
		global $this_result;
		return $this_result['search_result_title'];
	}

// -------------------------------------------------------------
	function search_result_excerpt() 
	{
		global $this_result;
		return $this_result['search_result_excerpt'];
	}

// -------------------------------------------------------------
	function search_result_url() 
	{
		global $this_result;
		return $this_result['search_result_url'];
	}

// -------------------------------------------------------------
	function search_result_date() 
	{
		global $this_result;
		return $this_result['search_result_date'];
	}


// -------------------------------------------------------------
	function image_index($atts)
	{
		global $permlink_mode,$s,$c,$p,$txpcfg,$img_dir,$path_to_site;
		if (is_array($atts)) extract($atts);
		$c = doSlash($c);
		
		$rs = safe_rows_start("*", "txp_image","category='$c' and thumbnail=1 order by name");

		if ($rs) {
			while ($a = nextRow($rs)) {
				extract($a);
				$impath = $img_dir.'/'.$id.'t'.$ext;
				$imginfo = getimagesize($path_to_site.'/'.$impath);
				$dims = (!empty($imginfo[3])) ? ' '.$imginfo[3] : '';
				if($permlink_mode == 'messy'){
					$out[] = '<a href="'.hu.'?c='.urlencode($c).a.'s='.urlencode($s).a.'p='.$id.'">'.
					'<img src="'.hu.$impath.'"'.$dims.' alt="'.$alt.'" />'.'</a>';
				} else {
					$out[] = '<a href="'.hu.$s.'/?c='.urlencode($c).a.'p='.$id.'">'.
					'<img src="'.hu.$impath.'"'.$dims.' alt="'.$alt.'" />'.'</a>';
				}

			}
			return join('',$out);
		}
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
		return ($thisarticle['if_comments']) ? parse($thing) : '';
	}

// -------------------------------------------------------------
	function if_individual_article($atts, $thing)	
	{
		global $is_article_list;
		return ($is_article_list == false) ? parse($thing) : '';
	}

// -------------------------------------------------------------
	function if_article_list($atts, $thing)	
	{
		global $is_article_list;
		return ($is_article_list == true) ? parse($thing) : '';
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
	function doWrap($list, $wraptag, $break)
	{
		if ($wraptag == 'ul' or $wraptag == 'ol') {
			return tag(tag(join('</li>'.n.'<li>',$list),'li'),$wraptag);
		}
		return ($wraptag) 
		?	tag(join($break.n,$list),$wraptag) 
		:	join($break.n,$list);
	}

// -------------------------------------------------------------
	function permlink($atts,$thing=NULL)
	{
		global $thisarticle;
		
		$url = permlinkurl($thisarticle);

		if ($thing === NULL)
			return $url;
		
		return tag(parse($thing),'a',' href="'.$url.'" title="'.gTxt('permanent_link').'"');
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
		global $permlink_mode, $txpac;

		if (isset($txpac['custom_url_func']) and is_callable($txpac['custom_url_func']))
			return call_user_func($txpac['custom_url_func'], $article_array);

		extract($article_array);
		
		if (!isset($title)) $title = $Title;
		if (empty($url_title)) $url_title = stripSpace($title);
		if (empty($section)) $section = $Section; // lame, huh?
		if (empty($posted)) $posted = $Posted;
		if (empty($thisid)) $thisid = $ID;
		
		switch($permlink_mode) {
			case 'section_id_title':
				if ($txpac['attach_titles_to_permalinks'])
				{
					return hu."$section/$thisid/$url_title";
				}else{
					return hu."$section/$thisid/";
				}
			case 'year_month_day_title':
				list($y,$m,$d) = explode("-",date("Y-m-d",$posted));
				return hu."$y/$m/$d/$url_title";
			case 'id_title':
				if ($txpac['attach_titles_to_permalinks'])
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

		$dc = safe_count('txp_discuss',"parentid='$ID' and visible=1");

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
			'label' => $sitename
		),$atts));
		$linked = ($link == 'y')? true: false; 		
		if ($linked) $label = '<a href="'.hu.'" class="noline">'.$sitename.'</a>';
		
		$content = array();
		
		extract($pretext);
		if(!empty($s) && $s!= 'default')
		{ 
			$content[] = ($linked)? (
				($GLOBALS['permlink_mode'] == 'messy')?
					tag(htmlspecialchars($s),'a',' href="'.hu.'?s='.urlencode($s).'"'):
					tag(htmlspecialchars($s),'a',' href="'.hu.urlencode($s).'/"')
				):$s;
		}
		
		$category = empty($c)? '': $c;
		$cattree = array();
		if (!empty($category)){

			do {
				$parent = safe_field('parent','txp_category',"name='$category'");
				//Use new /category/category_name scheme here too?
					$cattree[] = ($linked)? 
						tag(str_replace("& ","&#38; ", $category),'a',' href="'.hu.'?c='.urlencode($category).'"')
							:$category;
					$category = $parent;
					unset($parent);
			}		
			while ($category!='root');
		}
		if (!empty($cattree))
		{
			$cattree = array_reverse($cattree);
			$content = array_merge($content, $cattree);
		}
		//Add date month permlinks?
//		$year = ''; 
//		$month = '';
//		$date = '';
		//Add the label at the end, to prevent breadcrumb for home page
		if (!empty($content)) $content = array_merge(array($label),$content);
		//Add article title without link if we're on an individual archive page?
		return doWrap($content, $wraptag, $sep);
	}

// -------------------------------------------------------------
	//Doble Conditionals: if_search, if_excerpt	
	function EvalElse($thing, $condition)
	{
	         #party!
	         $cdtn = '/<txp:else\b\s*\/\s*>/sU';
	         $counter = preg_match_all($cdtn,$thing,$matches);
	         # Nested conditional tags
	         if ($counter>1)
	         {
	         	 $chunks = array();
		         $f = '/(.*?)(<txp:(\w+)\b>.+<txp:else\b\s*\/>.+<\/txp:\\3>)(.*?)/sU';
		         $splited = preg_split($f, $thing, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
		         if(sizeof($splited)>1){
		         		$chunks = array();
		                $new_thing = '';
		                for($i=0;$i<sizeof($splited);$i++){
		                        $new_thing .= $splited[$i];
		                        if(!empty($splited[$i+2])){
		                                $key = trim($splited[$i+2]);
		                                $new_thing.="<txp_chunk:$key />";
		                                $chunks[$key] = $splited[$i+1];
		                        }
		                        $i+=2;
		                }
		         }		         
	         }
	         #No conditional tags nested. Simply explode them
	         $thing = (!empty($new_thing))?$new_thing:$thing;
	         $match = preg_split($cdtn, $thing, -1, PREG_SPLIT_DELIM_CAPTURE);
	         $thing = $match[0];
	         $otherwise = (!empty($match[1]))?$match[1]:'';
	
	        $where = ($condition)? $thing : $otherwise;
	        if(!empty($new_thing)){
	                $g = '/<txp_chunk:(\S+)\b \/>/s';
	                $success = preg_match($g,$where, $matching);
	                if($success){
	                     $repl = $chunks[$matching[1]];
	                     $replaced = preg_replace($g, $repl, $where);
	                     return $replaced;
	                }
	        }
	        return $where;
	}
	
//------------------------------------------------------------------------

	function if_excerpt($atts, $thing)
	{
	        global $thisarticle;
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
			return parse(EvalElse($thing, ($c == $name)));
		}

		return parse(EvalElse($thing, !empty($c)));
	}

//--------------------------------------------------------------------------
	function if_article_category($atts, $thing)
	{
		global $thisarticle;

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
	function php($atts, $thing)
	{
		global $is_article_body, $thisarticle, $txpac;

		ob_start();
		if (empty($is_article_body)) {
			if (!empty($txpac['allow_page_php_scripting']))
				eval($thing);
		}
		else {
			if (!empty($txpac['allow_article_php_scripting'])
				and has_privs('article.php', $thisarticle['authorid']))
				eval($thing);
		}
		return ob_get_clean();
	}
	
//--------------------------------------------------------------------------
	function custom_field($atts)
	{
		global $thisarticle, $txpac;
		
		extract(lAtts(array(
			'name' => @$txpac['custom_1_set'],
		),$atts));

		if (isset($thisarticle[$name]))
			return $thisarticle[$name];
	}	
	
//--------------------------------------------------------------------------
	function if_custom_field($atts, $thing)
	{
		global $thisarticle, $txpac;
		
		extract(lAtts(array(
			'name' => @$txpac['custom_1_set'],
			'val' => NULL,
		),$atts));

		if ($val !== NULL)
			$cond = (@$thisarticle[$name] == $val);
		else
			$cond = !empty($thisarticle[$name]);

		return parse(EvalElse($thing, $cond));
	}	
	
//--------------------------------------------------------------------------
//File tags functions. 
//--------------------------------------------------------------------------

	function file_download_list($atts)
	{
		extract(lAtts(array(
			'form'     => 'files',
			'sort'     => 'filename',
			'label'    => '',
			'break'    => br,
			'limit'    => '10',
			'wraptag'  => '',
			'category' => ''
		),$atts));	
		
		$qparts = array(
			($category) ? "category='$category'" : '1',
			"order by",
			$sort,
			($limit) ? "limit $limit" : ''
		);
		
		$rs = safe_rows_start("*","txp_file",join(' ',$qparts));
	
		if ($rs) {
			if ($label) $outlist[] = $label;
		
			while ($a = nextRow($rs)) {
				
				$finfo = fileDownloadFetchInfo("id='$a[id]'");
				$outlist[] = file_download(
					array('id'=>$a['id'],'filename'=>$a['filename'],'form'=>$form),
					array('finfo'=>$finfo,'form'=>fetch('Form','txp_form','name',$form))
				);
			}
			
			if (!empty($outlist)) {
				if ($wraptag == 'ul' or $wraptag == 'ol') {
					return doWrap($outlist, $wraptag, $break);
				}	
				
				return ($wraptag) ? tag(join($break,$outlist),$wraptag) : join(n,$outlist);
			}
		}				
		return '';
	}

//--------------------------------------------------------------------------
	function file_download($atts, $called = array())
	{
		if (empty($called)){
			extract(lAtts(array(
				'form'=>'files',
				'id'=>'',
				'filename'=>'',
			),$atts));
			
			$thing = fetch('Form','txp_form','name',$form);
		}else{
			//do not repeat db queries if we've got the data
			extract($called['finfo']);
			$thing = $called['form'];
		}

		$where = (!empty($id) && $id != 0)? "id='$id'" : ((!empty($filename))? "filename='$filename'" : '');
		if (!empty($where)){			
			$out = fileDownloadTags($where, $thing, $called);
			return parse($out);		
		}
		return '';
	}
	
//--------------------------------------------------------------------------
	function file_download_link($atts,$thing)
	{
		global $permlink_mode;
		extract(lAtts(array(
			'id'=>'',
			'filename'=>'',
		),$atts));
		
		$out = '';
		
		$where = (!empty($id) && $id != 0)? "id='$id'" : ((!empty($filename))? "filename='$filename'" : '');
		
		$thing = ($permlink_mode == 'messy') ?
					'<a href="'.hu.'index.php?s=file_download&id=<txp:file_download_id />">'.$thing.'</a>':
					'<a href="'.hu.gTxt('file_download').'/<txp:file_download_id />">'.$thing.'</a>';		
		
		
		$out = fileDownloadTags($where, $thing);		
		return $out;
	}	
//--------------------------------------------------------------------------
	
	function fileDownloadTags($where, $thing, $called = array())
	{
		$finfo = (!empty($called))? $called['finfo'] : fileDownloadFetchInfo($where);
		
		$out = str_replace("<txp:file_download_id />", $finfo['id'], $thing);
		$out = str_replace("<txp:file_download_name />", $finfo['filename'], $out);
		$out = str_replace("<txp:file_download_category />", $finfo['category'], $out);
		$out = str_replace("<txp:file_download_downloads />", $finfo['downloads'], $out);
		$out = str_replace("<txp:file_download_description />", $finfo['description'], $out);
		$out = wrapedTag('file_download_size',$out, array('fsize'=>$finfo['size']));
		$out = wrapedTag('file_download_created',$out, array('ftime'=>$finfo['created']));
		$out = wrapedTag('file_download_modified',$out, array('ftime'=>$finfo['modified']));
		
		//If we're calling this tag from <txp:file_download /> do not call file_download_link
		//This prevent another call to this function foreach file if we're listing
		global $permlink_mode;
		
		$link = ($permlink_mode == 'messy') ?
					'<a href="'.hu.'index.php?s=file_download&id='.$finfo['id'].'">':
					'<a href="'.hu.gTxt('file_download').'/'.$finfo['id'].'">';
						
		preg_match('/<txp:file_download_link>(.*)<\/txp:file_download_link>/s',$out, $matched);
		if ($matched)
		{
			$out = str_replace($matched[0], $link.$matched[1].'</a>',$out);
		}		
		
		return $out;		
	}
//--------------------------------------------------------------------------
	//This function code could be inside of fileDownloadTags
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
	//Useful to be able to add attributes to tags which always are wraped
	//inside another one, without any need to populate globals.
	//Use only with single tags.
	function wrapedTag($tagname, $thing, $add_atts = array())
	{
		$rg = '/<txp:'.$tagname.'\b(.*)\/>/';
		if(preg_match($rg, $thing, $matches)){
			$tag_atts = splat(trim($matches[1]));
			$atts = array_merge($tag_atts, $add_atts);
			$func_res = call_user_func($tagname, $atts);
			$thing = preg_replace($rg,$func_res,$thing);			
		}
		return $thing;
	}
//--------------------------------------------------------------------------
	// Not properly a tag, but a wraped one
	function file_download_size($params)
	{
		extract($params);
		if (!isset($decimals) || $decimals < 0) $decimals = 2;
		if (is_numeric($decimals)) {
			$decimals = intval($decimals);			
		} else {
			$decimals = 2;
		}
		$t = $fsize;
		if (!empty($fsize) && !empty($format)) {
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
		
		return (!empty($fsize))? $fsize : '';
	}

//--------------------------------------------------------------------------
	function file_download_created($params)
	{
		return fileDownloadFormatTime($params);
	}
//--------------------------------------------------------------------------
	function file_download_modified($params)
	{
		return fileDownloadFormatTime($params);
	}
//-------------------------------------------------------------------------
	//All the time related file_download tags in one
	//One Rule to rule them all ... now using safe formats
	function fileDownloadFormatTime($params)
	{
		global $prefs;
		extract($params);
		if (!empty($ftime)) {
			return  (isset($format))? safe_strftime($format,$ftime) : safe_strftime($prefs['archive_dateformat'],$ftime);
		}
		return '';
	}

	
?>
