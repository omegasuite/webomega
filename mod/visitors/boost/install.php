<?php	/**
	 * Visitors module for phpWebSite 0.9.3
	 *
	 * @author rck <http://www.kiesler.at>
	 */



	/* Make sure the user is deity before running this script */
	if (!$_SESSION["OBJ_user"]->isDeity()) {
		header("location:index.php");
			exit();
	}


	function create_referrers() {

		$prefix=$GLOBALS['core']->tbl_prefix;

		$sql = "CREATE TABLE if not exists ";
		$sql.=		"${prefix}mod_visitors_referrers (";
		$sql.= 	"id int(11) NOT NULL default '0', ";
		$sql.=	"referrer varchar(255) NOT NULL default '', ";
		$sql.=	"site_id int NOT NULL default 0, ";
		$sql.=  "title varchar(100) NOT NULL default '', ";
		$sql.=	"description varchar(100) default '', ";
		$sql.=  "created datetime default '0000-00-00 00:00:00', ";
		$sql.=  "timestamp timestamp NOT NULL, ";
		$sql.= "PRIMARY KEY (id), ";
		$sql.= "UNIQUE KEY referrer (referrer), ";
		$sql.= "KEY site (site_id) ";
		$sql.= ") ENGINE=MyISAM";

		return($GLOBALS['core']->query($sql));

	}


	function create_sites() {

		$prefix=$GLOBALS['core']->tbl_prefix;

		$sql = "CREATE TABLE if not exists ";
		$sql.=		"${prefix}mod_visitors_sites (";
		$sql.=	"id int(11) NOT NULL default '0', ";
		$sql.=  "url varchar(255) NOT NULL default '', ";
		$sql.=	"name varchar(100) NOT NULL default '', ";
		$sql.=	"description varchar(100) NOT NULL default '' ";
		$sql.= ") ENGINE=MyISAM";

		return($GLOBALS['core']->query($sql));

	}



	function create_hits() {

		$prefix=$GLOBALS['core']->tbl_prefix;

		$sql = "CREATE TABLE if not exists ";
		$sql.=		"${prefix}mod_visitors_hit (";
		$sql.=	"id int(11) not null, ";
		$sql.=	"ip text not null, ";
		$sql.=	"timestamp timestamp not null, ";
		$sql.=	"module text not null, ";
		$sql.=	"session_id varchar(100) not null default '', ";
		$sql.=	"user text not null, ";
		$sql.=	"user_id int(11) not null default '0', ";
		$sql.=	"REMOTE_ADDR text not null, ";
		$sql.=	"REMOTE_PORT text not null, ";
		$sql.=	"HTTP_CLIENT_IP text not null, ";
		$sql.=	"HTTP_X_FORWARDED_FOR text not null, ";
		$sql.=	"HTTP_USER_AGENT text not null, ";
		$sql.=	"HTTP_REFERER varchar(255) not null, ";
		$sql.=	"HTTP_ACCEPT_LANGUAGE text not null, ";
		$sql.=	"QUERY_STRING text not null, ";
		$sql.=	"HTTP_HOST text not null, ";
		$sql.=	"REQUEST_URI text not null, ";
		$sql.=	"primary key (id), ";
		$sql.=	"key query (timestamp, session_id), ";
		$sql.=  "key HTTP_REFERER (HTTP_REFERER) ";
		$sql.= ") ENGINE=MyISAM;";

		return($GLOBALS['core']->query($sql));

	}


	$status =	create_hits() &&
			create_referrers() &&
			create_sites();

?>