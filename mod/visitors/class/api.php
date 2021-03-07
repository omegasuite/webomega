<?php	// part of visitors
	// (c) 2005-01 kiesler.at



	/*	getUnreadPostings

		delivers an array of ids of the phpwsbb postings
		that haven't been read yet.
	*/

	function getUnreadPostings() {

		$prefix=$_GLOBALS['core']->tbl_prefix;

		$sql ="SELECT forum_id, id ";
		$sql.="FROM ${prefix}mod_phpwsbb ";

	}

?>