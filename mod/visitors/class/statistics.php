<?php	// part of the visitors module
	// see http://www.kiesler.at/article148.html


	function timestampformat($type) {
		if (!isset($_SESSION['timestampformat'])) {
			$prefix=$GLOBALS['core']->tbl_prefix;
			$q = $GLOBALS['core']->query("SELECT timestamp FROM ${prefix}mod_visitors_hit LIMIT 0, 1", false);
			$q = $q->fetchRow();
			if (!$q) return array(0, 0);
			if (eregi("^[0-9]{4}-[0-9]{2}-[0-9]{2}", $q['timestamp']))
				$_SESSION['timestampformat'] = array('year'=>array(4, 6), 'month'=>array(7, 9), 'day'=>array(10, 12));
			else 
				$_SESSION['timestampformat'] = array('year'=>array(4, 5), 'month'=>array(6, 7), 'day'=>array(8, 9));
		}
		return $_SESSION['timestampformat'][$type];
	}

	function zlead($what, $how_much) {

		// adds '0' in front of $what
		// until $what is at least
		// $how_much long


		while(strlen($what) < $how_much)
			$what='0'.$what;

		return($what);

	}


	function getDayUsers($year, $month, $day) {
		if (isset($_REQUEST['subop']))
			$user = $_REQUEST['username'];

		$prefix=$GLOBALS['core']->tbl_prefix;

		$year=zlead($year, 4);
		$month=zlead($month, 2);
		$day=zlead($day, 2);

		$f = timestampformat('day');
		if ($f[1] - $f[0] == 1)
			$ymd="$year$month$day";
		else $ymd="$year-$month-$day";

		$sql =	"SELECT count(id) hits, ";
		$sql.=		"user username, ";
		$sql.=		"mid(timestamp, {$f[1]}, 2) hour ";
		if ($user) $sql.= ", REQUEST_URI, id ";
		$sql.=	"FROM ${prefix}mod_visitors_hit ";
		$sql.=	"WHERE left(timestamp, {$f[0]})='$ymd' ";
		if ($user) {
			$sql.=	" AND user='$user' ";
			$sql.=	"GROUP BY id ";
			$sql.=	"ORDER BY id";
		}
		else {
			$sql.=	"GROUP BY mid(timestamp, {$f[1]}, 2), username ";
			$sql.=	"ORDER BY mid(timestamp, {$f[1]}, 2), username";
		}

		return($GLOBALS['core']->getAllAssoc($sql));

	}


	function getMonthUsers($year, $month) {
		if (isset($_REQUEST['subop']))
			$user = $_REQUEST['username'];

		$prefix=$GLOBALS['core']->tbl_prefix;


		$year=zlead($year, 4);
		$month=zlead($month, 2);

		$f = timestampformat('month');
		if ($f[1] - $f[0] == 1)
			$ym="$year$month";
		else $ym="$year-$month";

		$sql =	"SELECT count(id) hits, ";
		$sql.=		"user username, ";
		$sql.=		"mid(timestamp, {$f[1]}, 2) day ";
		if ($user) $sql.= ", REQUEST_URI, id ";
		$sql.=	"FROM ${prefix}mod_visitors_hit ";
		$sql.=	"WHERE left(timestamp, {$f[0]})='$ym' ";
		if ($user) {
			$sql.=	" AND user='$user' ";
			$sql.=	"GROUP BY id ";
			$sql.=	"ORDER BY id";
		}
		else {
			$sql.=	"GROUP BY mid(timestamp, {$f[1]}, 2), username ";
			$sql.=	"ORDER BY mid(timestamp, {$f[1]}, 2), username";
		}

		return($GLOBALS['core']->getAllAssoc($sql));

	}


	function getYearUsers($year) {
		if (isset($_REQUEST['subop']))
			$user = $_REQUEST['username'];

		$prefix=$GLOBALS['core']->tbl_prefix;

		$f = timestampformat('year');

		$year=zlead($year, 4);

		$sql =	"SELECT count(id) hits, ";
		$sql.=		"user username, ";
		$sql.=		"mid(timestamp, {$f[1]}, 2) month ";
		if ($user) $sql.= ", REQUEST_URI, id ";
		$sql.=	"FROM ${prefix}mod_visitors_hit ";
		$sql.=	"WHERE left(timestamp, {$f[0]})='$year' ";
		if ($user) {
			$sql.=	" AND user='$user' ";
			$sql.=	"GROUP BY id ";
			$sql.=	"ORDER BY id";
		}
		else {
			$sql.=	"GROUP BY mid(timestamp, {$f[1]}, 2), username ";
			$sql.=	"ORDER BY mid(timestamp, {$f[1]}, 2), username";
		}

		return($GLOBALS['core']->getAllAssoc($sql));

	}

	function getAllUsers() {

		$prefix=$GLOBALS['core']->tbl_prefix;

		$sql =	"SELECT count(id) hits, ";
		$sql.=		"user username, ";
		$sql.=		"left(timestamp, 4) year ";
		$sql.=	"FROM ${prefix}mod_visitors_hit ";
		$sql.=	"GROUP BY left(timestamp, 4), username ";
		$sql.=	"ORDER BY left(timestamp, 4), username";

		return($GLOBALS['core']->getAllAssoc($sql));

	}


	function renderUserHeader($content_var_name) {

		$content_caption=$_SESSION['translate']->it($content_var_name);
		$hits_caption=$_SESSION['translate']->it('hits');
		$users_caption=$_SESSION['translate']->it('users');
		$usernames_caption=$_SESSION['translate']->it('usernames');


		$html ="<tr><th>$content_var_name</th><th>$hits_caption</th>";
		$html.="<th>$users_caption</th><th>$usernames_caption</th></tr>\n";

		return($html);
	}


	function renderUserLine($caption, $hit_count, $user_count, $user_array) {

		$html = "<tr>";
		$html.= "<td>$caption</td>";
		$html.= "<td>$hit_count</td>";
		$html.= "<td>$user_count</td>";

		$usernames=implode(", ", $user_array);

		$html.= "<td>$usernames</td>";
		$html.= "</tr>\n";

		return($html);

	}


	function renderUserLineWithLink($caption, $hit_count, $user_count, $user_array,
		$year=null, $month=null, $day=null) {


		$linka=array();
		$linka['visitors_op']='users';

		$fld = "visitors_op=users";

		if(isset($year)) {
			$linka['year']=$year;
			$fld .= "&year=$year";
		}

		if(isset($year) && isset($month)) {
			$linka['month']=$month;
			$fld .= "&month=$month";
		}

		if(isset($year) && isset($month) && isset($day)) {
			$linka['day']=$day;
			$fld .= "&day=$day";
		}

		if (isset($_REQUEST['subop']) && $_REQUEST['subop'] == 'useraction') {
			$link=PHPWS_Text::moduleLink($caption,
					'visitors', $linka);

			$html = "<tr>";
			$html.= "<td>$link</td>";
			$html.= "<td><a href=./index.php?module=visitors&$fld&subop=delete&username={$_REQUEST['username']}>Delete</a></td>";

			$html.= "<td>";
			$usernames=implode("<br>", $user_array);
			$html.= "<td>$usernames</td>";
			$html.= "</tr>\n";
		}
		elseif (isset($_REQUEST['subop']) && $_REQUEST['subop'] == 'delete') {
		}
		else {
			$link=PHPWS_Text::moduleLink($caption,
					'visitors', $linka);

			$html = "<tr>";
			$html.= "<td>$link</td>";
			$html.= "<td>$hit_count</td>";
			$html.= "<td>$user_count</td>";

			$html.= "<td>";
			$glue = "";

			foreach ($user_array as $u) {
				$html .= $glue . "<a href=./index.php?module=visitors&$fld&subop=useraction&username=$u>$u</a>";
				$glue = ", ";
			}
	//		$usernames=implode(", ", $user_array);
	//		$html.= "<td><a href=./index.php?module=visitors&$fld>$usernames</a></td>";
			$html.= "</td></tr>\n";
		}

		return($html);

	}



	function renderUsers($data, $content_var_name, $year=null, $month=null, $day=null) {

		$html="<table>";

		if (isset($_REQUEST['subop']) && $_REQUEST['subop'] == 'useraction') {
			$h = renderUserHeader($content_var_name);
			$h = str_replace("<th>usernames</th>", "<th>action</th>", str_replace("<th>hits</th>", "", $h));
			$html.= $h;
		}
		elseif (isset($_REQUEST['subop']) && $_REQUEST['subop'] == 'delete') {
			foreach($data as $nr => $row) {
				$prefix=$GLOBALS['core']->tbl_prefix;
				$GLOBALS['core']->query("DELETE FROM ${prefix}mod_visitors_hit WHERE id=" . $row['id'], false);
				$html .= "<tr><td>{$row['id']}: {$row['REQUEST_URI']} deleted.</td></tr>";
			}
			$html.="</table>";
			return($html);
		}
		else 
			$html.=renderUserHeader($content_var_name);

		$content="";

		$old_content_var="99";
		$usernames=array();

		$hit_count=0;
		$user_count=0;

		foreach($data as $nr => $row) {
			$content_var=$row[$content_var_name];

			if($content_var != $old_content_var) {

				if($hit_count > 0) {
					if($content_var_name != 'hour') {
						if($content_var_name == 'day')
							$day=$old_content_var;
						else
						if($content_var_name == 'month')
							$month=$old_content_var;
						else
						if($content_var_name == 'year')
							$year=$old_content_var;

						$content.=renderUserLineWithLink($old_content_var, $hit_count, $user_count, $usernames,
							$year, $month, $day);

					} else
						$content.=renderUserLine($old_content_var, $hit_count, $user_count, $usernames);

				}

				$usernames=array();
				$hit_count=0;
				$user_count=0;
				$old_content_var=$content_var;

			}


			$hit_count+=$row['hits'];

			$user=trim($row['username']);

			if(strlen($user)>0) {
				$user_count++;

				if(isset($row['email'])) {

					$email=$row['email'];
					$user.=" <$email>";
				}

				if (isset($_REQUEST['subop']))
					 $usernames[] = $row['REQUEST_URI'];
				else $usernames[] = $user;
			}

		}

		if($hit_count > 0) {
			if($content_var_name != 'hour') {
				if($content_var_name == 'day')
					$day=$old_content_var;
				else
				if($content_var_name == 'month')
					$month=$old_content_var;
				else
				if($content_var_name == 'year')
					$year=$old_content_var;

				$content.=renderUserLineWithLink($content_var, $hit_count, $user_count, $usernames,
					$year, $month, $day);
			} else
				$content.=renderUserLine($content_var, $hit_count, $user_count, $usernames);
		}


		$html.=$content;

		$html.="</table>";

		return($html);

	}

	
	function getDayHits($year, $month, $day) {
		$prefix=$GLOBALS['core']->tbl_prefix;


		$year=zlead($year, 4);
		$month=zlead($month, 2);
		$day=zlead($day, 2);

		$f = timestampformat('day');
		if ($f[1] - $f[0] == 1)
			$ymd="$year$month$day";
		else $ymd="$year-$month-$day";

		$sql =	"SELECT count(id) hits, ";
		$sql.=		"count(distinct session_id) sessions, ";
		$sql.=		"count(distinct ip) ips, ";
		$sql.=		"count(distinct user_id) users, ";
		$sql.=		"mid(timestamp, {$f[1]}, 2) hour ";
		$sql.=	"FROM ${prefix}mod_visitors_hit ";
		$sql.=	"WHERE left(timestamp, {$f[0]})='$ymd' ";
		$sql.=	"GROUP BY mid(timestamp, {$f[1]}, 2)";

		return($GLOBALS['core']->getAllAssoc($sql));
	}


	function sumDayHits($year, $month, $day) {

		$prefix=$GLOBALS['core']->tbl_prefix;

		$year=zlead($year, 4);
		$month=zlead($month, 2);
		$day=zlead($day, 2);

		$f = timestampformat('day');
		if ($f[1] - $f[0] == 1)
			$ymd="$year$month$day";
		else $ymd="$year-$month-$day";

		$sql =	"SELECT count(id) hits, ";
		$sql.=		"count(distinct session_id) sessions, ";
		$sql.=		"count(distinct ip) ips, ";
		$sql.=		"count(distinct user_id) users ";
		$sql.=	"FROM ${prefix}mod_visitors_hit ";
		$sql.=	"WHERE left(timestamp, {$f[0]})='$ymd' ";

		$result=$GLOBALS['core']->getAllAssoc($sql);
		return($result[0]);

	}



	function getMonthHits($year, $month) {

		$prefix=$GLOBALS['core']->tbl_prefix;

		$year=zlead($year, 4);
		$month=zlead($month, 2);

		$f = timestampformat('month');
		if ($f[1] - $f[0] == 1)
			$ym="$year$month";
		else $ym="$year-$month";

		$sql =	"SELECT count(id) hits, ";
		$sql.=		"count(distinct session_id) sessions, ";
		$sql.=		"count(distinct ip) ips, ";
		$sql.=		"count(distinct user_id) users, ";
		$sql.=		"mid(timestamp, {$f[1]}, 2) day ";
		$sql.=	"FROM ${prefix}mod_visitors_hit ";
		$sql.=	"WHERE left(timestamp, {$f[0]})='$ym' ";
		$sql.=	"GROUP BY mid(timestamp, {$f[1]}, 2)";

		return($GLOBALS['core']->getAllAssoc($sql));
	}


	function sumMonthHits($year, $month) {
		$prefix=$GLOBALS['core']->tbl_prefix;

		while(strlen($year)<4)
			$year='0'.$year;

		while(strlen($month)<2)
			$month='0'.$month;

		$f = timestampformat('month');
		if ($f[1] - $f[0] == 1)
			$ym="$year$month";
		else $ym="$year-$month";


		$sql =	"SELECT count(id) hits, ";
		$sql.=		"count(distinct session_id) sessions, ";
		$sql.=		"count(distinct ip) ips, ";
		$sql.=		"count(distinct user_id) users ";
		$sql.=	"FROM ${prefix}mod_visitors_hit ";
		$sql.=	"WHERE left(timestamp, {$f[0]})='$ym' ";

		$result=$GLOBALS['core']->getAllAssoc($sql);

		return($result[0]);
	}


	function getYearHits($year) {

		$prefix=$GLOBALS['core']->tbl_prefix;

		$f = timestampformat('year');

		while(strlen($year)<4)
			$year='0'.$year;

		$sql =	"SELECT count(id) hits, ";
		$sql.=		"count(distinct session_id) sessions, ";
		$sql.=		"count(distinct ip) ips, ";
		$sql.=		"count(distinct user_id) users, ";
		$sql.=		"mid(timestamp, {$f[1]}, 2) month ";
		$sql.=	"FROM ${prefix}mod_visitors_hit ";
		$sql.=	"WHERE left(timestamp, {$f[0]})='$year' ";
		$sql.=	"GROUP BY mid(timestamp, {$f[1]}, 2)";

		return($GLOBALS['core']->getAllAssoc($sql));
	}


	function sumYearHits($year) {

		$prefix=$GLOBALS['core']->tbl_prefix;

		$f = timestampformat('year');
		while(strlen($year)<4)
			$year='0'.$year;

		$sql =	"SELECT count(id) hits, ";
		$sql.=		"count(distinct session_id) sessions, ";
		$sql.=		"count(distinct ip) ips, ";
		$sql.=		"count(distinct user_id) users ";
		$sql.=	"FROM ${prefix}mod_visitors_hit ";
		$sql.=	"WHERE left(timestamp, {$f[0]})='$year' ";

		$result=$GLOBALS['core']->getAllAssoc($sql);
		return($result[0]);

	}


	function getAllHits() {

		$f = timestampformat('year');
		$prefix=$GLOBALS['core']->tbl_prefix;

		$sql =	"SELECT count(id) hits, ";
		$sql.=		"count(distinct session_id) sessions, ";
		$sql.=		"count(distinct ip) ips, ";
		$sql.=		"count(distinct user_id) users, ";
		$sql.=		"left(timestamp, {$f[0]}) year ";
		$sql.=	"FROM ${prefix}mod_visitors_hit ";
		$sql.=	"GROUP BY left(timestamp, {$f[0]})";

		return($GLOBALS['core']->getAllAssoc($sql));
	}


	function sumAllHits() {

		$prefix=$GLOBALS['core']->tbl_prefix;

		$sql =	"SELECT count(id) hits, ";
		$sql.=		"count(distinct session_id) sessions, ";
		$sql.=		"count(distinct ip) ips, ";
		$sql.=		"count(distinct user_id) users ";
		$sql.=	"FROM ${prefix}mod_visitors_hit ";

		$result=$GLOBALS['core']->getAllAssoc($sql);
		return($result[0]);

	}


	function avgHits($data, $sum) {

		$avg=array();

		if(empty($data)) {

			$avg['hits']=0;
			$avg['sessions']=0;
			$avg['ips']=0;
			$avg['users']=0;

		} else {

			$count=sizeof($data);


			$avg['hits']=$sum['hits']/$count;
			$avg['sessions']=$sum['sessions']/$count;
			$avg['ips']=$sum['ips']/$count;
			$avg['users']=$sum['users']/$count;
		}

		return($avg);
	}



	function renderHitLineComma($line, $caption) {

		$hits=sprintf("%01.1f", $line['hits']);
		$sessions=sprintf("%01.1f", $line['sessions']);
		$ips=sprintf("%01.1f", $line['ips']);
		$users=sprintf("%01.1f", $line['users']);

		$html ="<tr>";
		$html.="<td>$caption</td>";
		$html.="<td>$hits</td>";
		$html.="<td>$sessions</td>";
		$html.="<td>$ips</td>";
		$html.="<td>$users</td>";
		$html.="</tr>\n";

		return($html);

	}


	function renderHitHeader($content_var_name) {

		$content_caption=$_SESSION['translate']->it($content_var_name);

		$hits_caption=$_SESSION['translate']->it('hits');
		$sessions_caption=$_SESSION['translate']->it('sessions');
		$ips_caption=$_SESSION['translate']->it('ips');
		$users_caption=$_SESSION['translate']->it('users');

		$html = "<tr><th>$content_caption</th><th>$hits_caption</th>";
		$html.= "<th>$sessions_caption</th><th>$ips_caption</th>";
		$html.= "<th>$users_caption</th></tr>\n";

		return($html);

	}


	function renderHitLine($line, $caption) {

		$hits=$line['hits'];
		$sessions=$line['sessions'];
		$ips=$line['ips'];
		$users=$line['users'];

		$html ="<tr>";
		$html.="<td>$caption</td>";
		$html.="<td>$hits</td>";
		$html.="<td>$sessions</td>";
		$html.="<td>$ips</td>";
		$html.="<td>$users</td>";
		$html.="</tr>\n";

		return($html);

	}


	function renderDayHits($data, $avg=null, $sum=null,
			$year, $month, $day) {

		if(empty($data))
			return($_SESSION['translate']->it('no data.'));

		$html = "<table>\n";
		$html.= renderHitHeader('hour');

		foreach($data as $nr => $row) {

			$hour=$row['hour'];
			$html.=renderHitLine($row, $hour);

		}


		if(isset($sum)) {
			$total_caption=$_SESSION['translate']->it('TOTAL');
			$html.=renderHitLine($sum,
				"<strong>$total_caption</strong>");
		}

		if(isset($avg)) {
			$avg_caption=$_SESSION['translate']->it('AVG');
			$html.=renderHitLineComma($avg, "$avg_caption");
		}

		$html.="</table>\n"; 

		return($html);
	}




	function renderMonthHits($data, $avg=null, $sum=null,
			$year, $month) {

		if(empty($data))
			return($_SESSION['translate']->it('no data.'));

		$html = "<table>\n";
		$html.= renderHitHeader('day');

		$linka=array();
		$linka['visitors_op']='stats';
		$linka['year']=$year;
		$linka['month']=$month;

		foreach($data as $nr => $row) {

			$day=$row['day'];
			$linka['day']=$day;
			$link=PHPWS_Text::moduleLink($day, 'visitors', $linka);

			$html.=renderHitLine($row, $link);

		}

		if(isset($sum)) {
			$total_caption=$_SESSION['translate']->it('TOTAL');
			$html.=renderHitLine($sum,
				"<strong>$total_caption</strong>");
		}

		if(isset($avg)) {
			$avg_caption=$_SESSION['translate']->it('AVG');
			$html.=renderHitLineComma($avg, "$avg_caption");
		}

		$html.="</table>\n"; 

		return($html);
	}



	function renderYearHits($data, $avg=null, $sum=null,
			$year) {

		if(empty($data))
			return('no data.');

		$html ="<table>\n";
		$html.= renderHitHeader('month');

		$linka=array();
		$linka['visitors_op']='stats';
		$linka['year']=$year;

		foreach($data as $nr => $row) {

			$month=$row['month'];
			$linka['month']=$month;
			$link=PHPWS_Text::moduleLink($month, 'visitors', $linka);


			$html.=renderHitLine($row, $link);

		}


		if(isset($sum)) {
			$total_caption=$_SESSION['translate']->it('TOTAL');
			$html.=renderHitLine($sum,
				"<strong>$total_caption</strong>");
		}

		if(isset($avg)) {
			$avg_caption=$_SESSION['translate']->it('AVG');
			$html.=renderHitLineComma($avg, "$avg_caption");
		}

		$html.="</table>\n"; 

		return($html);
	}


	function renderAllHits($data, $avg=null, $sum=null) {

		if(empty($data))
			return('no data.');

		$html ="<table>\n";
		$html.= renderHitHeader('year');

		$linka=array();
		$linka['visitors_op']='stats';

		foreach($data as $nr => $row) {

			$year=$row['year'];
			$linka['year']=$year;
			$link=PHPWS_Text::moduleLink($year, 'visitors', $linka);


			$html.=renderHitLine($row, $link);

		}

		if(isset($sum)) {
			$total_caption=$_SESSION['translate']->it('TOTAL');
			$html.=renderHitLine($sum,
				"<strong>$total_caption</strong>");
		}

		if(isset($avg)) {
			$avg_caption=$_SESSION['translate']->it('AVG');
			$html.=renderHitLineComma($avg, "$avg_caption");
		}

		$html.="</table>\n"; 

		return($html);
	}


	function renderMenu($current, $year, $month, $day) {

		$menu=array();

		$menu['users']	=$_SESSION['translate']->it('by users');

		$menu['stats']	=$_SESSION['translate']->it('by hits');


		$menu['query']	=$_SESSION['translate']->it('by ext. search queries');

		$menu['refhosts']=
			$_SESSION['translate']->it('by referrer hosts');




		$html=array();

		foreach($menu as $op => $caption) {

			if($current == $op) {

				$html[]="[ $caption ]";

			} else {

				$linka=array();
				$linka['visitors_op']=$op;

				if(isset($year))
					$linka['year']=$year;

				if(isset($year) && isset($month))
					$linka['month']=$month;

				if(isset($year) && isset($month) && isset($day))
					$linka['day']=$day;

				$html[]=PHPWS_Text::moduleLink($caption,
					'visitors', $linka);

			}
		}


		return("<p>".implode(' | ', $html)."</p>");

	}



	//
	//   FIRST REFERER HOST
	//


	/* added 2005-03-15
	*/


	function firstRefTerm() {

		$term =	'substring(HTTP_REFERER, ';
		$term.= 	'locate("//", HTTP_REFERER)+2, ';
		$term.=		'locate("/", HTTP_REFERER,9)-';
		$term.=			'locate("//", HTTP_REFERER)-2';
		$term.=	')';

		return("rtrim($term)");
	}


	function getDayRefHosts($year, $month, $day) {

		$prefix=$GLOBALS['core']->tbl_prefix;
	
		$year=zlead($year, 4);
		$month=zlead($month, 2);
		$day=zlead($day, 2);

		$f = timestampformat('day');
		if ($f[1] - $f[0] == 1)
			$ymd="$year$month$day";
		else $ymd="$year-$month-$day";
		$firstref=firstRefTerm();

		$sql =	"SELECT count(id) hits, $firstref refhost, ";
		$sql.=		"mid(timestamp, {$f[1]}, 2) hour ";
		$sql.=	"FROM ${prefix}mod_visitors_hit ";
		$sql.=	"WHERE left(timestamp, {$f[0]})='$ymd' ";
		$sql.=	"GROUP BY mid(timestamp, {$f[1]}, 2), refhost ";
		$sql.=	"ORDER BY mid(timestamp, {$f[1]}, 2), hits";

		return($GLOBALS['core']->getAllAssoc($sql));
	}


	function getMonthRefHosts($year, $month) {

		$prefix=$GLOBALS['core']->tbl_prefix;
	
		$year=zlead($year, 4);
		$month=zlead($month, 2);

		$f = timestampformat('month');
		if ($f[1] - $f[0] == 1)
			$ym="$year$month";
		else $ym="$year-$month";
		$firstref=firstRefTerm();

		$sql =	"SELECT count(id) hits, $firstref refhost, ";
		$sql.=		"mid(timestamp, {$f[1]}, 2) day ";
		$sql.=	"FROM ${prefix}mod_visitors_hit ";
		$sql.=	"WHERE left(timestamp, {$f[0]})='$ym' ";
		$sql.=	"GROUP BY mid(timestamp, {$f[1]}, 2), refhost ";
		$sql.=	"ORDER BY mid(timestamp, {$f[1]}, 2), hits";

		return($GLOBALS['core']->getAllAssoc($sql));
	}


	function getYearRefHosts($year) {

		$prefix=$GLOBALS['core']->tbl_prefix;
	
		$f = timestampformat('year');

		$year=zlead($year, 4);

		$firstref=firstRefTerm();

		$sql =	"SELECT count(id) hits, $firstref refhost, ";
		$sql.=		"mid(timestamp, {$f[1]}, 2) month ";
		$sql.=	"FROM ${prefix}mod_visitors_hit ";
		$sql.=	"WHERE left(timestamp, {$f[0]})='$year' ";
		$sql.=	"GROUP BY mid(timestamp, {$f[1]}, 2), refhost ";
		$sql.=	"ORDER BY mid(timestamp, {$f[1]}, 2), hits";

		return($GLOBALS['core']->getAllAssoc($sql));

	}


	function getAllRefHosts() {

		$prefix=$GLOBALS['core']->tbl_prefix;
	
		$f = timestampformat('year');
		$firstref=firstRefTerm();

		$sql =	"SELECT count(id) hits, $firstref refhost, ";
		$sql.=		"left(timestamp, {$f[0]}) year ";
		$sql.=	"FROM ${prefix}mod_visitors_hit ";
		$sql.=	"GROUP BY left(timestamp, {$f[0]}), refhost ";
		$sql.=	"ORDER BY left(timestamp, {$f[0]}), hits";

		return($GLOBALS['core']->getAllAssoc($sql));
	}




	function renderRefHostsHeader($content_var_name) {

		$content_caption=$_SESSION['translate']->it($content_var_name);

		$host_caption=$_SESSION['translate']->it('hosts');
		$hits_caption=$_SESSION['translate']->it('hits');
		$ref_caption=$_SESSION['translate']->it('referrer hosts');

		$html = "<tr><th>$content_caption</th><th>$hits_caption</th>";
		$html.= "<th>$host_caption</th>";
		$html.= "<th>$ref_caption</th></tr>\n";

		return($html);

	}



	function renderRefHostsLine($caption, $hit_count, $ref_array) {

		$html = "<tr>";
		$html.= "<td>$caption</td>";
		$html.= "<td>$hit_count</td>";


		$ref_count=sizeof($ref_array);
		$html.= "<td>$ref_count</td>";

		$refs=implode(", ", $ref_array);

		$html.= "<td>$refs</td>";
		$html.= "</tr>\n";

		return($html);
	}



	function renderRefHostsLineWithLink($caption, $hit_count, $ref_array,
		$year=null, $month=null, $day=null) {

		$linka=array();
		$linka['visitors_op']='refhosts';

		if(isset($year));
			$linka['year']=$year;

		if(isset($year) && isset($month))
			$linka['month']=$month;

		if(isset($year) && isset($month) && isset($day))
			$linka['day']=$day;

		$link=PHPWS_Text::moduleLink($caption,
			'visitors', $linka);

		$html = "<tr>";
		$html.= "<td>$link</td>";
		$html.= "<td>$hit_count</td>";

		$ref_count=sizeof($ref_array);
		$html.= "<td>$ref_count</td>";

		$refs=implode(", ", $ref_array);

		$html.= "<td>$refs</td>";
		$html.= "</tr>\n";

		return($html);
	}

	function renderRefHosts($data, $content_var_name,
			$year=null, $month=null, $day=null) {

		$html ="<table>";


		$html.=renderRefHostsHeader($content_var_name);

		$content="";

		$old_content_var="99";
		$refs=array();

		$hit_count=0;
		$ref_count=0;


		foreach($data as $nr => $row) {

			$content_var=$row[$content_var_name];

			if($content_var != $old_content_var) {

				if($hit_count > 0) {

					if($content_var_name != 'hour') {

						if($content_var_name == 'day')
							$day=$old_content_var;
						else
						if($content_var_name == 'month')
							$month=$old_content_var;
						else
						if($content_var_name == 'year')
							$year=$old_content_var;

						$content.=renderRefHostsLineWithLink($old_content_var, $hit_count,
							$refs, $year, $month, $day);

					} else
						$content.=renderRefHostsLine(
							$old_content_var, $hit_count,
							$refs);

				}

				$refs=array();
				$hit_count=0;
				$ref_count=0;
				$old_content_var=$content_var;

			}

			$hit_count+=$row['hits'];

			$refhost=trim($row['refhost']);

			if(strlen($refhost)>0) {
				$ref_count++;
				$refs[]="$refhost&nbsp;($hit_count)";
			}

		}

		if($hit_count > 0) {

			if($content_var_name != 'hour') {

				if($content_var_name == 'day')
					$day=$old_content_var;
				else
				if($content_var_name == 'month')
					$month=$old_content_var;
				else
				if($content_var_name == 'year')
					$year=$old_content_var;

				$content.=renderRefHostsLineWithLink(
					$content_var, $hit_count, $refs,
						$year, $month, $day);

			}else
				$content.=renderRefHostsLine($content_var,
					$hit_count, $refs);

		}

		$html.=$content;

		$html.="</table>";

		return($html);

	}







	// SKELLETON -- added 2005-07-01


	function getDaySkelleton($year, $month, $day, $fields, $group_by, $where) {

		$prefix=$GLOBALS['core']->tbl_prefix;
	
		$year=zlead($year, 4);
		$month=zlead($month, 2);
		$day=zlead($day, 2);

		$f = timestampformat('day');
		if ($f[1] - $f[0] == 1)
			$ymd="$year$month$day";
		else $ymd="$year-$month-$day";

		$fields=implode(',', $fields);
		$group_by=implode(',', $group_by);

		$sql =	"SELECT count(id) hits, $fields, ";
		$sql.=		"mid(timestamp, {$f[1]}, 2) hour ";
		$sql.=	"FROM ${prefix}mod_visitors_hit ";
		$sql.=	"WHERE left(timestamp, {$f[0]})='$ymd' ";

		if(isset($where))
			$sql.="AND ($where) ";


		$sql.=	"GROUP BY mid(timestamp, {$f[1]}, 2), $group_by ";
		$sql.=	"ORDER BY mid(timestamp, {$f[1]}, 2), hits";

		return($GLOBALS['core']->getAllAssoc($sql));
	}



	function getMonthSkelleton($year, $month, $fields, $group_by, $where) {


		$prefix=$GLOBALS['core']->tbl_prefix;
	
		$year=zlead($year, 4);
		$month=zlead($month, 2);

		$f = timestampformat('month');
		if ($f[1] - $f[0] == 1)
			$ym="$year$month";
		else $ym="$year-$month";

		$fields=implode(',', $fields);
		$group_by=implode(',', $group_by);

		$sql =	"SELECT count(id) hits, $fields, ";
		$sql.=		"mid(timestamp, {$f[1]}, 2) day ";
		$sql.=	"FROM ${prefix}mod_visitors_hit ";
		$sql.=	"WHERE left(timestamp, {$f[0]})='$ym' ";

		if(isset($where))
			$sql.="AND ($where) ";

		$sql.=	"GROUP BY mid(timestamp, {$f[1]}, 2), $group_by ";
		$sql.=	"ORDER BY mid(timestamp, {$f[1]}, 2), hits";

		return($GLOBALS['core']->getAllAssoc($sql));

	}


	function getYearSkelleton($year, $fields, $group_by, $where) {


		$prefix=$GLOBALS['core']->tbl_prefix;
	
		$f = timestampformat('year');
		$year=zlead($year, 4);

		$fields=implode(',', $fields);
		$group_by=implode(',', $group_by);


		$sql =	"SELECT count(id) hits, $fields, ";
		$sql.=		"mid(timestamp, {$f[1]}, 2) month ";
		$sql.=	"FROM ${prefix}mod_visitors_hit ";
		$sql.=	"WHERE left(timestamp, {$f[0]})='$year' ";

		if(isset($where))
			$sql.="AND ($where) ";

		$sql.=	"GROUP BY mid(timestamp, {$f[1]}, 2), $group_by ";
		$sql.=	"ORDER BY mid(timestamp, {$f[1]}, 2), hits";

		return($GLOBALS['core']->getAllAssoc($sql));


	}


	function getAllSkelleton($fields, $group_by, $where) {


		$prefix=$GLOBALS['core']->tbl_prefix;

		$f = timestampformat('year');
		$fields=implode(',', $fields);
		$group_by=implode(',', $group_by);

		$sql =	"SELECT count(id) hits, $fields, ";
		$sql.=		"left(timestamp, {$f[0]}) year ";
		$sql.=	"FROM ${prefix}mod_visitors_hit ";

		if(isset($where))
			$sql.="WHERE $where ";

		$sql.=	"GROUP BY left(timestamp, {$f[0]}), $group_by ";
		$sql.=	"ORDER BY left(timestamp, {$f[0]}), hits";

		return($GLOBALS['core']->getAllAssoc($sql));
	
	}







	//
	//   EXTERNAL SEARCH QUERY STRINGS
	//


	/* added 2005-07-01
	*/


	function queryOrigin() {

		$term = 'SUBSTRING(';
		$term.=		'HTTP_REFERER,';
		$term.=		'LOCATE(".", HTTP_REFERER)+1,';
		$term.=		'LOCATE("/",HTTP_REFERER,9)-';
		$term.=			'LOCATE(".",HTTP_REFERER)-1';
		$term.= ')';

		return("$term origin");
	}


	function querySearchString() {

		$term = 'REPLACE(';
		$term.=		'SUBSTRING_INDEX(';
		$term.=			'SUBSTRING_INDEX(HTTP_REFERER,';
		$term.=				'"q=", -1),';
		$term.=			'"&",1),';
		$term.=		'"+", 0x20)';

		return("$term term");

	}




	function getQueriesFields() {

		$fields=array();
		$fields[]=queryOrigin();
		$fields[]=querySearchString();

		return($fields);
	}


	function getQueriesGroupBy() {

		$group_by=array();
		$group_by[]='origin';
		$group_by[]='term';

		return($group_by);
	}

	function getQueriesWhere() {

		$where ="HTTP_REFERER LIKE '%q=%' AND NOT (HTTP_REFERER LIKE '%cache:%')";
		return($where);

	}


	function getDayQueries($year, $month, $day) {


		$fields=getQueriesFields();
		$group_by=getQueriesGroupBy();
		$where=getQueriesWhere();

		return(getDaySkelleton($year, $month, $day, $fields, $group_by, $where));

	}


	function getMonthQueries($year, $month) {

		$fields=getQueriesFields();
		$group_by=getQueriesGroupBy();
		$where=getQueriesWhere();

		return(getMonthSkelleton($year, $month, $fields, $group_by, $where));


	}


	function getYearQueries($year) {

		$fields=getQueriesFields();
		$group_by=getQueriesGroupBy();
		$where=getQueriesWhere();

		return(getYearSkelleton($year, $fields, $group_by, $where));

	}


	function getAllQueries() {

		$fields=getQueriesFields();
		$group_by=getQueriesGroupBy();
		$where=getQueriesWhere();

		return(getAllSkelleton($fields, $group_by, $where));

	}






	function renderQueriesHeader($content_var_name) {

		$content_caption=$_SESSION['translate']->it($content_var_name);
		$hits_caption=$_SESSION['translate']->it('hits');

/*		$origins_count_caption=$_SESSION['translate']->it('#origins'); */

		$origin_caption=$_SESSION['translate']->it('origin');

/*		$terms_count_caption=$_SESSION['translate']->it('#terms'); */

		$terms_caption=$_SESSION['translate']->it('term');

/*		$words_caption=$_SESSION['translate']->it('word'); */

		$html = "<tr><th>$content_caption</th><th>$hits_caption</th>";
		$html.= "<th>$origin_caption</th><th>$terms_caption</th></tr>\n";

		return($html);

	}



	function renderQueriesLine($caption, $hit_count, $origins, $terms, $words) {

		$html = "<tr>";
		$html.= "<td>$caption</td>";
		$html.= "<td>$hit_count</td>";

/*
		$origin_count=sizeof($origins);
		$html.= "<td>$origin_count</td>";
*/

		$origins=implode(", ", $origins);
		$html.= "<td>$origins</td>";

/*
		$terms_count=sizeof($terms);
		$html.= "<td>$terms_count</td>";
*/

		$terms=implode(", ", $terms);
		$html.= "<td>$terms</td>";

/*
		$words=implode(", ", $words);
		$html.= "<td>$words</td>";
*/

		$html.= "</tr>\n";

		return($html);
	}



	function renderQueriesLineWithLink($caption, $hit_count, $origins, $terms, $words,
		$year=null, $month=null, $day=null) {

		$linka=array();
		$linka['visitors_op']='query';

		if(isset($year));
			$linka['year']=$year;

		if(isset($year) && isset($month))
			$linka['month']=$month;

		if(isset($year) && isset($month) && isset($day))
			$linka['day']=$day;

		$link=PHPWS_Text::moduleLink($caption,
			'visitors', $linka);



		$html = "<tr>";
		$html.= "<td>$link</td>";
		$html.= "<td>$hit_count</td>";

/*
		$origin_count=sizeof($origins);
		$html.= "<td>$origin_count</td>";
*/

		$origins=implode(", ", $origins);
		$html.= "<td>$origins</td>";

/*
		$terms_count=sizeof($terms);
		$html.= "<td>$terms_count</td>";
*/

		$terms=implode(", ", $terms);
		$html.= "<td>$terms</td>";

/*
		$words=implode(", ", $words);
		$html.= "<td>$words</td>\n";
*/

		$html.= "</tr>\n";

		return($html);
	}


	function prepare_origins($origins) {

		$new_origins=array();
		arsort($origins);

		foreach($origins as $name => $hits) {
			$new_origins[]="$name($hits)";
		}

		if(sizeof($new_origins)>0)
			return($new_origins);
		else
			return(null);
	}


	// from http://at.php.net/manual/en/function.html-entity-decode.php
	
	function decode_entities($text) {

		$text=html_entity_decode($text, ENT_QUOTES, 'ISO-8859-1');

		$text= preg_replace('/&#(\d+);/me',"chr(\\1)",$text); #decimal notation
		$text= preg_replace('/&#x([a-f0-9]+);/mei',"chr(0x\\1)",$text);  #hex notation

		return $text;
	}

	function prepare_terms($terms) {

		arsort($terms);

		$new_terms=array();

		foreach($terms as $name => $hits) {
			$new_terms[]="$name($hits)";
		}

		if(sizeof($new_terms)>0)
			return($new_terms);
		else
			return(null);
	}


	function renderQueries($data, $content_var_name,
			$year=null, $month=null, $day=null) {

		$html ="<table>";


		$html.=renderQueriesHeader($content_var_name);

		$content="";

		$old_content_var="99";
		$origins=array();
		$terms=array();
		$words=array();

		$hit_count=0;


		foreach($data as $nr => $row) {

			$content_var=$row[$content_var_name];

			if($content_var != $old_content_var) {

				if($hit_count > 0) {

					if($content_var_name != 'hour') {

						if($content_var_name == 'day')
							$day=$old_content_var;
						else
						if($content_var_name == 'month')
							$month=$old_content_var;
						else
						if($content_var_name == 'year')
							$year=$old_content_var;

						$content.=renderQueriesLineWithLink($old_content_var, $hit_count,
							prepare_origins($origins), prepare_terms($terms), prepare_terms($words),
								$year, $month, $day);

					} else
						$content.=renderQueriesLine(
							$old_content_var, $hit_count,
							prepare_origins($origins), prepare_terms($terms), prepare_terms($words));

				}


				$origins=array();
				$terms=array();
				$words=array();
				$hit_count=0;
				$old_content_var=$content_var;

			}

			$hit_count+=$row['hits'];

			$origin=trim($row['origin']);

			if(strlen($origin)>0)
				if(isset($origins[$origin]))
					$origins[$origin]+=$row['hits'];
				else
					$origins[$origin]=$row['hits'];


			$term=trim($row['term']);

			if(strlen($term)>0) {
				$term=rawurldecode($term);

				if(isset($terms[$term]))
					$terms[$term]+=$row['hits'];
				else
					$terms[$term]=$row['hits'];

				$term=str_replace('"', '', $term);
				$term=str_replace('+', '', $term);
				$term=str_replace('*', '', $term);

				$tmp_words=explode(' ',$term);

				foreach($tmp_words as $nr => $word)
					if(isset($words[$word]))
						$words[$word]+=$row['hits'];
					else
						$words[$word]=$row['hits'];

			}

		}

		if($hit_count > 0) {

			if($content_var_name != 'hour') {

				if($content_var_name == 'day')
					$day=$old_content_var;
				else
				if($content_var_name == 'month')
					$month=$old_content_var;
				else
				if($content_var_name == 'year')
					$year=$old_content_var;

				$content.=renderQueriesLineWithLink(
					$content_var, $hit_count, prepare_origins($origins),
						prepare_terms($terms), prepare_terms($words),
						$year, $month, $day);

			}else
				$content.=renderQueriesLine($content_var,
					$hit_count, prepare_origins($origins), prepare_terms($terms),
						prepare_terms($words));

		}

		$html.=$content;

		$html.="</table>";

		return($html);

	}





	// BOTS
	// 2005-07-04


	function botStrings() {

		$bots=array();

		$bots[]='%bot/%';
		$bots[]='%Bot/%';
		$bots[]='% Bot %';
		$bots[]='%bot.%';
		$bots[]='%;bot%';
		$bots[]='%bot %';
		$bots[]='BOT/%';

		$bots[]='Search%';
		$bots[]='%-search-%';

		$bots[]='%Scooter%';
		$bots[]='%crawler%';
		$bots[]='%Crawl%';
		$bots[]='%spider%';
		$bots[]='%Spider%';

		$bots[]='appie%';
		$bots[]='convoy%';
		$bots[]='%grub%';
		$bots[]='%ichiro%';
		$bots[]='%ia_archiver%';
		$bots[]='%Protocol Discovery%';
		$bots[]='%LinkWalker%';

		$bots[]='%Atrax%';
		$bots[]='BigBrother%';
		$bots[]='%Dig%';
		$bots[]='Dowser%';
		$bots[]='% Link %';
		$bots[]='MetaGer%';
		$bots[]='Missigua%';
		$bots[]='%Sleuth%';
		$bots[]='SiteSucker%';
		$bots[]='%Supervision%';
		$bots[]='TouchGraph%';
		$bots[]='% URL %';
		$bots[]='webcollage%';

		return($bots);

	}


	function getBotWhere() {

		$bots=botStrings();

		$where=array();;

		foreach($bots as $nr => $bot)
			$where="(HTTP_USER_AGENT like '$bot')";

		return(implode(" or ", $where));
	}




	function renderCrumbs($op, $year=null, $month=null, $day=null) {

		$html="";
		$linka=array();
		$linka['visitors_op']=$op;

		$stat_caption=$_SESSION['translate']->it('statistics');

		if(empty($year))
			$html=$stat_caption;
		else {
			$html=PHPWS_Text::moduleLink($stat_caption,
				'visitors', $linka);

			if(empty($month))
				$html.=" &gt; $year";
		}


		if(isset($year) && isset($month)) {

			$linka['year']=$year;

			$html.=" &gt; ";
			$html.=PHPWS_Text::moduleLink($year,
				'visitors', $linka);

			if(empty($day))
				$html.=" &gt; $month";

		}

		if(isset($year) && isset($month) && isset($day)) {

			$linka['month']=$month;


			$html.=" &gt; ";
			$html.=PHPWS_Text::moduleLink($month,
				'visitors', $linka);

			$html.=" &gt; $day";
		}


		return($html);

	}



	function genAllUserSessionsSQL($where=null) {

		$prefix=$GLOBALS["core"]->tbl_prefix;

		$sql ="SELECT user, user_id, count(session_id) visits, count(id) hits, ";
		$sql.="date_format(min(timestamp), '%Y-%m-%d') first, ";
		$sql.="date_format(max(timestamp), '%Y-%m-%d') latest, ";
		$sql.="to_days(now())-to_days(min(timestamp)) days ";
		$sql.="FROM ${prefix}mod_visitors_hit ";
		$sql.="$where ";
		$sql.="GROUP BY user ";
		$sql.="ORDER BY visits DESC, user ";

		return($sql);

	}



	function genUserSessionsSQL($user_id) {

		$prefix=$GLOBALS["core"]->tbl_prefix;

		$sql = "SELECT distinct session_id, user, user_id, ";
		$sql.= "date_format(timestamp, '%Y-%m-%d') timestamp ";
		$sql.= "FROM ${prefix}mod_visitors_hit ";
		$sql.= "WHERE user_id=$user_id ";
		$sql.= "ORDER BY id LIMIT 20";

		return($sql);
	}


	function genDistinctReferersSQL($where=null) {

		$prefix=$GLOBALS["core"]->tbl_prefix;

		$sql ="SELECT rtrim(substring( ";
		$sql.=	"HTTP_REFERER, ";
		$sql.=	'locate("//", HTTP_REFERER)+2, ';
		$sql.=  'locate("/", HTTP_REFERER, 9) -locate("//", HTTP_REFERER)-2';
		$sql.= ")) referer, count(id) visits ";
		$sql.= "FROM ${prefix}mod_visitors_hit ";
		$sql.= "$where ";
		$sql.= "GROUP BY referer ";
		$sql.= "ORDER BY visits DESC, referer ";

		return($sql);
	}



	function getAllUserSessions() {
		$sql = genAllUserSessionsSQL();

		return($GLOBALS['core']->getAllAssoc($sql));
	}


	function getDistinctReferers() {
		$sql = genDistinctReferersSQL();

		return($GLOBALS['core']->getAllAssoc($sql));
	}



	function getUserSessions($user_id) {

		$sql = genUserSessionsSQL($user_id);

		return($GLOBALS['core']->getAllAssoc($sql));
	}



	function renderAllUserSessions() {
		$data=getAllUserSessions();

		$html="<ol>\n";

		foreach($data as $nr => $row) {

			$user=$row["user"];
			$user_id=$row["user_id"];
			$visits=$row["visits"];

			$first=$row["first"];
			$latest=$row["latest"];

			$days=$row["days"];
			$visits_per_day=sprintf("%.2f", $visits/($days+1));

			$html.="\t<li><h4>";

			if(empty($user)) {

				$anon_caption=$_SESSION['translate']->it('Anonymous ([var1])',
					$visits);


				$html.="<em>$anon_caption</em></h4>\n";
			} else {
				$html.="<a href=\"./index.php?module=visitors&amp;";
				$html.="visitors_op=check&amp;";
				$html.="user_id=$user_id\">";

				$html.="$user ($visits)</a></h4>\n";

				$visits_caption=$_SESSION['translate']->it(
					'first visit: [var1], latest: [var2], [var3] visits per day',
					$first, $latest, $visits_per_day);

				$html.="<p>$visits_caption</p>";
			}

			$html.="</li>";
		}

		$html.="</ol>";

		return($html);
	}


	function renderUserSessions($user_id) {

		$data=getUserSessions($user_id);
		$user=$data[0]["user"];

		$html="<p>user $user</p>";

		foreach($data as $nr => $row) {

			$session_id=$row["session_id"];

			$clickpath_caption=$_SESSION['translate']->it(
				'clickpath for session [var1]', $session_id);

			$html.="<h3>$clickpath_caption</h3>";
			$html.=clickpath($session_id);
		}

		return($html);


	}


	function renderDistinctReferers() {

		$data=getDistinctReferers();

		$html="<ol>\n";

		foreach($data as $nr => $row) {

			$referer=$row["referer"];
			$visits=$row["visits"];

			$html.="\t<li><h4>";

			if(empty($referer)) {

				$unset_caption=$_SESSION['translate']->it(
					'not set ([var1])', $visits);

				$html.="<em>$unset_caption</em></h4>";
			} else {
				$html.="$referer ($visits)</h4>";
				$html.="<a href=\"http://www.google.at/search?q=$referer\"";
				$html.=">google</a>, <a ";
				$html.="href=\"http://uptime.netcraft.com/up/graph/?host=$referer\"";
				$html.=">netcraft</a>, <a ";
				$html.="href=\"http://web.archive.org/web/*/http://$referer\"";
				$html.=">wayback machine</a>";

			}

			$html.="</li>";
		}

		$html.="</ol>";

		return($html);
	}

?>