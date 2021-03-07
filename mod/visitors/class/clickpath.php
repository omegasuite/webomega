<?php	// part of visitors (c) kiesler.at
	//


	require_once(PHPWS_SOURCE_DIR . "mod/visitors/class/query.php");


	function genClickpathSQL($sid) {

		if(sizeof($sid)<0)
			return(null);

		$prefix=$GLOBALS['core']->tbl_prefix;

		$fields[]='date_format(timestamp, "%Y-%m-%d") date';
		$fields[]='date_format(timestamp, "%H:%i:%s") time';
		$fields[]="QUERY_STRING";
		$fields[]="HTTP_REFERER";

		$sql = "SELECT ".implode(", ", $fields)." ";
		$sql.= "FROM ${prefix}mod_visitors_hit ";
		$sql.= "WHERE session_id=\"$sid\" ";
		$sql.= "ORDER BY id";

		return($sql);
	}


	function clickpath($session_id) {

		$sql=genClickpathSQL($session_id);
		$results=$GLOBALS['core']->getAllAssoc($sql);


		if(isset($results[0]) && isset($results[0]["HTTP_REFERER"])) {
			$first_referer=$results[0]["HTTP_REFERER"];

			$first_referer=implode(". ", explode(".", $first_referer));
			$first_referer=implode("? ", explode("?", $first_referer));
			$first_referer=implode("& ", explode("&", $first_referer));
			$first_referer=implode("/ ", explode("/", $first_referer));
		} else {
			$unset_caption=$_SESSION['translate']->it('not set');
			$first_referer="<em>$unset_caption</em>";
		}

/*
		if(empty(trim($first_referer)))
			$first_referer="<em>empty</em>";
			*/

		$html.="<table border=\"0\">\n";

		$firstref_caption=$_SESSION['translate']->it('first referrer');

		$html.="\t<tr><th valign=\"top\">$firstref_caption</th>\n";
		$html.="\t<td valign=\"top\">$first_referer</th></tr>\n";

		foreach($results as $nr => $row) {
			$time=$row["time"];
			$date=$row["date"];
			$query=$row["QUERY_STRING"];

			$query_analyzed=analyze_query($query);

			$html.="\t<tr><th valign=\"top\">$date $time</th>\n";
			$html.="\t<td valign=\"top\">$query_analyzed</td></tr>\n";
		}

		$html.="</table>\n";


		return($html);

	}



?>