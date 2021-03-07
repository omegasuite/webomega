<?php

/**
 * Visitors module for phpWebSite
 *
 * @author rck <http://www.kiesler.at/>
 */

require_once(PHPWS_SOURCE_DIR . "core/Pager.php");
require_once(PHPWS_SOURCE_DIR . "mod/visitors/class/deity_details.php");
require_once(PHPWS_SOURCE_DIR . "mod/visitors/class/statistics.php");
require_once(PHPWS_SOURCE_DIR . "mod/visitors/class/clickpath.php");
require_once(PHPWS_SOURCE_DIR . "mod/visitors/class/query.php");

class PHPWS_visitors {


	var $_pager;
	var $_sortid = "counter desc";
	var $_id;

	var $_active=true;
	var $_visible=true;

	var $bots=null;
   


	function PHPWS_visitors() {

		include($GLOBALS["core"]->source_dir.
			"mod/visitors/conf/config.php");

		$this->_active=$active;
		$this->_visible=$visible;

		$bots=array();

		$bots["Wobot"]="Magellan";
		$bots["ABCdatos"]="ABCdatos BotLink";
		$bots["BotLink"]="ABCdatos BotLink";
		$bots["Ahoy!"]="Ahoy!";
		$bots["AlkalineBOT"]="Alkaline";
		$bots["AnthillV1.1"]="Anthill";
		$bots["appie"]="Walhello";
		$bots["Arachnophilia"]="Arachnophilia";
		$bots["Araneo"]="Araneo";
		$bots["AraybOt"]="AraybOt";
		$bots["ArchitextSpider"]="Excite";
		$bots["arks"]="arks";
		$bots["ASpider"]="Associative Spider";
		$bots["ATN_Worldwide"]="All that net";
		$bots["Atomz"]="Atomz.com";
		$bots["AURESYS"]="AURESYS";
		$bots["BackRub"]="BackRub";
		$bots["bbot"]="BBot";
		$bots["Big Brother"]="Big Brother";
		$bots["Bjaaland"]="Bjaaland";
		$bots["BlackWidow"]="BlackWidow";
		$bots["Die"]="Blinde Kuh";
		$bots["borg-bot"]="Borg-Bot";
		$bots["BoxSeaBot"]="BoxSea";
		$bots["BSpider"]="BSpider";
		$bots["CACTVS"]="CACTVS Chemistry Spider";
		$bots["Calif"]="Calif";
		$bots["Digimarc"]="Digimarc";
		$bots["Checkbot"]="Checkbot";
		$bots["cIeNcIaFiCcIoN.nEt"]="cIeNcIaFiCcIoN.nEt";
		$bots["CMC"]="CMC";
		$bots["LWP"]="Collective";
		$bots["combine"]="Combine System";
		$bots["Confuzzledbot"]="Confuzzled";
		$bots["CoolBot"]="suchmaschine21";
		$bots["root"]="Web Core";
		$bots["cosmos"]="XYLEME";
		$bots["Cusco"]="Cusco";
		$bots["CyberSpyder"]="CyberSpyder";
		$bots["CydralSpider"]="Cydral";
		$bots["DesertRealm.com;"]="Desert Realm";
		$bots["Deweb"]="DeWeb";
		$bots["dienstspider"]="DienstSpider";
		$bots["Digger"]="Diggit!";
		$bots["DIIbot"]="Digital Integrity";
		$bots["grabber"]="Direct Hit";
		$bots["DNAbot"]="DNAbot";
		$bots["DragonBot"]="DragonBot";
		$bots["DWCP"]="Dridus";
		$bots["LWP::"]="e-collector";
		$bots["EbiNess"]="EbiNess";
		$bots["EIT-Link-Verifier-Robot"]="EIT";
		$bots["elfinbot"]="Lets find it now";
		$bots["Emacs-w3"]="Emacs-w3";
		$bots["EMC"]="ananzi";
		$bots["esculapio"]="Esculapio";
		$bots["esther"]="Falcon Soft";
		$bots["Evliya"]="Evliya Celebi";
		$bots["explorersearch"]="nzexplorer";
		$bots["FastCrawler"]="1klik.dk";
		$bots["FelixIDE"]="Pentone";
		$bots["Hazel's"]="Green Earth";
		$bots["ESIRober"]="FetchRober";
		$bots["fido"]="Planetsearch";
		$bots["Hämähäkki"]="Hämähäkki";
		$bots["KIT-Fireball"]="Fireball";
		$bots["Fish-Search-Robot"]="Fish Search";
		$bots["Freecrawl"]="Euroseek";
		$bots["FunnelWeb-1.0"]="FunnelWeb";
		$bots["gammaSpider"]="Gammasite";
		$bots["gazz"]="gazz";
		$bots["gcreep"]="GCreep";
		$bots["GetURL.rexx"]="GetURL";
		$bots["Golem"]="Quibble";
		$bots["Googlebot"]="Google";
		$bots["griffon"]="Griffon";
		$bots["Gromit"]="Gromit";
		$bots["Gulliver"]="Gulliver";
		$bots["Gulpler"]="Yuntis";
		$bots["havIndex"]="havIndex";
		$bots["AITCSRobot"]="HTML Index";
		$bots["Hometown"]="Hometown Singles";
		$bots["wired-digital-newsbot"]="Wired Digital";
		$bots["htdig"]="ht://Dig";
		$bots["HTMLgobble"]="HTMLgobble";
		$bots["iajaBot"]="iajaBot";
		$bots["IBM_Planetwide,"]="IBM_Planetwide";
		$bots["gestaltIconoclast"]="Popular Iconoclast";
		$bots["INGRID"]="Ingrid";
		$bots["IncyWincy"]="IncyWincy";
		$bots["Informant"]="Informant";
		$bots["InfoSeek"]="Infoseek";
		$bots["Infoseek"]="Infoseek";
		$bots["InfoSpiders"]="InfoSpiders";
		$bots["inspectorwww"]="Greenpac";
		$bots["IAGENT"]="IntelliAgent";
		$bots["I"]="I, Robot";
		$bots["Iron33"]="Verno";
		$bots["IsraeliSearch"]="Israeli Search";
		$bots["JavaBee"]="Java Bee";
		$bots["JBot"]="JAVA Web Robot";
		$bots["JCrawler"]="VietGATE";
		$bots["JoBo"]="JoBo";
		$bots["Jobot"]="Jobot";
		$bots["JoeBot"]="JoeBot";
		$bots["JubiiRobot"]="Jubii";
		$bots["jumpstation"]="JumpStation";
		$bots["image.kapsi.net"]="image.kapsi.net";
		$bots["Katipo"]="Katipo";
		$bots["KDD-Explorer"]="CLINKS";
		$bots["KO_Yappo_Robot"]="Yappo AOL";
		$bots["LabelGrab"]="PICS Label";
		$bots["larbin"]="larbin";
		$bots["legs"]="legs";
		$bots["Linkidator"]="Linkidator";
		$bots["LinkScan"]="LinkScan";
		$bots["LinkWalker"]="LinkWalker";
		$bots["Lockon"]="Lockon";
		$bots["logo.gif"]="logo.gif";
		$bots["Lycos"]="Lycos";
		$bots["Magpie"]="Magpie";
		$bots["marvin"]="Infoseek";
		$bots["M"]="Mattie";
		$bots["MediaFox"]="MediaFox";
		$bots["MerzScope"]="MerzCom";
		$bots["NEC-MeshExplorer"]="NETPLAZA";
		$bots["MindCrawler"]="MindCrawler";
		$bots["UdmSearch"]="mnoGoSearch";
		$bots["moget"]="moget";
		$bots["MOMspider"]="MOMspider";
		$bots["Monster"]="Monster";
		$bots["Motor"]="Webindex.de";
		$bots["MSNBOT"]="MSN Search";
		$bots["msnbot"]="MSN Search";
		
	}


	function realip() {

		// taken from http://athon.me.uk/irc/faq_php.php and
		// adapted by rck


		$ip = FALSE;

		if(!empty($_SERVER["HTTP_CLIENT_IP"]))
			$ip=$_SERVER["HTTP_CLIENT_IP"];

		if(!empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
			$ips = explode(", ", $_SERVER["HTTP_X_FORWARDED_FOR"]);

			if($ip != FALSE) {
				array_unshift($ips, $ip);
				$ip=FALSE;
			}


			foreach($ips as $nr => $value)
				if($value != 'unknown')
					if (!eregi("^(10|172\.16|192\.168)\.", $ips[$nr])) {
						$ip = $value;
						break;
					}
		}

		// honor the ip-hack

		if(isset($_SERVER["REMOTE_ADDR_ORIG"]))
			$initial_ip=$_SERVER["REMOTE_ADDR_ORIG"];
		else
			$initial_ip=$_SERVER["REMOTE_ADDR"];
	
		return($ip? $ip : $initial_ip);
	}



	function collectData() {

		$data=array();

		$ip=$this->realip();
		if(!empty($ip))
			$data["ip"]=$ip;


		// honor IP-hack
		if(!empty($_SERVER["REMOTE_ADDR_ORIG"]))
			$data["REMOTE_ADDR"]=$_SERVER["REMOTE_ADDR_ORIG"];
		else
		if(!empty($_SERVER["REMOTE_ADDR"]))
			$data["REMOTE_ADDR"]=$_SERVER["REMOTE_ADDR"];

		if(!empty($_SERVER["REMOTE_PORT"]))
			$data["REMOTE_PORT"]=$_SERVER["REMOTE_PORT"];

		if(!empty($_SERVER["HTTP_CLIENT_IP"]))
			$data["HTTP_CLIENT_IP"]=$_SERVER["HTTP_CLIENT_IP"];

		if(!empty($_SERVER["HTTP_X_FORWARDED_FOR"]))
			$data["HTTP_X_FORWARDED_FOR"]=$_SERVER["HTTP_X_FORWARDED_FOR"];

		if(!empty($_SERVER["HTTP_REFERER"]))
			$data["HTTP_REFERER"]=$_SERVER["HTTP_REFERER"];

		if(!empty($_SERVER["HTTP_USER_AGENT"]))
			$data["HTTP_USER_AGENT"]=$_SERVER["HTTP_USER_AGENT"];

		if(!empty($_SERVER["HTTP_ACCEPT_LANGUAGE"]))
			$data["HTTP_ACCEPT_LANGUAGE"]=$_SERVER["HTTP_ACCEPT_LANGUAGE"];

		if(!empty($_SERVER["QUERY_STRING"]))
			$data["QUERY_STRING"]=$_SERVER["QUERY_STRING"];

		if(!empty($_SERVER["HTTP_HOST"]))
			$data["HTTP_HOST"]=$_SERVER["HTTP_HOST"];

		if(!empty($_SERVER["REQUEST_URI"]))
			$data["REQUEST_URI"]=$_SERVER["REQUEST_URI"];
		
		if($_SESSION["OBJ_user"]->isUser()) {
			$data["user"]=$_SESSION["OBJ_user"]->getUsername();
		}

		$data["user_id"]=$_SESSION["OBJ_user"]->getUserID();

		$data["session_id"]=session_id();

		if(!empty($GLOBALS['module']))
			$data['module']=$GLOBALS['module'];

		if(!empty($_POST))
			$data['POST_DATE']=$_POST;

		return($data);
	}



	function storeData() {
		
		$data=$this->collectData();
		$GLOBALS["core"]->sqlInsert($data, "mod_visitors_hit");
			
	}



	function genRetrieveSessionIDs() {

		$prefix=$GLOBALS['core']->tbl_prefix;

		$sql ="SELECT distinct(session_id) ";
		$sql.="FROM ${prefix}mod_visitors_hit ";
		$sql.="WHERE timestamp>date_sub(now(), interval 5 minute) ";
		$sql.="ORDER BY user DESC, ip";

		return($sql);

	}


	function genRetrieveUserData($session_ids) {

		if(sizeof($session_ids)<=0)
			return(null);

		$prefix=$GLOBALS['core']->tbl_prefix;


		$fields[]="ip";
		$fields[]="user_id";
		$fields[]="user";
		$fields[]="module";
		$fields[]="session_id";
		$fields[]="HTTP_USER_AGENT";
		$fields[]="QUERY_STRING";

		$sql = "SELECT max(id), ".implode(", ", $fields)." ";
		$sql.= "FROM ${prefix}mod_visitors_hit ";
		$sql.="WHERE ";
		
	
		$sessions=null;
		
		foreach($session_ids as $nr => $session_id) {
			if(!empty($sessions))
				$sessions.=" or ";

			$sessions.="session_id=\"${session_id["session_id"]}\"";

		}
		
		
		$sql.=$sessions." GROUP BY ip ORDER BY timestamp";


		return($sql);

		
	}

	function genRetrieveSID($sid) {

		if(sizeof($sid)<0)
			return(null);

		$prefix=$GLOBALS['core']->tbl_prefix;

		$fields[]="ip";
		$fields[]="user_id";
		$fields[]="session_id";
		$fields[]="user";
		$fields[]="module";
		$fields[]="HTTP_USER_AGENT";
		$fields[]="REQUEST_URI";

		$fields[]="REMOTE_ADDR";
		$fields[]="HTTP_CLIENT_IP";
		$fields[]="HTTP_X_FORWARDED_FOR";

		$fields[]="QUERY_STRING";

		$sql = "SELECT ".implode(", ", $fields)." ";
		$sql.= "FROM ${prefix}mod_visitors_hit ";
		$sql.= "WHERE session_id=\"$sid\" ";
		$sql.= "ORDER BY timestamp DESC LIMIT 1";

		return($sql);
	}



	function genRetrieveUserData2($session_ids) {

		// would work if mysql supported UNION...

		$sql=array();

		foreach($session_ids as $nr => $session_id) {

			$sql[]=$this->genRetrieveSID($session_id);
		}

		return(implode(" UNION ", $sql));

	}



	function retrieveSID($sid) {

		$sql=$this->genRetrieveSID($sid);

		$results=$GLOBALS['core']->getAllAssoc($sql);

		return($results[0]);
	}



	function retrieveData() {

		$sql=$this->genRetrieveSessionIDs();

		$session_ids=$GLOBALS["core"]->getAllAssoc($sql);


		$results=array();

		foreach($session_ids as $nr => $row) {

			$session_id=$row["session_id"];
			$results[]=$this->retrieveSID($session_id);
		}

		return($results);

	}


	function countRegistered($data) {

		$i=0;

		foreach($data as $nr => $row)
			if($row["user_id"]!=0)
				$i++;

		return($i);
	}


	function renderRegistered($data) {

		$users=array();

		if(!$data)
			return(null);

		foreach($data as $nr => $row)
			if($row["user_id"]!=0)
				$users[$row["user"]]++;

		$user_names=array();
		foreach($users as $name => $count)
			if($count > 1)
				$user_names[]="$name ($count)";
			else
				$user_names[]="$name";

		return(implode(", ", $user_names));
	}


	function renderGuestBox($data) {

		$users=sizeof($data);
		$registered=$this->countRegistered($data);
		$guests=$users-$registered;

		if(($users == 1) && ($guests == 1)) {

			$text ="You are on your own.";

			$text=$_SESSION['translate']->it($text);

		} else
		if($registered == 0) {

			$text ="Currently [var1] ";
			$text.="anonymous souls are browsing this site.";

			$text =$_SESSION['translate']->it($text,
				sizeof($data), $registered);

		} else
		if($guests == 0) {

			$text = "There are no guests here, only ";
			$text.= "[var1] members. Why are you seeing ";
			$text.= "this?";

			$text = $_SESSION["translate"]->it($text,
				$registered);

		} else
		if($registered == 1) {

			$text = "Currently, out of [var1] users, ";
			$text.= "there is only one registered.";

			$text = $_SESSION['translate']->it($text,
				$users);

		} else {

			$text = "[var1] users, [var2] of them registered.";

			$text = $_SESSION['translate']->it($text,
				$users, $registered);

		}

		$html ="<p>$text</p>";

		return($html);
	}


	function renderUserBox($data) {

		$reg=$this->countRegistered($data);
		$guests=sizeof($data)-$reg;
		if($guests==0)
			$guests_text="no guest";
		else
		if($guests==1)
			$guests_text="1 guest";
		else
			$guests_text="[var1] guests";

		$guests=$_SESSION["translate"]->it($guests_text,
			$guests);

		if(($reg == 1) && ($guests == 0)) {

			$users=$this->renderRegistered($data);

			$text=$_SESSION["translate"]->it('[var1], '.
				'all alone.', $users);

		} else
		if($reg>0) {
			$users=$this->renderRegistered($data);

			$text=$_SESSION["translate"]->it("[var1] ".
				"and [var2]", $users, $guests);
		} else 
			$text=$guests;

		return($text);
	}


	function spiderWatch($agent) {

		$details=explode("/", $agent);

		return($this->bots[$details[0]]);

	}


	function renderDetailsLink($sid) {

		$html ="<a href=\"./index.php?";
		$html.="module=visitors&amp;";
		$html.="view=$sid\">";
		$html.="details</a>";

		return($html);
	}


	function renderDeityBox($data) {


		$text=$this->renderUserBox($data);

		$reg=$this->countRegistered($data);
		if(empty($reg))
			$reg=0;

		$whole=sizeof($data);

		$guests=$whole-$reg;
		if(empty($guests))
			$guests=$_SESSION["translate"]->it("0");

		$html="<p>$text\n<dl>";

		foreach($data as $nr => $row) {

			$user=$row["user"];
			$uid=$row["user_id"];
			$module=$row["module"];
			$ip=$row["ip"];
			$sid=$row["session_id"];

			$lookup=gethostbyaddr($ip);

			if($lookup != $ip) {
				$lookup=explode(".", $lookup);
				$lookup=implode(". ", $lookup);
			}

			$agent=explode(" ",$row["HTTP_USER_AGENT"]);

			if($uid<=0) {

				$text=null;

				if(!empty($agent[0]))
					$text=$this->spiderWatch($agent[0]);

				if(empty($text))
					$text="Guest";

				$html.="\t<dt><em>$text ($lookup)</em></dt>\n";

			} else
				$html.="\t<dt>$user ($lookup)</dt>\n";

			$link=$this->renderDetailsLink($sid);

			$module_caption=$_SESSION['translate']->it('using [var1] ([var2])',
				$module, $link);

			$html.="\t<dd>$module_caption</dd>\n";
		}
		$html.="</dl></p>\n";

		return($html);
	}


	function renderDeityDetails($data) {

		$ip=$data["ip"];
		$lookup=gethostbyaddr($ip);
		$user=$data["user"];
		$user_id=$data["user_id"];
		$module=$data["module"];
		$agent=$data["HTTP_USER_AGENT"];
		$uri=$data["REQUEST_URI"];

		$forwarded_for=$data["HTTP_X_FORWARDED_FOR"];
		$remote_addr=$data["REMOTE_ADDR"];
		$client_ip=$data["HTTP_CLIENT_IP"];

		$query=$data["QUERY_STRING"];
		$session_id=$data["session_id"];


		$text="Details for IP [var1]";
		$text=$_SESSION['translate']->it($text, $ip);

		$html.="<h3>$text</h3>\n";

		if($lookup==$ip) {
			$text="cannot resolve this IP.";
			$text=$_SESSION['translate']->it($text);
		} else {
			$text="this IP resolves to [var1]";
			$text=$_SESSION['translate']->it($text,
				$lookup);
		}

		$html.="<p>$text</p>\n";


		$text=renderForwardedFor($data);
		$html.="<p>$text</p>\n";


		$text=renderRemoteAddr($data);
		$html.="<p>$text</p>\n";

		$resolve=null;

		if(empty($client_ip)) {

			$text="CLIENT_IP is unknown.";
			$text=$_SESSION['translate']->it($text);

		} else
		if($client_ip == $ip) {

			$text="CLIENT_IP equals the IP I got";
			$text=$_SESSION['translate']->it($text);

		} else {

			$text="CLIENT_IP is [var1]";
			$text=$_SESSION['translate']->it($text,
				$client_ip);

			$client_lookup=gethostbyaddr($client_ip);

			if($client_lookup == $client_ip) {

				$resolve="Does not resolve.";
				$resolve=$_SESSION['translate']->it($resolve);

			} else {

				$resolve="(${client_lookup})";
			}
		}

		$html.="<p>$text $resolve</p>\n";

		$resolve=null;

		$details_caption=$_SESSION['translate']->it('User Details');

		$html.="<h3>$details_caption</h3>\n";

		if(empty($agent)) {
			$text="could not determine user agent.";
			$text=$_SESSION['translate']->it($text);
		} else {
			$text="user is using [var1]";
			$text=$_SESSION['translate']->it($text,
				$agent);
		}

		$html.="<p>$text</p>\n";


		$text="Details for user";
		$text=$_SESSION['translate']->it($text);

		if($user_id<=0) {
			
			$couldBe=couldBe($ip);

			if(sizeof($couldBe)<=0) {

				$text="user is not logged in, couldn't find one that matches IP.";
				$text=$_SESSION['translate']->it($text);

			} else {

				$text="user is not logged in but could be [var1]";
				$text=$_SESSION['translate']->it($text, implode(", ", $couldBe));

			}

		} else {
			$text="user is logged in as [var1], uid [var2].";
			$text=$_SESSION['translate']->it($text, $user,
				$user_id);
		}

		$html.="<p>$text</p>\n";

		$happenin_caption=$_SESSION['translate']->it(
			"What's happening?");

		$html.="<h3>$happenin_caption</h3>\n";

		$text=analyze_query($query);

		$html.="<p>$text</p>\n";

		$text="current URI: [var1]";

		$uri_split=explode("~", $uri);
		$uri_split=implode("~ ", $uri_split);

		$text=$_SESSION['translate']->it($text, $uri_split);

		$html.="<p>$text</p>\n";

		$clickpath_caption=$_SESSION['translate']->it(
			'Clickpath');

		$html.="<h3>$clickpath_caption</h3>\n";

		$text=clickpath($session_id);

		$html.="<p>$text</p>\n";

		return($html);
	}


	function showVisitorsDetails($sid) {


		if(!$_SESSION["OBJ_user"]->isDeity()) {
			$text="please log in as deity to do that.";
			$text=$_SESSION["translate"]->it($text);

			return("<p>$text</p>");
		}

		if(empty($sid)) {
			$text="no session-id specified!";
			$text=$_SESSION['translate']->it($text);
			return("<p>$text</p>");
		}

		$data=$this->retrieveSID($sid);
		
		if(empty($data)) {

			$text="could not retrieve data for Session_ID [var1]";
			$text=$_SESSION['translate']->it($text, $sid);
			return("<p>$text</p>");
		} else
			return($this->renderDeityDetails($data));
	}



	function showVisitorsBox() {

		$data=$this->retrieveData();

		if($this->_visible) {

			if($_SESSION["OBJ_user"]->isDeity() || $_SESSION["OBJ_user"]->isService())
				return($this->renderDeityBox($data));
			/* -- no visitor block for non-deity users.
			else
			if($_SESSION["OBJ_user"]->isUser())
				return($this->renderUserBox($data));
			else
				return($this->renderGuestBox($data));
			*/
		}

	}

} 


?>