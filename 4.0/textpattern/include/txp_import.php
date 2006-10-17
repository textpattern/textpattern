<?php

/*
$HeadURL$
$LastChangedRevision$
*/

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
 	
 	$vars = array('import_tool', 'Section','type','comments_invite','blog_id','importdb','importdblogin','importdbpass','importdbhost','wpdbprefix');
 	
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
 	

 	if(!$step or !in_array($step, array('switch_tool','start_import'))){
		switch_tool();
	} else $step();
 
// -------------------------------------------------------------
// Select the tool we want to import from and provide required data 
	function switch_tool(){
			
		global $vars,$step,$tools;
		extract(gpsa($vars));
		pagetop(gTxt('txp_import'), '');

?>

<script type="text/javascript">
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
		$content= startTable('edit'); 
		$content.= tr(tdcs(hed(gTxt('txp_import'),3),2));		
		//Select tool
		$content.= tr(
			fLabelCell ('select_tool','import').
			td(
				tag(type_options($tools),
				 'select',
				 " name=\"import_tool\" onchange=\"showHideFields(this.value);\"")
			)
		);
		
		
		//Some data we collect
		$content.= tr(
			fLabelCell ('import_section','import_section').
			td(import_section_popup(''))
			);

		$status_options = array(
				4 => gTxt('live'),
				1 => gTxt('draft'),
				2 => gTxt('hidden'),
				3 => gTxt('pending')
			);

		$content.= tr(
			fLabelCell ('import_status','import_status').
			td(type_select($status_options))
		);

		$content.= tr(
			fLabelCell ('import_invite','import_invite').
			td(fInput('text','comments_invite', gTxt('comments'),'edit'))
		);			

		//DataBase imports only

		$databased = 
		tr(tdcs(hed(gTxt('database_stuff'),3),2)).		
		tr(
			fLabelCell ('import_database','import_database').
			td(fInput('text','importdb', '','edit'))
		).
		tr(
			fLabelCell ('import_login','import_login').
			td(fInput('text','importdblogin', '','edit'))
		).		
		tr(
			fLabelCell ('import_password','import_password').
			td(fInput('text','importdbpass', '','edit'))
		).
		tr(
			fLabelCell ('import_host','import_host').
			td(fInput('text','importdbhost', '','edit'))
		);

		//Ugly, but a way to present a clean screen with only required fields
		//while we keep JavaScript code at minimum
		$content.= tr(tda(tag($databased, 'table', ' id="databased" style="display: none; border: none;"'),' colspan="2"'));
		//MT-DB Specific
		$mtblogid = tr(
			fLabelCell ('import_blogid','import_blogid').
			td(fInput('text','blog_id','','edit'))
		);
		$content.= tr(tda(tag($mtblogid, 'table', ' id="mtblogid" style="display: none;  border: none;"'),' colspan="2"'));
		//WordPress specific option
		$wponly = tr(
			fLabelCell ('import_wpprefix','import_wpprefix').
			td(fInput('text','wpdbprefix', 'wp_','edit'))
		);
		$content.= tr(tda(tag($wponly, 'table', ' id="wponly" style="display: none;  border: none;"'),' colspan="2"'));
				
		$content.= endTable();
		$content.= tag(fInput('submit','choose',gTxt('continue'),'publish'),'p',' style="text-align:center"');
		$content.= sInput('start_import').eInput('import');
		echo tag($content, 'form', ' id="import" action="index.php" method="post"');
	}	
	
	
// ------------------------------------------------------------
//Pre import tasks, then call the import funtion
	function start_import()
	{	
		global $vars;
		extract(psa($vars));
		
		$insert_into_section = $Section;
		$insert_with_status = $type;
		$default_comment_invite = $comments_invite;
		include_once txpath.'/include/import/import_'.$import_tool.'.php';
		
		$ini_time = ini_get('max_execution_time');
		
		@ini_set('max_execution_time', 300 + intval($ini_time) );
		
		switch ($import_tool)
		{
			case 'mtdb':
				$out = doImportMTDB($importdblogin, $importdb, $importdbpass, $importdbhost, $blog_id, $insert_into_section, $insert_with_status, $default_comment_invite);
				rebuild_tree('root',1,'article');
			break;
			case 'mt':
				$file = check_import_file();
				if (!empty($file)){
					$out = doImportMT($file, $insert_into_section, $insert_with_status, $comments_invite);
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
				$out = doImportWP($importdblogin, $importdb, $importdbpass, $importdbhost, $wpdbprefix, $insert_into_section, $insert_with_status, $default_comment_invite);
				rebuild_tree('root',1,'article');
			break;
			case 'blogger':
				$file = check_import_file();
				if (!empty($file)){
					$out = doImportBLOGGER($file, $insert_into_section, $insert_with_status, $comments_invite);
				}else{
					$out = gTxt('import_file_not_found');
				}
			break;
		}
		
		$out = tag('max_execution_time = '.ini_get('max_execution_time'),'p', ' style="color:red;"').$out;
		pagetop(gTxt('txp_import'));
		$content= startTable('list');
		$content.= tr(tdcs(hed(gTxt('txp_import'),3),2));
		$content.= tr(td($out));
		$content.= endTable();
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
// Display a popup of textpattern avaliable sections

	function import_section_popup($Section)
	{
		$rs = safe_column("name", "txp_section", "name!='default'");
		if ($rs) {
			return selectInput("Section", $rs, $Section, 1);
		}
		return false;
	}
	
?>
