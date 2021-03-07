<?php


	function renderForwardedFor($data) {


		$forwarded_for=$data['HTTP_USER_AGENT'];
		$resolve=null;

		if(empty($forwarded_for)) {

			$text="HTTP_X_FORWARDED_FOR is unknown.";
			return($_SESSION['translate']->it($text));

		}


		$forwarded_for=explode(",", $forwarded_for);
		$forwarded_readable=array();


		foreach($forwarded_for as $nr=>$forward)

			if($forward != 'unknown') {

				/*modified by sean 2006/02/21 begin
				$forward_lookup=gethostbyaddr($forward); */

				$forward_lookup=($forward);
				/* end */

				if($forward_lookup==$forward) {

					$resolve='Does not resolve.';
					$resolve=$_SESSION['translate']->it($resolve);

				} else
					$resolve="${forward_lookup}";

				$forwarded_readable[]="$forward ($resolve)";


			} else
				$forwarded_readable[]='unknown';

		$text="HTTP_X_FORWARDED_FOR is [var1].";
		$text=$_SESSION['translate']->it($text,
			implode(', ', $forwarded_readable));


		return($text);

	}



	function renderRemoteAddr($data) {

		$remote_addr=$data['REMOTE_ADDR'];
		$resolve=null;

		if(empty($remote_addr)) {

			$text='REMOTE_ADDR is unknown.';
			return($_SESSION['translate']->it($text));

		} else
		if($remote_addr == $ip) {

			$text='REMOTE_ADDR equals the IP I got';
			$text=$_SESSION['translate']->it($text);

		} else {

			$text="REMOTE_ADDR is [var1]";
			$text=$_SESSION['translate']->it($text,
				$remote_addr);

			$remote_lookup=gethostbyaddr($remote_addr);

			if($remote_lookup == $remote_addr) {

				$resolve='Does not resolve.';
				$resolve=$_SESSION['translate']->it($resolve);

			} else
				$resolve="(${remote_lookup})";

		}


		return("$text $resolve");

	}



	function couldBe($IP) {

		if(empty($IP) || ($IP=='unknown'))
			return(null);

		$prefix=$GLOBALS['core']->tbl_prefix;

		$sql ="SELECT distinct user FROM ${prefix}mod_visitors_hit ";
		$sql.="WHERE ip='$IP' AND user_id>0";

		$data=$GLOBALS['core']->getAllAssoc($sql);

		if(sizeof($data)<=0)
			return(null);

		$res=array();

		foreach($data as $nr => $val)
			$res[]=$val['user'];

		return($res);
	}

?>