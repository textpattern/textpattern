<?php


// Textpattern admin options
// unless stated otherwise, 0 = false, 1 = true

$txpac = array(


// -------------------------------------------------------------
// bypass the Txp CSS editor entirely

	'edit_raw_css_by_default'     => 1,


// -------------------------------------------------------------
// php scripts on page templates will be parsed 

	'allow_page_php_scripting'    => 1,

// -------------------------------------------------------------
// use Textile on link titles and descriptions 

	'textile_links'               => 0,


// -------------------------------------------------------------
// in the article categories listing in the organise tab

	'show_article_category_count' => 1,


// -------------------------------------------------------------
// xml feeds display comment count as part of article title

	'show_comment_count_in_feed'  => 1,


// -------------------------------------------------------------
// include articles or full excerpts in feeds
// 0 = full article body
// 1 = excerpt
		
	'syndicate_body_or_excerpt'   => 1,


// -------------------------------------------------------------
// include (encoded) author email in atom feeds
		
	'include_email_atom'          => 1,
	

// -------------------------------------------------------------
// each comment received updates the site Last-Modified header 

	'comment_means_site_updated'  => 1,


// -------------------------------------------------------------
// comment email addresses are encoded to hide from spambots
// but if you never want to see them at all, set this to 1

	'never_display_email'         => 0,


// -------------------------------------------------------------
// comments must enter name and/or email address

	'comments_require_name'       => 1,
	'comments_require_email'      => 1,


// -------------------------------------------------------------
// show 'excerpt' pane in write tab

	'articles_use_excerpts'       => 1,


// -------------------------------------------------------------
// show form overrides on article-by-article basis

	'allow_form_override'         => 1,


// -------------------------------------------------------------
// whether or not to attach article titles to permalinks
// e.g., /article/313/IAteACheeseSandwich

	'attach_titles_to_permalinks' => 1,


// -------------------------------------------------------------
// if attaching titles to permalinks, which format?
// 0 = SuperStudlyCaps
// 1 = hyphenated-lower-case

	'permalink_title_format'      => 1,


// -------------------------------------------------------------
// number of days after which logfiles are purged
		
	'expire_logs_after'           => 7,


// -------------------------------------------------------------
// plugins on or off
		
	'use_plugins'                 => 1,

// -------------------------------------------------------------
// use custom fields for articles - must be in 'quotes'
		
	'custom_1_set'                => 'custom 1',
	'custom_2_set'                => 'custom 2',
	'custom_3_set'                => '',
	'custom_4_set'                => '',
	'custom_5_set'                => '',
	'custom_6_set'                => '',
	'custom_7_set'                => '',
	'custom_8_set'                => '',
	'custom_9_set'                => '',
	'custom_10_set'               => '',

// -------------------------------------------------------------
// ping textpattern.com when an article is published
		
	'ping_textpattern_com'        => 1,

);

?>
