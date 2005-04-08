<?php
//Long live zem!

//Time for 45KB: 0.9648-1.3698sec.
//Time for 2.612KB: 30sec.
function doImportMT($file, $section, $status, $invite) {

	# Parse a file in the MT Import Format, as described here:
	# http://www.movabletype.org/docs/mtimport.html
	# This doesn't interpret the data at all, just parse it into
	# a structure.

	$fp = fopen($file, 'r');
	if (!$fp)
		return false;
		
	//Keep some response on some part
	$results = array();	

	$state = 'metadata';
	$item = array();

	while (!feof($fp)) {
		$line = rtrim(fgets($fp, 8192));

		# The states suggested by the spec are inconsisent, but
		# we'll do our best to fake it

		if ($line == '--------') {
			# End of an item, so we can process it
			$results[]=import_mt_item($item, $section, $status, $invite);
			$item = array();
			$state = 'metadata';
		}
		elseif ($line == '-----' and $state == 'metadata') {
			$state = 'multiline';
			$multiline_type = '';
		}
		elseif ($line == '-----' and $state == 'multiline') {
			if (!empty($multiline_type))
				$item[$multiline_type][] = $multiline_data;
			$state = 'multiline';
			$multiline_type = '';
		}
		elseif ($state == 'metadata') {
			if (preg_match('/^([A-Z ]+):\s*(.*)$/', $line, $match))
				$item[$match[1]] = $match[2];
		}
		elseif ($state == 'multiline' and empty($multiline_type)) {
			if (preg_match('/^([A-Z ]+):\s*$/', $line, $match)) {
				$multiline_type = $match[1];
				$multiline_data = array();
			}
		}
		elseif ($state == 'multiline') {
			# Here's where things get hinky.  Rather than put the
			# multiline metadata before the field name, it goes
			# after, with no clear separation between metadata
			# and data.  And either the metadata or data might be
			# missing.

			if (empty($multiline_data['content']) and preg_match('/^([A-Z ]+):\s*(.*)$/', $line, $match)) {
				# Metadata within the multiline field
				$multiline_data[$match[1]] = $match[2];
			}
			elseif (empty($multiline_data['content'])) {
				$multiline_data['content'] = ($line . "\n");
			}
			else {
				$multiline_data['content'] .= ($line . "\n");
			}
		}
	}

	# Catch the last item in the file, if it doesn't end with a separator
	if (!empty($item))
		$results[]= import_mt_item($item, $section, $status, $invite, $blogid);

	fclose($fp);
	return join('<br />', $results);
}
//Some \n chars on empty fields should be removed from body_extended and excerpt
//What about the new title_html field?
function import_mt_item($item, $section, $status, $invite) {

	# Untested import code follows

	if (empty($item)) return;

	include_once txpath.'/lib/classTextile.php';
	$textile = new Textile();


	$title = $textile->TextileThis($item['TITLE'], 1);
	//nice non-english permlinks	
	$url_title = stripSpace(dumbDown($title));	

	$body = $item['BODY'][0]['content'] . (isset($item['EXTENDED_BODY']) ? "\n<!--more-->\n" . $item['EXTENDED_BODY'][0]['content'] : '');
	$body_html = $textile->textileThis($body);

	$excerpt = @$item['EXCERPT'][0]['content'];
	$excerpt_html = $textile->textileThis($excerpt);

	$date = strtotime($item['DATE']);
	$date = date('Y-m-d H:i:s', $date);

	if (isset($item['STATUS']))
		$post_status = ($item['STATUS'] == 'Draft' ? 1 : 4);
	else
		$post_status = $status;

	$category1 = @$item['PRIMARY CATEGORY'];
	if ($category1 and !safe_field("name","txp_category","name = '$category1'"))
			safe_insert('txp_category', "name='".doSlash($category1)."', type='article', parent='root'");

	$keywords = @$item['KEYWORDS'][0]['content'];

	$authorid = safe_field('user_id', 'txp_users', "name = '".doSlash($item['AUTHOR'])."'");
	if (!$authorid)
//		$authorid = safe_field('user_id', 'txp_users', 'order by user_id asc limit 1');
		//Add new authors
		safe_insert('txp_users', "name='".doSlash($item['AUTHOR'])."'");

		
	if (!safe_field("ID", "textpattern", "Title = '".doSlash($title)."' AND Posted = '".doSlash($date)."'")) {
		safe_insert('textpattern', 
			"Posted='".doSlash($date)."',".
			"LastMod='".doSlash($date)."',".
			"AuthorID='".doSlash($item['AUTHOR'])."',".
			"LastModID='".doSlash($item['AUTHOR'])."',".
			"Title='".doSlash($title)."',".
			"Body='".doSlash($body)."',".
			"Body_html='".doSlash($body_html)."',".
			"Excerpt='".doSlash($excerpt)."',".
			"Excerpt_html='".doSlash($excerpt_html)."',".
			"Category1='".doSlash($category1)."',".
			"AnnotateInvite='".doSlash($invite)."',".
			"Status='".doSlash($post_status)."',".
			"Section='".doSlash($section)."',".
			"Keywords='".doSlash($keywords)."',".
			"uid='".md5(uniqid(rand(),true))."',".
			"feed_time='".substr($date,0,10)."',".
			"url_title='".doSlash($url_title)."'");
			

		$parentid = mysql_insert_id();
	
		if (!empty($item['COMMENT'])) {
			foreach ($item['COMMENT'] as $comment) {
				$comment_date = date('Y-m-d H:i:s', strtotime(@$comment['DATE']));
				$comment_content = $textile->TextileThis(nl2br(@$comment['content']),1);
				if (!safe_field("discussid","txp_discuss","posted = '".doSlash($comment_date)."' AND message = '".doSlash($comment_content)."'")) {
					safe_insert('txp_discuss', 
						"parentid='".doSlash($parentid)."',".
						"name='".doSlash(@$comment['AUTHOR'])."',".
						"email='".doSlash(@$comment['EMAIL'])."',".
						"web='".doSlash(@$comment['URL'])."',".
						"ip='".doSlash(@$comment['IP'])."',".
						"posted='".doSlash($comment_date)."',".
						"message='".doSlash($comment_content)."',".
						"visible='1'");
				}
			}
		}
		return $title;
	}
	return $title.' already imported';
}

?>
