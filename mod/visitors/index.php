<?php

/**
 * Visitors Module for phpWebSite
 *
 * @author rck <http://www.kiesler.at/>
 */


if(!isset($GLOBALS['core'])) {
    header("Location: ../..");
    exit();
}

if (!isset($_SESSION["OBJ_visitors"])) {
	$_SESSION["OBJ_visitors"] = new PHPWS_visitors;
}



function genGenericOps() {

	$ops=array();
	$ops['users']='users';
	$ops['refhosts']='refhosts';
	$ops['query']='query';
	$ops['stats']='stats';

	return($ops);
}



function get_timespan($year, $month, $day) {

	$timespan=null;
	if(isset($year))
		$timespan=$year;
	if(isset($year) && isset($month))
		$timespan.="-$month";
	if(isset($year) && isset($month) && isset($day))
		$timespan.="-$day";

	return($timespan);
}



function replace_current($curr, &$year, &$month, &$day) {

	switch($curr) {

		case('year'):

			$year=date('Y');
			unset($month);
			unset($day);

			break;

		case('month'):

			$year=date('Y');
			$month=date('m');
			unset($day);

			break;

		case('day'):

			$year=date('Y');
			$month=date('m');
			$day=date('d');

			break;

	}
}



function render_title($op, $year, $month, $day) {

	$stitle=array();
	$stitle['users']   = 'Users on [var1]';
	$stitle['refhosts']= 'Referrer hosts on [var1]';
	$stitle['query']   = 'External search queries on [var1]';
	$stitle['stats']   = 'Hits on [var1]';


	$ntitle=array();
	$ntitle['users']   = 'User Statistics';
	$ntitle['refhosts']= 'Referrer Hosts';
	$ntitle['query']   = 'External search queries';
	$ntitle['stats']   = 'Hit Statistics';


	$timespan=get_timespan($year, $month, $day);

	if(isset($timespan)) {

		$title=$stitle[$op];

		if(empty($title))
			$title=$stitle['stats'];

	} else {

		$title=$ntitle[$op];

		if(empty($title))
			$title=$ntitle['stats'];

	}

	$title=$_SESSION['translate']->it($title, $timespan);


	return("<h3>$title</h3>");
}



function get_timespan_mode($year, $month, $day) {


	if(isset($year) && isset($month) && isset($day))
		return('day');
	else
	if(isset($year) && isset($month))
		return('month');
	else
	if(isset($year))
		return('year');
	else
		return('all');

}


function get_drilldown_caption($timespan_mode) {


	switch($timespan_mode) {

		case('day'):
			$caption='hour';
			break;

		case('month'):
			$caption='day';
			break;

		case('year'):
			$caption='month';
			break;

		case('all'):
			$caption='year';
			break;

	}

	return($caption);

}




function renderUserOp($year, $month, $day) {

	$timespan_mode=get_timespan_mode($year, $month, $day);

	$users=array();

	switch($timespan_mode) {

		case('day'):

			$users=getDayUsers($year, $month, $day);
			break;

		case('month'):

			$users=getMonthUsers($year, $month);
			break;

		case('year'):

			$users=getYearUsers($year);
			break;

		case('all'):

			$users=getAllUsers();
			break;
	}


	$drilldown_caption=get_drilldown_caption($timespan_mode);
	return(renderUsers($users, $drilldown_caption, $year, $month, $day));

}



function renderRefHostOp($year, $month, $day) {

	$timespan_mode=get_timespan_mode($year, $month, $day);

	$refs=array();

	switch($timespan_mode) {

		case('day'):

			$refs=getDayRefHosts($year, $month, $day);
			break;

		case('month'):

			$refs=getMonthRefHosts($year, $month);
			break;

		case('year'):

			$refs=getYearRefHosts($year);
			break;

		case('all'):

			$refs=getAllRefHosts();
			break;
	}

	$drilldown_caption=get_drilldown_caption($timespan_mode);
	return(renderRefHosts($refs, $drilldown_caption, $year, $month, $day));
}



function renderQueriesOp($year, $month, $day) {

	$timespan_mode=get_timespan_mode($year, $month, $day);

	$queries=array();


	switch($timespan_mode) {

		case('day'):

			$queries=getDayQueries($year, $month, $day);
			break;

		case('month'):

			$queries=getMonthQueries($year, $month);
			break;

		case('year'):
	
			$queries=getYearQueries($year);
			break;

		case('all'):

			$queries=getAllQueries();
			break;

	}


	$drilldown_caption=get_drilldown_caption($timespan_mode);
	return(renderQueries($queries, $drilldown_caption, $year, $month, $day));
}



function renderHitsOp($year, $month, $day) {

	$timespan_mode=get_timespan_mode($year, $month, $day);

	$content="";

	switch($timespan_mode) {

		case('day'):

			$hits=getDayHits($year, $month, $day);
			$sum=sumDayHits($year, $month, $day);
			$avg=avgHits($hits, $sum);
			
			$content.=renderDayHits($hits, $avg, $sum,
				$year, $month, $day);

			break;

		case('month'):

			$hits=getMonthHits($year, $month);
			$sum=sumMonthHits($year, $month);
			$avg=avgHits($hits, $sum);
			$content.=renderMonthHits($hits, $avg, $sum,
				$year, $month);

			break;

		case('year'):

			$hits=getYearHits($year);
			$sum=sumYearHits($year);
			$avg=avgHits($hits, $sum);
			$content.=renderYearHits($hits, $avg, $sum,
				$year);

			break;

		case('all'):
		
			$hits=getAllHits();
			$sum=sumAllHits();
			$avg=avgHits($hits, $sum);
			$content.=renderAllHits($hits, $avg, $sum);

			break;
			
	}

	return($content);


}



function renderOp($op, $year, $month, $day) {

	$content="";

	switch($op) {

		case('users'):
			$content=renderUserOp($year, $month, $day);
			break;

		case('refhosts'):
			$content=renderRefHostOp($year, $month, $day);
			break;

		case('query'):
			$content=renderQueriesOp($year, $month, $day);
			break;

		case('stats'):
			$content=renderHitsOp($year, $month, $day);
			break;

	}

	return($content);

}



function renderQuickCheck() {

	
	$caption=$_SESSION['translate']->it("Referrer Hosts");
	$content ="<h3>$caption</h3>\n";

	$txt ="This list shows you where your visitors come from. ";
	$txt.="Only the hostnames are counted here, not the full URLs!";
	$caption=$_SESSION['translate']->it($txt);

	$content.="<p>$caption</p>\n";

	$note = $_SESSION['translate']->it("Note:");


	$txt = "[var1] There is no direct link to the referer on purpose: to ";
	$txt.= "hinder referrer-spam. Please try to use the archive.org ";
	$txt.= "or google url instead. You can of course always enter ";
	$txt.= "the url directly in your address-bar, if you insist to.";

	$caption=$_SESSION['translate']->it($txt, "<em>$note</em>");

	$content.="<p>$caption</p>\n";

	$content.=renderDistinctReferers();

	$caption=$_SESSION['translate']->it("User Visits");
	$content.="<h3>$caption</h3>\n";

	$txt = "Here you can see, how often a certain user has ";
	$txt.= "visited your site.";

	$caption=$_SESSION['translate']->it($txt);
	$content.="<p>$caption</p>\n";

	$content.=renderAllUserSessions();

	return($content);
}



if ($GLOBALS['module'] == 'visitors' && isset($_SESSION["OBJ_user"]) &&	$_SESSION["OBJ_user"]->isDeity()) {

	$GLOBALS['CNT_visitors'] = array(
		'title'=>'Visitors',
		'content'=>''
	);

	if(isset($_REQUEST['user_id']))
		$user_id=$_REQUEST['user_id'];

	if(isset($_REQUEST['curr']))
		$curr=$_REQUEST['curr'];
	else

	if(isset($_REQUEST['day']))
		$day=$_REQUEST['day'];
	else
		$day=null;

	if(isset($_REQUEST['month']))
		$month=$_REQUEST['month'];
	else
		$month=null;

	if(isset($_REQUEST['year']))
		$year=$_REQUEST['year'];
	else
		$year=null;

	if(isset($_REQUEST['visitors_op']))
		$op=$_REQUEST['visitors_op'];

	$content="";

	$generic_ops=genGenericOps();

	if(isset($_REQUEST['view'])) {
		$view=$_REQUEST['view'];

		$content =
			$_SESSION['OBJ_visitors']->showVisitorsDetails($view);

		$GLOBALS['CNT_visitors']['content']=$content;
	} else
	if($op == 'check' && isset($user_id)) {

		$txt='Visits of User [var1]';
		$caption=$_SESSION['translate']->it($txt, $user_id);

		$content = "<h3>$caption</h3>";

		$content.=renderUserSessions($user_id);

	} else
	if(isset($generic_ops[$op])) {

		if(isset($curr))
			replace_current($curr, $year, $month, $day);
		

		$content.=renderMenu($op, $year, $month, $day)."\n";
		$content.=render_title($op, $year, $month, $day)."\n";
		$content.=renderCrumbs($op, $year, $month, $day)."\n";

		$content.=renderOp($op, $year, $month, $day)."\n";

	} else
	if($op == 'check')
		$content=renderQuickCheck();
	else
		$content=$_SESSION['translate']->it('nothing to do!');

	$GLOBALS['CNT_visitors']['content']=$content;
}


?>