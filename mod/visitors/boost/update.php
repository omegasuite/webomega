<?php	if(!$_SESSION['OBJ_user']->isDeity()) {
		header('location:index.php');
		exit();
	}


	function add_referrer_table() {

		$prefix=$GLOBALS['core']->tbl_prefix;

		$sql = "CREATE TABLE ${prefix}mod_visitors_referrers (";
		$sql.= 	"id int(11) NOT NULL default '0', ";
		$sql.=	"referrer varchar(255) NOT NULL default '', ";
		$sql.=	"site_id int NOT NULL default '', ";
		$sql.=  "title varchar(100) NOT NULL default '', ";
		$sql.=	"description varchar(100) default '', ";
		$sql.=  "created datetime default '0000-00-00 00:00:00', ";
		$sql.=  "timestamp timestamp(14) NOT NULL, ";
		$sql.= "PRIMARY KEY (id), ";
		$sql.= "UNIQUE KEY referrer (referrer), ";
		$sql.= "KEY site_id (site_id) ";
		$sql.= ") TYPE=MyISAM";

		return($GLOBALS['core']->query($sql));

	}


	function add_sites_table() {

		$prefix=$GLOBALS['core']->tbl_prefix;

		$sql = "CREATE TABLE ${prefix}mod_visitors_sites (";
		$sql.=	"id int(11) NOT NULL default '0', ";
		$sql.=  "url varchar(255) NOT NULL default '', ";
		$sql.=	"name varchar(100) NOT NULL default '', ";
		$sql.=	"description varchar(100) NOT NULL default '', ";
		$sql.= ") TYPE=MyISAM";

		return($GLOBALS['core']->query($sql));

	}


	function alter_referrer() {

		$prefix=$GLOBALS['core']->tbl_prefix;

		$sql = "ALTER TABLE ${prefix}mod_visitors_hit ";
		$sql.= "CHANGE HTTP_REFERER varchar(255)";

		return($GLOBALS['core']->query($sql));
	}


	function add_index_to_referrer() {

		$fields=array('HTTP_REFERER');

		return($GLOBALS['core']->sqlCreateIndex(
			'mod_visitors_hit', $fields));

	}


	if(version_compare($currentVersion, "1.1") <0) {

		echo('creating referrer table... ');
		if(add_referrer_table())
			echo('OK<br />');
		else
			echo('FAILED<br />');

		echo('creating sites table... ');
		if(add_sites_table())
			echo('OK<br />');
		else
			echo('FAILED<br />');

		echo('changing referrer-field from text to varchar(1024)... ');
		if(alter_referrer())
			echo('OK<br />');
		else
			echo('FAILED<br />');

		echo('adding an index on visitors_hit for referrer... ');
		if(add_index_to_referrer())
			echo('OK<br />');
		else
			echo('FAILED<br />');
	}

?>