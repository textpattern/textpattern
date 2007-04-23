<?php

/*
$HeadURL$
$LastChangedRevision$
*/

/**
 * Textpattern Wrapper Class for Textpattern 4.0.x
 * 
 * Main goal for this class is to be used as a textpattern data wrapper by
 * any code which needs to have access to the textpattern articles data,
 * like XML-RPC, Atom, Moblogging or other external implementations.
 * 
 * @link http://txp.kusor.com/wrapper
 * @author Pedro Palazon - http://kusor.net/
 * @copyright 2005-2006 The Textpattern Development Team - http://textpattern.com
 */

# This class requires to include some Textpattern files in order to work properly.
# See RPC Server implementation to view an example of the required files and predefined variables.

include_once txpath.'/include/txp_auth.php';
# Include constants.php?
if (!defined('LEAVE_TEXT_UNTOUCHED')) define('LEAVE_TEXT_UNTOUCHED', 0);
if (!defined('USE_TEXTILE')) define('USE_TEXTILE', 1);
if (!defined('CONVERT_LINEBREAKS')) define('CONVERT_LINEBREAKS', 2);

class TXP_Wrapper
{
	/**
	 * @var string The current user
	 * 
	 * Remeber to use allways $this->txp_user when checking for permissions with this class
	 */	
	var $txp_user = null;
	/**
	 * @var boolean Is the user authenticated
	 */	
	var $loggedin = false;
	/**
	 * @var array Predefined Textpattern vars to be populated
	 */	
	var $vars = array(
		'ID','Title','Title_html','Body','Body_html','Excerpt','Excerpt_html','textile_excerpt','Image',
		'textile_body', 'Keywords','Status','Posted','Section','Category1','Category2',
		'Annotate','AnnotateInvite','AuthorID','Posted','override_form',
		'url_title','custom_1','custom_2','custom_3','custom_4','custom_5',
		'custom_6','custom_7','custom_8','custom_9','custom_10'
	);	
	
	//Class constructor
	/**
	 * Class constructor
	 * @param string $txp_user the user login name
	 * @param strign $txpass user password
	 *
	 * @see _validate
	 */
	function TXP_Wrapper($txp_user, $txpass = NULL)
	{
		if ($this->_validate($txp_user, $txpass))
		{
			$this->txp_user = $txp_user;
			$this->loggedin = true;
		}
	}
	
	//Delete the article given the id
	/**
	 * Delete the article given the id
	 * @param mixed(string|integer) $article_id the ID of the article to delete
	 * @return boolean true on success deletion
	 */
	function deleteArticleID($article_id)
	{
		$article_id = assert_int($article_id);
		if ($this->loggedin && has_privs('article.delete', $this->txp_user)) {
			return safe_delete('textpattern', "ID = $article_id");
		}
		elseif ($this->loggedin && has_privs('article.delete.own', $this->txp_user))
		{
			$r = safe_field('ID', 'textpattern', "ID = $article_id AND AuthorID='".doSlash($this->txp_user)."'");
			if ($r || has_privs('article.delete', $this->txp_user))
			{
				return safe_delete('textpattern', "ID = $article_id");
			}			
		}
		return false;
	}
	
	//Retrieves a list of articles matching the given criteria
	/**
	 * Retrieves a list of articles matching the given criteria
	 * @param string $what SQL column names to retrieve
	 * @param string $where SQL condition to match
	 * @param string $offset SQL offset
	 * @param string $limit SQL limit
	 * @return mixed array on success, false on failure	 	 	 
	 */
	function getArticleList($what='*', $where='1', $offset='0', $limit='10')
	{
		
		if ($this->loggedin && has_privs('article.edit.own', $this->txp_user))
		{
			$offset = assert_int($offset); 
			$limit = assert_int($limit);
			$where = doSlash($where);
			$what = doSlash($what);
			
			if (has_privs('article.edit', $this->txp_user)) {
				$rs = safe_rows_start($what, 'textpattern', $where." order by Posted desc LIMIT $offset, $limit");
			}else{
				$rs = safe_rows_start($what, 'textpattern', $where." AND AuthorID='".doSlash($this->txp_user)."' order by Posted desc LIMIT $offset, $limit");
			}
			$out = array();
			if ($rs)
			{
				while ($a = nextRow($rs))
				{
					$out[]= $a;
				}
			}
			return $out;
		}
		return false;
	}
	
	//Retrieves an article matching the given criteria
	/**
	 * Retrieves an article matching the given criteria
	 * @param string $what SQL column names to retrieve
	 * @param string $where SQL condition to match	 
	 * @return mixed array on success, false on failure	 
	 */	
	function getArticle($what='*', $where='1')
	{
		if ($this->loggedin && has_privs('article.edit.own', $this->txp_user))
		{
			// Higer user groups should be able to edit any article
			if (has_privs('article.edit', $this->txp_user)) {
				return safe_row(doSlash($what), 'textpattern', doSlash($where));
			}else {
				// While restricted users should be able to edit their own articles only
				return safe_row(doSlash($what), 'textpattern', doSlash($where)." AND AuthorID='".doSlash($this->txp_user)."'");
			}			
		}
		return false;
	}
	
	//Same thing, but handy shortcut known the ID
	/**
	 * Same thing, but handy shortcut known the ID
	 * @param mixed(string|integer) $article_id the ID of the article
	 * @param string $what SQL column names to retrieve	 
	 * @return mixed array on success, false on failure	 
	 */	
	function getArticleID($article_id, $what='*')
	{
		if ($this->loggedin && has_privs('article.edit.own', $this->txp_user))
		{
			$article_id = assert_int($article_id);
			if (has_privs('article.edit', $this->txp_user)) {
				return safe_row(doSlash($what), 'textpattern', "ID = $article_id");
			}else{
				return safe_row(doSlash($what), 'textpattern', "ID = $article_id AND AuthorID='".doSlash($this->txp_user)."'");
			}
		}
		return false;
	}
	
	//Updates an existing article
	/**
	 * Updates an existing article
	 * @param array $params the article fields to update
	 * @param mixed(string|integer) $article_id the ID of the article to update 
	 * @return mixed integer article id on success, false on failure	 
	 * @see _setArticle
	 */	
	function updateArticleID($article_id, $params)
	{
		$article_id = assert_int($article_id);

		$r = safe_field('ID', 'textpattern', "AuthorID='".doSlash($this->txp_user)."' AND ID = $article_id");
		
		if ($this->loggedin && $r && has_privs('article.edit.own', $this->txp_user))
		{	//Unprivileged user
			//Check if can edit published arts
			$r = assert_int($r);
			$oldstatus = safe_field('Status', 'textpattern', "ID = $r");
			if (($oldstatus=='4' || $oldstatus == '5') && !has_privs('article.edit.published', $this->txp_user)) return false;
			//If can, let's go
			return $this->_setArticle($params, $article_id);			
		}
		elseif ($this->loggedin && has_privs('article.edit', $this->txp_user))
		{//Admin editing. Desires are behest.
			return $this->_setArticle($params, $article_id);
		}
		
		return false;
	}
	
	//Creates a new article
	/**
	 * Creates a new article
	 * @param array $params the article fields	 
	 * @return mixed integer article id on success, false on failure
	 * @see _setArticle
	 */	
	function newArticle($params)
	{
		if ($this->loggedin && has_privs('article', $this->txp_user))
		{
			//Prevent junior authors to publish articles
			if (($params['Status']=='4' || $params['Status']=='5') && !has_privs('article.publish', $this->txp_user))
			{				
				$params['Status']='3';
			}
			
			return $this->_setArticle($params);
		}
		return false;
	}
	
	//Get full sections information
	/**
	 * Get full sections information
	 * @return mixed array on success, false on failure	 	 
	 */	
	function getSectionsList()
	{
		if ($this->loggedin && has_privs('article', $this->txp_user))
		{
			return safe_rows('*', 'txp_section',"name!='default'");
		}
		return false;
	}
	
	//Get one section
	/**
	 * Get one section
	 * @param string $name the section name
	 * @return mixed array on success, false on failure	 	 
	 */	
	function getSection($name)
	{
		if ($this->loggedin && has_privs('article', $this->txp_user))
		{
			$name = doSlash($name);
			return safe_row('*', 'txp_section',"name='$name'");
		}
		return false;
	}
	
	//Get full categories information
	/**
	 * Get full categories information
	 * @return mixed array on success, false on failure	 	 
	 */	
	function getCategoryList()
	{
		if ($this->loggedin && has_privs('article', $this->txp_user))
		{
			return safe_rows('*', 'txp_category',"name!='root' AND type='article'");
		}
		return false;
	}
	/**
	 * Get one category
	 * @param string $name the category name
	 * @return mixed array on success, false on failure	 	 
	 */	
	function getCategory($name)
	{
		if ($this->loggedin && has_privs('article', $this->txp_user))
		{
			$name = doSlash($name);
			return safe_row('*', 'txp_category',"name='$name' AND type='article'");
		}
		return false;
	}
	/**
	 * Same thing, but using category id
	 * @param mixed(string|integer) $id category id	 
	 * @return mixed array on success, false on failure	 	 
	 */	
	function getCategoryID($id)
	{
		if ($this->loggedin && has_privs('article', $this->txp_user))
		{
			$id = assert_int($id);
			return safe_row('*', 'txp_category',"id = $id");
		}
		return false;
	}
	//Get full information for current user
	/**
	 * Get full information for current user
	 * @return mixed array on success, false on failure	 	 
	 */	
	function getUser()
	{
		if ($this->loggedin)
		{
			return safe_row('*', 'txp_users',"name='$this->txp_user'");
		}
		return false;
	}
	
	//Retrieves a template with the given name
	/**
	 * Retrieves a template with the given name
	 * @param string $name the template name
	 */	
	function getTemplate($name)
	{
		if ($this->loggedin && has_privs('page', $this->txp_user))
		{
			$name = doSlash($name);
			return safe_field('user_html', 'txp_page', "name='$name'");
		}
		return false;
	}
	//Updates a template with the given name
	/**
	 * Updates a template with the given name
	 * @param string $name the template name
	 * @param string $html the template contents
	 * @return boolean true on success	 
	 */	
	function setTemplate($name, $html)
	{
		if ($this->loggedin && has_privs('page', $this->txp_user))
		{
			$name = doSlash($name);
			$html = doSlash($html);
			return safe_update('txp_page', "user_html='$html'", "name='$name'");
		}
	}
	
	// Intended to update article non content fields, like categories
	// section or Keywords
	/**
	 * Intended to update article non content fields, like categories, section or Keywords
	 * @param mixed(string|integer) $article_id the ID of the article to update	 
	 * @param string $field the name of the field to update
	 * @param mixed $value desired value for that field
	 * @return boolean true on success
	 */	
	function updateArticleField($article_id, $field, $value)
	{
		$disallow = array('Body','Body_html','Title','Title_html','Excerpt',
					'Excerpt_html','textile_excerpt','textile_body','LastMod',
					'LastModID', 'feed_time', 'uid');
		if ($this->loggedin && has_privs('article.edit', $this->txp_user) && !in_array(doSlash($field),$disallow))
		{
			$field = doSlash($field);
			$value = doSlash($value);
			
			if($field == 'Posted') 
			{
				$value = strtotime($value)-tz_offset();
				$value = "from_unixtime($value)";
				$sql = "Posted = $value";
			}elseif ($field == 'Status'){
				$value = assert_int($value);
				if (!has_privs('article.publish', $this->txp_user) && $value >=4) $value = 3;
				$sql = "Status = $value";
			}else{
				$sql = "$field='$value'";
			}
			
			
			$sql.= ", LastMod = now(),
					LastModID = '$this->txp_user'";
			$article_id = assert_int($article_id);
			$rs = safe_update('textpattern', $sql, "ID = $article_id");
			//Do not update lastmod pref here. No new content at all.
			return $rs;
		}
		return false; 
	}	

// -------------------------------------------------------------
// Private. Real action takes place here.
// -------------------------------------------------------------
	/**
	 * Executes the real action for @see udpateArticleId and @see newArticle
	 * @param array $incoming containing the desired article fields
	 * @param mixed(string|integer) $article_id the ID of the article to update
	 * @return mixed integer article id on success, false otherwise
	 * @access private	 
	 */
	function _setArticle($incoming, $article_id = null)
	{
		global $txpcfg;
		
		$prefs = get_prefs();
		
		extract($prefs);
		
		if ($article_id!==null) {
			$article_id = assert_int($article_id);
		}
		
		//All validation rules assumed to be passed before this point.
		//Do content processing here
		
		$incoming = $this->_check_keys($incoming, 
			array(
				'AuthorID' => $this->txp_user,
				'Annotate' => $comments_on_default,
				'AnnotateInvite' => $comments_default_invite,
				'textile_body' => $use_textile,
				'textile_excerpt' => $use_textile
			)
		);
		
		
		$incoming_with_markup = $this->textile_main_fields($incoming, $use_textile);
		
		$incoming['Title'] = $incoming_with_markup['Title'];
		
		if (empty($incoming['Body_html']) && !empty($incoming['Body']))
		{			
			$incoming['Body_html'] = $incoming_with_markup['Body_html'];
		}

		if (empty($incoming['Excerpt_html']) && !empty($incoming['Excerpt']))
		{
				$incoming['Excerpt_html'] = $incoming_with_markup['Excerpt_html'];
		}
		
		unset($incoming_with_markup);

		if (empty($incoming['Posted'])) {
			if ($article_id===null) {
				$when = (!$article_id)? 'now()': '';
				$incoming['Posted'] = $when;
			}else{
				# do not override post time for existing articles unless Posted is present
				unset($incoming['Posted']);
			}
		} else {
			$when = strtotime($incoming['Posted'])-tz_offset();
			$when = "from_unixtime($when)";
		}
		

		if ($incoming['Title'] || $incoming['Body'] || $incoming['Excerpt']) {
			//Build SQL then and run query
			
			//Prevent data erase if not defined on the update action
			//but it was on the DB from a previous creation/edition time
			if ($article_id){
				
				$old = safe_row('*','textpattern', "ID = $article_id");
				//Status should be defined previously. Be sure of that.
				if (!has_privs('article.publish', $this->txp_user) && $incoming['Status']==4 && $old['Status']!=4) $incoming['Status'] = 3;
				
				foreach ($old as $key=>$val)
				{
					 if (!isset($incoming[$key])) $incoming[$key] = $val;
				}								
				
			}else{				

				if (empty($incoming['url_title'])) $incoming['url_title'] = stripSpace($incoming['Title']);
				//Status should be defined previously. Be sure of that.				
				if (!has_privs('article.publish', $this->txp_user) && $incoming['Status']==4) $incoming['Status'] = 3;
			}
			
			if (empty($incoming['Section']) && $article_id)
			{
				$incoming['Section'] = safe_field('Section','textpattern',"ID = $article_id");
			}
			
			//Build the SQL query
			$sql = array();
			
			foreach ($incoming as $key => $val)
			{
				if($key == 'Posted' && $val == 'now()')
				{
					$sql[]= "$key = $val";
				}elseif ($key!='ID' && $key!='uid' && $key!='feed_time' && $key!='LastMod' && $key!='LastModID')
				{
					$sql[]= "$key = '".doSlash($val)."'";
				}
			}
			$sql[]= 'LastMod = now()';
			$sql[]= "LastModID = '".doSlash($this->txp_user)."'";			
			if (!$article_id) $sql[]= "uid = '".doSlash(md5(uniqid(rand(),true)))."'";
			if (!$article_id)
			{
				if (empty($incoming['Posted']))
				{
					$sql[]= "feed_time = curdate()";
				}else{
					$when = strtotime($incoming['Posted'])-tz_offset();
					$when = strftime("%Y-%m-%d", $when);
					$sql[]= "feed_time ='".doSlash($when)."'";
				}
			}
			$sql = join(', ', $sql);			
			
			$rs = ($article_id)?
			   	safe_update('textpattern', $sql, "ID = $article_id"):
			   	safe_insert('textpattern', $sql);			   	
			   		   			   										   
		   $oldstatus = ($article_id)? $old['Status'] : '';
		   
		   if (!$article_id && $rs) $article_id = $rs;
		   
		   if (($incoming['Status']>=4 && !$article_id) || ($oldstatus!=4 && $article_id)) {	
				safe_update("txp_prefs", "val = now()", "name = 'lastmod'");
				//@$this->_sendPings();							
		   }			   
		   return $article_id;
		}
		
		return false;
	}
	
// -------------------------------------------------------------
// Private
// -------------------------------------------------------------

	/**
	 * Attemp to validates the user with the provided password
	 * or takes it from the global scope, assuming the user is logged in
	 * @param string $user login name of the user to validate
	 * @param string(optional) $password for that user
	 * @access private
	 * @return boolean, true if user is logged in
	 */
	function _validate($user,$password = NULL) {
    	
		if ($password!==NULL)
    	{			
	    	$r = txp_validate($user, $password);
    	}else{
    		$r = true;
    	}
    	if ($r) {
			// update the last access time
			$safe_user = addslashes($user);
			safe_update("txp_users", "last_access = now()", "name = '$safe_user'");
			return true;
    	}
		return false;
	}	
	
// -------------------------------------------------------------
// Keep this apart for now. Maybe future changes ob this?
// -------------------------------------------------------------
// This is duplicated code from txp_article.php too

	function _sendPings()
	{
		global $prefs, $txpcfg;
		extract($prefs);
		
		include_once txpath.'/lib/IXRClass.php';
					
		if ($ping_textpattern_com) {
			$tx_client = new IXR_Client('http://textpattern.com/xmlrpc/');
			$tx_client->query('ping.Textpattern', $sitename, hu);
		}

		if ($ping_weblogsdotcom==1) {
			$wl_client = new IXR_Client('http://rpc.pingomatic.com/');
			$wl_client->query('weblogUpdates.ping', $sitename, hu);
		}
	}

	/**
	 * Check if the given parameters are the appropiated ones for the articles
	 * @access private
	 * @param $incoming array the incoming associative array
	 * @param $default associative array containing default values for the desired keys
	 * @return array properly striped off the fields which don't match the defined ones.
	 */
	function _check_keys($incoming, $default = array())
	{				
		
		$out = array();
		# strip off unsuited keys
		foreach ($incoming as $key => $val)
		{
			if (in_array($key, $this->vars))
			{
				$out[$key] = $val;
			}
		}
		
		foreach ($this->vars as $def_key)
		{
			# Add those ones inexistent in the incoming array
			if (!array_key_exists($def_key,$out))
			{
				$out[$def_key] = '';
			}
			# setup the provided default value, if any, only when the incoming value is empty
			if (array_key_exists($def_key, $default) && $out[$def_key]!==0)
			{
				$out[$def_key] = $default[$def_key];
			}
		}
		return $out;		
	}
	
	/**
	 * Apply textile to the main article fields
	 * (duplicated from txp_article.php!)
	 * @param array containing the $incoming vars array
	 * @param global use_textile preference
	 * @return array the same one containing the formatted fields
	 */
	
	function textile_main_fields($incoming, $use_textile = 1)
	{
		global $txpcfg;
		
		include_once $txpcfg['txpath'].'/lib/classTextile.php';
		$textile = new Textile();
		
		if (!empty($event) and $event == 'article') 
		{
			$incoming['Title_plain'] = $incoming['Title'];
		}
		
		if ($incoming['textile_body'] == USE_TEXTILE) 
		{
			$incoming['Title'] = $textile->TextileThis($incoming['Title'],'',1);
		}
		
		
		$incoming['Body_html'] = TXP_Wrapper::format_field($incoming['Body'],$incoming['textile_body'],$textile);
		
		$incoming['Excerpt_html'] = TXP_Wrapper::format_field($incoming['Excerpt'],$incoming['textile_excerpt'],$textile);
		
		return $incoming;
	}

	# Try to avoid code duplication when formating fields.
	/**
	 * Apply markup to a given fields
	 *
	 * @param string $field raw field contents
	 * @param integer $format format type to apply
	 * @param object $textile instance
	 * @return string html formated field
	 */
	
	function format_field($field, $format,$textile)
	{
		switch ($format){
			case LEAVE_TEXT_UNTOUCHED: $html = trim($field); break;
			case CONVERT_LINEBREAKS: $html = nl2br(trim($field)); break;
			case USE_TEXTILE:
				$html = $textile->TextileThis($field);
			break;
		}
		return $html;
	}

}

?>
