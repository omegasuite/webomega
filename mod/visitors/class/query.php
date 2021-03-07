<?php	// part of the visitors module
	// see http://www.kiesler.at/article148.html



	function assoc2str($arr) {

		$str=null;

		foreach($arr as $key => $value)
			$str.=" [$key]=>$value ";

		return($str);
	}






	/* ------------------------------------------------------------- */




	function genArticleMetaSQL($article_id) {

		$prefix=$GLOBALS['core']->tbl_prefix;

		$sql ="SELECT created_id author_id, ";
		$sql.=	"created_username author_name, ";
		$sql.=	"title ";
		$sql.="FROM ${prefix}mod_article ";
		$sql.="WHERE id=$article_id";

		return($sql);

	}



	function getArticleMetaInfo($article_id) {

		$sql=genArticleMetaSQL($article_id);

		return($GLOBALS['core']->getAllAssoc($sql));

	}



	function renderArticleMetaInfo($data) {

		$title=$data["title"];

		$html="&bdquo;$title&ldquo;";

		return($html);
	}



	function genDocumentsMetaSQL($doc_id) {

		$prefix=$GLOBALS['core']->tbl_prefix;

		$sql = "SELECT owner author_name, ";
		$sql.=  "label title ";
		$sql.= "FROM ${prefix}mod_documents_docs ";
		$sql.= "WHERE id=$doc_id";

		return($sql);
	}



	function getDocumentsMetaInfo($doc_id) {

		$sql=genDocumentsMetaSQL($doc_id);

		return($GLOBALS['core']->getAllAssoc($sql));

	}


	function renderDocumentsMetaInfo($data) {

		$title=$data["title"];

		$html="&bdquo;$title&ldquo;";

		return($html);
	}



	function genFatcatMetaSQL($fatcat_id) {

		$prefix=$GLOBALS['core']->tbl_prefix;

		$sql ="SELECT title ";
		$sql.="FROM ${prefix}mod_fatcat_categories ";
		$sql.="WHERE cat_id=$fatcat_id";

		return($sql);

	}



	function getFatcatMetaInfo($fatcat_id) {

		$sql=genFatcatMetaSQL($fatcat_id);

		return($GLOBALS['core']->getAllAssoc($sql));

	}


	function renderFatcatMetaInfo($data) {

		$title=$data["title"];

		$html="&ldquo;$title&rdquo;";

		return($html);
	}







	function genIDLAMetaSQLView($lva_id) {

		$prefix=$GLOBALS['core']->tbl_prefix;

		$sql = "SELECT name ";
		$sql.= "FROM ${prefix}mod_idla_lva ";
		$sql.= "WHERE id=$lva_id";

		return($sql);

	}

	function getIDLAMetaInfoView($lva_id) {

		$sql=genIDLAMetaSQLView($lva_id);

		return($GLOBALS['core']->getAllAssoc($sql));
	}

	function renderIDLAMetaInfo($data) {

		$name=$data["name"];

		$html="&ldquo;$name&rdquo;";

		return($html);
	}
	



	function genPagemasterMetaSQL($page_id) {

		$prefix=$GLOBALS['core']->tbl_prefix;

		$sql = "SELECT title ";
		$sql.= "FROM ${prefix}mod_pagemaster_pages ";
		$sql.= "WHERE id=$page_id";

		return($sql);
	}


	function getPagemasterMetaInfo($page_id) {

		$sql=genPagemasterMetaSQL($page_id);

		return($GLOBALS['core']->getAllAssoc($sql));
	}


	function renderPagemasterMetaInfo($data) {

		$title=$data["title"];

		$html="&bdquo;$title&ldquo;";

		return($html);
	}


	function photoid2thumbnail($photo_id) {

		if(empty($photo_id))
			return("bogus");

		$prefix=$GLOBALS['core']->tbl_prefix;

		$sql = "SELECT tnname ";
		$sql.= "FROM ${prefix}mod_photoalbum_photos ";
		$sql.= "WHERE id=$photo_id";

		$result=$GLOBALS['core']->getAllAssoc($sql);

		return($result[0]["tnname"]);
	}



	function genVisitorsMetaSQL($sid) {

		$prefix=$GLOBALS['core']->tbl_prefix;

		$sql ="SELECT ip title ";
		$sql.="FROM ${prefix}mod_visitors_hit ";
		$sql.="WHERE session_id=\"$sid\"";

		return($sql);
	}



	function getVisitorsMetaInfo($sid) {

		$sql=genVisitorsMetaSQL($sid);

		return($GLOBALS['core']->getAllAssoc($sql));
	}


	function renderVisitorsMetaInfo($data) {

		$title=$data["title"];

		$html="IP $title";

		return($html);
	}


	function genBBThreadMetaSQL($thread_id) {

		if(strlen($thread_id)<=0)
			return(null);

		$prefix=$GLOBALS['core']->tbl_prefix;

		$sql = "SELECT label title ";
		$sql.= "FROM ${prefix}mod_phpwsbb_threads ";
		$sql.= "WHERE id=$thread_id";

		return($sql);

	}


	function getBBThreadMetaInfo($thread_id) {

		$sql=genBBThreadMetaSQL($thread_id);

		if(empty($sql))
			return(array());

		return($GLOBALS['core']->getAllAssoc($sql));

	}


	function renderBBThreadMetaInfo($data) {

		if(sizeof($data) == 0)
			return(null);

		$title=$data["title"];

		$html="&bdquo;$title&ldquo;";

		return($html);

	}


	function genBBPostMetaSQL($post_id) {

		if(strlen($post_id)<=0)
			return(null);

		$prefix=$GLOBALS['core']->tbl_prefix;

		$sql = "SELECT label title ";
		$sql.= "FROM ${prefix}mod_phpwsbb_messages ";
		$sql.= "WHERE id=$post_id";

		return($sql);
	}


	function getBBPostMetaInfo($post_id) {

		$sql=genBBPostMetaSQL($post_id);

		if(empty($sql))
			return(array());

		return($GLOBALS['core']->getAllAssoc($sql));

	}


	function renderBBPostMetaInfo($data) {

		if(sizeof($data) == 0)
			return(null);

		$title=$data["title"];

		$html="&bdquo;$title&ldquo;";

		return($html);
	}


	function genBBForumMetaSQL($forum_id) {

		if(strlen($forum_id)<=0)
			return(null);

		$prefix=$GLOBALS['core']->tbl_prefix;

		$sql = "SELECT label title ";
		$sql.= "FROM ${prefix}mod_phpwsbb_forums ";
		$sql.= "WHERE id=$forum_id";

		return($sql);
	}


	function getBBForumMetaInfo($forum_id) {

		$sql=genBBForumMetaSQL($forum_id);

		if(empty($sql))
			return(array());

		return($GLOBALS['core']->getAllAssoc($sql));
	}


	function renderBBForumMetaInfo($data) {

		if(sizeof($data) == 0)
			return(null);

		$title=$data["title"];

		$html="&bdquo;$title&ldquo;";

		return($html);

	}





	/* ------------------------------------------------------------- */


	function analyze_home($args) {

		return($_SESSION['translate']->it("on the mainpage."));
	}



	function analyze_article($args) {


		if($args["view"]=="news") {
			$txt="browsing through list of new articles.";

			return($_SESSION['translate']->it($txt));
		} else
		if($args["view"]=="archives") {
			$txt="browsing through the archives";

			return($_SESSION['translate']->it($txt));
		}

		$article_id=null;
		$operation=null;

		if(isset($args["view"])) {
			$operation="viewing";
			$article_id=$args["view"];
		} else
		if(isset($args["email"])) {
			$operation="mailing";
			$article_id=$args["email"];
		} else
		if(isset($args["print"])) {
			$operation="printing";
			$article_id=$args["print"];
		}

		if(empty($article_id)) {
			$txt="don't know what to do with that article.";

			return($_SESSION['translate']->it($txt));
		} else {
			$data=getArticleMetaInfo($article_id);
			$meta=renderArticleMetaInfo($data[0]);

			return("$operation article $article_id (".
				$meta.").");
		}
	}



	function analyze_calendar($args) {

		$granularity=$args["calendar[view]"];

		if($granularity == "day") {

			$month=$args["month"];
			$year=$args["year"];
			$day=$args["day"];

			$txt="browsing the calendar for day [var1]";

			return($_SESSION['translate']->it($txt,
				"$year-$month-$day"));

		} else
		if($granularity == "week") {

			$year=$args["year"];
			$month=$args["month"];
			$week=$args["week"];

			$txt="browsing the calendar for week [var1] in [var2]";

			return($_SESSION['translate']->it($txt,
				$week, "$year-$month"));

		} else
		if($granularity == "month") {

			$month=$args["month"];
			$year=$args["year"];

			$txt="browsing the calendar for month [var1]";

			return($_SESSION['translate']->it($txt,
				"$year-$month"));
		} else {
			$txt="don't know what to do with calendar-view [var1]";

			return($_SESSION['translate']->it($txt, $granularity));
		}
	}



	function analyze_controlpanel($args) {

		return($_SESSION['translate']->it("entering controlpanel."));

	}



	function analyze_documents($args) {

		$op=$args["JAS_DocumentManager_op"];

		if(empty($op)) {

			$outstr=assoc2str($args);
			$txt="don't have document operation in [var1]";

			return($_SESSION['translate']->it($txt, $outstr));

		} else
		if($op != "viewDocument") {

			$txt="don't know about document operation [var1] yet.";

			return($_SESSION['translate']->it($txt, $op));

		}

		$doc_id=$args["JAS_Document_id"];

		if(empty($doc_id))
			return($_SESSION['translate']->it(
				"don't have a document id!"));

		$data=getDocumentsMetaInfo($doc_id);
		$meta=renderDocumentsMetaInfo($data[0]);

		$txt="viewing document [var1] ([var2])";

		return($_SESSION['translate']->it($txt, $doc_id, $meta));

	}



	function analyze_fatcat($args) {

		$fatcat_id=$args["fatcat_id"];
		$module_title=$args["module_title"];

		$point_pos=strpos($module_title, ".");	// short-url .html
		if($point_pos !== false)
			$for_module=substr($module_title, 0, $point_pos);

		if(empty($fatcat_id)) {
			$txt="don't know what to do with that fatcat.";

			return($_SESSION['translate']->it($txt));
		} else {

			$data=getFatcatMetaInfo($fatcat_id);
			$meta=renderFatcatMetaInfo($data[0]);

			$txt="browsing category [var1] ([var2])";

			return($_SESSION['translate']->it($txt, $fatcat_id,
				$meta));
		}
	}



	function analyze_idla($args) {

		$view=$args["view"];
		$kennzahl=$args["kennzahl"];
		if(isset($view)) {

			$data=getIDLAMetaInfoView($view);
			$meta=renderIDLAMetaInfo($data[0]);

			$txt="viewing IDLA-LVA [var1] ([var2])";

			return($_SESSION['translate']->it($txt,
				$view, $meta));
		} else
		if(isset($kennzahl)) {


		} else {

			$txt="don't know how to handle idla-feature [var1]";

			return($_SESSION['translate']->it($txt,
				assoc2str($args)));

		}

	}



	function analyze_linkman($args) {

		$op=$args["LMN_op"];

		if(empty($op)) {
			$outstr=assoc2str($args);

			$txt="no linkman operation in [var1]!";

			return($_SESSION['translate']->it($txt,$outstr));
		} else
		if($op!="userMenuAction") {

			$txt="don't know what to do with linkman op [var1]";

			return($_SESSION['translate']->it($txt, $op));
		}

		$category=$args["category"];

		if(empty($category)) {
			return($_SESSION['translate']->it(
				"no category set!"));
		}

		$data=getFatcatMetaInfo($category);
		$meta=renderFatcatMetaInfo($data[0]);

		$txt="viewing linkman category [var1] ([var2])";

		return($_SESSION['translate']->it($txt,
			$category, $meta));

	}



	function analyze_pagemaster($args) {

		$op=$args["PAGE_user_op"];

		if(empty($op)) {
			$txt="no pagemaster operation!";

			return($_SESSION['translate']->it($txt));
		} else
		if($op!="view_page") {
			$txt="don't know what to do with pagemaster op [var1]";

			return($_SESSION['translate']->it($txt, $op));
		}

		$page_id=$args["PAGE_id"];

		$data=getPagemasterMetaInfo($page_id);
		$meta=renderPagemasterMetaInfo($data[0]);

		$txt="viewing pagemaster page [var1] ([var2])";

		return($_SESSION['translate']->it($txt, $page_id, $meta));
	}


	function analyze_photoalbum($args) {

		$op=$args["PHPWS_Photo_op"];

		if(empty($op))
			return($_SESSION['translate']->it(
				"no photoalbum operation!"));
		else
		if($op!="view") {
			$txt="don't know what to do with photoalbum op [var1]";
			return($_SESSION['translate']->it($txt, $op));
		}

		$album_id=$args["PHPWS_Album_id"];
		$photo_id=$args["PHPWS_Photo_id"];

		$thumbnail=photoid2thumbnail($photo_id);

		$html="";

		if(isset($thumbnail)) {

			$html.="<img align=\"top\" src=\"";
			$html.="./images/photoalbum/$album_id/$thumbnail\" />";

		} else {
			$html.="???";
		}

		$txt="viewing photo [var1] ([var2])";

		return($_SESSION['translate']->it($txt, $photo_id, $html));

	}


	function analyze_phpwsbb($args) {

		$op=$args["PHPWSBB_MAN_OP"];

		if($op=="list") {
			$txt="listing all forums.";
			return($_SESSION['translate']->it($txt));
		} else
		if($op=="viewforum") {

			$forum_id=$args["PHPWS_MAN_ITEMS"];

			$data=getBBForumMetaInfo($forum_id);
			$meta=renderBBForumMetaInfo($data[0]);

			$txt="browsing forum [var1] ([var2])";

			return($_SESSION['translate']->it($txt,
				$forum_id, $meta));
		} else
		if($op=="edit") {

			$post_id=$args["PHPWS_MAN_ITEMS[]"];
			if(empty($post_id))
				$post_id=$args["PHPWS_MAN_ITEMS"];

			$data=getBBPostMetaInfo($post_id);
			$meta=renderBBPostMetaInfo($data[0]);

			$txt="editing post [var1] ([var2])";

			return($_SESSION['translate']->it(
				$txt, $post_id, $meta));

		}
		if($op=="view")
			$operation=$_SESSION['translate']->it("viewing");
		else
		if($op=="reply")
			$operation=$_SESSION['translate']->it("replying to");
		else {
			$txt="don't know what to do with phpwsbb op [var1]";

			return($_SESSION['translate']->it($txt, $op));
		}

		$thread_id=$args["PHPWS_MAN_ITEMS[]"];
		if(empty($thread_id))
			$thread_id=$args["PHPWS_MAN_ITEMS"];

		$data=getBBThreadMetaInfo($thread_id);
		$meta=renderBBThreadMetaInfo($data[0]);

		$txt="[var1] phpwsbb thread [var2] ([var3])";

		return($_SESSION['translate']->it(
			$txt, $operation, $thread_id, $meta));
	}


	function analyze_visitors($args) {

		$sid=$args["view"];

		if(empty($sid))

			return("don't know what to do with that visitor.");

		else {

			$data=getVisitorsMetaInfo($sid);
			$meta=renderVisitorsMetaInfo($data[0]);

			return("user is viewing session $sid ($meta).");
		}

	}



	/* ------------------------------------------------------------- */



	function parse_short($args) {

		$data=explode("~", $args);

		$args_arr=array();

		$i=0;

		while($i<sizeof($data)) {
			$args_arr[$data[$i]]=$data[$i+1];
			$i+=2;
		}

		return($args_arr);
	}


	function parse_default($args) {
		$data=explode("&", $args);

		$args_arr=array();

		foreach($data as $nr => $row) {
			$equals_pos=strpos($row, "=");

			$key=substr($row, 0, $equals_pos);
			$value=substr($row, $equals_pos+1);

			$args_arr[$key]=$value;
		}

		return($args_arr);
	}


	function dispatch($module, $args_arr) {

		if($module == "")
			return(analyze_home($args_arr));
		else
		if($module == "article")
			return(analyze_article($args_arr));
		else
		if($module == "calendar")
			return(analyze_calendar($args_arr));
		else
		if($module == "controlpanel")
			return(analyze_controlpanel($args_arr));
		else
		if($module == "documents")
			return(analyze_documents($args_arr));
		else
		if($module == "fatcat")
			return(analyze_fatcat($args_arr));
		else
		if($module == "idla")
			return(analyze_idla($args_arr));
		else
		if($module == "linkman")
			return(analyze_linkman($args_arr));
		else
		if($module == "pagemaster")
			return(analyze_pagemaster($args_arr));
		else
		if($module == "photoalbum")
			return(analyze_photoalbum($args_arr));
		else
		if($module == "phpwsbb")
			return(analyze_phpwsbb($args_arr));
		else
		if($module == "visitors")
			return(analyze_visitors($args_arr));
		else {
			$txt=$_SESSION['translate']->it(
				"don't know about module [var1]",
				$module);

			return("$txt<br />".
				assoc2str($args_arr));
		}
	}


	function analyze_query($query) {

		$shorturl_hack=strpos($query, "mod_rewrite=");

		if($shorturl_hack === false) {

			$query=substr($query, 7);	// remove "module="
			$amp_pos=strpos($query, "&");

			$module=substr($query, 0, $amp_pos);

			$args=substr($query, $amp_pos+1);

			// echo("module=$module<br />");

			$args_arr=parse_default($args);

		} else {

			$query=substr($query, 12);	// remove "mod_rewrite="

			$amp_pos=strpos($query, "&");

			if($amp_pos !== false)
				$query=substr($query, 0, $amp_pos);

			$module=substr($query, 0, strpos($query, "~"));
			$args=substr($query, strpos($query, "~")+1);

			$point_pos=strpos($args, ".");	// .html
				if($point_pos !== false)
			$args=substr($args, 0, $point_pos);

			$args_arr=parse_short($args);
		}

		return(dispatch($module, $args_arr));
	}

?>