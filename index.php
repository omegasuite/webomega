<?php

/**
 * Routing file for phpWebSite
 *
 * Index initializes the core and database
 *
 * @version $Id: index.php,v 1.80 2005/03/02 20:59:20 matt Exp $
 * @author Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
 * @modified by Steven Levin <steven@NOSPAM.tux.appstate.edu>
 * @modified by Adam Morton <adam@NOSPAM.tux.appstate.edu>
 * @package phpWebSite
 */

/* Show all errors */
error_reporting (E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
//error_reporting (E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_STRICT);

// Change to TRUE to allow DEBUG mode
define('DEBUG_MODE', FALSE);

$GLOBALS['ALWAYS'] = array('layout', 'users', 'language', 'fatcat', 'search', 'menuman', 'comments');

header("Content-Type: text/html; charset=utf-8");
header("Content-Language: zh-CN");
ini_set('date.timezone','Asia/Shanghai');

if (!isset($hub_dir)) {
    $hub_dir = NULL;
}

/* Check to make sure $hub_dir is not set to an address */
if (!preg_match ("/:\/\//i", $hub_dir)) {
    loadConfig($hub_dir);
} else {
    exit('FATAL ERROR! Hub directory was malformed.');
}

define('HALLROOT', PHPWS_SOURCE_DIR . "data/");

require_once PHPWS_SOURCE_DIR . 'security.php';
require_once PHPWS_SOURCE_DIR . 'storage.php';

if (file_exists(PHPWS_SOURCE_DIR . 'core/Core.php') && file_exists(PHPWS_SOURCE_DIR . 'core/Debug.php')) {
    require_once PHPWS_SOURCE_DIR . 'core/Core.php';
} else {
    exit('FATAL ERROR! Required file <b>Core.php</b> not found.');
}

if (!isset($branchName)) {
    $branchName = NULL;
}

$GLOBALS['core'] = new PHPWS_Core($branchName, $hub_dir);
$GLOBALS['SCRIPT'] = 'index.php';

//$GLOBALS['storage'] = new SaeStorage('24mmj45nkl', '1l43wkxm35l0k3h5xjxi4m4y3xx0m12l4x0h3h5z');
$GLOBALS['storage'] = new Storage('24mmj45nkl', '1l43wkxm35l0k3h5xjxi4m4y3xx0m12l4x0h3h5z');
if (!isset($_REQUEST['module']) && !isset($_POST['module'])) $_REQUEST['module'] = 'rpc';

$includeList = $core->initModules();

$current_mod_file = NULL;

$showframe = TRUE;
if (isset($_POST['noframe']) && $_POST['noframe'] == 1) $showframe = FALSE;
if (isset($_REQUEST['noframe']) && $_REQUEST['noframe'] == 1) $showframe = FALSE;

if (!$_SESSION["OBJ_layout"] || $_SESSION["OBJ_layout"]->current_theme != 'Default')
	$_SESSION["OBJ_layout"] = new PHPWS_Layout('Default');

foreach ($includeList as $mod_title=>$current_mod_file) {
    if (($showframe && in_array($mod_title, $GLOBALS['ALWAYS'])) || (isset($_REQUEST['module']) && ($_REQUEST['module'] == $mod_title))) {	
		$core->current_mod = $mod_title;
		if (is_file($current_mod_file)) {
			include_once($current_mod_file);
		}
		
    }
    
    if (is_file(PHPWS_SOURCE_DIR . "mod/$mod_title/inc/runtime.php")) {
		include PHPWS_SOURCE_DIR . "mod/$mod_title/inc/runtime.php";
    }
}

/* Preventing last mod loaded from being 'current_mod' */
$core->current_mod = NULL;
$core->db->disconnect();

/* Loads the hubs config file and sets the source directory */
function loadConfig($hub_dir){
    if (file_exists($hub_dir . 'conf/config.php')) {
	if (filesize($hub_dir . 'conf/config.php') > 0) {
	    include($hub_dir . 'conf/config.php');
	    define('PHPWS_SOURCE_DIR', $source_dir);
	    define('PHPWS_SOURCE_HTTP', $source_http);
	} else {
	    header('Location: ./setup/set_config.php');
	    exit();
	}
    } else {
	header('Location: ./setup/set_config.php');
	exit();
    }  
}

function get_contents($url){
            $ch = curl_init();
			curl_setopt($ch , CURLOPT_TIMEOUT, 4);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_URL, $url);
            $response =  curl_exec($ch);
            curl_close($ch);

        //-------请求为空
        if(empty($response)){
//$GLOBALS['core']->query("insert into mxkj_mod_weixin (userid, user_id, request, reply) values ('CURL', 0, 'get_contents($url)', 'is empty')", false);
//            showError("50001");
        }

        return $response;
}

?>