<?php

require_once("../core/Form.php");
require_once("../core/File.php");
require_once("../core/Text.php");
require_once("../core/WizardBag.php");

// You can remove the line below to attempt a lower version install.
// Anythink less than 4.1.2 probably will not run.
if (phpversion() < "4.2.2"){
  echo "Sorry, but you appear to running PHP version" . " " . phpversion() . ". "
    . "phpWebSite requires 4.2.2 or above<br />";
  exit();
}

// if parseInput is set to TRUE, set_config will test all your form input
// Should be safe to leave FALSE if you hide this file after installation.
$parseInput = FALSE;

// phpWebSite defaults the allowed database password characters to the below
// If you wish to add others, include them in the regular expression.
// This is ignored if parseInput = FALSE
$allowedChars = "[^\w0-9_\-\*\!\.]";

if(isset($_SERVER['PATH_TRANSLATED'])) {
  $file_address = ereg_replace("[\][\]", "\\", $_SERVER["PATH_TRANSLATED"]);
  $file_address = str_replace("setup/set_config.php", "", $file_address);
  $file_address = str_replace("setup\set_config.php", "", $file_address);
} else {
  $file_address = $_SERVER["SCRIPT_FILENAME"];
  $file_address = str_replace("setup/set_config.php", "", $file_address); 
  $file_address = str_replace("setup\set_config.php", "", $file_address);
}

if (isset($_POST["step"])){
  $step = $_POST["step"];
}
else {
  $step = 1;
}
?>

<html>
<head>
<title>
Create Config File
</title>
</head>
<body>

<?php

echo "<img src=\"poweredby.jpg\" /><h1>phpWebSite - Config File Creation Utility</h1>";

if (file_exists("../conf/config.php") && filesize("../conf/config.php") > 0){
  echo "Your config.php file already exists. You will need to edit it by hand or move it to create another.";
  exit();
}

define("PHPWS_SOURCE_DIR", "../");

require_once ("../core/Core.php");

/* Require Table package */
require_once('HTML/Table.php');

$core = new PHPWS_Core("noDB");
$default_address = $_SERVER["SERVER_NAME"].str_replace("setup/set_config.php", "", $_SERVER["PHP_SELF"]);
PHPWS_WizardBag::seed_rand();
$temp_hash = md5($file_address.rand());
$available_dbs = array ("mysql", "pgsql");
/* $unavailable_dbs = array("ibase", "mssql", "msql", "oci8", "odbc", "sybase"); */
 
if (isset($_POST["hostname"])) {
  $hostname = $_POST["hostname"];
} else {
  $hostname = "localhost";
}
     
if (isset($_POST["username"])) {
  $username = $_POST["username"];
} else {
  $username = "phpwebsite";
}
     
if (isset($_POST["password"])) {
  $password = $_POST["password"];
} else {
  $password = NULL;
}
 
if (isset($_POST["database"])) {
  $database = $_POST["database"];
} else {
  $database = "phpwebsite";
}
 
if (isset($_POST["tbl_prefix"])) {
  $tbl_prefix = $_POST["tbl_prefix"];
} else {
  $tbl_prefix = NULL;
}
 
if (isset($_POST["http_src"])) {
  $http_src = $_POST["http_src"];
} else {
  $http_src = $default_address;
}

if (isset($_POST["filepath"])) {
  $filepath = $_POST["filepath"];

  // used for magic quotes
  if(get_magic_quotes_gpc()) {
    $filepath = stripslashes($filepath);
  }
} else {
  $filepath = $file_address;
}

if (isset($_POST["hubhash"])) {
  $hubhash = $_POST["hubhash"];
} else {
  $hubhash = $temp_hash;
}

if (isset($_POST["install_pass"])) {
  $install_pass = $_POST["install_pass"];
} else {
  $install_pass = NULL;
}

$http_src = str_replace("http://", "", $http_src);
if ("/" != substr($http_src, strlen($http_src) - 1 , 1))
     $http_src .= "/";

if (strstr($filepath, "/") && ("/" != substr($filepath, strlen($filepath) - 1 , 1)))
   $filepath .= "/";
else if (strstr($filepath, "\\") && ("\\" != substr($filepath, strlen($filepath) - 1 , 1)))
   $filepath .= "\\";

$dataArray = $_POST;
$dataArray['http_src'] = $http_src;
$dataArray['filepath'] = $filepath;

switch ($step){
 case "1":

   echo "You are missing a config.php file. This utility can help you create one.<br /><br />You need to set your directory permissions before you continue.  Read the docs/INSTALL.txt file for help on setting the correct permissions.<br /><br />";

   if(isset($PEAR_Errors)) {
     echo $PEAR_Errors;
   }

   echo "<form action=\"set_config.php\" method=\"post\">
".PHPWS_Form::formHidden("step", "2")."
";
   $table = new HTML_Table("cellpadding=\"6\" cellspacing=\"1\" bgcolor=\"black\"");

   if(isset($_POST['dbversion'])) {
     $dbversion = $_POST['dbversion'];
   } else {
     $dbversion = "mysql";
   }

   $row0 = array("<b>Database Version</b>", PHPWS_Form::formSelect("dbversion", $available_dbs, $dbversion, 1), "The SQL server type you are using."); 
   $row1 = array("<b>Database Host</b>", PHPWS_Form::formTextField("hostname", $hostname, 20), "The host address of your SQL server. Leave as <i>localhost</i> if you are not sure.");
   $row2 = array("<b>Database Username</b>", PHPWS_Form::formTextField("username", $username, 20), "The user that has access to the database created for this phpWebSite installation.<br />(It is suggested you make a database user JUST for this phpWebSite installation).");
   $row3 = array("<b>Database Password</b>", PHPWS_Form::formPassword("password", 20), "The password for the specified user.");
   $row4 = array("<b>Database Name</b>", PHPWS_Form::formTextField("database", $database, 20), "The name of the database, created for this phpWebSite installation.");
   $row5 = array("<b>Table Prefix</b>", PHPWS_Form::formTextField("tbl_prefix", $tbl_prefix, 20), "The prefix to append to all tables created by phpWebSite.<br />Leave blank if you don't require table prefixing.");
   $row6 = array("<b>Web Address</b>", "http://" . PHPWS_Form::formTextField("http_src", $http_src, 45), "The URL to use to access your phpWebSite site.");
   $row7 = array("<b>File Address</b>", PHPWS_Form::formTextField("filepath", $filepath, 50), "The location of your phpWebSite source files on the server.");
   $row8 = array("<b>Hub Hash</b>", PHPWS_Form::formTextField("hubhash", $hubhash, 36), "The hash to use to uniquely identify this phpWebSite installation.<br />Refresh this screen to create another or enter one of your own.");
   $row9 = array("<b>Install Password</b>", PHPWS_Form::formTextField("install_pass", $install_pass, 20), "The password to use when installing or updating this phpWebSite installation.<br />Set this to anything other than <i>default</i>, and do <b>not</b> share it with anyone.");

   $table->addRow($row0);
   $table->addRow($row1);
   $table->addRow($row2);
   $table->addRow($row3);
   $table->addRow($row4);
   $table->addRow($row5);
   $table->addRow($row6);
   $table->addRow($row7);
   $table->addRow($row8);
   $table->addRow($row9);

   $table->setRowAttributes(0, "bgcolor=\"#dddddd\"");
   $table->setRowAttributes(1, "bgcolor=\"#ffffff\"");
   $table->setRowAttributes(2, "bgcolor=\"#dddddd\"");
   $table->setRowAttributes(3, "bgcolor=\"#ffffff\"");
   $table->setRowAttributes(4, "bgcolor=\"#dddddd\"");
   $table->setRowAttributes(5, "bgcolor=\"#ffffff\"");
   $table->setRowAttributes(6, "bgcolor=\"#dddddd\"");
   $table->setRowAttributes(7, "bgcolor=\"#ffffff\"");
   $table->setRowAttributes(8, "bgcolor=\"#dddddd\"");
   $table->setRowAttributes(9, "bgcolor=\"#ffffff\"");

   echo $table->toHTML();
   echo "<br />" . PHPWS_Form::formSubmit("Create Config File");
   echo "</form>";
   break;

 case "2":
   include("./config.php");

   // windows - make sure escape last backslash
   $filepath = $dataArray['filepath'];
   if(strstr($filepath, "\\") && ("\\\\" != substr($filepath, strlen($filepath) - 2, 2))) {     
     $filepath .= "\\";
     $dataArray['filepath'] = $filepath;
   }

   $config_info = config_maker($dataArray);

   if ($parseInput){
     $dbversion = preg_replace("/[^\w]/i", "", $dataArray['dbversion']);
     $username = preg_replace("/[^\w_0-9]/i", "", $dataArray['username']);
     $password = preg_replace("/" . $allowedChars . "/i", "", $dataArray['password']);
     $hostname = preg_replace("/[^\w_0-9.\-\(\)]/i", "", $dataArray['hostname']);
     $database = preg_replace("/[^\w_0-9]/i", "", $dataArray['database']);
   } else {
     $dbversion = $dataArray['dbversion'];
     $username = $dataArray['username'];
     $password = $dataArray['password'];
     $hostname = $dataArray['hostname'];
     $database = $dataArray['database'];
   }

   if($dbversion == "mysql") {
       if (!extension_loaded('mysql')){
	   echo "Sorry, you must have the mysql PHP libraries installed in order to use phpWebSite<br />";
	   exit();
       }
   }

   $core->db = DB::connect("$dbversion://$username:$password@$hostname"."/".$database);

   $badPassWords = array("passwd", "password", "pass", "qwerty", "asdf", "admin", "phpws", "phpwebsite", "test", "asd", "passpass");

   if (isset($core->db->message))
     $error = "DB";
   elseif (!empty($tbl_prefix) && !(preg_match("/^[a-z]{1}[a-z0-9_]+$/iU", $tbl_prefix)))
     $error = "prefix";
   elseif (empty($filepath) || !is_dir($filepath))
     $error = "badDir";
   elseif (empty($http_src))
     $error = "noAddress";
   elseif (empty($install_pass) || in_array($install_pass, $badPassWords))
     $error = "noPW";

   if(isset($error)) {
     $back = $_POST;
     if(get_magic_quotes_gpc())
       $back["filepath"] = stripslashes($back["filepath"]);
     $back["step"] = 1;

     echo "<span style=\"color : red ; font-weight :heavy\">";
     if ($error == "DB")
       echo "Unable to connect to your server's database.<br />";
     elseif ($error == "prefix")
       echo "The table prefix entered was not valid.<br />";
     elseif ($error == "badDir")
       echo "The directory <b>$filepath</b> could not be found.<br />";
     elseif ($error == "noPW")
       echo "Your must enter an installation password and don't make it easy to guess.<br />";
     elseif ($error == "noAddress")
       echo "Your must enter a web address.<br />";

     echo "</span> Please reenter your settings.\n
<form action=\"set_config.php\" method=\"post\">
".PHPWS_Form::formHidden($back)."
".PHPWS_Form::formSubmit("Return to Setup")."
</form>";
     exit();
   }
 
 if (PHPWS_File::writeFile("../conf/config.php", $config_info, TRUE, 1)){
   echo "Configuration file saved under the name conf/config.php<br />After testing your installation, it would be best to make sure your conf/ directory is not writable.<br />";
   echo "Don't forget the installation password you supplied.<br /><br />Click ".PHPWS_Text::link($http_src . "setup/setup.php", "here")." to continue with the installation.<br /><hr />";
 } else {
   $transfer = $_POST;
   unset($transfer["step"]);
   $transfer["print_config"] = "1";
   echo "Unable to save config.php file to ".$_POST["filepath"]."conf<br /><br />";
   echo "You have two options:<br /><br />";
   echo "<b>Option 1</b><br />Click on the link below and cut and paste it into your newly created config.php file.<br />";
   echo PHPWS_Text::link($http_src . "setup/config.php", "Cut and Paste", NULL, $transfer)."<br /><br />";
   unset($transfer["print_config"]);

   $transfer["save_config"] = 1;
   echo "<b>Option 2</b><br />Right click on the link below and pick <b>Save Target As</b>. Then save the file as <b>config.php</b>. Upload the file to the \"conf\" directory in the phpWebSite root directory.<br />";
   echo PHPWS_Text::link($_POST["http_src"]."setup/config.php", "Right Click Me", NULL, $transfer) . "<hr />";;
   unset($transfer["save_config"]);
 }

 $writeable = TRUE;
 if(!is_writeable("../files")) {
   echo "<span style=\"color:red\">The <b>files</b> directory is not webserver writable, please read step 2 in ./docs/INSTALL.txt before you continue with the installation.</span><br />";
   $writeable = FALSE;
 }
 
 if(!is_writeable("../images")) {
   echo "<span style=\"color:red\">The <b>images</b> directory is not webserver writable, please read step 2 in ./docs/INSTALL.txt before you continue with the installation.</span><br />";
   $writeable = FALSE;
 }

 if(!is_writeable("../images/mod/controlpanel")) {
   echo "<span style=\"color:red\">The <b>images/mod/controlpanel</b> directory is not webserver writable, please read step 2 in ./docs/INSTALL.txt before you continue with the installation.  Control panel icons will be missing if you continue.</span><br />";
   $writeable = FALSE;
 }

 if(!$writeable) {
   echo "<hr />";
 }

 echo "<span style=\"color:red\">It is recommended that you read step 10 and 11 in ./docs/INSTALL.txt when you have finished installation.</span>";
 break;
}

?>
</body>
</html>