<?php

if (!empty($txpcfg['table_prefix'])) {
	define ("PFX",$txpcfg['table_prefix']);
} else define ("PFX",'');

class DB {

    function DB() {
         global $txpcfg;
         $this->host = $txpcfg['host'];
         $this->db   = $txpcfg['db'];
         $this->user = $txpcfg['user'];
         $this->pass = $txpcfg['pass'];
         $this->link = mysql_connect($this->host, $this->user, $this->pass);
         if (!$this->link) {
         	$GLOBALS['connected'] = false;
         } else $GLOBALS['connected'] = true;
         mysql_select_db($this->db);
    }
} 

$DB = new DB;

//-------------------------------------------------------------
	function safe_query($q='',$debug='',$unbuf='')
	{
		global $DB,$txpcfg;
		$method = (!$unbuf) ? 'mysql_query' : 'mysql_unbuffered_query';
		if (!$q) return false;
		if ($debug) { 
			dmp($q);
			dmp(mysql_error());
		}
		$result = $method($q,$DB->link);
		
		if(!$result) return false;
		return $result;
	}

// -------------------------------------------------------------
	function safe_delete($table, $where, $debug='')
	{
		$q = "delete from ".PFX."$table where $where";
		if ($r = safe_query($q,$debug)) {
			return true;
		}
		return false;
	}

// -------------------------------------------------------------
	function safe_update($table, $set, $where, $debug='') 
	{
		$q = "update ".PFX."$table set $set where $where";
		if ($r = safe_query($q,$debug)) {
			return true;
		}
		return false;
	}

// -------------------------------------------------------------
	function safe_insert($table,$set,$debug='') 
	{
		$q = "insert into ".PFX."$table set $set";
		if ($r = safe_query($q,$debug)) {
			return true;
		}
		return false;
	}

// -------------------------------------------------------------
	function safe_alter($table, $alter, $debug='') 
	{
		$q = "alter table ".PFX."$table $alter";
		if ($r = safe_query($q,$debug)) {
			return true;
		}
		return false;
	}

// -------------------------------------------------------------
	function safe_optimize($table, $debug='') 
	{
		$q = "optimize table ".PFX."$table";
		if ($r = safe_query($q,$debug)) {
			return true;
		}
		return false;
	}

// -------------------------------------------------------------
	function safe_repair($table, $debug='') 
	{
		$q = "repair table ".PFX."$table";
		if ($r = safe_query($q,$debug)) {
			return true;
		}
		return false;
	}

// -------------------------------------------------------------
	function safe_field($thing, $table, $where, $debug='') 
	{
		$q = "select $thing from ".PFX."$table where $where";
		$r = safe_query($q,$debug);
		if (mysql_num_rows($r) > 0) {
			return mysql_result($r,0);
		}
		return false;
	}

// -------------------------------------------------------------
	function safe_column($thing, $table, $where, $debug='') 
	{
		$q = "select $thing from ".PFX."$table where $where";
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
		$q = "select $things from ".PFX."$table where $where";
		$rs = getRow($q,$debug);
		if ($rs) {
			return $rs;
		}
		return array();
	}


// -------------------------------------------------------------
	function safe_rows($things, $table, $where, $debug='') 
	{
		$q = "select $things from ".PFX."$table where $where";
		$rs = getRows($q,$debug);
		if ($rs) {
			return $rs;
		}
		return array();
	}

//-------------------------------------------------------------
	function safe_count($table, $where, $debug='') 
	{
		return getThing("select count(*) from ".PFX."$table where $where",$debug);
	}



//-------------------------------------------------------------
	function fetch($col,$table,$key,$val,$debug='') 
	{
		$q = "select $col from ".PFX."$table where `$key` = '$val' limit 1";
		if ($r = safe_query($q,$debug)) {
			return (mysql_num_rows($r) > 0) ? mysql_result($r,0) : '';
		}
		return false;
	}

//-------------------------------------------------------------
	function getRow($query,$debug='') 
	{
		if ($r = safe_query($query,$debug)) {
			return (mysql_num_rows($r) > 0) ? mysql_fetch_assoc($r) : false;
		}
		return false;
	}

//-------------------------------------------------------------
	function getRows($query,$debug='') 
	{
		if ($r = safe_query($query,$debug)) {
			if (mysql_num_rows($r) > 0) {
				while ($a = mysql_fetch_assoc($r)) $out[] = $a; 
				return $out;
			}
		}
		return false;
	}

//-------------------------------------------------------------
	function getThing($query,$debug='') 
	{
		if ($r = safe_query($query,$debug)) {
			return (mysql_num_rows($r) != 0) ? mysql_result($r,0) : '';
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
		return getThing("select count(*) from ".PFX."$table where $where",$debug);
	}

// -------------------------------------------------------------
 	function getTree($root, $type)
 	{ 

		$root = doSlash($root);

	    extract(safe_row(
	    	"lft as l, rgt as r", 
	    	"txp_category", 
			"name='$root' and type = '$type'"
		));


		$right = array(); 

	    $rs = safe_rows(
	    	"name, lft, rgt, parent", 
	    	"txp_category",
	    	"lft between $l and $r and type = '$type' order by lft asc"
		); 

	    foreach ($rs as $row) {
	   		extract($row);
			while (count($right) > 0 && $right[count($right)-1] < $rgt) { 
				array_pop($right);
			}

        	$out[] = 
        		array(
        			'name' => $name,
        			'level' => count($right), 
        			'children' => ($rgt - $lft - 1) / 2
        		);

	        $right[] = $rgt; 
	    }
    	return($out);
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
		$r = safe_rows('name, val', 'txp_prefs', 'prefs_id=1');
		if ($r) {
			foreach ($r as $a) {
				$out[$a['name']] = $a['val']; 
			}
			return $out;
		}
		return false;
	}

?>
