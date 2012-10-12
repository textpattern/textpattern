<?php

	if (!defined('txpinterface')) die('txpinterface is undefined.');

	// TO-DO:
	// * Improve performance of file imports
	// * Test a php_ini format for blogger exports
	// * Provide an Export option
	// * Write best help

	//Keep error display until we add an error handler for this
	error_reporting(E_ALL);
	@ini_set("display_errors","1");

	require_privs('import');

	$vars = array(
		'import_tool',
		'import_section',
		'import_status',
		'import_comments_invite',
		'import_blog_id',
		'importdb',
		'importdblogin',
		'importdbpass',
		'importdbhost',
		'wpdbprefix',
		'wpdbcharset'
	);

	// Add new tools here.
	// First: Array key must be the end of the import tool file name;
	// that is import_[key].php.
	// Then: Add the specific tool call on start_import switch statement
	$tools = array(
		''=>'',
		'mt'=>'Movable Type (File)',
		'mtdb'=>'Movable Type (MySQL DB)',
		'blogger'=>'Blogger',
		'b2' => 'b2',
		'wp'=>'WordPress'
	);

	if(!$step or !bouncer($step, array('switch_tool' => false, 'start_import' => true))) {
		$step = 'switch_tool';
	}
	$step();

// -------------------------------------------------------------
// Select the tool we want to import from and provide required data
	function switch_tool(){

		global $vars,$event,$step,$tools;
		extract(gpsa($vars));
		pagetop(gTxt('txp_import'), '');
		echo '<h1 class="txp-heading">'.gTxt('tab_import').'</h1>';

?>

<script>
<!--//
function showHideFields($sel)
{
	if(document.getElementById){
		document.getElementById('mtblogid').style.display = ($sel=='mtdb') ? 'block': 'none';
		document.getElementById('wponly').style.display =  ($sel=='wp') ? 'block': 'none';
		document.getElementById('databased').style.display = ($sel=='wp' || $sel=='mtdb' || $sel=='b2')? 'block':'none';
	}
}
//-->
</script>

<?php
		$content = '<div class="txp-edit">';
		$content.= hed(gTxt('txp_import'), 2);

		//Select tool
		$content.= inputLabel('import_from', tag(type_options($tools), 'select', ' id="import_from" name="import_tool" onchange="showHideFields(this.value);"'), 'select_tool', 'import');

		//Some data we collect
		$content.= inputLabel('import_section', import_section_popup(''), 'import_section', 'import_section');

		$status_options = array(
			STATUS_LIVE    => gTxt('live'),
			STATUS_DRAFT   => gTxt('draft'),
			STATUS_HIDDEN  => gTxt('hidden'),
			STATUS_PENDING => gTxt('pending')
		);

		$content.= inputLabel('import_status', tag(type_options($status_options), 'select', ' id="import_status"'), 'import_status', 'import_status');
		$content.= inputLabel('import_comment', fInput('text', 'import_comments_invite', gTxt('comments'), '', '', '', INPUT_REGULAR, '', 'import_comment'), 'import_invite', 'import_invite');

		//DataBase imports only
		$databased =
			hed(gTxt('database_stuff'), 2).
			inputLabel('import_database', fInput('text', 'importdb', '', '', '', '', INPUT_REGULAR, '', 'import_database'), 'import_database', 'import_database').
			inputLabel('import_login', fInput('text', 'importdblogin', '', '', '', '', INPUT_REGULAR, '', 'import_login'), 'import_login', 'import_login').
			inputLabel('import_password', fInput('text', 'importdbpass', '', '', '', '', INPUT_REGULAR, '', 'import_password'), 'import_password', 'import_password').
			inputLabel('import_host', fInput('text', 'importdbhost', '', '', '', '', INPUT_REGULAR, '', 'import_host'), 'import_host', 'import_host');

		$content.= tag($databased, 'div', ' id="databased" style="display: none;"');

		//MT-DB Specific
		$mtblogid = inputLabel('import_blogid', fInput('text', 'import_blog_id', '', '', '', '', INPUT_REGULAR, '', 'import_blogid'), 'import_blogid', 'import_blogid');
		$content.= tag($mtblogid, 'div', ' id="mtblogid" style="display: none;"');

		//WordPress specific option
		$wponly = inputLabel('import_wpprefix', fInput('text', 'wpdbprefix', 'wp_', '', '', '', INPUT_REGULAR, '', 'import_wpprefix'), 'import_wpprefix', 'import_wpprefix').
			inputLabel('import_wpdbcharset', selectInput('wpdbcharset', array('utf8' => gTxt('utf8'), 'latin1' => gTxt('latin1')), 'utf8', '', '', 'import_wpdbcharset'), 'import_wpdbcharset', 'import_wpdbcharset');

		$content.= tag($wponly, 'div', ' id="wponly" style="display: none;"');
		$content.= graf(fInput('submit', 'choose', gTxt('continue'), 'publish'));
		$content.= sInput('start_import').eInput('import');
		$content.= '</div>';
		echo '<div id="'.$event.'_container" class="txp-container">'.
			form($content, '', '', 'post', '', '', 'import').
			'</div>';
	}


// ------------------------------------------------------------
//Pre import tasks, then call the import funtion
	function start_import()
	{
		global $event,$vars;
		extract(psa($vars));

		$insert_into_section = $import_section;
		$insert_with_status = $import_status;
		$default_comment_invite = $import_comments_invite;
		include_once txpath.'/include/import/import_'.$import_tool.'.php';

		$ini_time = ini_get('max_execution_time');

		@ini_set('max_execution_time', 300 + intval($ini_time) );

		switch ($import_tool)
		{
			case 'mtdb':
				$out = doImportMTDB($importdblogin, $importdb, $importdbpass, $importdbhost, $import_blog_id, $insert_into_section, $insert_with_status, $default_comment_invite);
				rebuild_tree('root',1,'article');
			break;
			case 'mt':
				$file = check_import_file();
				if (!empty($file)){
					$out = doImportMT($file, $insert_into_section, $insert_with_status, $import_comments_invite);
					//Rebuilding category tree
					rebuild_tree('root',1,'article');
				}else{
					$out = 'Import file not found';
				}
			break;
			case 'b2':
				$out = doImportB2($importdblogin, $importdb, $importdbpass, $importdbhost, $insert_into_section, $insert_with_status, $default_comment_invite);
			break;
			case 'wp':
				$out = doImportWP($importdblogin, $importdb, $importdbpass, $importdbhost, $wpdbprefix, $insert_into_section, $insert_with_status, $default_comment_invite, $wpdbcharset);
				rebuild_tree('root',1,'article');
			break;
			case 'blogger':
				$file = check_import_file();
				if (!empty($file)){
					$out = doImportBLOGGER($file, $insert_into_section, $insert_with_status, $import_comments_invite);
				}else{
					$out = gTxt('import_file_not_found');
				}
			break;
		}

		$out = tag('max_execution_time = '.ini_get('max_execution_time'),'p', ' class="highlight"').$out;
		pagetop(gTxt('txp_import'));

		$content= '<div id="'.$event.'_container" class="txp-container">';
		$content.= startTable('', '', 'txp-list');
		$content.= tr(tdcs(hed(gTxt('txp_import'),2),2));
		$content.= tr(td($out));
		$content.= endTable();
		$content.= '</div>';
		echo $content;

		$rs = safe_rows_start('parentid, count(*) as thecount','txp_discuss','visible=1 group by parentid');
		if (mysql_num_rows($rs) > 0)
        	while ($a = nextRow($rs))
                safe_update('textpattern',"comments_count=".$a['thecount'],"ID=".$a['parentid']);
	}


// -------------------------------------------------------------
//checks the existence of a file called import.txt on the import dir.
// Used when importing from a file
	function check_import_file()
	{
		//Check here file size too. And explain how to split the file if
		//size is too long and time_limit can not be altered

		$import_file = txpath.'/include/import/import.txt';
		if (!is_file($import_file)) {
			// trigger_error('Import file not found', E_USER_WARNING);
			return '';
		}
		return $import_file;
	}

// -------------------------------------------------------------
// from Dean import
// add slashes to $in, no matter if is a single

	function array_slash($in)
	{
		return is_array($in) ? array_map('addslashes',$in) : addslashes($in);
	}


// -----------------------------------------------------------------
// Some cut and paste here
//--------------------------------------------------------------
// Display a popup of textpattern available sections

	function import_section_popup($Section)
	{
		$rs = safe_column("name", "txp_section", "name!='default'");
		if ($rs) {
			return selectInput("import_section", $rs, $Section, 1, '', 'import_section');
		}
		return false;
	}

?>
