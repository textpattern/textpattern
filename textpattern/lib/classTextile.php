<?php

/**
 * Example: get XHTML from a given Textile-markup string ($string)
 *
 *		  $textile = new Textile;
 *		  echo $textile->TextileThis($string);
 *
 */

/*

_____________
T E X T I L E

A Humane Web Text Generator

Version 2.4.3

Copyright (c) 2003-2004, Dean Allen <dean@textism.com>
All rights reserved.

Thanks to Carlo Zottmann <carlo@g-blog.net> for refactoring
Textile's procedural code into a class framework

Additions and fixes Copyright (c) 2006    Alex Shiels       http://thresholdstate.com/
Additions and fixes Copyright (c) 2010    Stef Dawson       http://stefdawson.com/
Additions and fixes Copyright (c) 2010-12 Netcarver         http://github.com/netcarver
Additions and fixes Copyright (c) 2011    Jeff Soo          http://ipsedixit.net
Additions and fixes Copyright (c) 2012    Robert Wetzlmayr 	http://wetzlmayr.com/

_____________
L I C E N S E

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are met:

* Redistributions of source code must retain the above copyright notice,
  this list of conditions and the following disclaimer.

* Redistributions in binary form must reproduce the above copyright notice,
  this list of conditions and the following disclaimer in the documentation
  and/or other materials provided with the distribution.

* Neither the name Textile nor the names of its contributors may be used to
  endorse or promote products derived from this software without specific
  prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
POSSIBILITY OF SUCH DAMAGE.

_________
U S A G E

Block modifier syntax:

	Header: h(1-6).
	Paragraphs beginning with 'hn. ' (where n is 1-6) are wrapped in header tags.
	Example: h1. Header... -> <h1>Header...</h1>

	Paragraph: p. (also applied by default)
	Example: p. Text -> <p>Text</p>

	Blockquote: bq.
	Example: bq. Block quotation... -> <blockquote>Block quotation...</blockquote>

	Blockquote with citation: bq.:http://citation.url
	Example: bq.:http://textism.com/ Text...
	->	<blockquote cite="http://textism.com">Text...</blockquote>

	Footnote: fn(1-100).
	Example: fn1. Footnote... -> <p id="fn1">Footnote...</p>

	Numeric list: #, ##
	Consecutive paragraphs beginning with # are wrapped in ordered list tags.
	Example: <ol><li>ordered list</li></ol>

	Bulleted list: *, **
	Consecutive paragraphs beginning with * are wrapped in unordered list tags.
	Example: <ul><li>unordered list</li></ul>

	Definition list:
		Terms ;, ;;
		Definitions :, ::
	Consecutive paragraphs beginning with ; or : are wrapped in definition list tags.
	Example: <dl><dt>term</dt><dd>definition</dd></dl>

	Redcloth-style Definition list:
		- Term1 := Definition1
		- Term2 := Extended
		  definition =:

Phrase modifier syntax:

		   _emphasis_	->	 <em>emphasis</em>
		   __italic__	->	 <i>italic</i>
			 *strong*	->	 <strong>strong</strong>
			 **bold**	->	 <b>bold</b>
		 ??citation??	->	 <cite>citation</cite>
	   -deleted text-	->	 <del>deleted</del>
	  +inserted text+	->	 <ins>inserted</ins>
		^superscript^	->	 <sup>superscript</sup>
		  ~subscript~	->	 <sub>subscript</sub>
			   @code@	->	 <code>computer code</code>
		  %(bob)span%	->	 <span class="bob">span</span>

		==notextile==	->	 leave text alone (do not format)

	   "linktext":url	->	 <a href="url">linktext</a>
 "linktext(title)":url	->	 <a href="url" title="title">linktext</a>
            "$":url  ->  <a href="url">url</a>
     "$(title)":url  ->  <a href="url" title="title">url</a>

		   !imageurl!	->	 <img src="imageurl" />
	!imageurl(alt text)!	->	 <img src="imageurl" alt="alt text" />
	!imageurl!:linkurl	->	 <a href="linkurl"><img src="imageurl" /></a>

ABC(Always Be Closing)	->	 <acronym title="Always Be Closing">ABC</acronym>


Linked Notes:
============

	Allows the generation of an automated list of notes with links.

	Linked notes are composed of three parts, a set of named _definitions_, a set of
	_references_ to those definitions and one or more _placeholders_ indicating where
	the consolidated list of notes is to be placed in your document.

	Definitions.
	-----------

	Each note definition must occur in its own paragraph and should look like this...

	note#mynotelabel. Your definition text here.

	You are free to use whatever label you wish after the # as long as it is made up
	of letters, numbers, colon(:) or dash(-).

	References.
	----------

	Each note reference is marked in your text like this[#mynotelabel] and
	it will be replaced with a superscript reference that links into the list of
	note definitions.

	List Placeholder(s).
	-------------------

	The note list can go anywhere in your document. You have to indicate where
	like this...

	notelist.

	notelist can take attributes (class#id) like this: notelist(class#id).

	By default, the note list will show each definition in the order that they
	are referenced in the text by the _references_. It will show each definition with
	a full list of backlinks to each reference. If you do not want this, you can choose
	to override the backlinks like this...

	notelist(class#id)!.    Produces a list with no backlinks.
	notelist(class#id)^.    Produces a list with only the first backlink.

	Should you wish to have a specific definition display backlinks differently to this
	then you can override the backlink method by appending a link override to the
	_definition_ you wish to customise.

	note#label.    Uses the citelist's setting for backlinks.
	note#label!.   Causes that definition to have no backlinks.
	note#label^.   Causes that definition to have one backlink (to the first ref.)
	note#label*.   Causes that definition to have all backlinks.

	Any unreferenced notes will be left out of the list unless you explicitly state
	you want them by adding a '+'. Like this...

	notelist(class#id)!+. Giving a list of all notes without any backlinks.

	You can mix and match the list backlink control and unreferenced links controls
	but the backlink control (if any) must go first. Like so: notelist^+. , not
	like this: notelist+^.

	Example...
		Scientists say[#lavader] the moon is small.

		note#other. An unreferenced note.

		note#lavader(myliclass). "Proof":url of a small moon.

		notelist(myclass#myid)+.

		Would output (the actual IDs used would be randomised)...

		<p>Scientists say<sup><a href="#def_id_1" id="ref_id_1a">1</sup> the moon is small.</p>

		<ol class="myclass" id="myid">
			<li class="myliclass"><a href="#ref_id_1a"><sup>a</sup></a><span id="def_id_1"> </span><a href="url">Proof</a> of a small moon.</li>
			<li>An unreferenced note.</li>
		</ol>

		The 'a b c' backlink characters can be altered too.
		For example if you wanted the notes to have numeric backlinks starting from 1:

		notelist:1.

Table syntax:

	Simple tables:

		|a|simple|table|row|
		|And|Another|table|row|
		|With an||empty|cell|

		|=. My table caption goes here
		|_. A|_. table|_. header|_.row|
		|A|simple|table|row|

	Tables with attributes:

		table{border:1px solid black}. My table summary here
		{background:#ddd;color:red}. |{}| | | |

	To specify thead / tfoot / tbody groups, add one of these on its own line
	above the row(s) you wish to wrap (you may specify attributes before the dot):

		|^.     # thead
		|-.     # tbody
		|~.     # tfoot

	Column groups:

		|:\3. 100|

		Becomes:
			<colgroup span="3" width="100"></colgroup>

		You can omit either or both of the \N or width values. You may also
		add cells after the colgroup definition to specify col elements with
		span, width, or standard Textile attributes:

		|:. 50|(firstcol). |\2. 250||300|

		Becomes:
			<colgroup width="50">
				<col class="firstcol" />
				<col span="2" width="250" />
				<col />
				<col width="300" />
			</colgroup>

		(Note that, per the HTML specification, you should not add span
		to the colgroup if specifying col elements.)

Applying Attributes:

	Most anywhere Textile code is used, attributes such as arbitrary css style,
	css classes, and ids can be applied. The syntax is fairly consistent.

	The following characters quickly alter the alignment of block elements:

		<  ->  left align	 ex. p<. left-aligned para
		>  ->  right align		 h3>. right-aligned header 3
		=  ->  centred			 h4=. centred header 4
		<> ->  justified		 p<>. justified paragraph

	These will change vertical alignment in table cells:

		^  ->  top		   ex. |^. top-aligned table cell|
		-  ->  middle		   |-. middle aligned|
		~  ->  bottom		   |~. bottom aligned cell|

	Plain (parentheses) inserted between block syntax and the closing dot-space
	indicate classes and ids:

		p(hector). paragraph -> <p class="hector">paragraph</p>

		p(#fluid). paragraph -> <p id="fluid">paragraph</p>

		(classes and ids can be combined)
		p(hector#fluid). paragraph -> <p class="hector" id="fluid">paragraph</p>

	Curly {brackets} insert arbitrary css style

		p{line-height:18px}. paragraph -> <p style="line-height:18px">paragraph</p>

		h3{color:red}. header 3 -> <h3 style="color:red">header 3</h3>

	Square [brackets] insert language attributes

		p[no]. paragraph -> <p lang="no">paragraph</p>

		%[fr]phrase% -> <span lang="fr">phrase</span>

	Usually Textile block element syntax requires a dot and space before the block
	begins, but since lists don't, they can be styled just using braces

		#{color:blue} one  ->  <ol style="color:blue">
		# big					<li>one</li>
		# list					<li>big</li>
								<li>list</li>
							   </ol>

	Using the span tag to style a phrase

		It goes like this, %{color:red}the fourth the fifth%
			  -> It goes like this, <span style="color:red">the fourth the fifth</span>

Ordered List Start & Continuation:

	You can control the start attribute of an ordered list like so;

		#5 Item 5
		# Item 6

	You can resume numbering list items after some intervening anonymous block like so...

		#_ Item 7
		# Item 8

*/

// define these before including this file to override the standard glyphs

if (!defined('txt_quote_single_open'))
{
	define('txt_quote_single_open', '&#8216;');
}

if (!defined('txt_quote_single_close'))
{
	define('txt_quote_single_close', '&#8217;');
}

if (!defined('txt_quote_double_open'))
{
	define('txt_quote_double_open', '&#8220;');
}

if (!defined('txt_quote_double_close'))
{
	define('txt_quote_double_close', '&#8221;');
}

if (!defined('txt_apostrophe'))
{
	define('txt_apostrophe', '&#8217;');
}

if (!defined('txt_prime'))
{
	define('txt_prime', '&#8242;');
}

if (!defined('txt_prime_double'))
{
	define('txt_prime_double', '&#8243;');
}

if (!defined('txt_ellipsis'))
{
	define('txt_ellipsis', '&#8230;');
}

if (!defined('txt_emdash'))
{
	define('txt_emdash', '&#8212;');
}

if (!defined('txt_endash'))
{
	define('txt_endash', '&#8211;');
}

if (!defined('txt_dimension'))
{
	define('txt_dimension', '&#215;');
}

if (!defined('txt_trademark'))
{
	define('txt_trademark', '&#8482;');
}

if (!defined('txt_registered'))
{
	define('txt_registered', '&#174;');
}

if (!defined('txt_copyright'))
{
	define('txt_copyright', '&#169;');
}

if (!defined('txt_half'))
{
	define('txt_half', '&#189;');
}

if (!defined('txt_quarter'))
{
	define('txt_quarter', '&#188;');
}

if (!defined('txt_threequarters'))
{
	define('txt_threequarters', '&#190;');
}

if (!defined('txt_degrees'))
{
	define('txt_degrees', '&#176;');
}

if (!defined('txt_plusminus'))
{
	define('txt_plusminus', '&#177;');
}

if (!defined('txt_has_unicode'))
{
	define('txt_has_unicode', @preg_match('/\pL/u', 'a')); // Detect if Unicode is compiled into PCRE
}

if (!defined('txt_fn_ref_pattern'))
{
	define('txt_fn_ref_pattern', '<sup{atts}>{marker}</sup>');
}

if (!defined('txt_fn_foot_pattern'))
{
	define('txt_fn_foot_pattern', '<sup{atts}>{marker}</sup>');
}

if (!defined('txt_nl_ref_pattern'))
{
	define('txt_nl_ref_pattern', '<sup{atts}>{marker}</sup>');
}

class Textile
{
	var $hlgn;
	var $vlgn;
	var $clas;
	var $lnge;
	var $styl;
	var $cspn;
	var $rspn;
	var $a;
	var $s;
	var $c;
	var $pnct;
	var $rel;
	var $fn;

	var $shelf = array();
	var $restricted = false;
	var $noimage = false;
	var $lite = false;
	var $url_schemes = array();
	var $glyph = array();
	var $hu = '';
	var $max_span_depth = 5;

	var $ver = '2.4.3';
	var $rev = '';

	var $doc_root;

	var $doctype;

// -------------------------------------------------------------
	function Textile( $doctype = 'xhtml' )
	{
		$doctype_whitelist = array( # All lower case please...
			'xhtml',
			'html5',
		);
		$doctype = strtolower( $doctype );
		if( !in_array( $doctype, $doctype_whitelist ) )
			$this->doctype = 'xhtml';
		else
			$this->doctype = $doctype;

		$this->hlgn = "(?:\<(?!>)|(?<!<)\>|\<\>|\=|[()]+(?! ))";
		$this->vlgn = "[\-^~]";
		$this->clas = "(?:\([^)\n]+\))";	# Don't allow classes/ids/languages/styles to span across newlines
		$this->lnge = "(?:\[[^]\n]+\])";
		$this->styl = "(?:\{[^}\n]+\})";
		$this->cspn = "(?:\\\\\d+)";
		$this->rspn = "(?:\/\d+)";
		$this->a  = "(?:{$this->hlgn}|{$this->vlgn})*";
		$this->s  = "(?:{$this->cspn}|{$this->rspn})*";
		$this->c  = "(?:{$this->clas}|{$this->styl}|{$this->lnge}|{$this->hlgn})*";
		$this->lc = "(?:{$this->clas}|{$this->styl}|{$this->lnge})*";

		$this->pnct  = '[\!"#\$%&\'()\*\+,\-\./:;<=>\?@\[\\\]\^_`{\|}\~]';
		$this->urlch = '[\w"$\-_.+!*\'(),";\/?:@=&%#{}|\\^~\[\]`]';
		$this->syms  = '¤§µ¶†‡•∗∴◊♠♣♥♦';

		$pnc = '[[:punct:]]';

		$this->restricted_url_schemes = array('http','https','ftp','mailto');
		$this->unrestricted_url_schemes = array('http','https','ftp','mailto','file','tel','callto','sftp');

		$this->btag = array('bq', 'bc', 'notextile', 'pre', 'h[1-6]', 'fn\d+', 'p', '###' );

		if (txt_has_unicode) {
			$this->regex_snippets = array(
				'acr' => '\p{Lu}\p{Nd}',
				'abr' => '\p{Lu}',
				'nab' => '\p{Ll}',
				'wrd' => '(?:\p{L}|\p{M}|\p{N}|\p{Pc})',
				'mod' => 'u', # Make sure to mark the unicode patterns as such, Some servers seem to need this.
			);
		} else {
			$this->regex_snippets = array(
				'acr' => 'A-Z0-9',
				'abr' => 'A-Z',
				'nab' => 'a-z',
				'wrd' => '\w',
				'mod' => '',
			);
		}
		extract( $this->regex_snippets );
		$this->urlch = '['.$wrd.'"$\-_.+!*\'(),";\/?:@=&%#{}|\\^~\[\]`]';

		$this->glyph_search = array(
			'/('.$wrd.'|\))\'('.$wrd.')/'.$mod,     // I'm an apostrophe
			'/(\s)\'(\d+'.$wrd.'?)\b(?![.]?['.$wrd.']*?\')/'.$mod,	// back in '88/the '90s but not in his '90s', '1', '1.' '10m' or '5.png'
			'/(\S)\'(?=\s|'.$pnc.'|<|$)/',          // single closing
			'/\'/',                                 // single opening
			'/(\S)\"(?=\s|'.$pnc.'|<|$)/',          // double closing
			'/"/',                                  // double opening
			'/\b(['.$abr.']['.$acr.']{2,})\b(?:[(]([^)]*)[)])/'.$mod,  // 3+ uppercase acronym
			'/(?<=\s|^|[>(;-])(['.$abr.']{3,})(['.$nab.']*)(?=\s|'.$pnc.'|<|$)(?=[^">]*?(<|$))/'.$mod,  // 3+ uppercase
			'/([^.]?)\.{3}/',                       // ellipsis
			'/(\s?)--(\s?)/',                       // em dash
			'/( )-( )/',                            // en dash
			'/(\d+)( ?)x( ?)(?=\d+)/',              // dimension sign
			'/(\b ?|\s|^)[([]TM[])]/i',             // trademark
			'/(\b ?|\s|^)[([]R[])]/i',              // registered
			'/(\b ?|\s|^)[([]C[])]/i',              // copyright
			'/[([]1\/4[])]/',                       // 1/4
			'/[([]1\/2[])]/',                       // 1/2
			'/[([]3\/4[])]/',                       // 3/4
			'/[([]o[])]/',                          // degrees -- that's a small 'oh'
			'/[([]\+\/-[])]/',                      // plus minus
		);

		$this->glyph_replace = array(
			'$1'.txt_apostrophe.'$2',              // I'm an apostrophe
			'$1'.txt_apostrophe.'$2',              // back in '88
			'$1'.txt_quote_single_close,           // single closing
			txt_quote_single_open,                 // single opening
			'$1'.txt_quote_double_close,           // double closing
			txt_quote_double_open,                 // double opening
			(('html5' === $this->doctype) ? '<abbr title="$2">$1</abbr>' : '<acronym title="$2">$1</acronym>'),     // 3+ uppercase acronym
			'<span class="caps">glyph:$1</span>$2', // 3+ uppercase
			'$1'.txt_ellipsis,                     // ellipsis
			'$1'.txt_emdash.'$2',                  // em dash
			'$1'.txt_endash.'$2',                  // en dash
			'$1$2'.txt_dimension.'$3',             // dimension sign
			'$1'.txt_trademark,                    // trademark
			'$1'.txt_registered,                   // registered
			'$1'.txt_copyright,                    // copyright
			txt_quarter,                           // 1/4
			txt_half,                              // 1/2
			txt_threequarters,                     // 3/4
			txt_degrees,                           // degrees
			txt_plusminus,                         // plus minus
		);

		if (defined('hu'))
			$this->hu = hu;

		if (defined('DIRECTORY_SEPARATOR'))
			$this->ds = constant('DIRECTORY_SEPARATOR');
		else
			$this->ds = '/';

		$this->doc_root = @$_SERVER['DOCUMENT_ROOT'];
		if (!$this->doc_root)
			$this->doc_root = @$_SERVER['PATH_TRANSLATED']; // IIS

		$this->doc_root = rtrim($this->doc_root, $this->ds).$this->ds;
	}

// -------------------------------------------------------------

	function TextileThis($text, $lite = '', $encode = '', $noimage = '', $strict = '', $rel = '')
	{
		$this->span_depth = 0;
		$this->tag_index = 1;
		$this->notes = $this->unreferencedNotes = $this->notelist_cache = array();
		$this->note_index = 1;
		$this->rel = ($rel) ? ' rel="'.$rel.'"' : '';

		$this->lite = $lite;
		$this->noimage = $noimage;

		$this->url_schemes = $this->unrestricted_url_schemes;

		if ($encode)
		{
			$text = $this->incomingEntities($text);
			$text = str_replace("x%x%", "&amp;", $text);
			return $text;
		} else {
			if(!$strict) {
				$text = $this->cleanWhiteSpace($text);
			}

			if(!$lite) {
				$text = $this->block($text);
				$text = $this->placeNoteLists($text);
			}

			$text = $this->retrieve($text);
			$text = $this->replaceGlyphs($text);
			$text = $this->retrieveTags($text);
			$text = $this->retrieveURLs($text);
			$this->span_depth = 0;

			// just to be tidy
			$text = str_replace("<br />", "<br />\n", $text);

			return $text;
		}
	}

// -------------------------------------------------------------

	function TextileRestricted($text, $lite = 1, $noimage = 1, $rel = 'nofollow')
	{
		$this->restricted = true;
		$this->lite = $lite;
		$this->noimage = $noimage;

		$this->url_schemes = $this->restricted_url_schemes;

		$this->span_depth = 0;
		$this->tag_index = 1;
		$this->notes = $this->unreferencedNotes = $this->notelist_cache = array();
		$this->note_index = 1;

		$this->rel = ($rel) ? ' rel="'.$rel.'"' : '';

		// escape any raw html
		$text = $this->encode_html($text, 0);
		$text = $this->cleanWhiteSpace($text);

		if($lite) {
			$text = $this->blockLite($text);
		} else {
			$text = $this->block($text);
			$text = $this->placeNoteLists($text);
		}

		$text = $this->retrieve($text);
		$text = $this->replaceGlyphs($text);
		$text = $this->retrieveTags($text);
		$text = $this->retrieveURLs($text);
		$this->span_depth = 0;

		// just to be tidy
		$text = str_replace("<br />", "<br />\n", $text);

		return $text;
	}

// -------------------------------------------------------------
    function cleanba( $in )
    {
        $tmp    = $in;
        $before = -1;
        $after  =  0;
        $max    =  3;
        $i      =  0;
        while( ($after != $before) && ($i < $max) )
        {
            $before = strlen( $tmp );
            $tmp    = rawurldecode($tmp);
            $after  = strlen( $tmp );
            $i++;
        }

        if( $i === $max ) # If we hit the max allowed decodes, assume the input is tainted and consume it.
            $out = '';
        else
            $out = strtr( $tmp, array(
                '"'=>'',
                "'"=>'',
                '='=>'',
            ));
        return $out;
    }

// -------------------------------------------------------------
	function pba($in, $element = "", $include_id = 1, $autoclass = '') // "parse block attributes"
	{
		$style = '';
		$class = '';
		$lang = '';
		$colspan = '';
		$rowspan = '';
		$span = '';
		$width = '';
		$id = '';
		$atts = '';
		$align = '';

		$matched = $in;
		if ($element == 'td') {
			if (preg_match("/\\\\(\d+)/", $matched, $csp)) $colspan = $csp[1];
			if (preg_match("/\/(\d+)/", $matched, $rsp)) $rowspan = $rsp[1];
		}

		if ($element == 'td' or $element == 'tr') {
			if (preg_match("/($this->vlgn)/", $matched, $vert))
				$style[] = "vertical-align:" . $this->vAlign($vert[1]);
		}

		if (preg_match("/\{([^}]*)\}/", $matched, $sty)) {
			$style[] = rtrim($sty[1], ';');
			$matched = str_replace($sty[0], '', $matched);
		}

		if (preg_match("/\[([^]]+)\]/U", $matched, $lng)) {
			$matched = str_replace($lng[0], '', $matched);	# Consume entire lang block -- valid or invalid...
			if (preg_match("/\[([a-zA-Z]{2}(?:[\-\_][a-zA-Z]{2})?)\]/U", $lng[0], $lng)) {
				$lang = $lng[1];
			}
		}

		if (preg_match("/\(([^()]+)\)/U", $matched, $cls)) {
			$matched = str_replace($cls[0], '', $matched);	# Consume entire class block -- valid or invalid...
			# Only allow a restricted subset of the CSS standard characters for classes/ids. No encoding markers allowed...
			if (preg_match("/\(([-a-zA-Z 0-9_\.\:\#]+)\)/U", $cls[0], $cls)) {
				$hashpos = strpos( $cls[1], '#' );
				# If a textile class block attribute was found with a '#' in it
				# split it into the css class and css id...
				if( false !== $hashpos ) {
					if (preg_match("/#([-a-zA-Z0-9_\.\:]*)$/", substr( $cls[1], $hashpos ), $ids))
						$id = $ids[1];

					if (preg_match("/^([-a-zA-Z 0-9_]*)/", substr( $cls[1], 0, $hashpos ), $ids))
						$class = $ids[1];
				}
				else {
					if (preg_match("/^([-a-zA-Z 0-9_]*)$/", $cls[1], $ids))
						$class = $ids[1];
				}
			}
		}

		if (preg_match("/([(]+)/", $matched, $pl)) {
			$style[] = "padding-left:" . strlen($pl[1]) . "em";
			$matched = str_replace($pl[0], '', $matched);
		}

		if (preg_match("/([)]+)/", $matched, $pr)) {
			$style[] = "padding-right:" . strlen($pr[1]) . "em";
			$matched = str_replace($pr[0], '', $matched);
		}

		if (preg_match("/($this->hlgn)/", $matched, $horiz))
			$style[] = "text-align:" . $this->hAlign($horiz[1]);

		if ($element == 'col') {
			if (preg_match("/(?:\\\\(\d+))?\s*(\d+)?/", $matched, $csp)) {
				$span = isset($csp[1]) ? $csp[1] : '';
				$width = isset($csp[2]) ? $csp[2] : '';
			}
		}

		if ($this->restricted) {
			$class = trim( $autoclass );
			return join( '', array(
				($lang)  ? ' lang="'  . $this->cleanba($lang)  . '"': '',
				($class) ? ' class="' . $this->cleanba($class) . '"': '',
			));
		}
		else
			$class = trim( $class . ' ' . $autoclass );

		$o = '';
		if( $style ) {
			foreach($style as $s) {
				$parts = explode(';', $s);
				foreach( $parts as $p ) {
					$p = trim($p, '; ');
					if( !empty( $p ) )
						$o .= $p.'; ';
				}
			}
			$style = trim( strtr($o, array("\n"=>'',';;'=>';')) );
		}

		return join('',array(
			($style)   ? ' style="'   . $this->cleanba($style)    .'"' : '',
			($class)   ? ' class="'   . $this->cleanba($class)    .'"' : '',
			($lang)    ? ' lang="'    . $this->cleanba($lang)     .'"' : '',
			($id and $include_id) ? ' id="' . $this->cleanba($id) .'"' : '',
			($colspan) ? ' colspan="' . $this->cleanba($colspan)  .'"' : '',
			($rowspan) ? ' rowspan="' . $this->cleanba($rowspan)  .'"' : '',
			($span)    ? ' span="'    . $this->cleanba($span)     .'"' : '',
			($width)   ? ' width="'   . $this->cleanba($width)    .'"' : '',
		));
	}

// -------------------------------------------------------------
	function hasRawText($text)
	{
		// checks whether the text has text not already enclosed by a block tag
		$r = trim(preg_replace('@<(p|blockquote|div|form|table|ul|ol|dl|pre|h\d)[^>]*?'.chr(62).'.*</\1>@s', '', trim($text)));
		$r = trim(preg_replace('@<(hr|br)[^>]*?/>@', '', $r));
		return '' != $r;
	}

// -------------------------------------------------------------
	function table($text)
	{
		$text = $text . "\n\n";
		return preg_replace_callback("/^(?:table(_?{$this->s}{$this->a}{$this->c})\.(.*)?\n)?^({$this->a}{$this->c}\.? ?\|.*\|)[\s]*\n\n/smU",
			 array(&$this, "fTable"), $text);
	}

// -------------------------------------------------------------
	function fTable($matches)
	{
		$tatts = $this->pba($matches[1], 'table');

		$sum = trim($matches[2]) ? ' summary="'.htmlspecialchars(trim($matches[2])).'"' : '';
		$cap = '';
		$colgrp = $last_rgrp = '';
		$c_row = 1;
		foreach(preg_split("/\|\s*?$/m", $matches[3], -1, PREG_SPLIT_NO_EMPTY) as $row) {

			$row = ltrim($row);

			// Caption -- can only occur on row 1, otherwise treat '|=. foo |...' as a normal center-aligned cell.
			if ( ($c_row <= 1) && preg_match("/^\|\=($this->s$this->a$this->c)\. ([^\n]*)(.*)/s", ltrim($row), $cmtch)) {
				$capts = $this->pba($cmtch[1]);
				$cap = "\t<caption".$capts.">".trim($cmtch[2])."</caption>\n";
				$row = ltrim($cmtch[3]);
				if( empty($row) )
					continue;
			}
			$c_row += 1;

			// Colgroup
			if (preg_match("/^\|:($this->s$this->a$this->c\. .*)/m", ltrim($row), $gmtch)) {
				$nl = strpos($row,"\n");	# Is this colgroup def missing a closing pipe? If so, there will be a newline in the middle of $row somewhere.
				$idx=0;
				foreach (explode('|', str_replace('.', '', $gmtch[1])) as $col) {
					$gatts = $this->pba(trim($col), 'col');
					$colgrp .= "\t<col".(($idx==0) ? "group".$gatts.">" : $gatts." />")."\n";
					$idx++;
				}
				$colgrp .= "\t</colgroup>\n";

				if($nl === false) {
					continue;
				}
				else {
					$row = ltrim(substr( $row, $nl ));		# Recover from our missing pipe and process the rest of the line...
				}
			}

			preg_match("/(:?^\|($this->vlgn)($this->s$this->a$this->c)\.\s*$\n)?^(.*)/sm", ltrim($row), $grpmatch);

			// Row group
			$rgrp = isset($grpmatch[2]) ? (($grpmatch[2] == '^') ? 'head' : ( ($grpmatch[2] == '~') ? 'foot' : (($grpmatch[2] == '-') ? 'body' : '' ) ) ) : '';
			$rgrpatts = isset($grpmatch[3]) ? $this->pba($grpmatch[3]) : '';
			$row = $grpmatch[4];

			if (preg_match("/^($this->a$this->c\. )(.*)/m", ltrim($row), $rmtch)) {
				$ratts = $this->pba($rmtch[1], 'tr');
				$row = $rmtch[2];
			} else $ratts = '';

			$cells = array();
			$cellctr = 0;
			foreach(explode("|", $row) as $cell) {
				$ctyp = "d";
				if (preg_match("/^_/", $cell)) $ctyp = "h";
				if (preg_match("/^(_?$this->s$this->a$this->c\. )(.*)/", $cell, $cmtch)) {
					$catts = $this->pba($cmtch[1], 'td');
					$cell = $cmtch[2];
				} else $catts = '';

				if (!$this->lite) {
					$cell = $this->redcloth_lists($cell);
					$cell = $this->lists($cell);
				}

				if ($cellctr>0) // Ignore first 'cell': it precedes the opening pipe
					$cells[] = $this->doTagBr("t$ctyp", "\t\t\t<t$ctyp$catts>$cell</t$ctyp>");

				$cellctr++;
			}
			$grp = (($rgrp && $last_rgrp) ? "\t</t".$last_rgrp.">\n" : '') . (($rgrp) ? "\t<t".$rgrp.$rgrpatts.">\n" : '');
			$last_rgrp = ($rgrp) ? $rgrp : $last_rgrp;
			$rows[] = $grp."\t\t<tr$ratts>\n" . join("\n", $cells) . ($cells ? "\n" : "") . "\t\t</tr>";
			unset($cells, $catts);
		}

		return "\t<table{$tatts}{$sum}>\n" .$cap. $colgrp. join("\n", $rows) . "\n".(($last_rgrp) ? "\t</t".$last_rgrp.">\n" : '')."\t</table>\n\n";
	}

// -------------------------------------------------------------
	function redcloth_lists($text)
	{
		return preg_replace_callback("/^([-]+$this->lc[ .].*:=.*)$(?![^-])/smU", array(&$this, "fRCList"), $text);
	}

// -------------------------------------------------------------
	function fRCList($m)
	{
		$out = array();
		$text = preg_split('/\n(?=[-])/m', $m[0]);
		foreach($text as $nr => $line) {
			if (preg_match("/^[-]+($this->lc)[ .](.*)$/s", $line, $m)) {
				list(, $atts, $content) = $m;
				$content = trim($content);
				$atts = $this->pba($atts);

				preg_match( "/^(.*?)[\s]*:=(.*?)[\s]*(=:|:=)?[\s]*$/s", $content, $xm );
				list( , $term, $def, ) = array_pad($xm, 3, '');
				$term = trim( $term );
				$def  = trim( $def, ' ' );

				if( empty( $out ) ) {
					if(''==$def)
						$out[] = "<dl$atts>";
					else
						$out[] = '<dl>';
				}

				if( '' != $def && '' != $term )
				{
					$pos = strpos( $def, "\n" );
					$def = str_replace( "\n", "<br />", trim($def) );
					if( 0 === $pos )
						$def  = '<p>' . $def . '</p>';

					$term = $this->graf($term);
					$def  = $this->graf($def);

					$out[] = "\t<dt$atts>$term</dt>";
					$out[] = "\t<dd>$def</dd>";
				}
			}
		}
		$out[] = '</dl>';
		return implode("\n", $out);
	}


// -------------------------------------------------------------
	function lists($text)
	{
		return preg_replace_callback("/^((?:[*;:]+|[*;:#]*#(?:_|\d+)?)$this->lc[ .].*)$(?![^#*;:])/smU", array(&$this, "fList"), $text);
	}

// -------------------------------------------------------------
	function fList($m)
	{
		$text = preg_split('/\n(?=[*#;:])/m', $m[0]);
		$pt = '';
		foreach($text as $nr => $line) {
			$nextline = isset($text[$nr+1]) ? $text[$nr+1] : false;
			if (preg_match("/^([#*;:]+)(_|\d+)?($this->lc)[ .](.*)$/s", $line, $m)) {
				list(, $tl, $st, $atts, $content) = $m;
				$content = trim($content);
				$nl = '';
				$ltype = $this->lT($tl);
				$litem = (strpos($tl, ';') !== false) ? 'dt' : ((strpos($tl, ':') !== false) ? 'dd' : 'li');
				$showitem = (strlen($content) > 0);

				if( 'o' === $ltype ) {					// handle list continuation/start attribute on ordered lists...
					if( !isset($this->olstarts[$tl]) )
						$this->olstarts[$tl] = 1;

					if( strlen($tl) > strlen($pt) ) {			// first line of this level of ol -- has a start attribute?
						if( '' == $st )
							$this->olstarts[$tl] = 1;			// no => reset count to 1.
						elseif( '_' !== $st )
							$this->olstarts[$tl] = (int)$st;	// yes, and numeric => reset to given.
																// TRICKY: the '_' continuation marker just means
																// output the count so don't need to do anything
																// here.
					}

					if( (strlen($tl) > strlen($pt)) && '' !== $st)		// output the start attribute if needed...
						$st = ' start="' . $this->olstarts[$tl] . '"';

					if( $showitem ) 							// TRICKY: Only increment the count for list items; not when a list definition line is encountered.
						$this->olstarts[$tl] += 1;
				}

				if (preg_match("/^([#*;:]+)(_|[\d]+)?($this->lc)[ .].*/", $nextline, $nm))
					$nl = $nm[1];

				if ((strpos($pt, ';') !== false) && (strpos($tl, ':') !== false)) {
					$lists[$tl] = 2; // We're already in a <dl> so flag not to start another
				}

				$atts = $this->pba($atts);
				if (!isset($lists[$tl])) {
					$lists[$tl] = 1;
					$line = "\t<" . $ltype . "l$atts$st>" . (($showitem) ? "\n\t\t<$litem>" . $content : '');
				} else {
					$line = ($showitem) ? "\t\t<$litem$atts>" . $content : '';
				}

				if((strlen($nl) <= strlen($tl))) $line .= (($showitem) ? "</$litem>" : '');
				foreach(array_reverse($lists) as $k => $v) {
					if(strlen($k) > strlen($nl)) {
						$line .= ($v==2) ? '' : "\n\t</" . $this->lT($k) . "l>";
						if((strlen($k) > 1) && ($v != 2))
							$line .= "</".$litem.">";
						unset($lists[$k]);
					}
				}
				$pt = $tl; // Remember the current Textile tag
			}
			else {
				$line .= "\n";
			}
			$out[] = $line;
		}
		return $this->doTagBr($litem, join("\n", $out));
	}

// -------------------------------------------------------------
	function lT($in)
	{
		return preg_match("/^#+/", $in) ? 'o' : ((preg_match("/^\*+/", $in)) ? 'u' : 'd');
	}

// -------------------------------------------------------------
	function doTagBr($tag, $in)
	{
		return preg_replace_callback('@<('.preg_quote($tag).')([^>]*?)>(.*)(</\1>)@s', array(&$this, 'fBr'), $in);
	}

// -------------------------------------------------------------
	function doPBr($in)
	{
		return preg_replace_callback('@<(p)([^>]*?)>(.*)(</\1>)@s', array(&$this, 'fPBr'), $in);
	}

// -------------------------------------------------------------
	function fPBr($m)
	{
		# Less restrictive version of fBr() ... used only in paragraphs where the next
		# row may start with a smiley or perhaps something like '#8 bolt...' or '*** stars...'
		$content = preg_replace("@(.+)(?<!<br>|<br />)\n(?![\s|])@", '$1<br />', $m[3]);
		return '<'.$m[1].$m[2].'>'.$content.$m[4];
	}

// -------------------------------------------------------------
	function fBr($m)
	{
		$content = preg_replace("@(.+)(?<!<br>|<br />)\n(?![#*;:\s|])@", '$1<br />', $m[3]);
		return '<'.$m[1].$m[2].'>'.$content.$m[4];
	}

// -------------------------------------------------------------
	function block($text)
	{
		$find = $this->btag;
		$tre = join('|', $find);

		$text = explode("\n\n", $text);

		$tag = 'p';
		$atts = $cite = $graf = $ext = '';
		$eat = false;

		$out = array();

		foreach($text as $line) {
			$anon = 0;
			if (preg_match("/^($tre)($this->a$this->c)\.(\.?)(?::(\S+))? (.*)$/s", $line, $m)) {
				// last block was extended, so close it
				if ($ext)
					$out[count($out)-1] .= $c1;
				// new block
				list(,$tag,$atts,$ext,$cite,$graf) = $m;
				list($o1, $o2, $content, $c2, $c1, $eat) = $this->fBlock(array(0,$tag,$atts,$ext,$cite,$graf));

				// leave off c1 if this block is extended, we'll close it at the start of the next block
				if ($ext)
					$line = $o1.$o2.$content.$c2;
				else
					$line = $o1.$o2.$content.$c2.$c1;
			}
			else {
				// anonymous block
				$anon = 1;
				if ($ext or !preg_match('/^ /', $line)) {
					list($o1, $o2, $content, $c2, $c1, $eat) = $this->fBlock(array(0,$tag,$atts,$ext,$cite,$line));
					// skip $o1/$c1 because this is part of a continuing extended block
					if ($tag == 'p' and !$this->hasRawText($content)) {
						$line = $content;
					}
					else {
						$line = $o2.$content.$c2;
					}
				}
				else {
					$line = $this->graf($line);
				}
			}

			$line = $this->doPBr($line);
			$line = preg_replace('/<br>/', '<br />', $line);

			if ($ext and $anon)
				$out[count($out)-1] .= "\n".$line;
			elseif(!$eat)
				$out[] = $line;

			if (!$ext) {
				$tag = 'p';
				$atts = '';
				$cite = '';
				$graf = '';
				$eat = false;
			}
		}
		if ($ext) $out[count($out)-1] .= $c1;
		return join("\n\n", $out);
	}

// -------------------------------------------------------------
	function formatFootnote( $marker, $atts='', $anchor=true )
	{
		$pattern = ($anchor) ? txt_fn_foot_pattern : txt_fn_ref_pattern;
		return $this->replaceMarkers( $pattern, array( 'atts' => $atts, 'marker' => $marker ) );
	}

// -------------------------------------------------------------
	function replaceMarkers( $text, $replacements )
	{
		if( !empty( $replacements ) )
			foreach( $replacements as $k => $r )
				$text = str_replace( '{'.$k.'}', $r, $text );
		return $text;
	}

// -------------------------------------------------------------
	function fBlock($m)
	{
		extract($this->regex_snippets);
		list(, $tag, $att, $ext, $cite, $content) = $m;
		$atts = $this->pba($att);

		$o1 = $o2 = $c2 = $c1 = '';
		$eat = false;

		if( $tag === 'p' ) {
			# Is this an anonymous block with a note definition?
			$notedef = preg_replace_callback("/
					^note\#               #  start of note def marker
					([^%<*!@#^([{ \s.]+)  # !label
					([*!^]?)              # !link
					({$this->c})          # !att
					\.?                   #  optional period.
					[\s]+                 #  whitespace ends def marker
					(.*)$                 # !content
				/x$mod", array(&$this, "fParseNoteDefs"), $content);

			if( '' === $notedef ) # It will be empty if the regex matched and ate it.
				return array($o1, $o2, $notedef, $c2, $c1, true);
			}

		if (preg_match("/fn(\d+)/", $tag, $fns)) {
			$tag = 'p';
			$fnid = empty($this->fn[$fns[1]]) ? $fns[1] : $this->fn[$fns[1]];

			# If there is an author-specified ID goes on the wrapper & the auto-id gets pushed to the <sup>
			$supp_id = '';
			if (strpos($atts, ' id=') === false)
				$atts .= ' id="fn' . $fnid . '"';
			else
				$supp_id = ' id="fn' . $fnid . '"';

			if (strpos($atts, 'class=') === false)
				$atts .= ' class="footnote"';

			$sup = (strpos($att, '^') === false) ? $this->formatFootnote($fns[1], $supp_id) : $this->formatFootnote('<a href="#fnrev' . $fnid . '">'.$fns[1] .'</a>', $supp_id);

			$content = $sup . ' ' . $content;
		}

		if ($tag == "bq") {
			$cite = $this->shelveURL($cite);
			$cite = ($cite != '') ? ' cite="' . $cite . '"' : '';
			$o1 = "\t<blockquote$cite$atts>\n";
			$o2 = "\t\t<p".$this->pba($att, '', 0).">";
			$c2 = "</p>";
			$c1 = "\n\t</blockquote>";
		}
		elseif ($tag == 'bc') {
			$o1 = "<pre$atts>";
			$o2 = "<code>";
			$c2 = "</code>";
			$c1 = "</pre>";
			$content = $this->shelve($this->r_encode_html(rtrim($content, "\n")."\n"));
		}
		elseif ($tag == 'notextile') {
			$content = $this->shelve($content);
			$o1 = $o2 = '';
			$c1 = $c2 = '';
		}
		elseif ($tag == 'pre') {
			$content = $this->shelve($this->r_encode_html(rtrim($content, "\n")."\n"));
			$o1 = "<pre$atts>";
			$o2 = $c2 = '';
			$c1 = "</pre>";
		}
		elseif ($tag == '###') {
			$eat = true;
		}
		else {
			$o2 = "\t<$tag$atts>";
			$c2 = "</$tag>";
		}

		$content = (!$eat) ? $this->graf($content) : '';

		return array($o1, $o2, $content, $c2, $c1, $eat);
	}

// -------------------------------------------------------------
	function fParseHTMLComments($m)
	{
		list( , $content ) = $m;
		if( $this->restricted )
			$content = $this->shelve($this->r_encode_html($content));
		else
			$content = $this->shelve($content);
		return "<!--$content-->";
	}


	function getHTMLComments($text)
	{
		$text = preg_replace_callback("/
			\<!--    #  start
			(.*?)    # !content
			-->      #  end
		/sx", array(&$this, "fParseHTMLComments"), $text);
		return $text;
	}

// -------------------------------------------------------------
	function graf($text)
	{
		// handle normal paragraph text
		if (!$this->lite) {
			$text = $this->noTextile($text);
			$text = $this->code($text);
		}

		$text = $this->getHTMLComments($text);
		$text = $this->getRefs($text);
		$text = $this->links($text);
		if (!$this->noimage)
			$text = $this->image($text);

		if (!$this->lite) {
			$text = $this->table($text);
			$text = $this->redcloth_lists($text);
			$text = $this->lists($text);
		}

		$text = $this->span($text);
		$text = $this->footnoteRef($text);
		$text = $this->noteRef($text);
		$text = $this->glyphs($text);

		return rtrim($text, "\n");
	}

// -------------------------------------------------------------
	function span($text)
	{
		$qtags = array('\*\*','\*','\?\?','-','__','_','%','\+','~','\^');
		$pnct = ".,\"'?!;:‹›«»„“”‚‘’";
		$this->span_depth++;

		if( $this->span_depth <= $this->max_span_depth )
		{
			foreach($qtags as $f)
			{
				$text = preg_replace_callback("/
					(^|(?<=[\s>$pnct\(])|[{[])            # pre
					($f)(?!$f)                            # tag
					({$this->c})                          # atts
					(?::(\S+))?                           # cite
					([^\s$f]+|\S.*?[^\s$f\n])             # content
					([$pnct]*)                            # end
					$f
					($|[\[\]}<]|(?=[$pnct]{1,2}|\s|\)))  # tail
				/x".$this->regex_snippets['mod'], array(&$this, "fSpan"), $text);
			}
		}
		$this->span_depth--;
		return $text;
	}

// -------------------------------------------------------------
	function fSpan($m)
	{
		$qtags = array(
			'*'  => 'strong',
			'**' => 'b',
			'??' => 'cite',
			'_'  => 'em',
			'__' => 'i',
			'-'  => 'del',
			'%'  => 'span',
			'+'  => 'ins',
			'~'  => 'sub',
			'^'  => 'sup',
		);

		list(, $pre, $tag, $atts, $cite, $content, $end, $tail) = $m;

		$tag = $qtags[$tag];
		$atts = $this->pba($atts);
		$atts .= ($cite != '') ? 'cite="' . $cite . '"' : '';

		$content = $this->span($content);

		$opentag = '<'.$tag.$atts.'>';
		$closetag = '</'.$tag.'>';
		$tags = $this->storeTags($opentag, $closetag);
		$out = "{$tags['open']}{$content}{$end}{$tags['close']}";

		if (($pre and !$tail) or ($tail and !$pre))
			$out = $pre.$out.$tail;

		return $out;
	}

// -------------------------------------------------------------
	function storeTags($opentag,$closetag='')
	{
		$key = ($this->tag_index++);

		$key = str_pad( (string)$key, 10, '0', STR_PAD_LEFT ); # $key must be of fixed length to allow proper matching in retrieveTags
		$this->tagCache[$key] = array('open'=>$opentag, 'close'=>$closetag);
		$tags = array(
			'open'  => "textileopentag{$key} ",
			'close' => " textileclosetag{$key}",
		);
		return $tags;
	}

// -------------------------------------------------------------
	function retrieveTags($text)
	{
		$text = preg_replace_callback('/textileopentag([\d]{10}) /' , array(&$this, 'fRetrieveOpenTags'),  $text);
		$text = preg_replace_callback('/ textileclosetag([\d]{10})/', array(&$this, 'fRetrieveCloseTags'), $text);
		return $text;
	}

// -------------------------------------------------------------
	function fRetrieveOpenTags($m)
	{
		list(, $key ) = $m;
		return $this->tagCache[$key]['open'];
	}

// -------------------------------------------------------------
	function fRetrieveCloseTags($m)
	{
		list(, $key ) = $m;
		return $this->tagCache[$key]['close'];
	}

// -------------------------------------------------------------
	function placeNoteLists($text)
	{
		extract($this->regex_snippets);

		# Sequence all referenced definitions...
		if( !empty($this->notes) ) {
			$o = array();
			foreach( $this->notes as $label=>$info ) {
				$i = isset($info['seq']) ? $info['seq'] : '';
				if( !empty($i) ) {
					$info['seq'] = $label;
					$o[$i] = $info;
				} else {
					$this->unreferencedNotes[] = $info;	# unreferenced definitions go here for possible future use.
				}
			}
			if( !empty($o) ) ksort($o);
			$this->notes = $o;
		}

		# Replace list markers...
		$text = preg_replace_callback("@<p>notelist({$this->c})(?:\:([$wrd|{$this->syms}]))?([\^!]?)(\+?)\.?[\s]*</p>@U$mod", array(&$this, "fNoteLists"), $text );

		return $text;
	}

// -------------------------------------------------------------
	function fNoteLists($m)
	{
		list(, $att, $start_char, $g_links, $extras) = $m;
		if( !$start_char ) $start_char = 'a';
		$index = $g_links.$extras.$start_char;

		if( empty($this->notelist_cache[$index]) ) { # If not in cache, build the entry...
			$o = array();

			if( !empty($this->notes)) {
				foreach($this->notes as $seq=>$info) {
					$links = $this->makeBackrefLink($info, $g_links, $start_char );
					$atts = '';
					if( !empty($info['def'])) {
						$id = $info['id'];
						extract($info['def']);
						$o[] = "\t".'<li'.$atts.'>'.$links.'<span id="note'.$id.'"> </span>'.$content.'</li>';
					} else {
						$o[] = "\t".'<li'.$atts.'>'.$links.' Undefined Note [#'.$info['seq'].'].</li>';
					}
				}
			}
			if( '+' == $extras && !empty($this->unreferencedNotes) ) {
				foreach($this->unreferencedNotes as $seq=>$info) {
					if( !empty($info['def'])) {
						extract($info['def']);
						$o[] = "\t".'<li'.$atts.'>'.$content.'</li>';
					}
				}
			}

			$this->notelist_cache[$index] = join("\n",$o);
		}

		$_ = ($this->notelist_cache[$index]) ? $this->notelist_cache[$index] : '';

		if( !empty($_) ) {
			$list_atts = $this->pba($att);
			$_ = "<ol$list_atts>\n$_\n</ol>";
		}

		return $_;
	}

// -------------------------------------------------------------
	function makeBackrefLink( &$info, $g_links, $i )
	{
		$atts = $content = $id = $link = '';
		@extract( $info['def'] );
		$backlink_type = ($link) ? $link : $g_links;
		$allow_inc = (false === strpos( $this->syms, $i ) );

		$i_ = strtr( $this->encode_high($i) , array('&'=>'', ';'=>'', '#'=>''));
		$decode = (strlen($i) !== strlen($i_));

		if( $backlink_type === '!' )
			return '';
		elseif( $backlink_type === '^' )
			return '<sup><a href="#noteref'.$info['refids'][0].'">'.$i.'</a></sup>';
		else {
			$_ = array();
			foreach( $info['refids'] as $id ) {
				$_[] = '<sup><a href="#noteref'.$id.'">'. ( ($decode) ? $this->decode_high('&#'.$i_.';') : $i_ ) .'</a></sup>';
				if( $allow_inc )
					$i_++;
			}
			$_ = join( ' ', $_ );
			return $_;
		}
	}


// -------------------------------------------------------------
	function fParseNoteDefs($m)
	{
		list(, $label, $link, $att, $content) = $m;
		# Assign an id if the note reference parse hasn't found the label yet.
		$id = isset($this->notes[$label]['id']) ? $this->notes[$label]['id'] : '';
		if( !$id )
			$this->notes[$label]['id'] = uniqid(rand());

		if( empty($this->notes[$label]['def']) ) # Ignores subsequent defs using the same label
		{
			$this->notes[$label]['def'] = array(
				'atts'    => $this->pba($att),
				'content' => $this->graf($content),
				'link'    => $link,
			);
		}
		return '';
	}

// -------------------------------------------------------------
	function noteRef($text)
	{
		$text = preg_replace_callback("/
			\[                   #  start
			({$this->c})         # !atts
			\#
			([^\]!]+?)           # !label
			([!]?)               # !nolink
			\]
		/Ux", array(&$this, "fParseNoteRefs"), $text);
		return $text;
	}

// -------------------------------------------------------------
	function fParseNoteRefs($m)
	{
		#   By the time this function is called, all the defs will have been processed
		# into the notes array. So now we can resolve the link numbers in the order
		# we process the refs...

		list(, $atts, $label, $nolink) = $m;
		$atts = $this->pba($atts);
		$nolink = ($nolink === '!');

		# Assign a sequence number to this reference if there isn't one already...
		$num = isset($this->notes[$label]['seq']) ? $this->notes[$label]['seq'] : '';
		if( !$num )
			$num = $this->notes[$label]['seq'] = ($this->note_index++);

		# Make our anchor point & stash it for possible use in backlinks when the
		# note list is generated later...
		$this->notes[$label]['refids'][] = $refid = uniqid(rand());

		# If we are referencing a note that hasn't had the definition parsed yet, then assign it an ID...
		$id = isset($this->notes[$label]['id']) ? $this->notes[$label]['id'] : '';
		if( !$id )
			$id = $this->notes[$label]['id'] = uniqid(rand());

		# Build the link (if any)...
		$_ = '<span id="noteref'.$refid.'">'.$num.'</span>';
		if( !$nolink )
			$_ = '<a href="#note'.$id.'">'.$_.'</a>';

		# Build the reference...
		$_ = $this->replaceMarkers( txt_nl_ref_pattern, array( 'atts' => $atts, 'marker' => $_ ) );

		return $_;
	}

// -------------------------------------------------------------
	/**
	 * Parse URI
	 *
	 * Regex taken from the RFC at http://tools.ietf.org/html/rfc3986#appendix-B
	 **/
	function parseURI( $uri, &$m )
	{
		$r = "@^((?P<scheme>[^:/?#]+):)?(//(?P<authority>[^/?#]*))?(?P<path>[^?#]*)(\?(?P<query>[^#]*))?(#(?P<fragment>.*))?@";
		#       12                     3  4                      5              6  7                8 9
		#
		#	scheme    = $2
		#	authority = $4
		# 	path      = $5
		#	query     = $7
		#	fragment  = $9

		$ok = preg_match( $r, $uri, $m );
		return $ok;
	}

	function addPart( &$mask, $name, &$parts ) {
		return (in_array($name, $mask) && isset( $parts[$name]) && '' !== $parts[$name]);
	}


// -------------------------------------------------------------
	/**
	 * Rebuild a URI from parsed parts and a mask.
	 *
	 * Algorithm based on example from http://tools.ietf.org/html/rfc3986#section-5.3
	 **/
	function rebuildURI( $parts, $mask='scheme,authority,path,query,fragment', $encode=true )
	{
		$mask = explode( ',', $mask );
		$out  = '';

		if( $this->addPart( $mask, 'scheme', $parts ) ) {
			$out .= $parts['scheme'] . ':';
		}

		if( $this->addPart( $mask, 'authority', $parts) ) {
			$out .= '//' . $parts['authority'];
		}

		if( $this->addPart( $mask, 'path', $parts ) ) {
			if( !$encode )
				$out .= $parts['path'];
			else {
				$pp = explode( '/', $parts['path'] );
				foreach( $pp as &$p ) {
					$p = strtr( rawurlencode( $p ), array( '%40' => '@' ) );
				}

				$pp = implode( '/', $pp );
				$out .= $pp;
			}
		}

		if( $this->addPart( $mask, 'query', $parts ) ) {
			$out .= '?' . $parts['query'];
		}

		if( $this->addPart( $mask, 'fragment', $parts ) ) {
			$out .= '#' . $parts['fragment'];
		}

		return $out;
	}

// -------------------------------------------------------------
	function links($text)
	{
		return preg_replace_callback('/
			(^|(?<=[\s>.\(])|[{[]) # $pre
			"                      # start
			(' . $this->c . ')     # $atts
			([^"]+?)               # $text
			(?:\(([^)]+?)\)(?="))? # $title
			":
			('.$this->urlch.'+?)   # $url
			(\/)?                  # $slash
			([^'.$this->regex_snippets['wrd'].'\/;]*?)  # $post
			([\]}]|(?=\s|$|\)))	   # $tail
			/x'.$this->regex_snippets['mod'], array(&$this, "fLink"), $text);
	}

// -------------------------------------------------------------
	function fLink($m)
	{
		list(, $pre, $atts, $text, $title, $url, $slash, $post, $tail) = $m;

		$uri_parts = array();
		$this->parseURI( $url, $uri_parts );

		$scheme         = $uri_parts['scheme'];
		$scheme_in_list = in_array( $scheme, $this->url_schemes );
		$scheme_ok = '' === $scheme || $scheme_in_list;

		if( !$scheme_ok )
			return $m[0];

		if( '$' === $text ) {
			if( $scheme_in_list )
				$text = ltrim( $this->rebuildURI( $uri_parts, 'authority,path,query,fragment', false ), '/' );
			else
				$text = $url;
		}

		$atts = $this->pba($atts);
		$atts .= ($title != '') ? ' title="' . $this->encode_html($title) . '"' : '';

		if (!$this->noimage)
			$text = $this->image($text);

		$text = $this->span($text);
		$text = $this->glyphs($text);
		$url  = $this->shelveURL( $this->rebuildURI( $uri_parts ) . $slash );

		$opentag  = '<a href="' . $url . '"' . $atts . $this->rel . '>';
		$closetag = '</a>';
		$tags     = $this->storeTags($opentag, $closetag);
		$out      = $tags['open'].trim($text).$tags['close'];

		if (($pre and !$tail) or ($tail and !$pre))
		{
			$out = $pre.$out.$post.$tail;
			$post = '';
		}

		return $this->shelve($out).$post;
	}

// -------------------------------------------------------------
	function getRefs($text)
	{
		if( $this->restricted )
			$pattern = "/^\[(.+)\]((?:http:\/\/|https:\/\/|\/)\S+)(?=\s|$)/Um";
		else
			$pattern = "/^\[(.+)\]((?:http:\/\/|https:\/\/|tel:|file:|ftp:\/\/|sftp:\/\/|mailto:|callto:|\/)\S+)(?=\s|$)/Um";
		return preg_replace_callback( $pattern, array(&$this, "refs"), $text);
	}

// -------------------------------------------------------------
	function refs($m)
	{
		list(, $flag, $url) = $m;
		$uri_parts = array();
		$this->parseURI( $url, $uri_parts );
		$url = ltrim( $this->rebuildURI( $uri_parts ) ); // encodes URL if needed.
		$this->urlrefs[$flag] = $url;
		return '';
	}

// -------------------------------------------------------------
	function shelveURL($text)
	{
		if ('' === $text) return '';
		$ref = md5($text);
		$this->urlshelf[$ref] = $text;
		return 'urlref:'.$ref;
	}

// -------------------------------------------------------------
	function retrieveURLs($text)
	{
		return preg_replace_callback('/urlref:(\w{32})/',
			array(&$this, "retrieveURL"), $text);
	}

// -------------------------------------------------------------
	function retrieveURL($m)
	{
		$ref = $m[1];
		if (!isset($this->urlshelf[$ref]))
			return $ref;
		$url = $this->urlshelf[$ref];
		if (isset($this->urlrefs[$url]))
			$url = $this->urlrefs[$url];
		return $this->r_encode_html($this->relURL($url));
	}

// -------------------------------------------------------------
	function relURL($url)
	{
		$parts = @parse_url(urldecode($url));
		if ((empty($parts['scheme']) or @$parts['scheme'] == 'http') and
			 empty($parts['host']) and
			 preg_match('/^\w/', @$parts['path']))
			$url = $this->hu.$url;
		if ($this->restricted and !empty($parts['scheme']) and
				!in_array($parts['scheme'], $this->url_schemes))
			return '#';
		return $url;
	}

// -------------------------------------------------------------
	function isRelURL($url)
	{
		$parts = @parse_url($url);
		return (empty($parts['scheme']) and empty($parts['host']));
	}

// -------------------------------------------------------------
	function image($text)
	{
		return preg_replace_callback("/
			(?:[[{])?		   # pre
			\!				   # opening !
			(\<|\=|\>)? 	   # optional alignment atts
			($this->c)		   # optional style,class atts
			(?:\. )?		   # optional dot-space
			([^\s(!]+)		   # presume this is the src
			\s? 			   # optional space
			(?:\(([^\)]+)\))?  # optional title
			\!				   # closing
			(?::(\S+))? 	   # optional href
			(?:[\]}]|(?=\s|$|\))) # lookahead: space or end of string
		/x", array(&$this, "fImage"), $text);
	}

// -------------------------------------------------------------
	function fImage($m)
	{
		list(, $algn, $atts, $url) = $m;
		$url = htmlspecialchars($url);

		$extras = $align = '';
		if( '' !== $algn ) {
			$vals = array(
				'<' => 'left',
				'=' => 'center',
				'>' => 'right');
			if ( isset($vals[$algn]) ) {
				if( 'html5' === $this->doctype )
					$extras = "align-{$vals[$algn]}";
				else
					$align = " align=\"{$vals[$algn]}\"";
			}
		}
		$atts  = $this->pba($atts , '' , 1 , $extras) . $align;

 		if(isset($m[4]))
 		{
 			$m[4] = htmlspecialchars($m[4]);
			$atts .= ' title="' . $m[4] . '" alt="'	 . $m[4] . '"';
 		}
 		else
 			$atts .= ' alt=""';

		$size = false;
		if ($this->isRelUrl($url))
			$size = @getimagesize(realpath($this->doc_root.ltrim($url, $this->ds)));
		if ($size) $atts .= " $size[3]";

		$href = (isset($m[5])) ? $this->shelveURL($m[5]) : '';
		$url = $this->shelveURL($url);

		$out = array(
			($href) ? '<a href="' . $href . '"' . $this->rel .'>' : '',
			'<img src="' . $url . '"' . $atts . ' />',
			($href) ? '</a>' : ''
		);

		return $this->shelve(join('',$out));
	}

// -------------------------------------------------------------
	function code($text)
	{
		$text = $this->doSpecial($text, '<code>', '</code>', 'fCode');
		$text = $this->doSpecial($text, '@', '@', 'fCode');
		$text = $this->doSpecial($text, '<pre>', '</pre>', 'fPre');
		return $text;
	}

// -------------------------------------------------------------
	function fCode($m)
	{
		list(, $before, $text, $after) = array_pad($m, 4, '');
		return $before.$this->shelve('<code>'.$this->r_encode_html($text).'</code>').$after;
	}

// -------------------------------------------------------------
	function fPre($m)
	{
		list(, $before, $text, $after) = array_pad($m, 4, '');
		return $before.'<pre>'.$this->shelve($this->r_encode_html($text)).'</pre>'.$after;
	}

// -------------------------------------------------------------
	function shelve($val)
	{
		$i = uniqid(rand());
		$this->shelf[$i] = $val;
		return $i;
	}

// -------------------------------------------------------------
	function retrieve($text)
	{
		if (is_array($this->shelf))
			do {
				$old = $text;
				$text = strtr($text, $this->shelf);
			 } while ($text != $old);

		return $text;
	}

// -------------------------------------------------------------
// NOTE: deprecated
	function incomingEntities($text)
	{
		return preg_replace("/&(?![#a-z0-9]+;)/i", "x%x%", $text);
	}

// -------------------------------------------------------------
// NOTE: deprecated
	function encodeEntities($text)
	{
		return (function_exists('mb_encode_numericentity'))
		?	 $this->encode_high($text)
		:	 htmlentities($text, ENT_NOQUOTES, "utf-8");
	}

// -------------------------------------------------------------
// NOTE: deprecated
	function fixEntities($text)
	{
		/*	de-entify any remaining angle brackets or ampersands */
		return str_replace(array("&gt;", "&lt;", "&amp;"),
			array(">", "<", "&"), $text);
	}

// -------------------------------------------------------------
	function cleanWhiteSpace($text)
	{
		$out = preg_replace("/^\xEF\xBB\xBF|\x1A/", '', $text); # Byte order mark (if present)
		$out = preg_replace("/\r\n?/", "\n", $out); # DOS and MAC line endings to *NIX style endings
		$out = preg_replace("/^[ \t]*\n/m", "\n", $out);	# lines containing only whitespace
		$out = preg_replace("/\n{3,}/", "\n\n", $out);	# 3 or more line ends
		$out = preg_replace("/^\n*/", "", $out);		# leading blank lines
		return $out;
	}

// -------------------------------------------------------------
	function doSpecial($text, $start, $end, $method='fSpecial')
	{
		return preg_replace_callback('/(^|\s|[[({>])'.preg_quote($start, '/').'(.*?)'.preg_quote($end, '/').'(\s|$|[\])}])?/ms',
			array(&$this, $method), $text);
	}

// -------------------------------------------------------------
	function fSpecial($m)
	{
		// A special block like notextile or code
		list(, $before, $text, $after) = array_pad($m, 4, '');
		return $before.$this->shelve($this->encode_html($text)).$after;
	}

// -------------------------------------------------------------
	function noTextile($text)
	{
		 $text = $this->doSpecial($text, '<notextile>', '</notextile>', 'fTextile');
		 return $this->doSpecial($text, '==', '==', 'fTextile');

	}

// -------------------------------------------------------------
	function fTextile($m)
	{
		list(, $before, $notextile, $after) = array_pad($m, 4, '');
		#$notextile = str_replace(array_keys($modifiers), array_values($modifiers), $notextile);
		return $before.$this->shelve($notextile).$after;
	}

// -------------------------------------------------------------
	function footnoteRef($text)
	{
		return preg_replace_callback('/(?<=\S)\[([0-9]+)([\!]?)\](\s)?/U', array(&$this, 'footnoteID'), $text);
	}

// -------------------------------------------------------------
	function footnoteID($m)
	{
		list(, $id, $nolink, $t) = array_pad($m, 4, '');

		$backref = ' ';
		if (empty($this->fn[$id])) {
			$this->fn[$id] = $a = uniqid(rand());
			$backref = ' id="fnrev'.$a.'" ';
		}

		$fnid = $this->fn[$id];

		$footref = ( '!' == $nolink ) ? $id : '<a href="#fn'.$fnid.'">'.$id.'</a>';
		$backref .= 'class="footnote"';

		$footref = $this->formatFootnote( $footref, $backref, false );

		return $footref;
	}

// -------------------------------------------------------------
	function glyphs($text)
	{
		// fix: hackish -- adds a space if final char of text is a double quote.
		$text = preg_replace('/"\z/', "\" ", $text);

		$text = preg_split("@(<[\w/!?].*>)@Us", $text, -1, PREG_SPLIT_DELIM_CAPTURE);
		$i = 0;
		foreach($text as $line) {
			// text tag text tag text ...
			if (++$i % 2) {
				// raw < > & chars are already entity encoded in restricted mode
				if (!$this->restricted) {
					$line = $this->encode_raw_amp($line);
					$line = $this->encode_lt_gt($line);
				}
				$line = preg_replace($this->glyph_search, $this->glyph_replace, $line);
			}
			$glyph_out[] = $line;
		}
		return join('', $glyph_out);
	}

// -------------------------------------------------------------
	function replaceGlyphs($text)
	{
		return preg_replace('/glyph:([^<]+)/','$1',$text);
	}

// -------------------------------------------------------------
	function hAlign($in)
	{
		$vals = array(
			'<'  => 'left',
			'='  => 'center',
			'>'  => 'right',
			'<>' => 'justify');
		return (isset($vals[$in])) ? $vals[$in] : '';
	}

// -------------------------------------------------------------
	function vAlign($in)
	{
		$vals = array(
			'^' => 'top',
			'-' => 'middle',
			'~' => 'bottom');
		return (isset($vals[$in])) ? $vals[$in] : '';
	}

// -------------------------------------------------------------
// NOTE: used in notelists
	function encode_high($text, $charset = "UTF-8")
	{
		return mb_encode_numericentity($text, $this->cmap(), $charset);
	}

// -------------------------------------------------------------
// NOTE: used in notelists
	function decode_high($text, $charset = "UTF-8")
	{
		return mb_decode_numericentity($text, $this->cmap(), $charset);
	}

// -------------------------------------------------------------
	function cmap()
	{
		$f = 0xffff;
		$cmap = array(
			0x0080, 0xffff, 0, $f);
		return $cmap;
	}

// -------------------------------------------------------------
	function encode_raw_amp($text)
	 {
		return preg_replace('/&(?!#?[a-z0-9]+;)/i', '&amp;', $text);
	}

// -------------------------------------------------------------
	function encode_lt_gt($text)
	 {
		return strtr($text, array('<' => '&lt;', '>' => '&gt;'));
	}

// -------------------------------------------------------------
	function encode_quot($text)
	{
		return str_replace('"', '&quot;', $text);
	}

// -------------------------------------------------------------
	function encode_html($str, $quotes=1)
	{
		$a = array(
			'&' => '&amp;',
			'<' => '&lt;',
			'>' => '&gt;',
		);
		if ($quotes) $a = $a + array(
			"'" => '&#39;', // numeric, as in htmlspecialchars
			'"' => '&quot;',
		);

		return strtr($str, $a);
	}

// -------------------------------------------------------------
	function r_encode_html($str, $quotes=1)
	{
		// in restricted mode, all input but quotes has already been escaped
		if ($this->restricted)
			return $this->encode_quot($str);
		return $this->encode_html($str, $quotes);
	}

// -------------------------------------------------------------
	function textile_popup_help($name, $helpvar, $windowW, $windowH)
	{
		return ' <a target="_blank" href="http://www.textpattern.com/help/?item=' . $helpvar . '" onclick="window.open(this.href, \'popupwindow\', \'width=' . $windowW . ',height=' . $windowH . ',scrollbars,resizable\'); return false;">' . $name . '</a><br />';
	}

// -------------------------------------------------------------
// NOTE: deprecated
	function txtgps($thing)
	{
		if (isset($_POST[$thing])) {
			if (get_magic_quotes_gpc()) {
				return stripslashes($_POST[$thing]);
			}
			else {
				return $_POST[$thing];
			}
		}
		else {
			return '';
		}
	}

// -------------------------------------------------------------
// NOTE: deprecated
	function dump()
	{
		static $bool = array( 0=>'false', 1=>'true' );
		foreach (func_get_args() as $a)
			echo "\n<pre>",(is_array($a)) ? print_r($a) : ((is_bool($a)) ? $bool[(int)$a] : $a), "</pre>\n";
		return $this;
	}

// -------------------------------------------------------------

	function blockLite($text)
	{
		$this->btag = array('bq', 'p');
		return $this->block($text."\n\n");
	}


} // end class

