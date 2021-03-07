<?php

/**
 * This files attempts to help an admin fix their site.
 * 
 * This file SHOULD NEVER BE ACCESSIBLE except when needed. Change the name
 * of it to something else or make it unreadable. There are two safeguards to 
 * prevent it from being used maliciously, however don't depend upon them.
 *
 * IMPORTANT!!! When not using this file OR when adding material MAKE SURE
 * to turn $allowRepair to FALSE;
 *
 * @author Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
 */ 
define('PHPWS_SOURCE_DIR', '../');

require_once PHPWS_SOURCE_DIR .'core/Core.php';
if (checkConfig())
     require_once PHPWS_SOURCE_DIR .'conf/config.php';
     
require_once PHPWS_SOURCE_DIR .'conf/core_info.php';

require_once PHPWS_SOURCE_DIR .'core/Form.php';
require_once PHPWS_SOURCE_DIR .'core/Text.php';

include PHPWS_SOURCE_DIR .'mod/boost/class/Boost.php';

session_start();

include("allow_setup.php");

$GLOBALS["content"] = "
<html><head><title>phpWebSite Repair</title></head><body>";

$GLOBALS["content"] .= "<a href=\"../index.php\"><img src=\"poweredby.jpg\" border=\"0\" /></a>
<h1>phpWebSite Repair</h1>";

$core = new PHPWS_Core(NULL, "../", TRUE);

if (!DB::isError($core->db)) $db_connect = TRUE;
else $db_connect = FALSE;

passwordCheck($install_pw);

if (!$_SESSION["passwordAccepted"]) passwordCheck();

if (!$db_connect){
  $GLOBALS["content"] .= "I am unable to make a connection to the database.

Without a connection to the database, phpWebSite and this program will not function.
<ul>
<li> Check the settings in your <b>config.php</b>.</li>
<li> Make sure that your database username and password are correct.</li>
<li> Check the database listed in the config.php file actually exists.</li>
<li> If the database exist, see if there are any tables in it. If there aren't any, try running <b>setup.php</b> from this directory.</li>
<li> If there are just a few tables and you just tried an installation, drop the database and reinstall.</li>
</ul>

Remember, before you tinker with your database perform a backup dump of the data!
";
  done();
}


if (isset($_REQUEST["question"])) {
  backToQuestions();
  $_REQUEST["question"]();
}
else checkList();


function checkList(){
  $core = $GLOBALS["core"];
  $GLOBALS["content"] .= "There are two sections to assist you.
The top section takes you to specific tools.
The bottom section leads you through questions to try to direct you to the correct tool.
";


  $GLOBALS["content"] .= "<h3>I am not sure what needs to be done.</h3>";

  /* Questions for the unsure */
  $questions = questions();

  linkProblems($questions);
  $GLOBALS["content"] .= "<br /><hr /><h3>I know what needs to be done</h3>";
  $tools = tools();
  linkProblems($tools);

  
  done();
}

function questions(){
  $questions["noDBclass"] = "I am getting an error \"Undefined class name 'db' in myPath\core\Database.php\". I am running Windows.";
  $questions["TZ"] = "After installing calendar, I am receiving a TZ error in DateTime.php.";
  $questions["no_table"] = "I received a 'no such table' SQL error.";
  $questions["boost"] = "I went to Boost but it says it missing the table \"mod_boost_version\".";
  $questions["relayPear"] =  "Receiving this error- Fatal error: Cannot instantiate non-existent class: html_template_it in /mywebsite/core/Template.php on line 127 <b>or</b> you are unable to load PEAR.php";
  $questions["tableExists"] = "Receiving an error that a table already exists when I am installing phpWebSite.";
  $questions["needToBoost"] = "I have installed phpWebSite but all I get is a logon box and a logo. Where is the content?";
  $questions["defaultTheme"] = "I changed the theme and I am getting all these error messages. I can't logon to change it.";
  $questions["lockError"] = "When I try to make a menu link I get an error: \"LOCK TABLES mod_menuman_items WRITE\". How do I fix it?";
  return $questions;
}


function noDBclass(){
 $GLOBALS["content"] .= "Windows is probably going to need a little help finding your Pear files if they are not on your path.

First, look at your Core.php file. There are two sets of ini_set statements. The first one is not commented.
That one works with *nix/Linux. Below that line is an ini_set for Windows. Comment the first line and uncomment the second.

If that still does not work, you may need to hardcode the path to your Pear directory in the second <b>ini_set</b>.

<b>Remove</b>
\".PHPWS_SOURCE_DIR.\"

<b>Replace with:</b>
c:\Program Files\Apache Group\Apache2\htdocs\lib\pear\DB.php

If this isn't the path to your pear library files, then change it to the correct one.

<i>Thanks to jonasx at SourceForge for this tip.</i>
";
 done();
}

function TZ(){
  $GLOBALS["content"] .= "There is a command in a Pear function that tries to write information to the server.
Some Windows installations and Unix servers with strict Safe Mode options enabled, do not allow this.
You can fix this yourself however.

Open the file lib/pear/Date/TimeZone.php in a text editor.
Go to line 247. You should be in a function named 'inDaylightTime()'.
Add this line:         return date(\"I\");
at the very top of the function.

It should now look like this:
<PRE>
    function inDaylightTime(\$date)
    {
        return date(\"I\");
        \$env_tz = \"\";
        if(getenv(\"TZ\"))
            \$env_tz = getenv(\"TZ\");
        putenv(\"TZ=\".\$this->id);
        \$ltime = localtime(\$date->getTime(), true);
        putenv(\"TZ=\".\$env_tz);
        return \$ltime['tm_isdst'];
    }
</PRE>
This should stop the error. Perhaps in the future, the Pear team will supply a work around.
";
  done();
}

function needToBoost(){
  $GLOBALS["content"] .= "phpWebSite 0.9.x is modular. It requires modules to DO something.
When just the core is installed, it only installs required modules. To install other modules,
go into your admin menu and click the Boost icon. Boost allows you to install all the modules
it finds on your system.

If there aren't any modules to install, you will need to download some. Untar the modules into the
<b>mod</b> directory of your phpWebSite installation. Boost should be able to see it and allow you
to install the package.";
  done();

}

function defaultTheme(){
  $core = $GLOBALS["core"];
  $GLOBALS["content"] .= "Your new theme may not be compatible with this version of phpWebSite.

I will reset it back to the default. You may need to close your browser and reopen your site for the changes to take effect.
I also recommend you remove that theme from your system until it is updated.
If you do not remove the theme, you might need to remove your cookie.";

  $core->sqlUpdate(array("default_theme"=>"Default"), "mod_layout_config");
  done();
}


function relayPear(){
  $GLOBALS["content"] .= "phpWebSite 0.9.0 ships with a subset of the Pear library. It can be found under the lib/pear/ directory.

If you do have this directory, you will need to grab the current phpWebSite release set.
<a href=\"http://phpwebsite.appstate.edu/downloads/pear/pear.current.tar.gz\">Get them here</a>.
Untar this file in your phpWebSite directory.

If you do have these files, you might have a CVS copy of the core.php file which you will need to edit.

/* This line is for *nix/linux environments */
//ini_set(\"include_path\", \".:\".PHPWS_SOURCE_DIR.\"lib/pear/\"); # <---- Remove the comment lines (//) from the beginning of this line for Unix systems.

If you are installing into Windows:

/* This line is for windows environments */
//ini_set(\"include_path\", \".;\".PHPWS_SOURCE_DIR.\"lib\\\\pear\\\\\"); #  <---- Remove the comment lines (//) from the beginning of this line for Windows systems.

";
  done();
}

function tableExists(){
  $GLOBALS["content"] .= "This happens when there is already an installation of phpWebSite in that database.
You can try the following:
<ul>
<li> Drop the database, create it again, and try reinstalling. </li>
<li> Drop all the tables from the database. Try reinstalling. </li>
<li> Delete your config.php file, rerun set_config.php and enter a table prefix. This will allow two phpWebSite installations in one database.</li>
<li> Edit your config.php file and enter a table prefix value
Example:
\$table_prefix = \"phpws_\";
</li>
</ul>
";
  done();
}

function tools(){
  $tools["deactivateModule"] = "I need to deactivate some modules.";
  return  $tools;

}

function linkProblems($questions){
  if (is_array($questions))
    foreach ($questions as $qIndex=>$qFull)
      $GLOBALS["content"] .= "<a href=\"repair.php?question=$qIndex\">$qFull</a><br />";
  else
    return null;
}

function checkConfig(){
  if (file_exists(PHPWS_SOURCE_DIR .'conf/config.php'))
    return TRUE;
  else {
     $GLOBALS["content"] .= "It seems your main problem is that phpWebSite cannot read your config.php file.

Look in your <b>conf</b> directory and see if it is missing. If you are missing it, try running <b>set_config.php</b>
in your setup directory to restore it. Make sure that you <b>conf</b> directory is writable.";
     done();
  }
}

function sessionError(){
  $GLOBALS["content"] .= "There appears to be a problem with your sessions.
This page did not receive a session saved to the superglobal _SESSION array on the last page.
This could be because the session timed out while you were working outside of Repair.

Try refreshing the page and logging in. If that doesn't work, you need to fix your sessions.
Check your php.ini file. Make sure that a session directory is defined and writable by your web server.";
  
  done();
}

function backToQuestions(){
  $GLOBALS["content"] .= "<a href=\"repair.php\">Back to Questions</a><br /><br />";
}


function passwordCheck($install_pw){
  $core = $GLOBALS["core"];
  
  if (isset($_POST["password"])){
    if (!$_SESSION["checkSession"])
      sessionError();
    
    if ($_POST["password"] == $install_pw)
      $_SESSION["passwordAccepted"] = TRUE;
    else {
      if ($_SESSION["passwordTries"] > 4){
	$GLOBALS["content"] .= "You unsuccessfully tried to enter a password 5 times. You will need to close your browser and try again.

Make sure to check your config.php file for the correct password.";
	done();
      }
      
      $_SESSION["passwordTries"]++;
      $GLOBALS["content"] .= "That password was incorrect. Try Again.<br /><br />";
    }
  }
  
  if (!$_SESSION["passwordAccepted"]){
    $_SESSION["checkSession"] = 1;
    $GLOBALS["content"] .= "I will need your install password (looking in your config.php file) before we can continue.<br /><br />";
    $GLOBALS["content"] .= "<form action=\"repair.php\" method=\"post\">";
    $GLOBALS["content"] .= "Install Password: " . PHPWS_Form::formPassword("password") . "<br />\n"
       . PHPWS_Form::formSubmit("Go");
    $GLOBALS["content"] .= "</form>";
    done();
  }
}

function done(){
  $GLOBALS["content"] .= "</body></html>";
  echo PHPWS_Text::parseOutput($GLOBALS["content"]);
  exit();
}

/********************************************************************************************************
ANSWERS
********************************************************************************************************/

function deactivateModule(){
  $core = $GLOBALS["core"];

  if (!$core->sqlTableExists("modules", TRUE)){
    $GLOBALS["content"] = "The <b>modules</b> table is missing. Please check your database. You may have to reinstall
if you are unable to recover it. Make sure to backup your database before doing so.";
    done();
  }

  if ($_POST["changeStatus"])
    changeStatus();
  
  if(!$modules = $core->sqlSelect("modules")){
    $GLOBALS["content"] .= "I am unable to pull modules from the <b>modules</b> table in your database.
You may want to check that table to see if it is corrupt.";
    done();
  }

  $GLOBALS["content"] .= "Here are the currently installed modules. You can activate and deactivate them by clicking the 
on and off radio buttons. Click the <b>Finished</b> button when you have made your selection.<br /><br />
<form action=\"repair.php\" method=\"post\">\n"
     . PHPWS_Form::formHidden("question", "deactivateModule") ."\n"
     . PHPWS_Form::formHidden("changeStatus", "1") ."\n
<table cellpadding=\"6\" border=\"1\">\n";
  
  foreach ($modules as $modInfo){
    extract($modInfo);
    $GLOBALS["content"] .= "<tr><td width=\"20%\"><b>$mod_pname</b></td>\n<td>";
    $GLOBALS["content"] .= PHPWS_Form::formRadio("$mod_title", "off", $active) . " Off ";
    $GLOBALS["content"] .= PHPWS_Form::formRadio("$mod_title", "on", $active) . " On</td>\n</tr>\n";
  }
  $GLOBALS["content"] .= "</table><br />" . PHPWS_Form::formSubmit("Go") . "\n</form>";

  $GLOBALS["content"] .= "</body></html>";
  echo $GLOBALS["content"];
}

function changeStatus(){
  $core = $GLOBALS["core"];

  $module = $core->listModules();
  foreach ($module as $mod_title){
    if ($_POST[$mod_title] == "on")
      $core->sqlUpdate(array("active"=>"on"), "modules", "mod_title", $mod_title);
    else
      $core->sqlUpdate(array("active"=>"off"), "modules", "mod_title", $mod_title);
  }
}



function boost(){
  $core = $GLOBALS["core"];

  if ($_GET["fixBoost"] == 1){
    if (!$core->sqlTableExists("mod_boost_version")){
      $core->sqlImport("repair/boostRC2.sql");
      $modules = $core->sqlSelect("modules");
      foreach ($modules as $modInfo){
	extract($modInfo);
	$core->sqlInsert(array("version"=>$version, "mod_title"=>$mod_title, "update_link"=>$update_link, "branch_allow"=>$branch_allow), "mod_boost_version");
      }
      include("../conf/core_info.php");
      $core->sqlInsert(array("version"=>.1, "mod_title"=>$mod_title, "update_link"=>$update_link, "branch_allow"=>$branch_allow), "mod_boost_version");
      $GLOBALS["content"] .= "Boost is repaired. Click on the phpWebSite logo to return to your installation. Go into Boost and finish the Boost update.";
    } else {
      $GLOBALS["content"] .= "The new boost version table exists already.<br />";
    }
  } else {
    $GLOBALS["content"] .= "Older RC's of Boost used the modules table to keep track of a module's version.
This is now controlled by <b>mod_boost_version</b>. You can fix Boost by clicking here: <a href=\"repair.php?question=boost&fixBoost=1\">Fix Boost</a>.";
  }
  done();
}


function lockError(){
  $GLOBALS["content"] .= 
"This error is most likely caused by your version of MySQL.

 Earlier versions of MySQL gave programs the permission to lock and unlock tables if you had insert access.
This is no longer the case. You will need to GRANT lock permissions for tables.

<a href=\"http://www.mysql.com/documentation/mysql/bychapter/manual_MySQL_Database_Administration.html#GRANT\">MySQL Documentation</a>";
  done();
}

function no_table(){
  $GLOBALS["content"] .= "There could be a couple of reasons for this:
<ul>
<li> You don't have phpWebSite installed but the database has a few tables in it. Check your <b>config.php</b> settings and make sure
it is pointed at the right database.<li>
<li> You ran the installation but it something caused it to stop prematurely. Clear out your database and try reinstalling.
DO NOT install the extra modules this time. If you are able to login, add the modules one by one until you identify which
one is causing the problem.</li>
<li> If you just copied an updated module, it may require a new table that wasn't in the last version. Try deactivating it here: <a href=\"repair.php?question=deactivateModule\">Deactivate Modules</a>

After you deactivate it, go into Boost, update the module, and activate it again.</li>
</ul>";

  done();
}

?>