<?php

/*
$HeadURL$
$LastChangedRevision$
*/

if (!defined('PFX')) {
	if (!empty($txpcfg['table_prefix'])) {
		define ("PFX",$txpcfg['table_prefix']);
	} else define ("PFX",'');
}

set_magic_quotes_runtime(0);

class DB {
    function DB() 
	{
		global $txpcfg;

		$this->host = $txpcfg['host'];
		$this->db   = $txpcfg['db'];
		$this->user = $txpcfg['user'];
		$this->pass = $txpcfg['pass'];

		$this->link = @mysql_connect($this->host, $this->user, $this->pass);
		if (!$this->link) die(db_down());

		$this->version = mysql_get_server_info();;

		if (!$this->link) {
			$GLOBALS['connected'] = false;
		} else $GLOBALS['connected'] = true;
		@mysql_select_db($this->db) or die(db_down());

		$version = $this->version;
		// be backwardscompatible
		if ( isset($txpcfg['dbcharset']) && (intval($version[0]) >= 5 || preg_match('#^4\.[1-9]#',$version)) )
			mysql_query("SET NAMES ". $txpcfg['dbcharset']);
    }
}
$DB = new DB;

//-------------------------------------------------------------
	function safe_pfx($table) {
		$name = PFX.$table;
		if (preg_match('@[^\w._$]@', $name))
			return '`'.$name.'`';
		return $name;
	}

//-------------------------------------------------------------
	function safe_pfx_j($table)
	{
		$ts = array();
		foreach (explode(',', $table) as $t) {
			$name = PFX.trim($t);
			if (preg_match('@[^\w._$]@', $name))
				$ts[] = "`$name`".(PFX ? " as `$t`" : '');
			else
				$ts[] = "$name".(PFX ? " as $t" : '');
		}
		return join(', ', $ts);
	}

//-------------------------------------------------------------
	function safe_query($q='',$debug='',$unbuf='')
	{
		global $DB,$txpcfg, $qcount, $qtime, $production_status;
		$method = (!$unbuf) ? 'mysql_query' : 'mysql_unbuffered_query';
		if (!$q) return false;
		if ($debug or TXP_DEBUG === 1) { 
			dmp($q);
			dmp(mysql_error());
//			dmp(debug_backtrace());
		}
		$start = getmicrotime();
		$result = $method($q,$DB->link);
		$time = sprintf('%02.6f', getmicrotime() - $start);
		@$qtime += $time;
		@$qcount++;
		if ($result === false and (@$production_status == 'debug' or @$production_status == 'test')) {
			$caller = ($production_status == 'test') ? n . join("\n", get_caller()) : '';
			trigger_error(mysql_error() . n . $q . $caller, E_USER_WARNING);
		}

		trace_add("[SQL ($time): $q]");

		if(!$result) return false;
		return $result;
	}

// -------------------------------------------------------------
	function safe_delete($table, $where, $debug='')
	{
		$q = "delete from ".safe_pfx($table)." where $where";
		if ($r = safe_query($q,$debug)) {
			return true;
		}
		return false;
	}

// -------------------------------------------------------------
	function safe_update($table, $set, $where, $debug='') 
	{
		$q = "update ".safe_pfx($table)." set $set where $where";
		if ($r = safe_query($q,$debug)) {
			return true;
		}
		return false;
	}

// -------------------------------------------------------------
	function safe_insert($table,$set,$debug='') 
	{
		global $DB;
		$q = "insert into ".safe_pfx($table)." set $set";
		if ($r = safe_query($q,$debug)) {
			$id = mysql_insert_id($DB->link);
			return ($id === 0 ? true : $id);
		}
		return false;
	}

// -------------------------------------------------------------
// insert or update
	function safe_upsert($table,$set,$where,$debug='') 
	{
		// FIXME: lock the table so this is atomic?
		$r = safe_update($table, $set, $where, $debug);
		if ($r and mysql_affected_rows())
			return $r;
		else
			return safe_insert($table, join(', ', array($where, $set)), $debug);
	}

// -------------------------------------------------------------
	function safe_alter($table, $alter, $debug='') 
	{
		$q = "alter table ".safe_pfx($table)." $alter";
		if ($r = safe_query($q,$debug)) {
			return true;
		}
		return false;
	}

// -------------------------------------------------------------
	function safe_optimize($table, $debug='') 
	{
		$q = "optimize table ".safe_pfx($table)."";
		if ($r = safe_query($q,$debug)) {
			return true;
		}
		return false;
	}

// -------------------------------------------------------------
	function safe_repair($table, $debug='') 
	{
		$q = "repair table ".safe_pfx($table)."";
		if ($r = safe_query($q,$debug)) {
			return true;
		}
		return false;
	}

// -------------------------------------------------------------
	function safe_field($thing, $table, $where, $debug='') 
	{
		$q = "select $thing from ".safe_pfx_j($table)." where $where";
		$r = safe_query($q,$debug);
		if (@mysql_num_rows($r) > 0) {
			$f = mysql_result($r,0);
			mysql_free_result($r);
			return $f;
		}
		return false;
	}

// -------------------------------------------------------------
	function safe_column($thing, $table, $where, $debug='') 
	{
		$q = "select $thing from ".safe_pfx_j($table)." where $where";
		$rs = getRows($q,$debug);
		if ($rs) {
			foreach($rs as $a) {
				$v = array_shift($a);
				$out[$v] = $v;
			}
			return $out;
		}
		return array();
	}

// -------------------------------------------------------------
	function safe_row($things, $table, $where, $debug='') 
	{
		$q = "select $things from ".safe_pfx_j($table)." where $where";
		$rs = getRow($q,$debug);
		if ($rs) {
			return $rs;
		}
		return array();
	}


// -------------------------------------------------------------
	function safe_rows($things, $table, $where, $debug='') 
	{
		$q = "select $things from ".safe_pfx_j($table)." where $where";
		$rs = getRows($q,$debug);
		if ($rs) {
			return $rs;
		}
		return array();
	}

// -------------------------------------------------------------
	function safe_rows_start($things, $table, $where, $debug='') 
	{
		$q = "select $things from ".safe_pfx_j($table)." where $where";
		return startRows($q,$debug);
	}

//-------------------------------------------------------------
	function safe_count($table, $where, $debug='') 
	{
		return getThing("select count(*) from ".safe_pfx_j($table)." where $where",$debug);
	}

// -------------------------------------------------------------
	function safe_show($thing, $table, $debug='') 
	{
		$q = "show $thing from ".safe_pfx($table)."";
		$rs = getRows($q,$debug);
		if ($rs) {
			return $rs;
		}
		return array();
	}


//-------------------------------------------------------------
	function fetch($col,$table,$key,$val,$debug='') 
	{
		$q = "select $col from ".safe_pfx($table)." where `$key` = '$val' limit 1";
		if ($r = safe_query($q,$debug)) {
			$thing = (mysql_num_rows($r) > 0) ? mysql_result($r,0) : '';
			mysql_free_result($r);
			return $thing;
		}
		return false;
	}

//-------------------------------------------------------------
	function getRow($query,$debug='') 
	{
		if ($r = safe_query($query,$debug)) {
			$row = (mysql_num_rows($r) > 0) ? mysql_fetch_assoc($r) : false;
			mysql_free_result($r);
			return $row;
		}
		return false;
	}

//-------------------------------------------------------------
	function getRows($query,$debug='') 
	{
		if ($r = safe_query($query,$debug)) {
			if (mysql_num_rows($r) > 0) {
				while ($a = mysql_fetch_assoc($r)) $out[] = $a; 
				mysql_free_result($r);
				return $out;
			}
		}
		return false;
	}

//-------------------------------------------------------------
	function startRows($query,$debug='')
	{
		return safe_query($query,$debug);
	}

//-------------------------------------------------------------
	function nextRow($r)
	{
		$row = mysql_fetch_assoc($r);
		if ($row === false)
			mysql_free_result($r);
		return $row;
	}

//-------------------------------------------------------------
	function numRows($r)
	{
		return mysql_num_rows($r);
	}

//-------------------------------------------------------------
	function getThing($query,$debug='') 
	{
		if ($r = safe_query($query,$debug)) {
			$thing = (mysql_num_rows($r) != 0) ? mysql_result($r,0) : '';
			mysql_free_result($r);
			return $thing;
		}
		return false;
	}

//-------------------------------------------------------------
	function getThings($query,$debug='') 
	// return values of one column from multiple rows in an num indexed array
	{
		$rs = getRows($query,$debug);
		if ($rs) {
			foreach($rs as $a) $out[] = array_shift($a);
			return $out;
		}
		return array();
	}
	
//-------------------------------------------------------------
	function getCount($table,$where,$debug='') 
	{
		return getThing("select count(*) from ".safe_pfx_j($table)." where $where",$debug);
	}

// -------------------------------------------------------------
 	function getTree($root, $type)
 	{ 

		$root = doSlash($root);
		$type = doSlash($type);

	   $rs = safe_row(
	    	"lft as l, rgt as r", 
	    	"txp_category", 
			"name='$root' and type = '$type'"
		);

		if (!$rs) return array();
		extract($rs);

		$out = array();
		$right = array(); 

	    $rs = safe_rows_start(
	    	"id, name, lft, rgt, parent, title", 
	    	"txp_category",
	    	"lft between $l and $r and type = '$type' order by lft asc"
		); 

	    while ($rs and $row = nextRow($rs)) {
	   		extract($row);
			while (count($right) > 0 && $right[count($right)-1] < $rgt) { 
				array_pop($right);
			}

        	$out[] = 
        		array(
        			'id' => $id,
        			'name' => $name,
        			'title' => $title,
        			'level' => count($right), 
        			'children' => ($rgt - $lft - 1) / 2
        		);

	        $right[] = $rgt; 
	    }
    	return($out);
 	}

// -------------------------------------------------------------
 	function getTreePath($target, $type)
 	{ 

	  	$rs = safe_row(
	    	"lft as l, rgt as r", 
	    	"txp_category", 
			"name='".doSlash($target)."' and type = '".doSlash($type)."'"
		);
		if (!$rs) return array();
		extract($rs);

	    $rs = safe_rows_start(
	    	"*", 
	    	"txp_category",
				"lft <= $l and rgt >= $r and type = '".doSlash($type)."' order by lft asc"
		); 

		$out = array();
		$right = array(); 

	    while ($rs and $row = nextRow($rs)) {
	   		extract($row);
			while (count($right) > 0 && $right[count($right)-1] < $rgt) { 
				array_pop($right);
			}

        	$out[] = 
        		array(
        			'id' => $id,
        			'name' => $name,
        			'title' => $title,
        			'level' => count($right), 
        			'children' => ($rgt - $lft - 1) / 2
        		);

	        $right[] = $rgt; 
	    }
		return $out;
	}

// -------------------------------------------------------------
	function rebuild_tree($parent, $left, $type) 
	{ 
		$right = $left+1;

		$parent = doSlash($parent);

		$result = safe_column("name", "txp_category", 
			"parent='$parent' and type='$type' order by name");
	
		foreach($result as $row) { 
    	    $right = rebuild_tree($row, $right, $type); 
	    } 

	    safe_update(
	    	"txp_category", 
	    	"lft=$left, rgt=$right",
	    	"name='$parent' and type='$type'"
	    );
    	return $right+1; 
 	} 

//-------------------------------------------------------------
	function get_prefs()
	{
		$r = safe_rows_start('name, val', 'txp_prefs', 'prefs_id=1');
		if ($r) {
			while ($a = nextRow($r)) {
				$out[$a['name']] = $a['val']; 
			}
			return $out;
		}
		return false;
	}

// -------------------------------------------------------------
	function db_down() 
	{
		// 503 status might discourage search engines from indexing or caching the error message
		header('Status: 503 Service Unavailable');
		$error = mysql_error();
		return <<<eod
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<title>Untitled</title>
</head>
<body>
<p align="center" style="margin-top:4em">Database unavailable.</p>
<!-- $error -->
</body>
</html>
eod;
	}

?>
