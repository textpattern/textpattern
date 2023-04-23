<?php

/**
 * Textile - A Humane Web Text Generator.
 *
 * @link https://github.com/textile/php-textile
 */

/*
 * Textile - A Humane Web Text Generator
 *
 * Copyright (c) 2003-2004, Dean Allen
 * All rights reserved.
 *
 * Thanks to Carlo Zottmann <carlo@g-blog.net> for refactoring
 * Textile's procedural code into a class framework
 *
 * Additions and fixes Copyright (c) 2006    Alex Shiels       https://twitter.com/tellyworth
 * Additions and fixes Copyright (c) 2010    Stef Dawson       http://stefdawson.com/
 * Additions and fixes Copyright (c) 2010-17 Netcarver         https://github.com/netcarver
 * Additions and fixes Copyright (c) 2011    Jeff Soo          http://ipsedixit.net/
 * Additions and fixes Copyright (c) 2012    Robert Wetzlmayr  http://wetzlmayr.com/
 * Additions and fixes Copyright (c) 2012-19 Jukka Svahn       http://rahforum.biz/
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * * Redistributions of source code must retain the above copyright notice,
 * this list of conditions and the following disclaimer.
 *
 * * Redistributions in binary form must reproduce the above copyright notice,
 * this list of conditions and the following disclaimer in the documentation
 * and/or other materials provided with the distribution.
 *
 * * Neither the name Textile nor the names of its contributors may be used to
 * endorse or promote products derived from this software without specific
 * prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

/*
Textile usage examples.

Block modifier syntax:

    Header: h(1-6).
    Paragraphs beginning with 'hn. ' (where n is 1-6) are wrapped in header tags.
    Example: h1. Header... -> <h1>Header...</h1>

    Paragraph: p. (also applied by default)
    Example: p. Text -> <p>Text</p>

    Blockquote: bq.
    Example: bq. Block quotation... -> <blockquote>Block quotation...</blockquote>

    Blockquote with citation: bq.:http://citation.url
    Example: bq.:http://example.com/ Text...
    ->    <blockquote cite="http://example.com">Text...</blockquote>

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

           _emphasis_    ->     <em>emphasis</em>
           __italic__    ->     <i>italic</i>
             *strong*    ->     <strong>strong</strong>
             **bold**    ->     <b>bold</b>
         ??citation??    ->     <cite>citation</cite>
       -deleted text-    ->     <del>deleted</del>
      +inserted text+    ->     <ins>inserted</ins>
        ^superscript^    ->     <sup>superscript</sup>
          ~subscript~    ->     <sub>subscript</sub>
               @code@    ->     <code>computer code</code>
          %(bob)span%    ->     <span class="bob">span</span>

        ==notextile==    ->     leave text alone (do not format)

       "linktext":url    ->     <a href="url">linktext</a>
"linktext(title)":url    ->     <a href="url" title="title">linktext</a>
              "$":url    ->     <a href="url">url</a>
       "$(title)":url    ->     <a href="url" title="title">url</a>

           !imageurl!    ->     <img src="imageurl" />
 !imageurl(alt text)!    ->     <img src="imageurl" alt="alt text" />
   !imageurl!:linkurl    ->     <a href="linkurl"><img src="imageurl" /></a>

ABC(Always Be Closing)   ->     <acronym title="Always Be Closing">ABC</acronym>

Linked Notes:

    Allows the generation of an automated list of notes with links.

    Linked notes are composed of three parts, a set of named _definitions_, a set of
    _references_ to those definitions and one or more _placeholders_ indicating where
    the consolidated list of notes is to be placed in your document.

    Definitions:

    Each note definition must occur in its own paragraph and should look like this...

    note#mynotelabel. Your definition text here.

    You are free to use whatever label you wish after the # as long as it is made up
    of letters, numbers, colon(:) or dash(-).

    References:

    Each note reference is marked in your text like this[#mynotelabel] and
    it will be replaced with a superscript reference that links into the list of
    note definitions.

    List placeholder(s):

    The note list can go anywhere in your document. You have to indicate where
    like this:

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

        note#lavader(myliclass). "Proof":http://example.com of a small moon.

        notelist(myclass#myid)+.

        Would output (the actual IDs used would be randomised)...

        <p>Scientists say<sup><a href="#note1" id="noteref1">1</sup> the moon is small.</p>

        <ol class="myclass" id="myid">
            <li class="myliclass"><a href="#noteref1"><sup>a</sup></a>
                <span id="note1"> </span><a href="http://example.com">Proof</a> of a small moon.</li>
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

    Note: Table captions *must* be the first line of the table else treated as a center-aligned cell.

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

        <  ->  left align     ex. p<. left-aligned para
        >  ->  right align         h3>. right-aligned header 3
        =  ->  centred             h4=. centred header 4
        <> ->  justified         p<>. justified paragraph

    These will change vertical alignment in table cells:

        ^  ->  top           ex. |^. top-aligned table cell|
        -  ->  middle           |-. middle aligned|
        ~  ->  bottom           |~. bottom aligned cell|

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
        # big                    <li>one</li>
        # list                    <li>big</li>
                                <li>list</li>
                               </ol>

    Using the span tag to style a phrase

        It goes like this, %{color:red}the fourth the fifth%
              -> It goes like this, <span style="color:red">the fourth the fifth</span>

Ordered list start and continuation:

    You can control the start attribute of an ordered list like so;

        #5 Item 5
        # Item 6

    You can resume numbering list items after some intervening anonymous block like so...

        #_ Item 7
        # Item 8
*/

namespace Netcarver\Textile;

/**
 * Textile parser.
 *
 * The Parser class takes Textile input and converts it to well formatted HTML.
 * This is the library's main class, hosting the parsing functionality and
 * exposing a simple public interface for you to use.
 *
 * The most basic use case would involve initialising a instance of the class
 * and calling the Parser::parse() method:
 *
 * bc. $parser = new \Netcarver\Textile\Parser();
 * echo $parser->parse('h1. Hello World!');
 *
 * The above generates:
 *
 * bc. <h1>Hello World!</h1>
 *
 * The functionality of the parser can be customized with the setters:
 *
 * bc. $parser = new \Netcarver\Textile\Parser();
 * $parser->setImages(false)->parse('!no-image.jpg!');
 *
 * The Parser class can also be extended to create pre-configured classes:
 *
 * bc.. namespace MyApp;
 *
 * use \Netcarver\Textile\Parser;
 *
 * class CommentParser extends Parser
 * {
 *     protected function configure()
 *     {
 *         $this->setImages(false)->setRestricted(true)->setLite(true);
 *     }
 * }
 *
 * p. Keep in mind that the classes' protected methods and properties should be
 * considered part of the private API and depending on them should be avoided.
 * Instead try to only use the public methods marked as being part of the
 * public API.
 *
 * @see Parser::__construct()
 * @see Parser::parse()
 */

class Parser
{
    /**
     * HTML5 document type.
     *
     * @since 3.8.0
     */
    const DOCTYPE_HTML5 = 'html5';

    /**
     * XHTML document type.
     *
     * @since 3.8.0
     */
    const DOCTYPE_XHTML = 'xhtml';

    /**
     * Version number.
     *
     * @var string
     */
    protected $ver = '4.0.0';

    /**
     * Regular expression snippets.
     *
     * @var string[]
     */
    protected $regex_snippets = array();

    /**
     * Pattern for horizontal align.
     *
     * @var string
     */
    protected $hlgn = "(?:\<(?!>)|&lt;&gt;|&gt;|&lt;|(?<!<)\>|\<\>|\=|[()]+(?! ))";

    /**
     * Pattern for vertical align.
     *
     * @var string
     */
    protected $vlgn = "[\-^~]";

    /**
     * Pattern for HTML classes and IDs.
     *
     * Does not allow classes/ids/languages/styles to span across
     * newlines if used in a dotall regular expression.
     *
     * @var string
     */
    protected $clas = "(?:\([^)\n]+\))";

    /**
     * Pattern for language attribute.
     *
     * @var string
     */
    protected $lnge = "(?:\[[^]\n]+\])";

    /**
     * Pattern for style attribute.
     *
     * @var string
     */
    protected $styl = "(?:\{[^}\n]+\})";

    /**
     * Regular expression pattern for column spans in tables.
     *
     * @var string
     */
    protected $cspn = "(?:\\\\[0-9]+)";

    /**
     * Regular expression for row spans in tables.
     *
     * @var string
     */
    protected $rspn = "(?:\/[0-9]+)";

    /**
     * Regular expression for horizontal or vertical alignment.
     *
     * @var string
     */
    protected $a;

    /**
     * Regular expression for column or row spans in tables.
     *
     * @var string
     */
    protected $s;

    /**
     * Pattern that matches a class, style, language and horizontal alignment attributes.
     *
     * @var string
     */
    protected $c;

    /**
     * Pattern that matches class, style and language attributes.
     *
     * Allows all 16 possible permutations of class, style and language attributes.
     * No attribute, c, cl, cs, cls, csl, l, lc, ls, lcs, lsc, s, sc, sl, scl or slc.
     *
     * @var string
     */
    protected $cls;

    /**
     * Whitelisted block tags.
     *
     * @var string[]
     */
    protected $blocktag_whitelist = array();

    /**
     * Whether raw blocks are enabled.
     *
     * @var bool
     * @since 3.7.0
     */
    protected $rawBlocksEnabled = false;

    /**
     * An array of patterns used for matching phrasing tags.
     *
     * Phrasing tags, unline others, are wrapped in a paragraph even if they
     * already wrap the block.
     *
     * @var string[]
     * @since 3.7.0
     */
    protected $phrasingContent = array(
        'a',
        'abbr',
        'acronym',
        'area',
        'audio',
        'b',
        'bdo',
        'br',
        'button',
        'canvas',
        'cite',
        'code',
        'command',
        'data',
        'datalist',
        'del',
        'dfn',
        'em',
        'embed',
        'i',
        'iframe',
        'img',
        'input',
        'ins',
        'kbd',
        'keygen',
        'label',
        'link',
        'map',
        'mark',
        'math',
        'meta',
        'meter',
        'noscript',
        'object',
        'output',
        'progress',
        'q',
        'ruby',
        'samp',
        'script',
        'select',
        'small',
        'span',
        'strong',
        'sub',
        'sup',
        'svg',
        'textarea',
        'time',
        'var',
        'video',
        'wbr',
    );

    /**
     * An array of patterns used to match divider tags.
     *
     * Blocks containing only self-closing divider tags are not wrapped in
     * paragraph tags.
     *
     * @var string[]
     * @since 3.7.0
     */
    protected $dividerContent = array(
        'br',
        'hr',
        'img',
    );

    /**
     * An array of patterns used to match unwrappable block tags.
     *
     * Blocks containing any of these unwrappable tags will not be wrapped in
     * paragraphs.
     *
     * @var string[]
     * @since 3.7.0
     */
    protected $blockContent = array(
        'address',
        'article',
        'aside',
        'blockquote',
        'details',
        'div',
        'dl',
        'fieldset',
        'figure',
        'footer',
        'form',
        'h1',
        'h2',
        'h3',
        'h4',
        'h5',
        'h6',
        'header',
        'hgroup',
        'main',
        'menu',
        'nav',
        'ol',
        'p',
        'pre',
        's',
        'section',
        'table',
        'template',
        'ul',
    );

    /**
     * An array of built patterns.
     *
     * @var   array<string, string>|null
     * @since 3.7.0
     */
    protected $patterns = null;

    /**
     * Whether block tags are enabled.
     *
     * @var bool
     * @since 3.6.0
     */
    protected $blockTagsEnabled = true;

    /**
     * Whether lines are wrapped.
     *
     * @var bool
     * @since 3.6.0
     */
    protected $lineWrapEnabled = true;

    /**
     * Whether aligning with class selectors is enabled.
     *
     * @var bool|null
     */
    protected $isAlignClassesEnabled = null;

    /**
     * Pattern for punctation.
     *
     * @var string
     */
    protected $pnct = '[\!"#\$%&\'()\*\+,\-\./:;<=>\?@\[\\\]\^_`{\|}\~]';

    /**
     * Pattern for URL.
     *
     * @var string
     */
    protected $urlch;

    /**
     * Matched marker symbols.
     *
     * @var string
     */
    protected $syms = '¤§µ¶†‡•∗∴◊♠♣♥♦';

    /**
     * HTML rel attribute used for links.
     *
     * @var string
     */
    protected $rel = '';

    /**
     * Array of footnotes.
     *
     * @var array<string|int, string>
     */
    protected $fn = array();

    /**
     * Shelved content.
     *
     * Stores fragments of the source text that have been parsed
     * and require no more processing.
     *
     * @var array<string, string>
     */
    protected $shelf = array();

    /**
     * Restricted mode.
     *
     * @var bool
     */
    protected $restricted = false;

    /**
     * Disallow images.
     *
     * @var bool
     */
    protected $noimage = false;

    /**
     * Lite mode.
     *
     * @var bool
     */
    protected $lite = false;

    /**
     * Accepted link protocols.
     *
     * @var string[]
     */
    protected $url_schemes = array();

    /**
     * Restricted link protocols.
     *
     * @var string[]
     */
    protected $restricted_url_schemes = array(
        'http',
        'https',
        'ftp',
        'mailto',
    );

    /**
     * Unrestricted link protocols.
     *
     * @var string[]
     */
    protected $unrestricted_url_schemes = array(
        'http',
        'https',
        'ftp',
        'mailto',
        'file',
        'tel',
        'callto',
        'sftp',
    );

    /**
     * Span tags.
     *
     * @var array<string, string>
     */
    protected $span_tags = array(
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

    /**
     * Span wrappers.
     *
     * @var array<string, string>
     * @since 3.7.2
     */
    protected $spanWrappers = array(
        '[' => ']',
    );

    /**
     * Patterns for finding glyphs.
     *
     * An array of regex patterns used to find text features
     * such as apostrophes, fractions and em-dashes. Each
     * entry in this array must have a corresponding entry in
     * the $glyph_replace array.
     *
     * @var string[]
     * @see Parser::$glyph_replace
     */
    protected $glyph_search = array();

    /**
     * Glyph replacements.
     *
     * An array of replacements used to insert typographic glyphs
     * into the text. Each entry must have a corresponding entry in
     * the $glyph_search array and may refer to values captured in
     * the corresponding search regex.
     *
     * @var array<int, string|null|bool>
     * @see Parser::$glyph_search
     */
    protected $glyph_replace = array();

    /**
     * Indicates whether glyph substitution is required.
     *
     * Dirty flag, set by Parser::setSymbol(), indicating the parser needs to
     * rebuild the glyph substitutions before the next parse.
     *
     * @var bool
     * @see Parser::setSymbol()
     */
    protected $rebuild_glyphs = true;

    /**
     * Relative image path.
     *
     * @var string
     * @deprecated in 3.7.0
     * @see Parser::$relImagePrefix
     * @see Parser::$relLinkPrefix
     */
    protected $relativeImagePrefix;

    /**
     * Relative link prefix.
     *
     * @var string
     * @since 3.7.0
     */
    protected $relLinkPrefix = '';

    /**
     * Prefix applied to relative images.
     *
     * @var string
     * @since 3.7.0
     */
    protected $relImagePrefix = '';

    /**
     * Maximum nesting level for inline elements.
     *
     * @var int
     */
    protected $max_span_depth = 5;

    /**
     * Server document root.
     *
     * @var string
     */
    protected $doc_root;

    /**
     * Target document type.
     *
     * @var string
     */
    protected $doctype;

    /**
     * An array of supported doctypes.
     *
     * @var string[]
     * @since 3.6.0
     */
    protected $doctypes = array(
        self::DOCTYPE_XHTML,
        self::DOCTYPE_HTML5,
    );

    /**
     * Substitution symbols.
     *
     * Basic symbols used in textile glyph replacements. To override these, call
     * setSymbol method before calling Parser::parse().
     *
     * @var array<string, string|null|bool>
     * @see Parser::setSymbol()
     * @see Parser::parse()
     */
    protected $symbols = array(
        'quote_single_open'  => '&#8216;',
        'quote_single_close' => '&#8217;',
        'quote_double_open'  => '&#8220;',
        'quote_double_close' => '&#8221;',
        'apostrophe'         => '&#8217;',
        'prime'              => '&#8242;',
        'prime_double'       => '&#8243;',
        'ellipsis'           => '&#8230;',
        'emdash'             => '&#8212;',
        'endash'             => '&#8211;',
        'dimension'          => '&#215;',
        'trademark'          => '&#8482;',
        'registered'         => '&#174;',
        'copyright'          => '&#169;',
        'half'               => '&#189;',
        'quarter'            => '&#188;',
        'threequarters'      => '&#190;',
        'degrees'            => '&#176;',
        'plusminus'          => '&#177;',
        'fn_ref_pattern'     => '<sup{atts}>{marker}</sup>',
        'fn_foot_pattern'    => '<sup{atts}>{marker}</sup>',
        'nl_ref_pattern'     => '<sup{atts}>{marker}</sup>',
        'caps'               => '<span class="caps">{content}</span>',
        'acronym'            => null,
    );

    /**
     * Dimensionless images flag.
     *
     * @var bool
     */
    protected $dimensionless_images = false;

    /**
     * Directory separator.
     *
     * @var string
     */
    protected $ds = '/';

    /**
     * Whether mbstring extension is installed.
     *
     * @var bool
     */
    protected $mb;

    /**
     * Multi-byte conversion map.
     *
     * @var int[]
     */
    protected $cmap = array(0x0080, 0xffff, 0, 0xffff);

    /**
     * Stores note index.
     *
     * @var int
     */
    protected $note_index = 1;

    /**
     * Stores unreferenced notes.
     *
     * @var array<int, array<string, array<string, string>|int|string>>
     */
    protected $unreferencedNotes = array();

    /**
     * Stores note lists.
     *
     * @var array<int|string, string>
     */
    protected $notelist_cache = array();

    /**
     * Stores notes.
     *
     * @var array<string, array<string, array<string, string>|int|string>>
     */
    protected $notes = array();

    /**
     * Stores URL references.
     *
     * @var array<int|string, string>
     */
    protected $urlrefs = array();

    /**
     * Stores span depth.
     *
     * @var int
     */
    protected $span_depth = 0;

    /**
     * Unique ID used for reference tokens.
     *
     * @var string
     */
    protected $uid;

    /**
     * Token reference index.
     *
     * @var int
     */
    protected $refIndex = 1;

    /**
     * Stores references values.
     *
     * @var array<int|string, string>
     */
    protected $refCache = array();

    /**
     * Matched open and closed quotes.
     *
     * @var array<string, string>
     */
    protected $quotes = array(
        '"' => '"',
        "'" => "'",
        '(' => ')',
        '{' => '}',
        '[' => ']',
        '«' => '»',
        '»' => '«',
        '‹' => '›',
        '›' => '‹',
        '„' => '“',
        '‚' => '‘',
        '‘' => '’',
        '”' => '“',
    );

    /**
     * Regular expression that matches starting quotes.
     *
     * @var string
     */
    protected $quote_starts;

    /**
     * Ordered list starts.
     *
     * @var array<string|int, int>
     */
    protected $olstarts = array();

    /**
     * Link prefix.
     *
     * @var string
     */
    protected $linkPrefix;

    /**
     * Link index.
     *
     * @var int
     */
    protected $linkIndex = 1;

    /**
     * Constructor.
     *
     * The constructor allows setting options that affect the class instance as
     * a whole, such as the output doctype. To instruct the parser to return
     * HTML5 markup instead of XHTML, set $doctype argument to 'html5'.
     *
     * bc. use Netcarver\Textile\Parser;
     * $parser = new Parser(Parser::DOCTYPE_HTML5);
     * echo $parser->parse('HTML(HyperText Markup Language)");
     *
     * @param string $doctype The output document type, either 'xhtml' or 'html5'
     * @throws \InvalidArgumentException
     * @see Parser::configure()
     * @see Parser::parse()
     * @see Parser::setDocumentType()
     * @see Parser::DOCTYPE_HTML5
     * @see Parser::DOCTYPE_XHTML
     * @api
     */
    public function __construct($doctype = 'xhtml')
    {
        $this->setDocumentType($doctype)->setRestricted(false);
        $uid = uniqid((string) rand());
        $this->uid = 'textileRef:'.$uid.':';
        $this->linkPrefix = $uid.'-';
        $this->a = "(?:$this->hlgn|$this->vlgn)*";
        $this->s = "(?:$this->cspn|$this->rspn)*";
        $this->c = "(?:$this->clas|$this->styl|$this->lnge|$this->hlgn)*";

        $this->cls = '(?:'.
            "$this->clas(?:".
                "$this->lnge(?:$this->styl)?|$this->styl(?:$this->lnge)?".
                ')?|'.
            "$this->lnge(?:".
                "$this->clas(?:$this->styl)?|$this->styl(?:$this->clas)?".
                ')?|'.
            "$this->styl(?:".
                "$this->clas(?:$this->lnge)?|$this->lnge(?:$this->clas)?".
                ')?'.
            ')?';

        if ($this->isUnicodePcreSupported()) {
            $this->regex_snippets = array(
                'acr'   => '\p{Lu}\p{Nd}',
                'abr'   => '\p{Lu}',
                'nab'   => '\p{Ll}',
                'wrd'   => '(?:\p{L}|\p{M}|\p{N}|\p{Pc})',
                'mod'   => 'u', // Make sure to mark the unicode patterns as such, Some servers seem to need this.
                'cur'   => '\p{Sc}',
                'digit' => '\p{N}',
                'space' => '(?:\p{Zs}|\h|\v)',
                'char'  => '(?:[^\p{Zs}\h\v])',
            );
        } else {
            $this->regex_snippets = array(
                'acr'   => 'A-Z0-9',
                'abr'   => 'A-Z',
                'nab'   => 'a-z',
                'wrd'   => '\w',
                'mod'   => '',
                'cur'   => '',
                'digit' => '\d',
                'space' => '(?:\s|\h|\v)',
                'char'  => '\S',
            );
        }

        $this->urlch = '['.$this->regex_snippets['wrd'].'"$\-_.+!*\'(),";\/?:@=&%#{}|\\^~\[\]`]';
        $this->quote_starts = implode('|', array_map('preg_quote', array_keys($this->quotes)));

        if (defined('DIRECTORY_SEPARATOR')) {
            $this->ds = DIRECTORY_SEPARATOR;
        }

        if (php_sapi_name() === 'cli') {
            if (($cwd = getcwd()) !== false) {
                $this->setDocumentRootDirectory($cwd);
            }
        } elseif (!empty($_SERVER['DOCUMENT_ROOT'])) {
            $this->setDocumentRootDirectory($_SERVER['DOCUMENT_ROOT']);
        } elseif (!empty($_SERVER['PATH_TRANSLATED'])) {
            $this->setDocumentRootDirectory($_SERVER['PATH_TRANSLATED']);
        }

        $this->configure();
    }

    /**
     * Configure the current parser.
     *
     * This method can be extended to create a pre-configured parser class.
     *
     * bc.. namespace MyApp;
     *
     * use Netcarver\Textile\Parser;
     *
     * class CommentParser extends Parser
     * {
     *     protected function configure()
     *     {
     *         $this->setImages(false)->setRestricted(true)->setLite(true);
     *     }
     * }
     *
     * @since 3.7.0
     * @return void Return value is ignored
     * @api
     */
    protected function configure()
    {
    }

    /**
     * Sets the output document type.
     *
     * bc. use Netcarver\Textile\Parser;
     * $parser = new Parser();
     * echo $parser
     *     ->setDocumentType(Parser::DOCTYPE_HTML5)
     *     ->parse('HTML(HyperText Markup Language)");
     *
     * @param string $doctype Either 'xhtml' or 'html5'
     * @return Parser This instance
     * @since 3.6.0
     * @see Parser::getDocumentType()
     * @see Parser::DOCTYPE_HTML5
     * @see Parser::DOCTYPE_XHTML
     * @api
     */
    public function setDocumentType($doctype)
    {
        if (in_array($doctype, $this->doctypes, true)) {
            if ($this->getDocumentType() !== $doctype) {
                $this->doctype = $doctype;
                $this->rebuild_glyphs = true;
            }

            return $this;
        }

        throw new \InvalidArgumentException('Invalid doctype given.');
    }

    /**
     * Gets the current output document type.
     *
     * bc. $parser = new \Netcarver\Textile\Parser();
     * echo $parser->getDocumentType();
     *
     * @return string The document type
     * @since 3.6.0
     * @see Parser::setDocumentType()
     * @api
     */
    public function getDocumentType()
    {
        return $this->doctype;
    }

    /**
     * Sets the document root directory path.
     *
     * This method sets the path that is used to resolve relative file paths
     * within local filesystem. This is used to fetch image dimensions, for
     * instance.
     *
     * bc. $parser = new \Netcarver\Textile\Parser();
     * $parser->setDocumentRootDirectory('/path/to/document/root/dir');
     *
     * If not set, document root defaults to the current working directory if
     * PHP-Textile is used via CLI. On server environment, DOCUMENT_ROOT or
     * PATH_TRANSLATED server variable is used based on which ever is available.
     *
     * @param string $path The root path
     * @return Parser This instance
     * @since 3.6.0
     * @see Parser::getDocumentRootDirectory()
     * @api
     */
    public function setDocumentRootDirectory($path)
    {
        $this->doc_root = rtrim($path, '\\/').$this->ds;
        return $this;
    }

    /**
     * Gets the current document root directory path.
     *
     * bc. $parser = new \Netcarver\Textile\Parser();
     * echo $parser->getDocumentRootDirectory();
     *
     * @return string Path to the document root directory
     * @since 3.6.0
     * @see Parser::setDocumentRootDirectory()
     * @api
     */
    public function getDocumentRootDirectory()
    {
        return $this->doc_root;
    }

    /**
     * Enables lite mode.
     *
     * If enabled, allowed tags are limited. Parser will prevent the use extra
     * Textile formatting, accepting only paragraphs and blockquotes as valid
     * block tags.
     *
     * bc. $parser = new \Netcarver\Textile\Parser();
     * $parser
     *     ->setLite(true)
     *     ->parse('h1. Headings are disabled too');
     *
     * Generates:
     *
     * bc. <p>h1. Headings are disabled too</p>
     *
     * This doesn't prevent unsafe input values. If you wish to parse untrusted
     * user-given Textile input, also enable the restricted parser mode with
     * Parser::setRestricted().
     *
     * bc. $parser = new \Netcarver\Textile\Parser();
     * echo $parser
     *     ->setRestricted(true)
     *     ->setLite(true)
     *     ->parse('h1. Hello World!');
     *
     * @param bool $lite TRUE to enable
     * @return Parser This instance
     * @since 3.6.0
     * @see Parser::isLiteModeEnabled()
     * @see Parser::setRestricted()
     * @api
     */
    public function setLite($lite)
    {
        $this->lite = (bool) $lite;
        return $this;
    }

    /**
     * Gets the lite mode status.
     *
     * bc. $parser = new \Netcarver\Textile\Parser();
     * if ($parser->isLiteModeEnabled() === true) {
     *     echo 'Lite mode is enabled.';
     * }
     *
     * @return bool TRUE if enabled, FALSE otherwise
     * @since 3.6.0
     * @see  Parser::setLite()
     * @api
     */
    public function isLiteModeEnabled()
    {
        return (bool) $this->lite;
    }

    /**
     * Disables and enables images.
     *
     * If disabled, image tags are not generated. This option is ideal for
     * minimalist output such as text-only comments.
     *
     * bc. $parser = new \Netcarver\Textile\Parser();
     * echo $parser
     *     ->setImages(true)
     *     ->parse('!image.png!');
     *
     * Generates:
     *
     * bc. <p>!image.png!</p>
     *
     * @param bool $enabled TRUE to enable, FALSE to disable
     * @return Parser This instance
     * @since 3.6.0
     * @see Parser::isImageTagEnabled()
     * @api
     */
    public function setImages($enabled)
    {
        $this->noimage = !$enabled;
        return $this;
    }

    /**
     * Whether images are enabled.
     *
     * bc. $parser = new \Netcarver\Textile\Parser();
     * if ($parser->isImageTagEnabled() === true) {
     *     echo 'Images are enabled.';
     * }
     *
     * @return bool TRUE if enabled, FALSE otherwise
     * @since 3.6.0
     * @see Parser::setImages()
     * @api
     */
    public function isImageTagEnabled()
    {
        return !$this->noimage;
    }

    /**
     * Sets link relationship status value.
     *
     * This method sets the HTML relationship tokens that are applied to links
     * generated by PHP-Textile.
     *
     * bc. $parser = new \Netcarver\Textile\Parser();
     * echo $parser
     *     ->setLinkRelationShip('nofollow')
     *     ->parse('"Link":http://example.com/');
     *
     * Generates:
     *
     * bc. <p><a href="http://example.com/" rel="nofollow">Link</a></p>
     *
     * @param string|array<int, string> $relationship The HTML rel attribute value
     * @return Parser This instance
     * @since 3.6.0
     * @see Parser::getLinkRelationShip()
     * @api
     */
    public function setLinkRelationShip($relationship)
    {
        $this->rel = (string) implode(' ', (array) $relationship);
        return $this;
    }

    /**
     * Gets the link relationship status value.
     *
     * bc. $parser = new \Netcarver\Textile\Parser();
     * echo $parse
     *     ->setLinkRelationShip('nofollow')
     *     ->getLinkRelationShip();
     *
     * The above outputs "nofollow".
     *
     * @return string The value
     * @since 3.6.0
     * @see Parser::setLinkRelationShip()
     * @api
     */
    public function getLinkRelationShip()
    {
        return $this->rel;
    }

    /**
     * Enables restricted parser mode.
     *
     * This option should be enabled when parsing untrusted user input,
     * including comments or forum posts. When enabled, the parser escapes any
     * raw HTML input, ignores unsafe attributes and links only whitelisted URL
     * schemes.
     *
     * For instance the following malicious input:
     *
     * bc. $parser = new \Netcarver\Textile\Parser();
     * echo $parser
     *     ->setRestricted(true)
     *     ->parse('Innocent _looking_ "link":javacript:window.alert().');
     *
     * Returns safe, sanitized HTML with valid Textile input still parsed:
     *
     * bc. <p>Innocent <em>looking</em> &#8220;link&#8221;:javacript:window.alert().</p>
     *
     * If left disabled, the parser allows users to mix raw HTML and Textile.
     * Using the parser in non-restricted on untrusted input, like comments
     * and forum posts, will lead to XSS issues, as users will be able to use
     * any HTML code, JavaScript links and Textile attributes in their input.
     *
     * @param bool $enabled TRUE to enable, FALSE to disable
     * @return Parser This instance
     * @since 3.6.0
     * @see Parser::isRestrictedModeEnabled()
     * @api
     */
    public function setRestricted($enabled)
    {
        if ($enabled) {
            $this->url_schemes = $this->restricted_url_schemes;
            $this->restricted = true;
        } else {
            $this->url_schemes = $this->unrestricted_url_schemes;
            $this->restricted = false;
        }

        return $this;
    }

    /**
     * Whether restricted parser mode is enabled.
     *
     * bc. $parser = new \Netcarver\Textile\Parser();
     * if ($parser->isRestrictedModeEnabled() === true) {
     *     echo 'PHP-Textile is in restricted mode.';
     * }
     *
     * @return bool TRUE if enabled, FALSE otherwise
     * @since 3.6.0
     * @see Parser::setRestricted()
     * @api
     */
    public function isRestrictedModeEnabled()
    {
        return (bool) $this->restricted;
    }

    /**
     * Enables and disables raw blocks.
     *
     * When raw blocks are enabled, any paragraph blocks wrapped in a tag
     * not matching Parser::$blockContent or Parser::$phrasingContent will not
     * be parsed, and instead is left as is.
     *
     * bc. $parser = new \Netcarver\Textile\Parser();
     * echo $parser
     *     ->setRawBlocks(true)
     *     ->parse('<div>A *raw* block.</div>');
     *
     * The above generates:
     *
     * bc. <div>A *raw* block.</div>
     *
     * @param bool $enabled TRUE to enable, FALSE to disable
     * @return Parser This instance
     * @since 3.7.0
     * @see Parser::isRawBlocksEnabled()
     * @see Parser::isRawBlock()
     * @api
     */
    public function setRawBlocks($enabled)
    {
        $this->rawBlocksEnabled = (bool) $enabled;
        return $this;
    }

    /**
     * Whether raw blocks are enabled.
     *
     * bc. $parser = new \Netcarver\Textile\Parser();
     * if ($parser->isRawBlocksEnabled() === true) {
     *     echo 'Raw blocks are enabled';
     * }
     *
     * @return bool TRUE if enabled, FALSE otherwise
     * @since 3.7.0
     * @see Parser::setRawBlocks()
     * @api
     */
    public function isRawBlocksEnabled()
    {
        return (bool) $this->rawBlocksEnabled;
    }

    /**
     * Sets class alignment mode independent of the document type.
     *
     * In HTML5 document type, img elements are generated with align-left,
     * align-center and align-right class selectors rather than align
     * attribute being added to the image.
     *
     * With this option you can enable that functionality in XHTML document type mode too.
     *
     * bc. $parser = new \Netcarver\Textile\Parser();
     * $parser
     *     ->setAlignClasses(true)
     *     ->parse(!<image.png!);
     *
     * Generates:
     *
     * bc. <p><img alt="" class="align-left" src="image.png" /></p>
     *
     * @param bool $enabled TRUE to enable, FALSE to disable
     * @return Parser This instance
     * @since 3.8.0
     * @api
     */
    public function setAlignClasses($enabled)
    {
        $this->isAlignClassesEnabled = (bool) $enabled;
        return $this;
    }

    /**
     * Whether class alignment mode is enabled.
     *
     * bc. $parser = new \Netcarver\Textile\Parser();
     * if ($parser->isAlignClassesEnabled() === true) {
     *     echo 'Images are aligned with class instead of align attribute';
     * }
     *
     * @return bool TRUE if enabled, FALSE otherwise
     * @since 3.8.0
     * @see Parser::setAlignClasses()
     * @api
     */
    public function isAlignClassesEnabled()
    {
        if ($this->isAlignClassesEnabled === null
            && $this->getDocumentType() === self::DOCTYPE_HTML5
        ) {
            return true;
        }

        return (bool) $this->isAlignClassesEnabled;
    }

    /**
     * Enables and disables block-level tags and formatting features.
     *
     * When disabled, block-level tags aren't rendered. This allows PHP-Textile
     * to operate on a single line of text, rather than blocks of text and does
     * not wrap the output in paragraph tags.
     *
     * bc. $parser = new \Netcarving\Textile\Parser();
     * echo $parser
     *     ->setBlockTags(false)
     *     ->parse('h1. Hello *strong* world!');
     *
     * The above generates:
     *
     * bc. h1. Hello <strong>strong</strong> world!
     *
     * @param bool $enabled TRUE to enable, FALSE to disable
     * @return Parser This instance
     * @since 3.6.0
     * @see Parser::isBlockTagEnabled()
     * @api
     */
    public function setBlockTags($enabled)
    {
        $this->blockTagsEnabled = (bool) $enabled;
        return $this;
    }

    /**
     * Whether block-level tags are enabled and parsed.
     *
     * bc. $parser = new \Netcarving\Textile\Parser();
     * if ($parser->isBlockTagAllowed() === true) {
     *     echo 'Block tags are enabled.';
     * }
     *
     * @return bool TRUE if enabled, FALSE otherwise
     * @since 3.6.0
     * @see Parser::setBlockTags()
     * @api
     */
    public function isBlockTagEnabled()
    {
        return (bool) $this->blockTagsEnabled;
    }

    /**
     * Enables and disables line-wrapping.
     *
     * If enabled, line-breaks are replaced by target document's break tag. If
     * disabled, input document's line-breaks are ignored. This setting can be
     * used if the the input document's lines are pre-wrapped. For instance,
     * in case the input is from CLI content, or source code documentation.
     *
     * bc. $parser = new \Netcarving\Textile\Parser();
     * echo $parser
     *     ->setLineWrap(false)
     *     ->parse("Hello\nworld!");
     *
     * The above generates:
     *
     * bc. <p>Hello world!</p>
     *
     * @param bool $enabled TRUE to enable, FALSE to disable
     * @return Parser This instance
     * @since 3.6.0
     * @see Parser::isLineWrapEnabled()
     * @api
     */
    public function setLineWrap($enabled)
    {
        $this->lineWrapEnabled = (bool) $enabled;
        return $this;
    }

    /**
     * Whether line-wrapping is enabled.
     *
     * bc. $parser = new \Netcarving\Textile\Parser();
     * if ($parser->isLineWrapEnabled() === true) {
     *     echo 'Line-wrapping is enabled.';
     * }
     *
     * @return bool TRUE if enabled, FALSE otherwise
     * @see Parser::setLineWrap()
     * @since 3.6.0
     * @api
     */
    public function isLineWrapEnabled()
    {
        return (bool) $this->lineWrapEnabled;
    }

    /**
     * Sets a substitution symbol.
     *
     * This method lets you to redefine a substitution symbol. The following
     * sets the 'half' glyph:
     *
     * bc. $parser = new \Netcarver\Textile\Parser();
     * echo $parser
     *     ->setSymbol('half', '1&#8260;2')
     *     ->parse('Hello [1/2] World!');
     *
     * Generates:
     *
     * bc. <p>Hello 1&#⁄2 World!</p>
     *
     * Symbol can be set to FALSE to disable it:
     *
     * bc. $parser = new \Netcarver\Textile\Parser();
     * $parser->setSymbol('dimension', false);
     *
     * See Parser::getSymbol() to find out all available symbols.
     *
     * @param string  $name  Name of the symbol to assign a new value to
     * @param string|bool $value New value for the symbol, or FALSE to disable
     * @return Parser This instance
     * @see Parser::getSymbol()
     * @api
     */
    public function setSymbol($name, $value)
    {
        if ($value !== false) {
            $value = (string) $value;
        }

        $this->symbols[(string) $name] = $value;
        $this->rebuild_glyphs = true;
        return $this;
    }

    /**
     * Gets a symbol definitions.
     *
     * This method gets a symbol definition by name, or the full symbol table
     * as an array.
     *
     * bc. $parser = new \Netcarver\Textile\Parser();
     * echo $parser->getSymbol('dimension');
     *
     * To get all available symbol definitions:
     *
     * bc. $parser = new \Netcarver\Textile\Parser();
     * print_r($parser->getSymbol());
     *
     * @param string|null $name The name of the symbol, or NULL if requesting the symbol table
     * @return array<string, string|null|bool>|string|bool The symbol table or the requested symbol
     * @throws \InvalidArgumentException
     * @see Parser::setSymbol()
     * @api
     */
    public function getSymbol($name = null)
    {
        if ($name !== null) {
            if (isset($this->symbols[$name])) {
                return $this->symbols[$name];
            }

            throw new \InvalidArgumentException('The specified name does not match any symbols.');
        }

        return $this->symbols;
    }

    /**
     * Sets base relative image prefix.
     *
     * The given string is used to prefix relative image paths, usually an
     * absolute HTTP address pointing a the site's image, or upload, directory.
     * PHP-Textile to convert relative paths to absolute, or prefixed paths.
     *
     * bc. $parser = new \Netcarver\Textile\Parser();
     * $parser->setImagePrefix('https://static.example.com/images/');
     *
     * @param string $prefix The prefix
     * @return Parser This instance
     * @since 3.7.0
     * @see Parser::getImagePrefix()
     * @api
     */
    public function setImagePrefix($prefix)
    {
        $this->relImagePrefix = (string) $prefix;
        return $this;
    }

    /**
     * Gets base relative image prefix.
     *
     * bc. $parser = new \Netcarver\Textile\Parser();
     * echo $parser->getImagePrefix();
     *
     * @return string The prefix
     * @since 3.7.0
     * @see Parser::setImagePrefix()
     * @api
     */
    public function getImagePrefix()
    {
        return (string) $this->relImagePrefix;
    }

    /**
     * Sets base relative link prefix.
     *
     * The given string is used to prefix relative link paths. This allows
     * PHP-Textile convert relative paths to absolute, or prefixed, links.
     *
     * bc. $parser = new \Netcarver\Textile\Parser();
     * $parser->setLinkPrefix('https://example.com/');
     *
     * @param string $prefix The prefix
     * @return Parser This instance
     * @since 3.7.0
     * @see Parser::getLinkPrefix()
     * @api
     */
    public function setLinkPrefix($prefix)
    {
        $this->relLinkPrefix = (string) $prefix;
        return $this;
    }

    /**
     * Gets base relative link prefix.
     *
     * bc. $parser = new \Netcarver\Textile\Parser();
     * echo $parser->getLinkPrefix();
     *
     * @return string The prefix
     * @since 3.7.0
     * @see Parser::setLinkPrefix()
     * @api
     */
    public function getLinkPrefix()
    {
        return (string) $this->relLinkPrefix;
    }

    /**
     * Sets base relative image and link directory path.
     *
     * This is used when Textile is supplied with a relative image or link path.
     * Allows client systems to have PHP-Textile convert relative paths to
     * absolute or prefixed paths. This method is used to set that base path,
     * usually an absolute HTTP address pointing to a directory. Note that
     * despite its name it applies to both links and images.
     *
     * bc. $parser = new \Netcarver\Textile\Parser();
     * $parser->setRelativeImagePrefix('https://example.com/');
     *
     * @param  string $prefix The string to prefix all relative image paths with
     * @return Parser This instance
     * @deprecated in 3.7.0
     * @see Parser::setImagePrefix
     * @see Parser::setLinkPrefix
     * @api
     */
    public function setRelativeImagePrefix($prefix = '')
    {
        trigger_error(
            'Parser::setRelativeImagePrefix() is deprecated.'.
            'Use Parser::setImagePrefix() and Parser::setLinkPrefix() instead.',
            E_USER_DEPRECATED
        );

        $this->relativeImagePrefix = $prefix;
        return $this;
    }

    /**
     * Enables dimensionless images.
     *
     * If enabled, image width and height attributes will not be included in
     * rendered image tags. Normally, PHP-Textile will add width and height
     * to images linked with a local relative path, as long as the image file
     * can be accessed.
     *
     * bc. $parser = new \Netcarver\Textile\Parser();
     * echo $parser
     *     ->setDimensionlessImages(true)
     *     ->parse('!image.jpg!');
     *
     * @param bool $dimensionless TRUE to disable image dimensions, FALSE to enable
     * @return Parser This instance
     * @see Parser::getDimensionlessImages()
     * @api
     */
    public function setDimensionlessImages($dimensionless = true)
    {
        $this->dimensionless_images = (bool) $dimensionless;
        return $this;
    }

    /**
     * Whether dimensionless images are enabled.
     *
     * bc. $parser = new \Netcarver\Textile\Parser();
     * if ($parser->getDimensionlessImages() === true) {
     *     echo 'Images do not get dimensions.';
     * }
     *
     * @return bool TRUE if images will not get dimensions, FALSE otherwise
     * @see Parser::setDimensionlessImages()
     * @api
     */
    public function getDimensionlessImages()
    {
        return (bool) $this->dimensionless_images;
    }

    /**
     * Gets PHP-Textile version number.
     *
     * bc. $parser = new \Netcarver\Textile\Parser();
     * echo $parser->getVersion();
     *
     * @return string Version number
     * @api
     */
    public function getVersion()
    {
        return $this->ver;
    }

    /**
     * Encodes the given text.
     *
     * bc. $parser = new \Netcarver\Textile\Parser();
     * $parser->textileEncode('Some content to encode.');
     *
     * @param string $text The text to be encoded
     * @return string The encoded text
     * @api
     */
    public function textileEncode($text)
    {
        return (string)preg_replace('/&(?!(?:[a-z][a-z\d]*|#(?:\d+|x[a-f\d]+));)/i', '&amp;', $text);
    }

    /**
     * Parses the given Textile input according to the previously set options.
     *
     * The parser's features can be changed by using the various public setter
     * methods this class has. The most basic use case is:
     *
     * bc. $parser = new \Netcarver\Textile\Parser();
     * echo $parser->parse('h1. Hello World!');
     *
     * The above parses trusted input in full-feature mode, generating:
     *
     * bc. <h1>Hello World!</h1>
     *
     * Additionally the parser can be run in safe, restricted mode using the
     * Parser::setRestricted() method.
     *
     * bc. $parser = new \Netcarver\Textile\Parser();
     * echo $parser
     *     ->setRestricted(true)
     *     ->parse('h1. Hello World!');
     *
     * This enables restricted mode and allows safe parsing of untrusted input.
     * PHP-Textile will disable unsafe attributes, links and escapes any raw
     * HTML input. This option should be enabled when parsing untrusted user
     * input.
     *
     * If restricted mode is disabled, the parser allows users to mix raw HTML
     * and Textile.
     *
     * @param string $text The Textile input to parse
     * @return string Parsed Textile input
     * @since 3.6.0
     * @api
     */
    public function parse($text)
    {
        $this->prepare();
        $text = (string) $text;

        if ($this->isRestrictedModeEnabled()) {
            // Escape any raw HTML.
            $text = $this->encodeHTML($text, false);
        }

        $text = $this->cleanWhiteSpace($text);
        $text = $this->cleanUniqueTokens($text);

        if ($this->isBlockTagEnabled()) {
            if ($this->isLiteModeEnabled()) {
                $this->blocktag_whitelist = array('bq', 'p');
                $text = $this->blocks($text."\n\n");
            } else {
                $this->blocktag_whitelist = array(
                    'bq',
                    'p',
                    'bc',
                    'notextile',
                    'pre',
                    'h[1-6]',
                    'fn'.$this->regex_snippets['digit'].'+',
                    '###',
                );
                $text = $this->blocks($text);
                $text = $this->placeNoteLists($text);
            }
        } else {
            $text .= "\n\n";

            // Treat quoted quote as a special glyph.
            $text = $this->glyphQuotedQuote($text);

            // Inline markup (em, strong, sup, sub, del etc).
            $text = $this->spans($text);

            // Glyph level substitutions (mainly typographic -- " & ' => curly quotes, -- => em-dash etc.
            $text = $this->glyphs($text);
        }

        $text = $this->retrieve($text);
        $text = $this->replaceGlyphs($text);
        $text = $this->retrieveTags($text);
        $text = $this->retrieveURLs($text);

        $text = str_replace($this->getLineBreak(), $this->getLineBreak()."\n", $text);

        return $text;
    }

    /**
     * Parses the given Textile input in un-restricted mode.
     *
     * This method is deprecated, use Parser::parse() method instead.
     * This method is equivalent of:
     *
     * bc. $parser = new \Netcarver\Textile\Parser();
     * echo $parser->parse('h1. Hello World!');
     *
     * Additional arguments can be passed with setter methods:
     *
     * bc. $parser = new \Netcarver\Textile\Parser();
     * echo $parser
     *     ->setLite(true)
     *     ->setImages(true)
     *     ->setLinkRelationShip('nofollow')
     *     ->parse('h1. Hello World!');
     *
     * @param string $text The Textile input to parse
     * @param bool $lite Switch to lite mode
     * @param bool $encode Encode input and return
     * @param bool $noimage Disables images
     * @param bool $strict This argument is ignored
     * @param string $rel Relationship attribute applied to generated links
     * @return string Parsed $text
     * @see Parser::parse()
     * @deprecated in 3.6.0
     * @api
     */
    public function textileThis($text, $lite = false, $encode = false, $noimage = false, $strict = false, $rel = '')
    {
        if ($encode) {
            trigger_error(
                '$encode argument is deprecated. Use Parser::textileEncode() instead.',
                \E_USER_DEPRECATED
            );

            return $this->textileEncode($text);
        }

        trigger_error(
            'Parser::textileThis() is deprecated. Use Parser::parse() instead.',
            \E_USER_DEPRECATED
        );

        return $this
            ->setRestricted(false)
            ->setLite($lite)
            ->setBlockTags(true)
            ->setImages(!$noimage)
            ->setLinkRelationShip($rel)
            ->parse($text);
    }

    /**
     * Parses the given Textile input in restricted mode.
     *
     * This method is deprecated, use Parser::parse() method with
     * Parser::setRestricted() and Parser::setLite() enabled, and
     * Parser::setImages() disabled.
     *
     * This method's defaults are identical to:
     *
     * bc. $parser = new \Netcarver\Textile\Parser();
     * echo $parser
     *     ->setRestricted(true)
     *     ->setLite(true)
     *     ->setImages(false)
     *     ->setLinkRelationShip('nofollow')
     *     ->parse('h1. Hello World!');
     *
     * As in the above, restricted mode should be used when parsing any
     * untrusted user input, including comments or forum posts.
     *
     * @param string $text The Textile input to parse
     * @param bool $lite Controls lite mode, allowing extra formatting
     * @param bool $noimage Allow images
     * @param string $rel Relationship attribute applied to generated links
     * @return string Parsed input
     * @see Parser::setRestricted()
     * @see Parser::setLite()
     * @see Parser::setImages()
     * @see Parser::setLinkRelationShip()
     * @see Parser::parse()
     * @deprecated in 3.6.0
     * @api
     */
    public function textileRestricted($text, $lite = true, $noimage = true, $rel = 'nofollow')
    {
        trigger_error(
            'Parser::textileRestricted() is deprecated. Use Parser::parse() with Parser::setRestricted() instead.',
            E_USER_DEPRECATED
        );

        return $this
            ->setRestricted(true)
            ->setLite($lite)
            ->setBlockTags(true)
            ->setImages(!$noimage)
            ->setLinkRelationShip($rel)
            ->parse($text);
    }

    /**
     * Parses Textile syntax.
     *
     * This method performs common parse actions.
     *
     * @param  string $text The input to parse
     * @param  bool   $lite Enables lite mode
     * @return string Parsed input
     * @deprecated in 3.6.0
     */
    protected function textileCommon($text, $lite)
    {
        trigger_error('Parser::textileCommon() is deprecated.', E_USER_DEPRECATED);
        return $this->setLite($lite)->parse($text);
    }

    /**
     * Output line break according to document type.
     *
     * @return string The break tag
     * @since  4.0.0
     * @see    Parser::getDocumentType()
     */
    protected function getLineBreak()
    {
        return ($this->getDocumentType() === self::DOCTYPE_HTML5) ? '<br>' : '<br />';
    }

    /**
     * Prepares the glyph patterns from the symbol table.
     *
     * @return void
     * @see Parser::setSymbol()
     * @see Parser::getSymbol()
     */
    protected function prepGlyphs()
    {
        if ($this->rebuild_glyphs === false) {
            return;
        }

        $pnc = '[[:punct:]]';
        $cur = '';

        if ($this->regex_snippets['cur']) {
            $cur = '(?:['.$this->regex_snippets['cur'].']'.$this->regex_snippets['space'].'*)?';
        }

        $this->glyph_search = array();
        $this->glyph_replace = array();

        // Dimension sign
        if ($this->symbols['dimension'] !== false) {
            $this->glyph_search[] = '/(?<=\b|x)([0-9]++[\])]?[\'"]? ?)[x]( ?[\[(]?)(?=[+-]?'.$cur.'[0-9]*\.?[0-9]++)/i'.
            $this->regex_snippets['mod'];
            $this->glyph_replace[] = '$1'.$this->symbols['dimension'].'$2';
        }

        // Apostrophe
        if ($this->symbols['apostrophe'] !== false) {
            $this->glyph_search[] = '/('.$this->regex_snippets['wrd'].'|\))\''.
            '('.$this->regex_snippets['wrd'].')/'.$this->regex_snippets['mod'];
            $this->glyph_replace[] = '$1'.$this->symbols['apostrophe'].'$2';

            // Back in '88/the '90s but not in his '90s', '1', '1.' '10m' or '5.png'
            $this->glyph_search[] = '/('.$this->regex_snippets['space'].')\''.
            '(\d+'.$this->regex_snippets['wrd'].'?)\b(?![.]?['.$this->regex_snippets['wrd'].']*?\')/'.
            $this->regex_snippets['mod'];
            $this->glyph_replace[] = '$1'.$this->symbols['apostrophe'].'$2';
        }

        // Single open following open bracket
        if ($this->symbols['quote_single_open'] !== false) {
            $this->glyph_search[] = "/([([{])'(?=\S)/".$this->regex_snippets['mod'];
            $this->glyph_replace[] = '$1'.$this->symbols['quote_single_open'];
        }

        // Single closing
        if ($this->symbols['quote_single_close'] !== false) {
            $this->glyph_search[] = '/(\S)\'(?='.$this->regex_snippets['space'].'|'.$pnc.'|<|$)/'.
                $this->regex_snippets['mod'];
            $this->glyph_replace[] = '$1'.$this->symbols['quote_single_close'];
        }

        // Default single opening
        if ($this->symbols['quote_single_open'] !== false) {
            $this->glyph_search[] = "/'/";
            $this->glyph_replace[] = $this->symbols['quote_single_open'];
        }

        // Double open following an open bracket. Allows things like Hello ["(Mum) & dad"]
        if ($this->symbols['quote_double_open'] !== false) {
            $this->glyph_search[] = '/([([{])"(?=\S)/'.$this->regex_snippets['mod'];
            $this->glyph_replace[] = '$1'.$this->symbols['quote_double_open'];
        }

        // Double closing
        if ($this->symbols['quote_double_close'] !== false) {
            $this->glyph_search[] = '/(\S)"(?='.$this->regex_snippets['space'].'|'.$pnc.'|<|$)/'.
                $this->regex_snippets['mod'];
            $this->glyph_replace[] = '$1'.$this->symbols['quote_double_close'];
        }

        // Default double opening
        if ($this->symbols['quote_double_open'] !== false) {
            $this->glyph_search[] = '/"/';
            $this->glyph_replace[] = $this->symbols['quote_double_open'];
        }

        if ($this->symbols['acronym'] === null) {
            if ($this->getDocumentType() === 'html5') {
                $acronym = '<abbr title="{title}">{content}</abbr>';
            } else {
                $acronym = '<acronym title="{title}">{content}</acronym>';
            }
        } else {
            $acronym = $this->symbols['acronym'];
        }

        // 3+ uppercase acronym
        if ($acronym !== false) {
            $this->glyph_search[] = '/\b(['.$this->regex_snippets['abr'].']['.
                $this->regex_snippets['acr'].']{2,})\b(?:[(]([^)]*)[)])/'.$this->regex_snippets['mod'];
            $this->glyph_replace[] = $this->replaceMarkers((string) $acronym, array(
                'title' => '$2',
                'content' => '$1',
            ));
        }

        // 3+ uppercase
        if ($this->symbols['caps'] !== false) {
            $this->glyph_search[] = '/('.$this->regex_snippets['space'].'|^|[>(;-])'.
                '(['.$this->regex_snippets['abr'].']{3,})'.
                '(['.$this->regex_snippets['nab'].']*)(?='.
                $this->regex_snippets['space'].'|'.$pnc.'|<|$)'.
                '(?=[^">]*?(<|$))/'.$this->regex_snippets['mod'];
            $this->glyph_replace[] = $this->replaceMarkers('$1'.$this->symbols['caps'].'$3', array(
                'content' => $this->uid.':glyph:$2',
            ));
        }

        // Ellipsis
        if ($this->symbols['ellipsis'] !== false) {
            $this->glyph_search[] = '/([^.]?)\.{3}/';
            $this->glyph_replace[] = '$1'.$this->symbols['ellipsis'];
        }

        // em dash
        if ($this->symbols['emdash'] !== false) {
            $this->glyph_search[] = '/--/';
            $this->glyph_replace[] = $this->symbols['emdash'];
        }

        // en dash
        if ($this->symbols['endash'] !== false) {
            $this->glyph_search[] = '/ - /';
            $this->glyph_replace[] = ' '.$this->symbols['endash'].' ';
        }

        // Trademark
        if ($this->symbols['trademark'] !== false) {
            $this->glyph_search[] = '/(\b ?|'.$this->regex_snippets['space'].'|^)[([]TM[])]/i'.
                $this->regex_snippets['mod'];
            $this->glyph_replace[] = '$1'.$this->symbols['trademark'];
        }

        // Registered
        if ($this->symbols['registered'] !== false) {
            $this->glyph_search[] = '/(\b ?|'.$this->regex_snippets['space'].'|^)[([]R[])]/i'.
                $this->regex_snippets['mod'];
            $this->glyph_replace[] = '$1'.$this->symbols['registered'];
        }

        // Copyright
        if ($this->symbols['copyright'] !== false) {
            $this->glyph_search[] = '/(\b ?|'.$this->regex_snippets['space'].'|^)[([]C[])]/i'.
                $this->regex_snippets['mod'];
            $this->glyph_replace[] = '$1'.$this->symbols['copyright'];
        }

        // 1/4
        if ($this->symbols['quarter'] !== false) {
            $this->glyph_search[] = '/[([]1\/4[])]/';
            $this->glyph_replace[] = $this->symbols['quarter'];
        }

        // 1/2
        if ($this->symbols['half'] !== false) {
            $this->glyph_search[] = '/[([]1\/2[])]/';
            $this->glyph_replace[] = $this->symbols['half'];
        }

        // 3/4
        if ($this->symbols['threequarters'] !== false) {
            $this->glyph_search[] = '/[([]3\/4[])]/';
            $this->glyph_replace[] = $this->symbols['threequarters'];
        }

        // Degrees -- that's a small 'oh'
        if ($this->symbols['degrees'] !== false) {
            $this->glyph_search[] = '/[([]o[])]/';
            $this->glyph_replace[] = $this->symbols['degrees'];
        }

        // Plus minus
        if ($this->symbols['plusminus'] !== false) {
            $this->glyph_search[] = '/[([]\+\/-[])]/';
            $this->glyph_replace[] = $this->symbols['plusminus'];
        }

        // No need to rebuild next run unless a symbol is redefined
        $this->rebuild_glyphs = false;
    }

    /**
     * Gets the maximum allowed link index.
     *
     * @return int Maximum link index
     * @since  3.5.5
     */
    protected function getMaxLinkIndex()
    {
        return 1000000;
    }

    /**
     * Prepares the parser for parsing.
     *
     * This method prepares the transient internal state of
     * Textile parser in preparation for parsing a new document.
     *
     * @param bool|null $lite Controls lite mode
     * @param bool|null $noimage Disallow images
     * @param string|null $rel A relationship attribute applied to links
     * @return void
     */
    protected function prepare($lite = null, $noimage = null, $rel = null)
    {
        if ($this->linkIndex >= $this->getMaxLinkIndex()) {
            $this->linkPrefix .= '-';
            $this->linkIndex = 1;
        }

        $this->unreferencedNotes = array();
        $this->notelist_cache = array();
        $this->notes = array();
        $this->urlrefs = array();
        $this->shelf = array();
        $this->fn = array();
        $this->span_depth = 0;
        $this->refIndex = 1;
        $this->refCache = array();
        $this->note_index = 1;

        if ($lite !== null) {
            trigger_error(
                '$lite argument is deprecated. Use Parser::setLite() instead.',
                E_USER_DEPRECATED
            );

            $this->setLite($lite);
        }

        if ($noimage !== null) {
            trigger_error(
                '$noimage argument is deprecated. Use Parser::setImages() instead.',
                E_USER_DEPRECATED
            );

            $this->setImages(!$noimage);
        }

        if ($rel !== null) {
            trigger_error(
                '$rel argument is deprecated. Use Parser::setRelative() instead.',
                E_USER_DEPRECATED
            );

            $this->setLinkRelationShip($rel);
        }

        if ($this->patterns === null) {
            $block = implode('|', $this->blockContent);
            $divider = implode('|', $this->dividerContent);
            $phrasing = implode('|', $this->phrasingContent);

            $this->patterns = array(
                'block' => '/^(?:'.$block.')$/i',
                'contained' => '/^<\/?(?P<open>[^\s<>\/]+)(?:\s.*|\/?>.*|)>$/si',
                'divider' => '/^(?:<\/?('.$divider.')(?:\s[^<>]*?|\/?)>(?:<\/\1\s*?>)?)+$/si',
                'phrasing' => '/^(?:'.$phrasing.')$/i',
                'wrapped' => '/^<\/?(?P<open>[^\s<>\/]+)[^<>]*?>(?:.*<\/\1\s*?>)?$/si',
                'unwrappable' => '/<\/?(?:'.$block.')(?:\s[^<>]*?|\/?)>/si',
            );
        }

        $this->prepGlyphs();
    }

    /**
     * Cleans a HTML attribute value.
     *
     * This method checks for presence of URL encoding in the value.
     * If the number encoded characters exceeds the threshold,
     * the input is discarded. Otherwise the encoded
     * instances are decoded.
     *
     * This method also strips any ", ' and = characters
     * from the given value. This method does not guarantee
     * valid HTML or full sanitization.
     *
     * @param  string $in The input string
     * @return string Cleaned string
     */
    protected function cleanAttribs($in)
    {
        $tmp = $in;
        $before = -1;
        $after = 0;
        $max = 3;
        $i = 0;

        while (($after != $before) && ($i < $max)) {
            $before = strlen($tmp);
            $tmp = rawurldecode($tmp);
            $after = strlen($tmp);
            $i++;
        }

        if ($i === $max) {
            // If we hit the max allowed decodes, assume the input is tainted and consume it.
            $out = '';
        } else {
            $out = str_replace(array('"', "'", '='), '', $tmp);
        }

        return $out;
    }

    /**
     * Constructs a HTML tag from an object.
     *
     * This is a helper method that creates a new
     * instance of \Netcarver\Textile\Tag.
     *
     * @param string $name The HTML element name
     * @param array<string, int|string> $atts HTML attributes applied to the tag
     * @param bool $selfclosing Determines if the tag should be self-closing
     * @return Tag
     */
    protected function newTag($name, $atts, $selfclosing = true)
    {
        return new Tag($name, $atts, $selfclosing && $this->getDocumentType() !== self::DOCTYPE_HTML5);
    }

    /**
     * Parses Textile attributes.
     *
     * @param  string $in         The Textile attribute string to be parsed
     * @param  string $element    Focus the routine to interpret the attributes as applying to a specific HTML tag
     * @param  bool   $include_id If FALSE, IDs are not included in the attribute list
     * @param  string $autoclass  An additional classes applied to the output
     * @return string HTML attribute list
     * @see    Parser::parseAttribsToArray()
     */
    protected function parseAttribs($in, $element = '', $include_id = true, $autoclass = '')
    {
        $o = $this->parseAttribsToArray($in, $element, $include_id, $autoclass);

        return $this->formatAttributeString($o);
    }

    /**
     * Converts an array of named attribute => value mappings to a string.
     *
     * @param array<string, string|int> $attribute_array
     * @return string
     */
    protected function formatAttributeString(array $attribute_array)
    {
        $out = '';

        if (count($attribute_array)) {
            foreach ($attribute_array as $k => $v) {
                $out .= " $k=\"$v\"";
            }
        }

        return $out;
    }

    /**
     * Parses Textile attributes into an array.
     *
     * @param string $in The Textile attribute string to be parsed
     * @param string $element Focus the routine to interpret the attributes as applying to a specific HTML tag
     * @param bool $include_id If FALSE, IDs are not included in the attribute list
     * @param string $autoclass An additional classes applied to the output
     * @return array<string, string> HTML attributes as key => value mappings
     * @see Parser::parseAttribs()
     */
    protected function parseAttribsToArray($in, $element = '', $include_id = true, $autoclass = '')
    {
        $style = array();
        $class = '';
        $lang = '';
        $colspan = '';
        $rowspan = '';
        $span = '';
        $width = '';
        $id = '';
        $matched = $in;

        if ($element == 'td') {
            if (preg_match("/\\\\([0-9]+)/", $matched, $csp)) {
                $colspan = $csp[1];
            }

            if (preg_match("/\/([0-9]+)/", $matched, $rsp)) {
                $rowspan = $rsp[1];
            }
        }

        if (($element === 'td' || $element === 'tr')
            && preg_match("/^($this->vlgn)/", $matched, $vert)
        ) {
            $style[] = "vertical-align:" . $this->vAlign($vert[1]);
        }

        if (preg_match("/\{([^}]*)\}/", $matched, $sty)) {
            if ($sty[1] = $this->cleanAttribs($sty[1])) {
                $style[] = rtrim($sty[1], ';');
            }

            $matched = str_replace($sty[0], '', $matched);
        }

        if (preg_match("/\(([^()]+)\)/U", $matched, $cls)) {
            $class_regex = "/^([-a-zA-Z 0-9_\.\/\[\]]*)$/";

            // Consume entire class block -- valid or invalid.
            $matched = str_replace($cls[0], '', $matched);

            // Only allow a restricted subset of the CSS standard characters for classes/ids.
            // No encoding markers allowed.
            if (preg_match("/\(([-a-zA-Z 0-9_\/\[\]\.\:\#]+)\)/U", $cls[0], $cls)) {
                $hashpos = strpos($cls[1], '#');
                // If a textile class block attribute was found with a '#' in it
                // split it into the css class and css id...
                if (false !== $hashpos) {
                    if (preg_match("/#([-a-zA-Z0-9_\.\:]*)$/", substr($cls[1], $hashpos), $ids)) {
                        $id = $ids[1];
                    }

                    if (preg_match($class_regex, substr($cls[1], 0, $hashpos), $ids)) {
                        $class = $ids[1];
                    }
                } else {
                    if (preg_match($class_regex, $cls[1], $ids)) {
                        $class = $ids[1];
                    }
                }
            }
        }

        if (preg_match("/\[([^]]+)\]/U", $matched, $lng)) {
            // Consume entire lang block -- valid or invalid.
            $matched = str_replace($lng[0], '', $matched);
            if ($element === 'code' && preg_match("/\[([a-zA-Z0-9_-]+)\]/U", $lng[0], $lng1)) {
                $lang = $lng1[1];
            } elseif (preg_match("/\[([a-zA-Z]{2}(?:[\-\_][a-zA-Z]{2})?)\]/U", $lng[0], $lng2)) {
                $lang = $lng2[1];
            }
        }

        if (preg_match("/(\(+)/", $matched, $pl)) {
            $style[] = "padding-left:" . strlen($pl[1]) . "em";
            $matched = str_replace($pl[0], '', $matched);
        }

        if (preg_match("/(\)+)/", $matched, $pr)) {
            $style[] = "padding-right:" . strlen($pr[1]) . "em";
            $matched = str_replace($pr[0], '', $matched);
        }

        if (preg_match("/($this->hlgn)/", $matched, $horiz)) {
            $style[] = "text-align:" . $this->hAlign($horiz[1]);
        }

        if ($element == 'col'
            && preg_match("/(?:\\\\([0-9]+))?{$this->regex_snippets['space']}*([0-9]+)?/", $matched, $csp)
        ) {
            $span = isset($csp[1]) ? $csp[1] : '';
            $width = isset($csp[2]) ? $csp[2] : '';
        }

        if ($this->isRestrictedModeEnabled()) {
            $o = array();
            $class = trim($autoclass);

            if ($class) {
                $o['class'] = $this->cleanAttribs($class);
            }

            if ($lang) {
                $o['lang'] = $this->cleanAttribs($lang);
            }

            ksort($o);
            return $o;
        } else {
            $class = trim($class . ' ' . $autoclass);
        }

        $o = array();

        if ($class) {
            $o['class'] = $this->cleanAttribs($class);
        }

        if ($colspan) {
            $o['colspan'] = $this->cleanAttribs($colspan);
        }

        if ($id && $include_id) {
            $o['id'] = $this->cleanAttribs($id);
        }

        if ($lang) {
            $o['lang'] = $this->cleanAttribs($lang);
        }

        if ($rowspan) {
            $o['rowspan'] = $this->cleanAttribs($rowspan);
        }

        if ($span) {
            $o['span'] = $this->cleanAttribs($span);
        }

        if (!empty($style)) {
            $so = '';
            $tmps = array();

            foreach ($style as $s) {
                $parts = explode(';', $s);

                foreach ($parts as $p) {
                    if ($p = trim(trim($p), ":")) {
                        $tmps[] = $p;
                    }
                }
            }

            sort($tmps);

            foreach ($tmps as $p) {
                // @phpstan-ignore-next-line
                if ($p) {
                    $so .= $p.';';
                }
            }

            $o['style'] = trim(str_replace(array("\n", ';;'), array('', ';'), $so));
        }

        if ($width) {
            $o['width'] = $this->cleanAttribs($width);
        }

        ksort($o);
        return $o;
    }

    /**
     * Checks whether the text block should be wrapped in a paragraph.
     *
     * @param  string $text The input string
     * @return bool   TRUE if the text can be wrapped, FALSE otherwise
     */
    protected function hasRawText($text)
    {
        if (preg_match($this->patterns['unwrappable'], $text)) {
            return false;
        }

        if (preg_match($this->patterns['divider'], $text)) {
            return false;
        }

        if (preg_match($this->patterns['wrapped'], $text, $m)) {
            if (preg_match($this->patterns['phrasing'], $m['open'])) {
                return true;
            }

            return false;
        }

        return true;
    }

    /**
     * Parses textile table structures into HTML.
     *
     * @param  string $text The textile input
     * @return string The parsed text
     */
    protected function tables($text)
    {
        $text = $text . "\n\n";
        return (string)preg_replace_callback(
            "/^(?:table(?P<tatts>_?{$this->s}{$this->a}{$this->cls})\.".
            "(?P<summary>.*)?\n)?^(?P<rows>{$this->a}{$this->cls}\.? ?\|.*\|){$this->regex_snippets['space']}*\n\n/smU",
            array($this, "fTable"),
            $text
        );
    }

    /**
     * Constructs an HTML table from a textile table structure.
     *
     * This method is used by Parser::tables() to process
     * found table structures.
     *
     * @param array<int|string, string> $matches
     * @return string HTML table
     * @see Parser::tables()
     */
    protected function fTable($matches)
    {
        $tatts = $this->parseAttribs($matches['tatts'], 'table');
        $space = $this->regex_snippets['space'];

        $cap = '';
        $colgrp = '';
        $last_rgrp = '';
        $c_row = 1;
        $sum = '';
        $rows = array();

        $summary = trim($matches['summary']);

        if ($summary !== '') {
            $sum = ' summary="'.htmlspecialchars($summary, ENT_QUOTES, 'UTF-8').'"';
        }

        /** @var array<int, string> $components */
        $components = preg_split("/\|{$space}*?$/m", $matches['rows'], -1, PREG_SPLIT_NO_EMPTY);

        foreach ($components as $row) {
            $row = ltrim($row);

            // Caption -- can only occur on row 1, otherwise treat '|=. foo |...'
            // as a normal center-aligned cell.
            if (($c_row <= 1) && preg_match(
                "/^\|\=(?P<capts>$this->s$this->a$this->cls)\. (?P<cap>[^\n]*)(?P<row>.*)/s",
                ltrim($row),
                $cmtch
            )) {
                $capts = $this->parseAttribs($cmtch['capts']);
                $cap = "\t<caption".$capts.">".trim($cmtch['cap'])."</caption>\n";
                $row = ltrim($cmtch['row']);
                if (!$row) {
                    continue;
                }
            }

            $c_row += 1;

            // Colgroup
            if (preg_match("/^\|:(?P<cols>$this->s$this->a$this->cls\. .*)/m", ltrim($row), $gmtch)) {
                // Is this colgroup def missing a closing pipe? If so, there
                // will be a newline in the middle of $row somewhere.
                $nl = strpos($row, "\n");
                $idx = 0;

                foreach (explode('|', str_replace('.', '', $gmtch['cols'])) as $col) {
                    $gatts = $this->parseAttribs(trim($col), 'col');
                    $colgrp .= "\t<col".(($idx==0) ? "group".$gatts.">" : $gatts." />")."\n";
                    $idx++;
                }

                $colgrp .= "\t</colgroup>\n";

                if ($nl === false) {
                    continue;
                } else {
                    // Recover from our missing pipe and process the rest of the line.
                    $row = ltrim(substr($row, $nl));
                }
            }

            // Row group
            $rgrpatts = $rgrp = '';

            if (preg_match(
                "/(:?^\|(?P<part>$this->vlgn)(?P<rgrpatts>$this->s$this->a$this->cls)\.{$space}*$\n)?^(?P<row>.*)/sm",
                ltrim($row),
                $grpmatch
            )) {
                if (isset($grpmatch['part'])) {
                    if ($grpmatch['part'] === '^') {
                        $rgrp = 'head';
                    } elseif ($grpmatch['part'] === '~') {
                        $rgrp = 'foot';
                    } elseif ($grpmatch['part'] === '-') {
                        $rgrp = 'body';
                    }
                }

                if (isset($grpmatch['part'])) {
                    $rgrpatts = $this->parseAttribs($grpmatch['rgrpatts']);
                }

                if (isset($grpmatch['row'])) {
                    $row = $grpmatch['row'];
                }
            }

            if (preg_match("/^(?P<ratts>$this->a$this->cls\. )(?P<row>.*)/m", ltrim($row), $rmtch)) {
                $ratts = $this->parseAttribs($rmtch['ratts'], 'tr');
                $row = $rmtch['row'];
            } else {
                $ratts = '';
            }

            $cells = array();
            $cellctr = 0;

            foreach (explode("|", $row) as $cell) {
                $ctyp = "d";

                if (preg_match("/^_(?=[{$this->regex_snippets['space']}[:punct:]])/", $cell)) {
                    $ctyp = "h";
                }

                if (preg_match("/^(?P<catts>_?$this->s$this->a$this->cls\. )(?P<cell>.*)/s", $cell, $cmtch)) {
                    $catts = $this->parseAttribs($cmtch['catts'], 'td');
                    $cell = $cmtch['cell'];
                } else {
                    $catts = '';
                }

                if (!$this->isLiteModeEnabled()) {
                    $a = array();

                    if (preg_match('/(?<space>'.$this->regex_snippets['space'].'*)(?P<cell>.*)/s', $cell, $a)) {
                        $cell = $this->redclothLists($a['cell']);
                        $cell = $this->textileLists($cell);
                        $cell = $a['space'] . $cell;
                    }
                }

                if ($cellctr > 0) {
                    // Ignore first 'cell': it precedes the opening pipe
                    $cells[] = $this->doTagBr("t$ctyp", "\t\t\t<t$ctyp$catts>$cell</t$ctyp>");
                }

                $cellctr++;
            }

            $grp = '';

            if ($rgrp && $last_rgrp) {
                $grp .= "\t</t".$last_rgrp.">\n";
            }

            if ($rgrp) {
                $grp .= "\t<t".$rgrp.$rgrpatts.">\n";
            }

            $last_rgrp = ($rgrp) ? $rgrp : $last_rgrp;
            $rows[] = $grp."\t\t<tr$ratts>\n" . join("\n", $cells) . ($cells ? "\n" : "") . "\t\t</tr>";
            unset($cells, $catts);
        }

        $rows = join("\n", $rows) . "\n";
        $close = '';

        if ($last_rgrp) {
            $close = "\t</t".$last_rgrp.">\n";
        }

        return "<table{$tatts}{$sum}>\n".$cap.$colgrp.$rows.$close."</table>\n\n";
    }

    /**
     * Parses RedCloth-style definition lists into HTML.
     *
     * @param  string $text The textile input
     * @return string The parsed text
     */
    protected function redclothLists($text)
    {
        return (string)preg_replace_callback(
            "/^([-]+$this->cls[ .].*:=.*)$(?![^-])/smU",
            array($this, "fRedclothList"),
            $text
        );
    }

    /**
     * Constructs a HTML definition list from a RedCloth-style definition structure.
     *
     * This method is used by Parser::redclothLists() to process
     * found definition list structures.
     *
     * @param array<string|int, string> $m
     * @return string HTML definition list
     * @see Parser::redclothLists()
     */
    protected function fRedclothList($m)
    {
        $in = $m[0];
        $out = array();

        /** @var array<int, string> $text */
        $text = preg_split('/\n(?=-)/m', $in);

        foreach ($text as $line) {
            $m = array();

            if (preg_match("/^[-]+(?P<atts>$this->cls)\.? (?P<content>.*)$/s", $line, $m)) {
                $content = trim($m['content']);
                $atts = $this->parseAttribs($m['atts']);

                if (!preg_match(
                    "/^(.*?){$this->regex_snippets['space']}*:=(.*?)".
                    "{$this->regex_snippets['space']}*(=:|:=)?".
                    "{$this->regex_snippets['space']}*$/s",
                    $content,
                    $xm
                )) {
                    $xm = array( $content, $content, '' );
                }

                list(, $term, $def,) = $xm;
                $term = trim($term);
                $def = trim($def, ' ');

                if (!$out) {
                    if ($def === '') {
                        $out[] = "<dl$atts>";
                    } else {
                        $out[] = '<dl>';
                    }
                }

                if ($term !== '') {
                    $pos = strpos($def, "\n");
                    $def = trim($def);

                    if ($this->isLineWrapEnabled()) {
                        $def = str_replace("\n", $this->getLineBreak(), $def);
                    }

                    if ($pos === 0) {
                        $def = '<p>' . $def . '</p>';
                    }

                    if ($this->isLineWrapEnabled()) {
                        $term = str_replace("\n", $this->getLineBreak(), $term);
                    }

                    $term = $this->graf($term);
                    $def = $this->graf($def);

                    $out[] = "\t<dt$atts>$term</dt>";

                    if ($def !== '') {
                        $out[] = "\t<dd>$def</dd>";
                    }
                }
            }
        }

        $out[] = '</dl>';
        return implode("\n", $out);
    }

    /**
     * Parses Textile list structures into HTML.
     *
     * Searches for ordered, un-ordered and definition lists in the
     * textile input and generates HTML lists for them.
     *
     * @param  string $text The input
     * @return string The parsed text
     */
    protected function textileLists($text)
    {
        return (string)preg_replace_callback(
            "/^((?:[*;:]+|[*;:#]*#(?:_|\d+)?)$this->cls[ .].*)$(?![^#*;:])/smU",
            array($this, "fTextileList"),
            $text
        );
    }

    /**
     * Constructs a HTML list from a Textile list structure.
     *
     * This method is used by Parser::textileLists() to process
     * found list structures.
     *
     * @param array<string|int, string> $m
     * @return string HTML list
     * @see Parser::textileLists()
     */
    protected function fTextileList($m)
    {
        $text = $m[0];
        $lines = preg_split('/\n(?=[*#;:])/m', $m[0]);
        $list = array();
        $prev = false;
        $out = array();
        $lists = array();
        $litem = '';

        if ($lines === false) {
            return '';
        }

        foreach ($lines as $line) {
            $match = preg_match(
                "/^(?P<tl>[#*;:]+)(?P<st>_|\d+)?(?P<atts>$this->cls)[ .](?P<content>.*)$/s",
                $line,
                $m
            );

            if ($match) {
                $list[] = array_merge($m, array(
                    'level' => strlen($m['tl']),
                ));
            } else {
                $list[count($list) - 1]['content'] .= "\n" . $line;
            }
        }

        if (!$list || $list[0]['level'] > 1) {
            return $text;
        }

        foreach ($list as $index => $m) {
            $start = '';
            $content = trim($m['content']);
            $ltype = $this->liType($m['tl']);

            if (isset($list[$index + 1])) {
                $next = $list[$index + 1];
            } else {
                $next = false;
            }

            if (strpos($m['tl'], ';') !== false) {
                $litem = 'dt';
            } elseif (strpos($m['tl'], ':') !== false) {
                $litem = 'dd';
            } else {
                $litem = 'li';
            }

            $showitem = ($content !== '');

            if ('o' === $ltype) {
                if (!isset($this->olstarts[$m['tl']])) {
                    $this->olstarts[$m['tl']] = 1;
                }

                if (!$prev || $m['level'] > $prev['level']) {
                    if ($m['st'] === '') {
                        $this->olstarts[$m['tl']] = 1;
                    } elseif ($m['st'] !== '_') {
                        $this->olstarts[$m['tl']] = (int) $m['st'];
                    }
                }

                if ((!$prev || $m['level'] > $prev['level']) && $m['st'] !== '') {
                    $start = ' start="' . $this->olstarts[$m['tl']] . '"';
                }

                if ($showitem) {
                    $this->olstarts[$m['tl']] += 1;
                }
            }

            if ($prev && $prev['tl'] && strpos($prev['tl'], ';') !== false && strpos($m['tl'], ':') !== false) {
                $lists[$m['tl']] = 2;
            }

            $tabs = str_repeat("\t", $m['level'] - 1);
            $atts = $this->parseAttribs($m['atts']);

            if (!isset($lists[$m['tl']])) {
                $lists[$m['tl']] = 1;
                $line = $tabs.'<'.$ltype.'l'.$atts.$start.'>';

                if ($showitem) {
                    $line .= "\n$tabs\t<$litem>$content";
                }
            } elseif ($showitem) {
                $line = "$tabs\t<$litem$atts>$content";
            } else {
                $line = '';
            }

            if ((!$next || $next['level'] <= $m['level']) && $showitem) {
                $line .= "</$litem>";
            }

            foreach (array_reverse($lists) as $k => $v) {
                $indent = strlen((string) $k);

                if (!$next || $indent > $next['level']) {
                    if ($v !== 2) {
                        $line .= "\n$tabs</" . $this->liType((string) $k) . "l>";
                    }

                    if ($v !== 2 && $indent > 1) {
                        $line .= "</".$litem.">";
                    }

                    unset($lists[$k]);
                }
            }

            $prev = $m;
            $out[] = $line;
        }

        $out = implode("\n", $out);
        return $this->doTagBr($litem, $out);
    }

    /**
     * Determines the list type from the Textile input symbol.
     *
     * @param  string $in Textile input containing the possible list marker
     * @return string Either 'd', 'o', 'u'
     */
    protected function liType($in)
    {
        $m = array();
        $type = 'd';
        if (preg_match('/^(?P<type>[#*]+)/', $in, $m)) {
            $type = ('#' === substr($m['type'], -1)) ? 'o' : 'u';
        }
        return $type;
    }

    /**
     * Adds br tags within the specified container tag.
     *
     * @param  string $tag The tag
     * @param  string $in  The input
     * @return string
     */
    protected function doTagBr($tag, $in)
    {
        return (string)preg_replace_callback(
            '@<(?P<tag>'.preg_quote($tag).')(?P<atts>[^>]*?)>(?P<content>.*)(?P<closetag></\1>)@s',
            array($this, 'fBr'),
            $in
        );
    }

    /**
     * Adds br tags to paragraphs and headings.
     *
     * @param  string $in The input
     * @return string
     */
    protected function doPBr($in)
    {
        return (string)preg_replace_callback(
            '@<(?P<tag>p|h[1-6])(?P<atts>[^>]*?)>(?P<content>.*)(?P<closetag></\1>)@s',
            array($this, 'fPBr'),
            $in
        );
    }

    /**
     * Less restrictive version of fBr method.
     *
     * Used only in paragraphs and headings where the next row may
     * start with a smiley or perhaps something like '#8 bolt...'
     * or '*** stars...'.
     *
     * @param array<string|int, string> $m The input
     * @return string
     */
    protected function fPBr($m)
    {
        if ($this->isLineWrapEnabled()) {
            // Replaces <br/>\n instances that are not followed by white-space,
            // or at end, with single LF.
            $m['content'] = preg_replace(
                "~<br[ ]*/?>{$this->regex_snippets['space']}*\n(?![{$this->regex_snippets['space']}|])~i",
                "\n",
                (string) $m['content']
            );
        }

        // Replaces those LFs that aren't followed by white-space, or at end, with <br /> or a space.
        $m['content'] = preg_replace(
            "/\n(?![\s|])/",
            $this->isLineWrapEnabled() ? $this->getLineBreak() : ' ',
            (string) $m['content']
        );

        return '<'.$m['tag'].$m['atts'].'>'.$m['content'].$m['closetag'];
    }

    /**
     * Formats line breaks.
     *
     * @param array<string|int, string> $m The input
     * @return string
     */
    protected function fBr($m)
    {
        $content = preg_replace(
            "@(.+)(?<!<br>|<br />|</li>|</dd>|</dt>)\n(?![\s|])@",
            $this->isLineWrapEnabled() ? '$1'.$this->getLineBreak() : '$1 ',
            $m['content']
        );

        return '<'.$m['tag'].$m['atts'].'>'.$content.$m['closetag'];
    }

    /**
     * Splits the given input into blocks.
     *
     * Blocks are separated by double line-break boundaries, and processed
     * the blocks one by one.
     *
     * @param  string $text Textile source text
     * @return string Input text with blocks processed
     */
    protected function blocks($text)
    {
        $regex = '/^(?P<tag>'.join('|', $this->blocktag_whitelist).')'.
            '(?P<atts>'.$this->a.$this->cls.$this->a.')\.(?P<ext>\.?)(?::(?P<cite>\S+))? (?P<graf>.*)$/Ss'.
            $this->regex_snippets['mod'];

        $textblocks = preg_split('/(\n{2,})/', $text, -1, PREG_SPLIT_DELIM_CAPTURE);

        if ($textblocks === false) {
            return '';
        }

        $eatWhitespace = false;
        $whitespace = '';
        $ext = '';
        $out = array();
        $c1 = '';

        foreach ($textblocks as $block) {
            // Line is just whitespace, keep it for the next block.
            if (trim($block) === '') {
                if ($eatWhitespace === false) {
                    $whitespace .= $block;
                }
                continue;
            }

            // @phpstan-ignore-next-line
            if (!$ext) {
                $tag = 'p';
                $atts = '';
                $cite = '';
                $eat = false;
            }

            $eatWhitespace = false;
            $anonymous_block = !preg_match($regex, $block, $m);

            if (!$anonymous_block) {
                // Last block was extended, so close it
                // @phpstan-ignore-next-line
                if ($ext) {
                    $out[count($out)-1] .= $c1;
                }

                // Extract the new block's parts
                extract($m);
                list($o1, $o2, $content, $c2, $c1, $eat) = $this->fBlock($m);

                // Leave off c1 if this block is extended, we'll close it at the start of the next block
                $block = $o1.$o2.$content.$c2;

                // @phpstan-ignore-next-line
                if (!$ext) {
                    $block .= $c1;
                }
            } else {
                $rawBlock = preg_match($this->patterns['divider'], $block) ||
                    ($this->isRawBlocksEnabled() && $this->isRawBlock($block));

                // @phpstan-ignore-next-line
                if ($ext || (strpos($block, ' ') !== 0 && !$rawBlock)) {
                    list($o1, $o2, $content, $c2, $c1, $eat) = $this->fBlock(array(
                        '',
                        $tag,
                        $atts,
                        $ext,
                        $cite,
                        $block,
                    ));

                    // Skip $o1/$c1 because this is part of a continuing extended block
                    // @phpstan-ignore-next-line
                    if ($tag == 'p' && !$this->hasRawText($content)) {
                        $block = $content;
                    } else {
                        $block = $o2.$content.$c2;
                    }
                } elseif ($rawBlock && $this->isRestrictedModeEnabled()) {
                    $block = $this->shelve($this->rEncodeHTML($block));
                } elseif ($rawBlock) {
                    $block = $this->shelve($block);
                } else {
                    $block = $this->graf($block);
                }
            }

            $block = $whitespace . $this->doPBr((string) $block);
            if ($this->getDocumentType() === self::DOCTYPE_XHTML) {
                $block = str_replace('<br>', '<br />', $block);
            }

            // @phpstan-ignore-next-line
            if ($ext && $anonymous_block) {
                $out[count($out)-1] .= $block;
            } elseif (!$eat) {
                $out[] = $block;
            }

            if ($eat) {
                $eatWhitespace = true;
            } else {
                $whitespace = '';
            }
        }

        // @phpstan-ignore-next-line
        if ($ext) {
            $out[count($out)-1] .= $c1;
        }

        return join('', $out);
    }

    /**
     * Formats the given block.
     *
     * Adds block tags and formats the text content inside
     * the block.
     *
     * @param array<int, string> $m The block content to format
     * @return array<int, string|bool|null>
     */
    protected function fBlock($m)
    {
        list(, $tag, $att, $ext, $cite, $content) = $m;
        $atts = $this->parseAttribs($att);
        $space = $this->regex_snippets['space'];

        $o1 = '';
        $o2 = '';
        $c2 = '';
        $c1 = '';
        $eat = false;

        if ($tag === 'p') {
            // Is this an anonymous block with a note definition?
            $notedef = preg_replace_callback(
                "/
                    ^note\#                              # start of note def marker
                    (?P<label>[^%<*!@#^([{ {$space}.]+)  # label
                    (?P<link>[*!^]?)                     # link
                    (?P<att>{$this->cls})                # att
                    \.?                                  # optional period.
                    {$space}+                            # whitespace ends def marker
                    (?P<content>.*)$                     # content
                /x".$this->regex_snippets['mod'],
                array($this, "fParseNoteDefs"),
                $content
            );

            if ($notedef === '' || $notedef === null) {
                // It will be empty if the regex matched and ate it.
                return array($o1, $o2, $notedef, $c2, $c1, true);
            }
        }

        if (preg_match("/fn(?P<fnid>{$this->regex_snippets['digit']}+)/".$this->regex_snippets['mod'], $tag, $fns)) {
            $tag = 'p';
            $fnid = empty($this->fn[$fns['fnid']]) ? $this->linkPrefix . ($this->linkIndex++) : $this->fn[$fns['fnid']];

            // If there is an author-specified ID goes on the wrapper & the auto-id gets pushed to the <sup>
            $supp_id = '';
            if (strpos($atts, 'class=') === false) {
                $atts .= ' class="footnote"';
            }

            if (strpos($atts, ' id=') === false) {
                $atts .= ' id="fn' . $fnid . '"';
            } else {
                $supp_id = ' id="fn' . $fnid . '"';
            }

            if (strpos($att, '^') === false) {
                $sup = $this->formatFootnote($fns['fnid'], $supp_id);
            } else {
                $sup = $this->formatFootnote('<a href="#fnrev' . $fnid . '">'.$fns['fnid'] .'</a>', $supp_id);
            }

            $content = $sup . ' ' . $content;
        }

        if ($tag == "bq") {
            $cite = $this->shelveURL($cite);
            $cite = ($cite != '') ? ' cite="' . $cite . '"' : '';
            $o1 = "<blockquote$cite$atts>\n";
            $o2 = "\t<p".$this->parseAttribs($att, '', false).">";
            $c2 = "</p>";
            $c1 = "\n</blockquote>";
        } elseif ($tag == 'bc') {
            $attrib_array = $this->parseAttribsToArray($att, 'code');
            $code_class   = '';
            if (isset($attrib_array['lang'])) {
                $code_class = ' class="'.$attrib_array['lang'].'"';
                unset($attrib_array['lang']);
                $atts = $this->formatAttributeString($attrib_array);
            }
            $o1 = "<pre$atts><code$code_class>";
            $c1 = "</code></pre>";
            $content = $this->shelve($this->rEncodeHTML($content));
        } elseif ($tag == 'notextile') {
            $content = $this->shelve($content);
            $o1 = '';
            $o2 = '';
            $c1 = '';
            $c2 = '';
        } elseif ($tag == 'pre') {
            $content = $this->shelve($this->rEncodeHTML($content));
            $o1 = "<pre$atts>";
            $o2 = '';
            $c2 = '';
            $c1 = "</pre>";
        } elseif ($tag == '###') {
            $eat = true;
        } else {
            $o2 = "<$tag$atts>";
            $c2 = "</$tag>";
        }

        $content = (!$eat) ? $this->graf($content) : '';

        return array($o1, $o2, $content, $c2, $c1, $eat);
    }

    /**
     * Whether the block is a raw document node.
     *
     * Raw blocks will be shelved and left as is.
     *
     * @param  string $text Block to check
     * @return bool   TRUE if the block is raw, FALSE otherwise
     * @since  3.7.0
     */
    protected function isRawBlock($text)
    {
        if (preg_match($this->patterns['contained'], $text, $m)) {
            if (preg_match($this->patterns['phrasing'], $m['open'])) {
                return false;
            }

            if (preg_match($this->patterns['block'], $m['open'])) {
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * Formats a footnote.
     *
     * @param  string $marker The marker
     * @param  string $atts   Attributes
     * @param  bool   $anchor TRUE, if its a reference link
     * @return string Processed footnote
     */
    protected function formatFootnote($marker, $atts = '', $anchor = true)
    {
        $pattern = ($anchor) ? $this->symbols['fn_foot_pattern'] : $this->symbols['fn_ref_pattern'];
        return $this->replaceMarkers((string) $pattern, array('atts' => $atts, 'marker' => $marker));
    }

    /**
     * Replaces markers with replacements in the given input.
     *
     * @param string $text The input
     * @param array<string, string> $replacements Marker replacement pairs
     * @return string
     */
    protected function replaceMarkers($text, $replacements)
    {
        $map = array();

        foreach ($replacements as $from => $to) {
            $map['{'.$from.'}'] = $to;
        }

        return strtr($text, $map);
    }

    /**
     * Parses HTML comments in the given input.
     *
     * This method finds HTML comments in the given input
     * and replaces them with reference tokens.
     *
     * @param  string $text Textile input
     * @return string $text Processed input
     */
    protected function getHTMLComments($text)
    {
        return (string)preg_replace_callback(
            "/\<!--(?P<content>.*?)-->/sx",
            array($this, "fParseHTMLComments"),
            $text
        );
    }

    /**
     * Formats a HTML comment.
     *
     * Stores the comment on the shelf and returns
     * a reference token wrapped in to a HTML comment.
     *
     * @param array<string|int, string> $m Options
     * @return string Reference token wrapped to a HTML comment tags
     */
    protected function fParseHTMLComments($m)
    {
        return '<!--'.$this->shelve($m['content']).'-->';
    }

    /**
     * Parses paragraphs in the given input.
     *
     * @param  string $text Textile input
     * @return string Processed input
     */
    protected function graf($text)
    {
        // Handle normal paragraph text
        if (!$this->isLiteModeEnabled()) {
            // Notextile blocks and inlines
            $text = $this->noTextile($text);
            // Handle code
            $text = $this->code($text);
        }

        // HTML comments --
        $text = $this->getHTMLComments($text);
        // Consume link aliases
        $text = $this->getRefs($text);
        // Treat quoted quote as a special glyph.
        $text = $this->glyphQuotedQuote($text);
        // Generate links
        $text = $this->links($text);

        // Handle images (if permitted)
        if ($this->isImageTagEnabled()) {
            $text = $this->images($text);
        }

        if (!$this->isLiteModeEnabled()) {
            // Handle tables
            $text = $this->tables($text);
            // Handle redcloth-style definition lists
            $text = $this->redclothLists($text);
            // Handle ordered & unordered lists plus txp-style definition lists
            $text = $this->textileLists($text);
        }

        // Inline markup (em, strong, sup, sub, del etc)
        $text = $this->spans($text);

        if (!$this->isLiteModeEnabled()) {
            // Turn footnote references into supers or links.
            // As footnote blocks are banned in lite mode there is no point
            // generating links for them.
            $text = $this->footnoteRefs($text);

            // Turn note references into links
            $text = $this->noteRefs($text);
        }

        // Glyph level substitutions (mainly typographic -- " & ' => curly quotes, -- => em-dash etc.
        $text = $this->glyphs($text);

        return rtrim($text, "\n");
    }

    /**
     * Replaces Textile span tags with their equivalent HTML inline tags.
     *
     * @param string $text The Textile document to perform the replacements in
     * @return string The Textile document with spans replaced by their HTML inline equivalents
     */
    protected function spans($text)
    {
        $span_tags = array_keys($this->span_tags);
        $pnct = ".,\"'?!;:‹›«»„“”‚‘’";
        $this->span_depth++;

        if ($this->span_depth <= $this->max_span_depth) {
            foreach ($span_tags as $tag) {
                $tag = preg_quote($tag);
                $text = (string)preg_replace_callback(
                    "/
                    (?P<before>^|(?<=[\s>$pnct\(])|[{[])
                    (?P<tag>$tag)(?!$tag)
                    (?P<atts>{$this->cls})
                    (?!$tag)
                    (?::(?P<cite>\S+[^$tag]{$this->regex_snippets['space']}))?
                    (?P<content>[^{$this->regex_snippets['space']}$tag]+|\S.*?[^\s$tag\n])
                    (?P<end>[$pnct]*)
                    $tag
                    (?P<after>$|[\[\]}<]|(?=[$pnct]{1,2}[^0-9]|\s|\)))
                    /x".$this->regex_snippets['mod'],
                    array($this, "fSpan"),
                    $text
                );
            }
        }
        $this->span_depth--;
        return $text;
    }

    /**
     * Formats a span tag and stores it on the shelf.
     *
     * @param array<string|int, string> $m Options
     * @return string Content wrapped to reference tokens
     * @see Parser::spans()
     */
    protected function fSpan($m)
    {
        $m = $this->getSpecialOptions($m);
        $tag = $this->span_tags[$m['tag']];
        $atts = $this->parseAttribsToArray($m['atts']);

        if ($m['cite'] != '') {
            $atts['cite'] = trim($m['cite']);
            ksort($atts);
        }

        $atts = $this->formatAttributeString($atts);
        $content = $this->spans($m['content']);
        $opentag = '<'.$tag.$atts.'>';
        $closetag = '</'.$tag.'>';
        $tags = $this->storeTags($opentag, $closetag);
        $out = "{$tags['open']}{$content}{$m['end']}{$tags['close']}";

        return $m['before'].$out.$m['after'];
    }

    /**
     * Stores a tag pair in the tag cache.
     *
     * @param string $opentag  Opening tag
     * @param string $closetag Closing tag
     * @return array<string, string> Reference tokens for both opening and closing tag
     */
    protected function storeTags($opentag, $closetag = '')
    {
        $tags = array();

        $this->refCache[$this->refIndex] = $opentag;
        $tags['open'] = $this->uid.$this->refIndex.':ospan ';
        $this->refIndex++;

        $this->refCache[$this->refIndex] = $closetag;
        $tags['close'] = ' '.$this->uid.$this->refIndex.':cspan';
        $this->refIndex++;

        return $tags;
    }

    /**
     * Replaces reference tokens with corresponding shelved span tags.
     *
     * This method puts all shelved span tags back to the final,
     * parsed input.
     *
     * @param string $text The input
     * @return string Processed text
     * @see Parser::storeTags()
     */
    protected function retrieveTags($text)
    {
        $text = (string)preg_replace_callback(
            '/'.$this->uid.'(?P<token>[0-9]+):ospan /',
            array($this, 'fRetrieveTags'),
            $text
        );

        $text = (string)preg_replace_callback(
            '/ '.$this->uid.'(?P<token>[0-9]+):cspan/',
            array($this, 'fRetrieveTags'),
            $text
        );

        return $text;
    }

    /**
     * Retrieves a tag from the tag cache.
     *
     * @param array<string|int, string> $m Options
     * @return string
     * @see Parser::retrieveTags()
     */
    protected function fRetrieveTags($m)
    {
        return $this->refCache[$m['token']];
    }

    /**
     * Parses note lists in the given input.
     *
     * This method should be ran after other blocks
     * have been processed, but before reference tokens
     * have been replaced with their replacements.
     *
     * @param string $text Textile input
     * @return string Processed input
     */
    protected function placeNoteLists($text)
    {
        // Sequence all referenced definitions.
        if ($this->notes) {
            $o = array();
            foreach ($this->notes as $label => $info) {
                if (!empty($info['seq'])) {
                    // @phpstan-ignore-next-line
                    $o[$info['seq']] = $info;
                    $info['seq'] = $label;
                } else {
                    // Unreferenced definitions go here for possible future use.
                    $this->unreferencedNotes[] = $info;
                }
            }

            if ($o) {
                ksort($o);
            }

            // @phpstan-ignore-next-line
            $this->notes = $o;
        }

        // Replace list markers.
        $text = (string)preg_replace_callback(
            '@<p>notelist(?P<atts>'.$this->c.')'.
            '(?:\:(?P<startchar>['.$this->regex_snippets['wrd'].'|'.$this->syms.']))?'.
            '(?P<links>[\^!]?)(?P<extras>\+?)\.?'.$this->regex_snippets['space'].'*</p>@U'.
            $this->regex_snippets['mod'],
            array($this, "fNoteLists"),
            $text
        );

        return $text;
    }

    /**
     * Formats a note list.
     *
     * @param array<string|int, string> $m Options
     * @return string Processed note list
     */
    protected function fNoteLists($m)
    {
        if (!$m['startchar']) {
            $m['startchar'] = 'a';
        }

        $index = $m['links'].$m['extras'].$m['startchar'];

        if (empty($this->notelist_cache[$index])) {
            // If not in cache, build the entry
            $out = array();

            if ($this->notes) {
                foreach ($this->notes as $seq => $info) {
                    $links = $this->makeBackrefLink($info, $m['links'], $m['startchar']);

                    if (!empty($info['def'])) {
                        // @phpstan-ignore-next-line
                        $out[] = "\t".'<li'.$info['def']['atts'].'>'.$links.
                            '<span id="note'.$info['id'].'"> </span>'.$info['def']['content'].'</li>';
                    } else {
                        // @phpstan-ignore-next-line
                        $out[] = "\t".'<li>'.$links.' Undefined Note [#'.$info['seq'].'].</li>';
                    }
                }
            }

            if ('+' == $m['extras'] && $this->unreferencedNotes) {
                foreach ($this->unreferencedNotes as $info) {
                    if (!empty($info['def'])) {
                        // @phpstan-ignore-next-line
                        $out[] = "\t".'<li'.$info['def']['atts'].'>'.$info['def']['content'].'</li>';
                    }
                }
            }

            $this->notelist_cache[$index] = join("\n", $out);
        }

        if ($this->notelist_cache[$index]) {
            $atts = $this->parseAttribs($m['atts']);
            return "<ol$atts>\n{$this->notelist_cache[$index]}\n</ol>";
        }

        return '';
    }

    /**
     * Renders a note back reference link.
     *
     * This method renders an array of back reference
     * links for notes.
     *
     * @param array<string, array<string|int, string>|int|string> $info Options
     * @param string $g_links Reference type
     * @param string $i Instance count
     * @return string Processed input
     */
    protected function makeBackrefLink($info, $g_links, $i)
    {
        $backlink_type = !empty($info['def']) && $info['def']['link'] ? $info['def']['link'] : $g_links;
        $allow_inc = (false === strpos($this->syms, $i));

        $i_ = str_replace(array('&', ';', '#'), '', $this->encodeHigh($i));
        $decode = (strlen($i) !== strlen($i_));

        if ($backlink_type === '!') {
            return '';
        } elseif ($backlink_type === '^') {
            return '<sup><a href="#noteref'.$info['refids'][0].'">'.$i.'</a></sup>';
        } else {
            $out = array();

            /** @var array<int|string, string> $items */
            $items = $info['refids'];

            foreach ($items as $id) {
                $out[] = '<sup><a href="#noteref'.$id.'">'. (($decode) ? $this->decodeHigh($i_) : $i_) .'</a></sup>';
                if ($allow_inc) {
                    $i_++;
                }
            }

            return join(' ', $out);
        }
    }

    /**
     * Formats note definitions.
     *
     * This method formats notes and stores them in
     * note cache for later use and to build reference
     * links.
     *
     * @param array<string|int, string> $m Options
     * @return string Empty string
     */
    protected function fParseNoteDefs($m)
    {
        $label = $m['label'];
        $link = $m['link'];
        $att = $m['att'];
        $content = $m['content'];

        // Assign an id if the note reference parse hasn't found the label yet.
        if (empty($this->notes[$label]['id'])) {
            $this->notes[$label]['id'] = $this->linkPrefix . ($this->linkIndex++);
        }

        // Ignores subsequent defs using the same label
        if (empty($this->notes[$label]['def'])) {
            $this->notes[$label]['def'] = array(
                'atts'    => $this->parseAttribs($att),
                'content' => $this->graf($content),
                'link'    => $link,
            );
        }
        return '';
    }

    /**
     * Parses note references in the given input.
     *
     * This method replaces note reference tags with
     * links.
     *
     * @param  string $text Textile input
     * @return string
     */
    protected function noteRefs($text)
    {
        return (string)preg_replace_callback(
            "/\[(?P<atts>{$this->c})\#(?P<label>[^\]!]+?)(?P<nolink>[!]?)\]/Ux",
            array($this, "fParseNoteRefs"),
            $text
        );
    }

    /**
     * Formats note reference links.
     *
     * By the time this function is called, all note lists will have been
     * processed into the notes array, and we can resolve the link numbers in
     * the order we process the references.
     *
     * @param array<string, string> $m Options
     * @return string Note reference
     */
    protected function fParseNoteRefs($m)
    {
        $atts = $this->parseAttribs($m['atts']);
        $nolink = ($m['nolink'] === '!');

        // Assign a sequence number to this reference if there isn't one already.

        if (empty($this->notes[$m['label']]['seq'])) {
            $num = $this->notes[$m['label']]['seq'] = ($this->note_index++);
        } else {
            /** @var int $num */
            $num = $this->notes[$m['label']]['seq'];
        }

        // Make our anchor point & stash it for possible use in backlinks when the
        // note list is generated later.
        $refid = $this->linkPrefix . ($this->linkIndex++);

        // @phpstan-ignore-next-line
        $this->notes[$m['label']]['refids'][] = $refid;

        // If we are referencing a note that hasn't had the definition parsed yet, then assign it an ID.

        if (empty($this->notes[$m['label']]['id'])) {
            $id = $this->notes[$m['label']]['id'] = $this->linkPrefix . ($this->linkIndex++);
        } else {
            $id = $this->notes[$m['label']]['id'];
        }

        // Build the link (if any).
        $out = '<span id="noteref'.$refid.'">'.$num.'</span>';

        if (!$nolink) {
            $out = '<a href="#note'.$id.'">'.$out.'</a>';
        }

        // Build the reference.
        return $this->replaceMarkers((string) $this->symbols['nl_ref_pattern'], array(
            'atts' => $atts,
            'marker' => $out,
        ));
    }

    /**
     * Parses URI into component parts.
     *
     * This method splits a URI-like string apart into component parts, while
     * also providing validation.
     *
     * @param string $uri The string to pick apart (if possible)
     * @param array<string|int, string> $m Reference to an array the URI component parts are assigned to
     * @return bool TRUE if the string validates as a URI
     * @link http://tools.ietf.org/html/rfc3986#appendix-B
     */
    protected function parseURI($uri, &$m)
    {
        $r = "@^((?P<scheme>[^:/?#]+):)?".
            "(//(?P<authority>[^/?#]*))?".
            "(?P<path>[^?#]*)".
            "(\?(?P<query>[^#]*))?".
            "(#(?P<fragment>.*))?@";

        return preg_match($r, $uri, $m) === 1;
    }

    /**
     * Checks whether a component part can be added to a URI.
     *
     * @param array<int, string> $mask An array of allowed component parts
     * @param string $name  The component to add
     * @param array<string, string> $parts An array of existing components to modify
     * @return bool TRUE if the component can be added
     */
    protected function addPart($mask, $name, $parts)
    {
        return (in_array($name, $mask) && isset($parts[$name]) && '' !== $parts[$name]);
    }

    /**
     * Rebuild a URI from parsed parts and a mask.
     *
     * @param array<string, string> $parts Full array of URI parts
     * @param string $mask Comma separated list of URI parts to include in the rebuilt URI
     * @param bool $encode Flag to control encoding of the path part of the rebuilt URI
     * @return string The rebuilt URI
     * @link http://tools.ietf.org/html/rfc3986#section-5.3
     */
    protected function rebuildURI($parts, $mask = 'scheme,authority,path,query,fragment', $encode = true)
    {
        $mask = explode(',', $mask);
        $out = '';

        if ($this->addPart($mask, 'scheme', $parts)) {
            $out .= $parts['scheme'] . ':';
        }

        if ($this->addPart($mask, 'authority', $parts)) {
            $out .= '//' . $parts['authority'];
        }

        if ($this->addPart($mask, 'path', $parts)) {
            if (!$encode) {
                $out .= $parts['path'];
            } else {
                $pp = explode('/', $parts['path']);
                foreach ($pp as &$p) {
                    $p = str_replace(array('%25', '%40'), array('%', '@'), rawurlencode($p));
                    if (!in_array($parts['scheme'], array('mailto'))) {
                        $p = str_replace('%2B', '+', $p);
                    }
                }

                $pp = implode('/', $pp);
                $out .= $pp;
            }
        }

        if ($this->addPart($mask, 'query', $parts)) {
            $out .= '?' . $parts['query'];
        }

        if ($this->addPart($mask, 'fragment', $parts)) {
            $out .= '#' . $parts['fragment'];
        }

        return $out;
    }

    /**
     * Parses and shelves links in the given input.
     *
     * This method parses the input Textile document for links.
     * Formats and encodes them, and stores the created link
     * elements in cache.
     *
     * @param  string $text Textile input
     * @return string The input document with link pulled out and replaced with tokens
     */
    protected function links($text)
    {
        $text = $this->markStartOfLinks($text);
        return $this->replaceLinks($text);
    }

    /**
     * Finds and marks the start of well formed links in the input text.
     *
     * @param  string $text String to search for link starting positions
     * @return string Text with links marked
     * @see    Parser::links()
     */
    protected function markStartOfLinks($text)
    {
        // Slice text on '":<not space>' boundaries. These always occur in inline
        // links between the link text and the url part and are much more
        // infrequent than '"' characters so we have less possible links
        // to process.
        $mod = $this->regex_snippets['mod'];
        $slices = preg_split('/":(?='.$this->regex_snippets['char'].')/'.$mod, $text);

        if ($slices === false) {
            return '';
        }

        if (count($slices) > 1) {
            // There are never any start of links in the last slice, so pop it
            // off (we'll glue it back later).
            $last_slice = array_pop($slices);

            foreach ($slices as &$slice) {
                // If there is no possible start quote then this slice is not a link
                if (strpos($slice, '"') === false) {
                    continue;
                }

                // Cut this slice into possible starting points wherever we
                // find a '"' character. Any of these parts could represent
                // the start of the link text - we have to find which one.
                $possible_start_quotes = explode('"', $slice);

                // Start our search for the start of the link with the closest prior
                // quote mark.
                $possibility = rtrim(array_pop($possible_start_quotes));

                // Init the balanced count. If this is still zero at the end
                // of our do loop we'll mark the " that caused it to balance
                // as the start of the link and move on to the next slice.
                $balanced = 0;
                $linkparts = array();
                $iter = 0;

                while ($possibility !== null) {
                    // Starting at the end, pop off the previous part of the
                    // slice's fragments.

                    // Add this part to those parts that make up the link text.
                    $linkparts[] = $possibility;

                    if ($possibility !== '') {
                        // did this part inc or dec the balanced count?
                        if (preg_match('/^\S|=$/'.$mod, $possibility)) {
                            $balanced--;
                        }

                        if (preg_match('/\S$/'.$mod, $possibility)) {
                            $balanced++;
                        }

                        $possibility = array_pop($possible_start_quotes);
                    } else {
                        // If quotes occur next to each other, we get zero length strings.
                        // eg. ...""Open the door, HAL!"":url...
                        // In this case we count a zero length in the last position as a
                        // closing quote and others as opening quotes.
                        $balanced = (!$iter++) ? $balanced+1 : $balanced-1;

                        $possibility = array_pop($possible_start_quotes);

                        // If out of possible starting segments we back the last one
                        // from the linkparts array
                        if ($possibility === null) {
                            array_pop($linkparts);
                            break;
                        }

                        // If the next possibility is empty or ends in a space we have a
                        // closing ".
                        if ($possibility === '' ||
                            preg_match("~{$this->regex_snippets['space']}$~".$mod, $possibility)) {
                            $balanced = 0; // force search exit
                        }
                    }

                    if ($balanced <= 0) {
                        array_push($possible_start_quotes, $possibility);
                        break;
                    }
                }

                // Rebuild the link's text by reversing the parts and sticking them back
                // together with quotes.
                $link_content = implode('"', array_reverse($linkparts));

                // Rebuild the remaining stuff that goes before the link but that's
                // already in order.
                $pre_link = implode('"', $possible_start_quotes);

                // Re-assemble the link starts with a specific marker for the next regex.
                $slice = $pre_link . $this->uid.'linkStartMarker:"' . $link_content;
            }

            // Add the last part back
            $slices[] = $last_slice;
        }

        // Re-assemble the full text with the start and end markers
        $text = implode('":', $slices);

        return $text;
    }

    /**
     * Replaces links with tokens and stores them on the shelf.
     *
     * @param  string $text The input
     * @return string Processed input
     * @see    Parser::links()
     */
    protected function replaceLinks($text)
    {
        $stopchars = "\s|^'\"*";
        $needle = $this->uid . 'linkStartMarker:';
        $prev = null;

        while (\strpos($text, $needle) !== false) {
            $text = (string)preg_replace_callback(
                '/
                (?P<pre>\[)?
                ' . $needle . '"
                (?P<inner>(?:.|\n)*?)
                ":(?P<urlx>[^' . $stopchars . ']*)
                /x' . $this->regex_snippets['mod'],
                array($this, "fLink"),
                $text
            );

            if ($prev === $text) {
                break;
            }

            $prev = $text;
        }

        return $text;
    }

    /**
     * Formats a link and stores it on the shelf.
     *
     * @param array<string|int, string> $m Options
     * @return string Reference token for the shelved content
     * @see Parser::replaceLinks()
     */
    protected function fLink($m)
    {
        $in = $m[0];
        $pre = $m['pre'];
        if ($this->isLineWrapEnabled()) {
            $inner = str_replace("\n", $this->getLineBreak(), $m['inner']);
        } else {
            $inner = str_replace("\n", ' ', $m['inner']);
        }
        $url = $m['urlx'];
        $m = array();

        // Treat empty inner part as an invalid link.
        if (trim($inner) === '') {
            return $pre.'"'.$inner.'":'.$url;
        }

        // Split inner into $atts, $text and $title..
        preg_match(
            '/
            ^
            (?P<atts>' . $this->cls . ')            # $atts (if any)
            ' . $this->regex_snippets['space'] . '* # any optional spaces
            (?P<text>                               # $text is...
                (!.+!)                              #     an image
            |                                       #   else...
                .+?                                 #     link text
            )                                       # end of $text
            (?:\((?P<title>[^)]+?)\))?              # $title (if any)
            $
            /x'.$this->regex_snippets['mod'],
            $inner,
            $m
        );
        $atts = isset($m['atts']) ? $m['atts'] : '';
        $text = isset($m['text']) ? trim($m['text']) : $inner;
        $title = isset($m['title']) ? $m['title'] : '';
        $m = array();

        $pop = $tight = '';
        $counts = array(
            '[' => null,
            ']' => substr_count($url, ']'), # We need to know how many closing square brackets we have
            '(' => null,
            ')' => null,
        );

        // Look for footnotes or other square-bracket delimieted stuff at the end of the url...
        // eg. "text":url][otherstuff... will have "[otherstuff" popped back out.
        //     "text":url?q[]=x][123]    will have "[123]" popped off the back, the remaining closing square brackets
        //                               will later be tested for balance
        if ($counts[']']
            && 1 === preg_match('@(?P<url>^.*\])(?P<tight>\[.*?)$@' . $this->regex_snippets['mod'], $url, $m)
        ) {
            $url = $m['url'];
            $tight = $m['tight'];
            $m = array();
        }

        // Split off any trailing text that isn't part of an array assignment.
        // eg. "text":...?q[]=value1&q[]=value2 ... is ok
        // "text":...?q[]=value1]following  ... would have "following"
        // popped back out and the remaining square bracket
        // will later be tested for balance
        if ($counts[']']
            && 1 === preg_match('@(?P<url>^.*\])(?!=)(?P<end>.*?)$@' . $this->regex_snippets['mod'], $url, $m)
        ) {
            $url = $m['url'];
            $tight = $m['end'] . $tight;
            $m = array();
        }

        // Does this need to be mb_ enabled? We are only searching for text in the ASCII charset anyway
        // Create an array of (possibly) multi-byte characters.
        // This is going to allow us to pop off any non-matched or nonsense chars from the url
        $url_chars = str_split($url);

        // Now we have the array of all the multi-byte chars in the url we will parse the
        // uri backwards and pop off
        // any chars that don't belong there (like . or , or unmatched brackets of various kinds).
        $first = true;
        do {
            $c = array_pop($url_chars);
            $popped = false;
            switch ($c) {
                // Textile URL shouldn't end in these characters, we pop
                // them off the end and push them out the back of the url again.
                case '!':
                case '?':
                case ':':
                case ';':
                case '.':
                case ',':
                    $pop = $c . $pop;
                    $popped = true;
                    break;

                case '>':
                    $urlLeft = implode('', $url_chars);

                    if (preg_match('@(?P<tag><\/[a-z]+)$@', $urlLeft, $m)) {
                        $url_chars = str_split(substr($urlLeft, 0, strlen($m['tag']) * -1));
                        $pop = $m['tag'] . $c . $pop;
                        $popped = true;
                    }

                    break;

                case ']':
                    // If we find a closing square bracket we are going to see if it is balanced.
                    // If it is balanced with matching opening bracket then it is part of the URL
                    // else we spit it back out of the URL.
                    if (null === $counts['[']) {
                        $counts['['] = substr_count($url, '[');
                    }

                    if ($counts['['] === $counts[']']) {
                        // It is balanced, so keep it
                        $url_chars[] = $c;
                    } else {
                        // In the case of un-matched closing square brackets we just eat it
                        $popped = true;
                        $counts[']'] -= 1;
                        if ($first) {
                            $pre = '';
                        }
                    }
                    break;

                case ')':
                    if (null === $counts[')']) {
                        $counts['('] = substr_count($url, '(');
                        $counts[')'] = substr_count($url, ')');
                    }

                    if ($counts['('] === $counts[')']) {
                        // It is balanced, so keep it
                        $url_chars[] = $c;
                    } else {
                        // Unbalanced so spit it out the back end
                        $pop = $c . $pop;
                        $counts[')'] -= 1;
                        $popped = true;
                    }
                    break;

                default:
                    // We have an acceptable character for the end of the url so put it back and
                    // exit the character popping loop
                    $url_chars[] = $c;
                    break;
            }
            $first = false;
        } while ($popped);

        $url = implode('', $url_chars);
        $uri_parts = array();
        $this->parseURI($url, $uri_parts);

        if (!$this->isValidUrl($url)) {
            return str_replace($this->uid.'linkStartMarker:', '', $in);
        }

        $scheme = $uri_parts['scheme'];
        $scheme_in_list = in_array($scheme, $this->url_schemes);

        if ('$' === $text) {
            if ($scheme_in_list) {
                $text = ltrim($this->rebuildURI($uri_parts, 'authority,path,query,fragment', false), '/');
            } else {
                if (isset($this->urlrefs[$url])) {
                    $url = urldecode($this->urlrefs[$url]);
                }

                $text = $url;
            }
        }

        $text = trim($text);
        $title = $this->encodeHTML($title);

        if ($this->isImageTagEnabled()) {
            $text = $this->images($text);
        }

        $text = $this->spans($text);
        $text = $this->glyphs($text);
        $url = $this->shelveURL($this->rebuildURI($uri_parts));
        $a = $this->newTag(
            'a',
            $this->parseAttribsToArray($atts),
            false
        )->title($title)->href($url, true)->rel($this->rel);
        $tags = $this->storeTags((string) $a, '</a>');
        $out = $this->shelve($tags['open'].trim($text).$tags['close']);

        return $pre . $out . $pop . $tight;
    }

    /**
     * Finds URI aliases within the given input.
     *
     * This method finds URI aliases in the Textile input. Links are stored
     * in an internal cache, so that they can be referenced from any link
     * in the document.
     *
     * This operation happens before the actual link parsing takes place.
     *
     * @param  string $text Textile input
     * @return string The Textile document with any URI aliases removed
     */
    protected function getRefs($text)
    {
        $pattern = array();

        foreach ($this->url_schemes as $scheme) {
            $pattern[] = preg_quote($scheme.':', '/');
        }

        $pattern =
            '/^\[(?P<alias>.+)\]'.
            '(?P<url>(?:'.join('|', $pattern).'|\/)\S+)'.
            '(?='.$this->regex_snippets['space'].'|$)/Um';

        return (string)preg_replace_callback(
            $pattern.$this->regex_snippets['mod'],
            array($this, "refs"),
            $text
        );
    }

    /**
     * Parses, encodes and shelves the current URI alias.
     *
     * @param array<string|int, string> $m Options
     * @return string Empty string
     * @see Parser::getRefs()
     */
    protected function refs($m)
    {
        $uri_parts = array();
        $this->parseURI($m['url'], $uri_parts);
        // Encodes URL if needed.
        $this->urlrefs[$m['alias']] = ltrim($this->rebuildURI($uri_parts));
        return '';
    }

    /**
     * Shelves parsed URLs.
     *
     * Stores away a URL fragments that have been parsed
     * and requires no more processing.
     *
     * @param  string $text  The URL
     * @param  string $type  The type
     * @return string The fragment's unique reference ID
     * @see    Parser::retrieveURLs()
     */
    protected function shelveURL($text, $type = null)
    {
        if ('' === $text) {
            return '';
        }

        if ($type === null) {
            $type = 'url';
        }

        $this->refCache[$this->refIndex] = $text;
        return $this->uid.($this->refIndex++).':'.$type;
    }

    /**
     * Replaces reference tokens with corresponding shelved URL.
     *
     * This method puts all shelved URLs back to the final,
     * parsed input.
     *
     * @param  string $text The input
     * @return string Processed text
     * @see    Parser::shelveURL()
     */
    protected function retrieveURLs($text)
    {
        return (string)preg_replace_callback(
            '/'.$this->uid.'(?P<token>[0-9]+):(?P<type>url|image)/',
            array($this, 'retrieveURL'),
            $text
        );
    }

    /**
     * Retrieves an URL from the shelve.
     *
     * @param array<string|int, string> $m Options
     * @return string The URL
     */
    protected function retrieveURL($m)
    {
        if (!isset($this->refCache[$m['token']])) {
            return '';
        }

        $url = $this->refCache[$m['token']];

        if (isset($this->urlrefs[$url])) {
            $url = $this->urlrefs[$url];
        }

        return $this->rEncodeHTML($this->relURL($url, $m['type']));
    }

    /**
     * Whether the URL is valid.
     *
     * Checks are done according the used preferences to
     * determinate whether the URL should be accepted and
     * essentially whether its safe.
     *
     * @param  string $url The URL to check
     * @return bool   TRUE if valid, FALSE otherwise
     * @since  3.6.0
     */
    protected function isValidUrl($url)
    {
        if ($this->parseURI($url, $component)) {
            if (!isset($component['scheme']) || $component['scheme'] === '') {
                return true;
            }

            if (in_array($component['scheme'], $this->url_schemes, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Completes and formats a relative URL.
     *
     * This method adds $this->relativeImagePrefix to the
     * URL if it is relative.
     *
     * The URI is kept as is if it starts with a '/', './', '../',
     * or the URL starts with one of $this->url_schemes. Otherwise
     * the URL is prefixed.
     *
     * @param  string $url  The URL
     * @param  string $type The type
     * @return string Absolute URL
     */
    protected function relURL($url, $type = null)
    {
        if ($this->relativeImagePrefix !== null) {
            // Use legacy fallback if set. Deprecated in 3.7.0.
            $prefix = $this->relativeImagePrefix;
        } elseif ($type === null || $type === 'image') {
            $prefix = $this->relImagePrefix;
        } else {
            $prefix = $this->relLinkPrefix;
        }

        if ($prefix) {
            if (strpos($url, '/') === 0 || strpos($url, './') === 0 || strpos($url, '../') === 0 ||
                strpos($url, '#') === 0
            ) {
                return $url;
            }

            foreach ($this->url_schemes as $scheme) {
                if (strpos($url, $scheme . ':') === 0) {
                    return $url;
                }
            }

            return $prefix.$url;
        }

        return $url;
    }

    /**
     * Checks if an URL is relative.
     *
     * The given URL is considered relative if it
     * start anything other than with '//' or a
     * valid scheme.
     *
     * @param  string $url The URL
     * @return bool   TRUE if relative, FALSE otherwise
     */
    protected function isRelURL($url)
    {
        if (strpos($url, '//') === 0) {
            return false;
        }

        foreach ($this->url_schemes as $scheme) {
            if (strpos($url, $scheme . '://') === 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * Parses and shelves images in the given input.
     *
     * This method parses the input Textile document for images and
     * generates img HTML tags for each one found, caching the
     * generated img tag internally and replacing the Textile image with a
     * token to the cached tag.
     *
     * @param  string $text Textile input
     * @return string The input document with images pulled out and replaced with tokens
     */
    protected function images($text)
    {
        return (string)preg_replace_callback(
            '/
            (?:[[{])?                       # pre
            \!                              # opening !
            (?P<align>\<|\=|\>|&lt;|&gt;)?  # optional alignment
            (?P<atts>'.$this->cls.')        # optional attributes
            (?:\.\s)?                       # optional dot-space
            (?P<url>[^\s(!]+)               # presume this is the src
            \s?                             # optional space
            (?:\((?P<title>[^\)]+)\))?      # optional title
            \!                              # closing
            (?::(?P<href>\S+)(?<![\]).,]))? # optional href sans final punct
            (?:[\]}]|(?=[.,\s)|]|$))        # lookahead: space,.)| or end of string ("|" needed if image in table cell)
            /x'.$this->regex_snippets['mod'],
            array($this, "fImage"),
            $text
        );
    }

    /**
     * Checks that the given path is under the document root.
     *
     * @param  string $path Path to check
     * @return bool   TRUE if path is within the image document root
     * @see    Parser::images()
     * @since  3.6.0
     */
    protected function isInDocumentRootDirectory($path)
    {
        $realpath = realpath($path);

        if ($realpath) {
            $root = str_replace('\\', '/', $this->getDocumentRootDirectory());
            $realpath = str_replace('\\', '/', $realpath);
            return (0 === strpos($realpath, $root));
        }

        return false;
    }

    /**
     * Formats an image and stores it on the shelf.
     *
     * @param array<string|int, string> $m Options
     * @return string Reference token for the shelved content
     * @see Parser::images()
     */
    protected function fImage($m)
    {
        if (!$this->isValidUrl($m['url'])) {
            return $m[0];
        }

        $extras = '';
        $align = (isset($m['align'])) ? $m['align'] : '';
        $atts = $m['atts'];
        $url = $m['url'];
        $title = (isset($m['title'])) ? $m['title'] : '';
        $href = (isset($m['href'])) ? $m['href'] : '';

        $alignments = array(
            '<'    => 'left',
            '='    => 'center',
            '>'    => 'right',
            '&lt;' => 'left',
            '&gt;' => 'right',
        );

        if (isset($alignments[$align])) {
            if ($this->isAlignClassesEnabled()) {
                $extras = 'align-'.$alignments[$align];
                $align = '';
            } else {
                $align = $alignments[$align];
            }
        } else {
            $align = '';
        }

        if ($title) {
            $title = $this->encodeHTML($title);
        }

        $img = $this->newTag('img', $this->parseAttribsToArray($atts, '', true, $extras))
            ->align($align)
            ->alt($title, true)
            ->src($this->shelveURL($url, 'image'), true)
            ->title($title);

        if (!$this->dimensionless_images && $this->isRelUrl($url)) {
            $location = $this->getDocumentRootDirectory().ltrim($url, '\\/');
            $location_ok = $this->isInDocumentRootDirectory($location);
            if ($location_ok) {
                $real_location = realpath($location);
                if ($real_location && ($size = getimagesize($real_location))) {
                    $img->height($size[1])->width($size[0]);
                }
            }
        }

        $out = (string) $img;

        if ($href) {
            $href = $this->shelveURL($href);
            $link = $this->newTag('a', array(), false)->href($href)->rel($this->rel);
            $out = (string) $link . "$img</a>";
        }

        return $this->shelve($out);
    }

    /**
     * Parses code blocks in the given input.
     *
     * @param  string $text The input
     * @return string Processed text
     */
    protected function code($text)
    {
        $text = $this->doSpecial($text, '<code>', '</code>', 'fCode');
        $text = $this->doSpecial($text, '@', '@', 'fCode');
        $text = $this->doSpecial($text, '<pre>', '</pre>', 'fPre');
        return $text;
    }

    /**
     * Formats inline code tags.
     *
     * @param array<string|int, string> $m
     * @return string
     */
    protected function fCode($m)
    {
        $m = $this->getSpecialOptions($m);

        return $m['before'].$this->shelve('<code>'.$this->rEncodeHTML($m['content']).'</code>').$m['after'];
    }

    /**
     * Formats pre tags.
     *
     * @param array<string|int, string> $m Options
     * @return string
     */
    protected function fPre($m)
    {
        $m = $this->getSpecialOptions($m);

        return $m['before'].'<pre>'.$this->shelve($this->rEncodeHTML($m['content'])).'</pre>'.$m['after'];
    }

    /**
     * Shelves parsed content.
     *
     * Stores away a fragment of the source text that have been parsed
     * and requires no more processing.
     *
     * @param  string $val The content
     * @return string The fragment's unique reference ID
     * @see    Parser::retrieve()
     */
    protected function shelve($val)
    {
        $i = $this->uid.($this->refIndex++).':shelve';
        $this->shelf[$i] = $val;
        return $i;
    }

    /**
     * Replaces reference tokens with corresponding shelved content.
     *
     * This method puts all shelved content back to the final,
     * parsed input.
     *
     * @param  string $text The input
     * @return string Processed text
     * @see    Parser::shelve()
     */
    protected function retrieve($text)
    {
        if ($this->shelf) {
            do {
                $old = $text;
                $text = str_replace(array_keys($this->shelf), $this->shelf, $text);
            } while ($text != $old);
        }

        return $text;
    }

    /**
     * Removes BOM and unifies line ending in the given input.
     *
     * @param  string $text Input Textile
     * @return string Cleaned version of the input
     */
    protected function cleanWhiteSpace($text)
    {
        // Removes byte order mark.
        $out = (string)preg_replace("/^\xEF\xBB\xBF|\x1A/", '', $text);
        // Replaces CRLF and CR with single LF.
        $out = (string)preg_replace("/\r\n?/", "\n", $out);
        // Removes leading tabs and spaces, if the line is otherwise empty.
        $out = (string)preg_replace("/^[ \t]*\n/m", "\n", $out);
        // Removes leading and ending blank lines.
        $out = trim($out, "\n");
        return $out;
    }

    /**
     * Removes any unique tokens from the input.
     *
     * @param  string $text The input to clean
     * @return string Cleaned input
     * @since  3.5.5
     */
    protected function cleanUniqueTokens($text)
    {
        return str_replace($this->uid, '', $text);
    }

    /**
     * Uses the specified callback method to format the content between end and start nodes.
     *
     * @param  string $text   The input to format
     * @param  string $start  The start node to look for
     * @param  string $end    The end node to look for
     * @param  string $method The callback method
     * @return string Processed input
     */
    protected function doSpecial($text, $start, $end, $method)
    {
        return (string)preg_replace_callback(
            '/(?P<before>^|\s|[|[({>])'.
            preg_quote($start, '/').'(?P<content>.*?)'.preg_quote($end, '/').
            '(?<after>\]?)/ms',
            array($this, $method),
            $text
        );
    }

    /**
     * Gets an array of processed special options.
     *
     * @param array<string|int, string> $m Options
     * @return array<string|int, string>
     * @since 3.7.2
     */
    protected function getSpecialOptions($m)
    {
        foreach ($this->spanWrappers as $before => $after) {
            if ($m['before'] === $before && $m['after'] === $after) {
                $m['before'] = '';
                $m['after'] = '';
                break;
            }
        }

        return $m;
    }

    /**
     * Parses notextile tags in the given input.
     *
     * @param  string $text The input
     * @return string Processed input
     */
    protected function noTextile($text)
    {
        $text = $this->doSpecial($text, '<notextile>', '</notextile>', 'fTextile');
        return $this->doSpecial($text, '==', '==', 'fTextile');
    }

    /**
     * Format notextile blocks.
     *
     * @param array<string|int, string> $m Options
     * @return string
     */
    protected function fTextile($m)
    {
        $m = $this->getSpecialOptions($m);

        return $m['before'].$this->shelve($m['content']).$m['after'];
    }

    /**
     * Parses footnote reference links in the given input.
     *
     * This method replaces [n] instances with links.
     *
     * @param  string $text The input
     * @return string $text Processed input
     * @see    Parser::footnoteID()
     */
    protected function footnoteRefs($text)
    {
        return (string)preg_replace_callback(
            '/(?<=\S)\[(?P<id>'.$this->regex_snippets['digit'].'+)'.
            '(?P<nolink>!?)\]'.$this->regex_snippets['space'].'?/U'.$this->regex_snippets['mod'],
            array($this, 'footnoteID'),
            $text
        );
    }

    /**
     * Renders a footnote reference link or ID.
     *
     * @param array<string|int, string> $m Options
     * @return string Footnote link, or ID
     */
    protected function footnoteID($m)
    {
        $backref = ' class="footnote"';

        if (empty($this->fn[$m['id']])) {
            $this->fn[$m['id']] = $id = $this->linkPrefix . ($this->linkIndex++);
            $backref .= " id=\"fnrev$id\"";
        }

        $fnid = $this->fn[$m['id']];
        $footref = ('!' == $m['nolink']) ? $m['id'] : '<a href="#fn'.$fnid.'">'.$m['id'].'</a>';
        $footref = $this->formatFootnote($footref, $backref, false);

        return $footref;
    }

    /**
     * Parses and shelves quoted quotes in the given input.
     *
     * @param  string $text The text to search for quoted quotes
     * @param  string $find Pattern to search
     * @return string
     * @since  3.5.4
     */
    protected function glyphQuotedQuote($text, $find = '"?|"[^"]+"')
    {
        return (string)preg_replace_callback(
            "/ (?P<pre>{$this->quote_starts})(?P<quoted>$find)(?P<post>.) /".$this->regex_snippets['mod'],
            array($this, "fGlyphQuotedQuote"),
            $text
        );
    }

    /**
     * Formats quoted quotes and stores it on the shelf.
     *
     * @param array<string|int, string> $m Named regular expression parts
     * @return string Input with quoted quotes removed and replaced with tokens
     * @see Parser::glyphQuotedQuote()
     */
    protected function fGlyphQuotedQuote($m)
    {
        // Check the correct closing character was found.
        if (!isset($this->quotes[$m['pre']]) || $m['post'] !== $this->quotes[$m['pre']]) {
            return $m[0];
        }

        $pre = strtr($m['pre'], array(
            '"' => '&#8220;',
            "'" => '&#8216;',
            ' ' => '&nbsp;',
        ));

        $post = strtr($m['post'], array(
            '"' => '&#8221;',
            "'" => '&#8217;',
            ' ' => '&nbsp;',
        ));

        $found = $m['quoted'];

        if (strlen($found) > 1) {
            $found = rtrim($this->glyphs($m['quoted']));
        } elseif ('"' === $found) {
            $found = "&quot;";
        }

        return $this->shelve(' '.$pre.$found.$post.' ');
    }

    /**
     * Replaces glyphs in the given input.
     *
     * This method performs typographical glyph replacements. The input is split
     * across HTML-like tags in order to avoid attempting glyph
     * replacements within tags.
     *
     * @param  string $text Input Textile
     * @return string
     */
    protected function glyphs($text)
    {
        if (!$this->glyph_search) {
            return $text;
        }

        // Fix: hackish -- adds a space if final char of text is a double quote.
        if (($text = preg_replace('/"\z/', "\" ", $text)) === null) {
            return '';
        }

        $text = preg_split(
            "@(<[\w/!?].*>)@Us".$this->regex_snippets['mod'],
            $text,
            -1,
            PREG_SPLIT_DELIM_CAPTURE
        );

        if ($text === false) {
            return '';
        }

        $i = 0;
        $glyph_out = array();

        foreach ($text as $line) {
            // Text tag text tag text ...
            if (++$i % 2) {
                // Raw < > & chars are already entity encoded in restricted mode
                if (!$this->isRestrictedModeEnabled()) {
                    $line = preg_replace('/&(?!#?[a-z0-9]+;)/i', '&amp;', $line);
                    $line = str_replace(array('<', '>'), array('&lt;', '&gt;'), (string)$line);
                }

                $line = preg_replace($this->glyph_search, $this->glyph_replace, $line);
            }

            $glyph_out[] = $line;
        }

        return join('', $glyph_out);
    }

    /**
     * Replaces glyph references in the given input.
     *
     * This method removes temporary glyph: instances
     * from the input.
     *
     * @param  string $text The input
     * @return string Processed input
     */
    protected function replaceGlyphs($text)
    {
        return str_replace($this->uid.':glyph:', '', $text);
    }

    /**
     * Translates alignment tag into corresponding CSS text-align property value.
     *
     * @param  string $in The Textile alignment tag
     * @return string CSS text-align value
     */
    protected function hAlign($in)
    {
        $vals = array(
            '&lt;'     => 'left',
            '&gt;'     => 'right',
            '&lt;&gt;' => 'justify',
            '<'        => 'left',
            '='        => 'center',
            '>'        => 'right',
            '<>'       => 'justify',
        );

        return (isset($vals[$in])) ? $vals[$in] : '';
    }

    /**
     * Translates vertical alignment tag into corresponding CSS vertical-align property value.
     *
     * @param  string $in The Textile alignment tag
     * @return string CSS vertical-align value
     */
    protected function vAlign($in)
    {
        $vals = array(
            '^' => 'top',
            '-' => 'middle',
            '~' => 'bottom',
        );

        return (isset($vals[$in])) ? $vals[$in] : '';
    }

    /**
     * Converts character codes in the given input from HTML numeric character reference to character code.
     *
     * Conversion is done according to Textile's multi-byte conversion map.
     *
     * @param  string $text    The input
     * @param  string $charset The character set
     * @return string Processed input
     */
    protected function encodeHigh($text, $charset = 'UTF-8')
    {
        if ($this->isMultiByteStringSupported()) {
            return mb_encode_numericentity($text, $this->cmap, $charset);
        }

        return htmlentities($text, ENT_NOQUOTES, $charset);
    }

    /**
     * Converts numeric HTML character references to character code.
     *
     * @param  string $text    The input
     * @param  string $charset The character set
     * @return string Processed input
     */
    protected function decodeHigh($text, $charset = 'UTF-8')
    {
        $text = (string) intval($text) === (string) $text ? "&#$text;" : "&$text;";

        if ($this->isMultiByteStringSupported()) {
            return mb_decode_numericentity($text, $this->cmap, $charset);
        }

        return html_entity_decode($text, ENT_NOQUOTES, $charset);
    }

    /**
     * Convert special characters to HTML entities.
     *
     * This method's functionality is identical to PHP's own
     * htmlspecialchars(). In Textile this is used for sanitising
     * the input.
     *
     * @param  string $str    The string to encode
     * @param  bool   $quotes Encode quotes
     * @return string Encoded string
     * @see    htmlspecialchars()
     */
    protected function encodeHTML($str, $quotes = true)
    {
        $a = array(
            '&' => '&amp;',
            '<' => '&lt;',
            '>' => '&gt;',
        );

        if ($quotes) {
            $a = $a + array(
                "'" => '&#39;', // Numeric, as in htmlspecialchars
                '"' => '&quot;',
            );
        }

        return str_replace(array_keys($a), $a, $str);
    }

    /**
     * Convert special characters to HTML entities.
     *
     * This is identical to encodeHTML(), but takes restricted
     * mode into account. When in restricted mode, only escapes
     * quotes.
     *
     * @param  string $str    The string to encode
     * @param  bool   $quotes Encode quotes
     * @return string Encoded string
     * @see    Parser::encodeHTML()
     */
    protected function rEncodeHTML($str, $quotes = true)
    {
        // In restricted mode, all input but quotes has already been escaped
        if ($this->isRestrictedModeEnabled()) {
            return str_replace('"', '&quot;', $str);
        }

        return $this->encodeHTML($str, $quotes);
    }

    /**
     * Whether multiple mbstring extensions is loaded.
     *
     * @return bool
     * @since  3.5.5
     */
    protected function isMultiByteStringSupported()
    {
        if ($this->mb === null) {
            $this->mb = is_callable('mb_strlen');
        }

        return $this->mb;
    }

    /**
     * Whether PCRE supports UTF-8.
     *
     * @return bool
     * @since  3.5.5
     */
    protected function isUnicodePcreSupported()
    {
        return (bool) @preg_match('/\pL/u', 'a');
    }
}
