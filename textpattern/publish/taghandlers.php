<?php

/*
	This is Textpattern
	Copyright 2004 by Dean Allen - all rights reserved.

	Use of this software denotes acceptance of the Textpattern license agreement 

*/

// -------------------------------------------------------------
	function page_title($atts) 
	{
		global $sitename,$id,$c,$q,$parentid,$pg;
		if (is_array($atts)) extract($atts);
		$sep = (!empty($separator)) ? $separator : ': ';
		$s = $sitename;
		if ($id)       return $s.$sep.fetch('Title','textpattern','ID',$id);
		if ($c)        return $s.$sep.$c;
		if ($q)        return $s.$sep.gTxt('search_results').$sep.' '.$q;
		if ($pg)       return $s.$sep.gTxt('page').' '.$pg;
		if ($parentid) return $s.$sep.'comments on '.
			fetch('Title','textpattern','ID',$parentid);
		return $sitename;
	}

// -------------------------------------------------------------
	function css($atts) 	// generates the css src in <head>
	{
		global $s;
		if (is_array($atts)) extract($atts);
		if (!empty($n)) return hu.'textpattern/css.php?n='.$n;
		return hu.'textpattern/css.php?s='.$s;
	}

// -------------------------------------------------------------
	function image($atts) 
	{
		global $pfr,$img_dir;
		if (is_array($atts)) extract($atts);
		if (!empty($name)) {
			$name = doSlash($name);
			$rs = safe_row("*", "txp_image", "name='$name' limit 1");
		} elseif (!empty($id)) {
			$rs = safe_row("*", "txp_image", "id='$id' limit 1");
		} else return;
		
		if ($rs) {
			extract($rs);
			$out = array(
				'<img',
				'src="'.$pfr.$img_dir.'/'.$id.$ext.'"',
				'height="'.$h.'" width="'.$w.'" alt="'.$alt.'"',
				(!empty($style)) ? 'style="'.$style.'"' : '',
				(!empty($align)) ? 'align="'.$align.'"' : '',
				'/>'
			);
			
			return join(' ',$out);
		}
		return '<txp:notice message="malformed image tag" />';
	}

// -------------------------------------------------------------
    function thumbnail($atts) 
    {
        global $pfr,$img_dir;
        if (is_array($atts)) extract($atts);
        
        if (!empty($name)) {
            $name = doSlash($name);
            $rs = safe_row("*", "txp_image", "name='$name' limit 1");
        } elseif (!empty($id)) {
            $rs = safe_row("*", "txp_image", "id='$id' limit 1");
        } else return;

        if ($rs) {
            extract($rs);
            if(!empty($thumbnail)) {
                $out = array(
                    (!empty($poplink)) 
                    ?   '<a href="'.$pfr.$img_dir.'/'.$id.$ext.
                            '" onclick="window.open(this.href, \'popupwindow\', \'width='.
                            $w.',height='.$h.',scrollbars,resizable\'); return false;">'
                    :   '',
                    '<img src="'.$pfr.$img_dir.'/'.$id.'t'.$ext.'"',
                    ' alt="'.$alt.'"',
                    (!empty($style)) ? 'style="'.$style.'"' : '',
                    (!empty($align)) ? 'align="'.$align.'"' : '',
                    '/>',
                    (!empty($poplink)) ? '</a>' : ''
                );
                return join(' ',$out);
            }
        }
    }

// -------------------------------------------------------------
	function output_form($atts) 
	{
		if (is_array($atts)) extract($atts);
		if (empty($form)) return false;
		return parse(fetch('form','txp_form','name',doSlash($form)));
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
			'flavor'   => ''
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
			'flavor'   => ''
		),$atts));
	
		$category = (!$category) ? '' : a.'category='.urlencode($category);

		return '<a href="'.hu.'?'.$flavor.'=1'.a.'area=link'.$category.
			'" title="XML feed">'.$label.'</a>';
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
		
		$rs = safe_rows("*","txp_link",join(' ',$qparts));
	
		if ($rs) {
			$outlist = ($label) ? $label : '';
		
			foreach ($rs as $a) {
				extract($a);
				$linkname = str_replace("& ","&#38; ", $linkname);
				$link = '<a href="'.$url.'">'.$linkname.'</a>';
				$linkdesctitle = '<a href="'.$url.'" title="'.$description.'">'.$linkname.'</a>';

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
	function stripSpace($text) 
	{
		global $txpac;
		if ($txpac['attach_titles_to_permalinks']) {
		
			$text = preg_replace("/(^| &\S+;)|(<[^>]*>)/U","",$text);		

			if ($txpac['permalink_title_format']) {
				return 
				strtolower(
					preg_replace("/[^[:alnum:]\-]/","",
						str_replace(" ","-",
							$text
						)
					)
				);			
			} else {
				return preg_replace("/[^[:alnum:]]/","",$text);
			}
		}
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

		$rs = safe_rows(
			"*", 
			"textpattern", 
			"Status = 4 and Posted <= now() $catq order by $sortby $sortdir limit 0,$limit"
		);
		
		if ($rs) {
			if ($label) $out[] = $label;
			foreach ($rs as $a) {
				extract($a);
				$out[] = doArticleHref($ID,$Title,$url_title,$Section);
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
			'limit'    => 10,
		),$atts));

		$q = "select ".PFX."txp_discuss.*,".PFX."textpattern.* from ".PFX."txp_discuss
            left join ".PFX."textpattern on ".PFX."textpattern.ID = ".PFX."txp_discuss.parentid
			order by ".PFX."txp_discuss.posted desc limit 0,$limit";

		$rs = getRows($q);

		if ($rs) {
			if ($label) $out[] = $label;
        	foreach($rs as $a) {
				extract($a);
				$out[] = doArticleHref($ID, $Title, $url_title, $Section);
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
			'limit'    => 10,
		),$atts));
		
		global $thisid;
		
		if($thisid) $id = $thisid;

		$cats = doSlash(safe_row("Category1,Category2","textpattern", "ID='$id' limit 1"));

		if (!empty($cats[0]) or !empty($cats[1])) {

			$q = array("select * from ".PFX."textpattern where Status = 4 and ID!='$id'",
				(!empty($cats[0])) ? "and ((Category1='$cats[0]') or (Category2='$cats[0]'))" :'',
				(!empty($cats[1])) ? "or ((Category1='$cats[1]') or (Category2='$cats[1]'))" :'',
				"and Status=4 and Posted <= now() order by Posted desc limit 0,$limit");

			$rs = getRows(join(' ',$q));
	
			if ($rs) {
				if ($label) $out[] = $label;
				foreach($rs as $a) {
					extract($a);
					$out[] = doArticleHref($ID,$Title,$url_title,$Section);
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
	function popup($atts) // popup navigation. possible atts: type (c or s), label
	{
		global $pretext,$pfr;
		
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
			return '<form action="'.$pfr.'" method="get">'.n.$out.'</form>';
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
	function section_list($atts) // output href list of site categories
	{
		extract(lAtts(array(
			'label'    => '',
			'break'    => br,
			'wraptag'  => ''
		),$atts));
		
		$rs = safe_column("name","txp_section","name != 'default' order by name");
		
		if ($rs) {
			if ($label) $out[] = $label;
			foreach ($rs as $a) {
				if($a) {
					if($GLOBALS['url_mode']) {
						$out[] = tag(htmlspecialchars($a),'a',' href="'.hu.urlencode($a).'/"');
					} else {
						$out[] = tag(htmlspecialchars($a),'a',' href="'.hu.'?s='.urlencode($a).'"');
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
		global $q,$pfr;
		extract(lAtts(array(
			'form' => 'search_input',
			'wraptag'  => 'p',
			'size'  => '15',
			'label'  => 'Search',
			'button' => ''
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
		
		return '<form action="'.$pfr.'index.php" method="get">'.$out.'</form>';
	}

// -------------------------------------------------------------
	function link_to_next($atts, $thing) // link to next article, if it exists
	{
		global $s,$pfr,$next_id,$next_title,$next_utitle;
		$next_link = ($next_utitle) ? $next_utitle : $next_title;
		$thing = (isset($thing)) ? parse($thing) : '';
		if($next_id) {		
			return formatHref($pfr,$s,$next_id,$thing,$next_link,'noline');
		}
		return '';
	}
		
// -------------------------------------------------------------
	function link_to_prev($atts, $thing) // link to next article, if it exists
	{
		global $s,$pfr,$prev_id,$prev_title,$prev_utitle,$url_mode;
		$prev_link = ($prev_utitle) ? $prev_utitle : $prev_title;
		$thing = (isset($thing)) ? parse($thing) : '';
		if ($prev_id) {
			return formatHref($pfr,$s,$prev_id,$thing,$prev_link,'noline');
		}
		return '';
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
		global $pfr;
		if (!empty($thing)) {
			return '<a href="'.$pfr.'" class="noline">'.parse($thing).'</a>';
		}
	}

// -------------------------------------------------------------
	function newer($atts, $thing, $match='') 
	{
		global $thispage,$url_mode;
				
		if (is_array($atts)) extract($atts);
		if (is_array($thispage)) { 
			extract($thispage);
		} else { 
			return $match; 
		}

		ob_start();

		if ($pg > 1) {
			$out = array(
				'<a href="?pg='.($pg - 1),
				($c) ? a.'c='.urlencode($c) : '',
				($s && !$url_mode) ? a.'s='.urlencode($s) : '',
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
		global $thispage,$url_mode;
		if (is_array($atts)) extract($atts);
		if (is_array($thispage)) {
			extract($thispage); 
		} else { 
			return $match; 
		}
		
		ob_start();

		if ($pg != $numPages) {
			$out = array(
				'<a href="?pg='.($pg + 1),
				($c) ? a.'c='.urlencode($c) : '',
				($s && !$url_mode) ? a.'s='.urlencode($s) : '',
				'"',
				(empty($title)) ? '' : ' title="'.$title.'"',
				'>',
				$thing,
				'</a>');
			return join('',$out);
		} else return;
	}

// -------------------------------------------------------------
	function mentions($atts) 
	{
		global $thisarticle;
		$out = $thisarticle['mentions_link'];
		if (is_array($atts)) extract($atts);
		if(!empty($wraptag)) return tag($out,$wraptag);
		return $out;	
	}	

// -------------------------------------------------------------
	function text($atts) 
	{
		if (is_array($atts)) extract($atts);
		if (!empty($item)) return gTxt($item);
	}

// -------------------------------------------------------------
	function article_id($atts) 
	{
		global $thisarticle;
		if (is_array($atts)) extract($atts);
		return $thisarticle['thisid'];
	}

// -------------------------------------------------------------
	function posted($atts) 
	{
		global $dateformat,$archive_dateformat,$timeoffset,
				$pg,$c,$thisarticle,$id,$txpcfg;

		$date_offset = $thisarticle['posted'] + $timeoffset;

		if (is_array($atts)) extract($atts);

		if(!empty($format)) {
			if($format=='since') {
				$date_out = since($thisarticle['posted']);
			} else {
				$date_out = date($format,$date_offset);
			}

		} else {
		
			if ($pg or $id or $c) { 	
				$dateformat = $archive_dateformat; 
			}

			if($dateformat == "since") { 
				$date_out = since($thisarticle['posted']); 
			} else { 
				$date_out = date($dateformat,$date_offset); 
			}
		}

		if (!empty($lang)) {
			if (empty($GLOBALS['date_lang'])) {
				$date_lang = load_lang($lang.'_dates');	
			} else global $date_lang;
			if ($date_lang) {
				foreach ($date_lang as $k => $v) {
					$date_out = str_replace($k,$v,$date_out);
				}
			}
		}

		if(!empty($wraptag)) $date_out = tag($date_out,$wraptag);

		return $date_out;
	}

// -------------------------------------------------------------
	function comments_count($atts) 
	{
		global $thisarticle;
		if (is_array($atts)) extract($atts);
		return ($thisarticle['comments_count'] > 0) ? $thisarticle['comments_count'] : '';
	}

// -------------------------------------------------------------
	function comments_invite($atts) 
	{
		global $thisarticle;
		$out = $thisarticle['comments_invite'];
		if (is_array($atts)) extract($atts);
		if(!empty($wraptag)) return tag($out,$wraptag);
		return $out;	
	}

// -------------------------------------------------------------
	function author($atts) 
	{
		global $thisarticle;
		if (is_array($atts)) extract($atts);
		return $thisarticle['author'];	
	}

// -------------------------------------------------------------
	function permlink($atts) 
	{
		global $thisarticle;
		if (is_array($atts)) extract($atts);
		if(!empty($wraptag)) return tag($thisarticle['permlink'],$wraptag);
		return $thisarticle['permlink'];
	}
	
// -------------------------------------------------------------
	function body($atts) 
	{
		global $thisarticle;
		if (is_array($atts)) extract($atts);
		return $thisarticle['body'];
	}	
	
// -------------------------------------------------------------
	function title($atts) 
	{
		global $thisarticle;
		if (is_array($atts)) extract($atts);
		return $thisarticle['title'];	
	}

// -------------------------------------------------------------
	function excerpt($atts) 
	{
		global $thisarticle;
		if (is_array($atts)) extract($atts);
		return $thisarticle['excerpt'];	
	}

// -------------------------------------------------------------
	function category1($atts) 
	{
		global $thisarticle, $pfr;
		if (is_array($atts)) extract($atts);
		if ($thisarticle['category1']) {
			if (!empty($link)) 
				return '<a href="'.$pfr.'?c='.$thisarticle['category1'].'">'.
					$thisarticle['category1'].'</a>';
			return $thisarticle['category1'];
		}
	}
	
// -------------------------------------------------------------
	function category2($atts) 
	{
		global $thisarticle, $pfr;
		if (is_array($atts)) extract($atts);
		if ($thisarticle['category2']) {
			if (!empty($link)) 
				return '<a href="'.$pfr.'?c='.$thisarticle['category2'].'">'.
					$thisarticle['category2'].'</a>';
			return $thisarticle['category2'];
		}
	}

// -------------------------------------------------------------
	function section($atts) 
	{
		global $thisarticle, $pfr;
		if (is_array($atts)) extract($atts);
		if ($thisarticle['section']) {
			if (!empty($link)) 
				return '<a href="'.$pfr.$thisarticle['section'].'/">'.
					$thisarticle['section'].'</a>';
			return $thisarticle['section'];
		}
	}

// -------------------------------------------------------------
	function keywords($atts) 
	{
		global $thisarticle;
		return ($thisarticle['keywords']) ? $thisarticle['section'] : '';
	}

// -------------------------------------------------------------
	function article_image($atts) 
	{
		global $thisarticle,$pfr,$img_dir;
		if (is_array($atts)) extract($atts);
		$theimage = ($thisarticle['article_image']) ? $thisarticle['article_image'] : '';
		
		if ($theimage) {
		
			if (is_numeric($theimage)) {
				$rs = safe_row("*",'txp_image',"id='$theimage'");
				if ($rs) {
					extract($rs);
					$out = array(
						'<img',
						'src="'.$pfr.$img_dir.'/'.$id.$ext.'"',
						'height="'.$h.'" width="'.$w.'" alt="'.$alt.'"',
						(!empty($style)) ? 'style="'.$style.'"' : '',
						(!empty($align)) ? 'align="'.$align.'"' : '',
						'/>'
					);			
					return join(' ',$out);
				}
			} else {
				return '<img src="'.$theimage.'" />';
			}
		}
	}

// -------------------------------------------------------------
	function search_result_title($atts) 
	{
		global $this_result;
		if (is_array($atts)) extract($atts);
		return $this_result['search_result_title'];
	}

// -------------------------------------------------------------
	function search_result_excerpt($atts) 
	{
		global $this_result;
		if (is_array($atts)) extract($atts);
		return $this_result['search_result_excerpt'];
	}

// -------------------------------------------------------------
	function search_result_url($atts) 
	{
		global $this_result;
		if (is_array($atts)) extract($atts);
		return $this_result['search_result_url'];
	}

// -------------------------------------------------------------
	function search_result_date($atts) 
	{
		global $this_result;
		if (is_array($atts)) extract($atts);
		return $this_result['search_result_date'];
	}


// -------------------------------------------------------------
	function image_index($atts)
	{
		global $url_mode,$s,$c,$p,$pfr,$txpcfg,$img_dir;
		if (is_array($atts)) extract($atts);
		$c = doSlash($c);
		
		$rs = safe_rows("*", "txp_image","category='$c' and thumbnail=1 order by name");

		if ($rs) {
		        // pedro@kusor.net(09-06-2004): if you put this in the loop
		        // section is re-encoded for each item
		        if(!$url_mode){
		                $s = (!empty($s)) ? a.'s='.urlencode($s) : '';
		        }
			foreach($rs as $a) {
				extract($a);
				$impath = $pfr.$img_dir.'/'.$id.'t'.$ext;
				$imginfo = getimagesize($txpcfg['doc_root'].$impath);
				$dims = (!empty($imginfo[3])) ? ' '.$imginfo[3] : '';
				if(!$url_mode){
					$out[] = '<a href="'.$pfr.'?c='.urlencode($c).$s.a.'p='.$id.'">'.
					'<img src="'.$impath.'"'.$dims.' alt="'.$alt.'" />'.'</a>';
				}else{
					$out[] = '<a href="'.$pfr.$s.'/?c='.urlencode($c).a.'p='.$id.'">'.
					'<img src="'.$impath.'"'.$dims.' alt="'.$alt.'" />'.'</a>';
				}

			}
			return join('',$out);
		}
	}
/*
// -------------------------------------------------------------
	function image_index($atts) 
	{
		global $url_mode,$s,$c,$p,$pfr,$txpcfg,$img_dir;
		if (is_array($atts)) extract($atts);
		$c = doSlash($c);
		
		$rs = safe_rows("*", "txp_image","category='$c' and thumbnail=1 order by name");
		
		if ($rs) {
			foreach($rs as $a) {
				extract($a);
				$impath = $pfr.$img_dir.'/'.$id.'t'.$ext;
				$imginfo = getimagesize($txpcfg['doc_root'].$impath);
				$dims = (!empty($imginfo[3])) ? ' '.$imginfo[3] : '';
				$out[] = '<a href="'.$pfr.$s.'/?c='.urlencode($c).a.'p='.$id.'">'.
					'<img src="'.$impath.'"'.$dims.' alt="'.$alt.'" />'.
					'</a>';
			}
			return join('',$out);
		}
	}
*/
// -------------------------------------------------------------
	function image_display($atts) 
	{
		if (is_array($atts)) extract($atts);
		global $url_mode,$s,$c,$p,$pfr,$img_dir;
		if($p) {
			$rs = safe_row("*", "txp_image", "id='$p' limit 1");
			if ($rs) {
				extract($rs);
				$impath = $pfr.$img_dir.'/'.$id.$ext;
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
	function doArticleHref($ID,$Title,$url_title,$Section)
	{
		$conTitle = ($url_title) ? $url_title : stripSpace($Title);	
		return ($GLOBALS['url_mode'])
		?	tag($Title,'a',' href="'.hu.$Section.'/'.$ID.'/'.$conTitle.'"')
		:	tag($Title,'a',' href="'.hu.'index.php?id='.$ID.'"');
	}



	
?>
