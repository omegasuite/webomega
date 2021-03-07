<?php
/**
 * Controls the text parsing and profanity controls for phpWebSite
 * Also contains extra HTML utilities
 * 
 * @version $Id: Text.php,v 1.99 2005/08/17 13:34:28 matt Exp $
 * @author  Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
 * @author  Adam Morton <adam@NOSPAM.tux.appstate.edu>
 * @author  Steven Levin <steven@NOSPAM.tux.appstate.edu>
 * @author  Don Seiler <don@NOSPAM.seiler.us>
 * @package Core
 */

if (!function_exists("ereg")) {
	function eregi($pat, $target) {
		if (strstr($pat, "/"))
			return preg_match("@$pat@i", $target);
		return preg_match("/$pat/i", $target);
	}
	function ereg($pat, $target) {
		if (strstr($pat, "/"))
			return preg_match("@$pat@", $target);
		return preg_match("/$pat/", $target);
	}
}

class PHPWS_Text {

  /**
   * An array of "bad" words for this site.  Used to filter
   * profanity.
   * @var array
   * @access private
   */
  var $bad_words;

  /**
   * Determines whether or not to strip profanity.
   * @var boolean
   * @access private
   */
  var $strip_profanity;

  /**
   * An array of allowed HTML tags for the site.
   * @var array
   * @access private
   */
  var $allowed_tags;

  /**
   * Determines whether or not to convert newlines to breaks.
   * @var boolean
   * @access private
   */
  var $add_breaks;

  /**
   * Text settings for the core
   *
   * Only loaded if a database connection is successful
   *
   * @author Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   */
  function loadTextSettings(){

    if (file_exists(PHPWS_HOME_DIR . "conf/textSettings.php"))
      $textFile = PHPWS_HOME_DIR . "conf/textSettings.php";
    elseif(file_exists(PHPWS_SOURCE_DIR . "conf/textSettings.php"))
      $textFile = PHPWS_SOURCE_DIR . "conf/textSettings.php";
    else {
      exit("Error: Unable to locate textSettings file.<br />" . PHPWS_SOURCE_DIR . "conf/textSettings.php");
      return;
    }

    include ($textFile);
    $this->allowed_tags = $allowed_tags;
    $this->bad_words = $bad_words;
    $this->strip_profanity = $strip_profanity;
    $this->add_breaks = $add_breaks;
  }

  /**
   * Removes profanity from a text string
   *
   * Profanity definitions are set by the core in the textSettings.php file
   *
   * @author Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param  string $text Text to be parsed
   * @return string Parsed text
   * @access public
   */
  function profanityFilter($text) {
    if (!is_array($GLOBALS["core"]->text->bad_words))
      exit("Error: bad_words variable in your textSettings file is not an array");

    foreach ($GLOBALS["core"]->text->bad_words as $matchWord=>$replaceWith)
      $text = preg_replace("/$matchWord/i", $replaceWith, $text);

    return $text;
  }// END FUNC profanityFilter()


  /**
   * Breaks text up into row sentences in an array
   * 
   * @author Matt McNaney <matt@NOSPAM_tux.appstate.edu>
   * @param  string  $text       Text string to break into array
   * @return array   $text_array Array of sentences
   * @access public
   */
  function sentence($text, $stripNewlines = FALSE){
    if (!is_string($text)) exit ("sentence() was not sent a string");

    if (strstr($text, "\r"))
      $text_array = explode("\r\n",$text);
    else
      $text_array = explode("\n",$text);

    return $text_array;
  }// END FUNC sentence()


  /**
   * Adds breaks to text where newlines exist
   * 
   * This function will ONLY add a break if the current break is not preceded
   * by certain tags (see below). This will prevent breaks in tables etc.
   * Make sure you enter the tags in regular expression form.
   *
   * @author Matt McNaney <matt@NOSPAM_tux.appstate.edu>
   * @param  string $text    Text you wish breaked
   * @return string $content Formatted text
   * @access public
   */
  function breaker($text){
    if (!is_string($text)) exit ("breaker() was not sent a string");
    if (!$GLOBALS["core"]->text->add_breaks) return $text;

    $text_array = PHPWS_Text::sentence($text);
    $lines = count($text_array);
    $endings = array ("<br \/>",
		      "<br>",
		      "<img .*>",
		      "<\/?p.*>",
		      "<\/?area.*>",
		      "<\/?map.*>",
		      "<\/?li.*>",
		      "<\/?ol.*>",
		      "<\/?ul.*>",
		      "<\/?dl.*>",
		      "<\/?dt.*>",
		      "<\/?dd.*>",
		      "<\/?table.*>",
		      "<\/?th.*>",
		      "<\/?tr.*>",
		      "<\/?td.*>",
		      "<\/?h..*>");

    $loop = 0;
    $search_string = NULL;
    foreach ($endings as $tag){
      if ($loop) $search_string .= "|";
      $search_string .= $tag."\$";
      $loop = 1;
    }

    $count = 0;
    $content = NULL;
    $preFlag = false;
    foreach ($text_array as $sentence){
      $count++;
      if ($count < $lines){
	if(!$preFlag) {
	  if(preg_match("/<pre>\$/iU", trim($sentence))) {
	    $preFlag = true;
	    $content .= $sentence."\n";
	    continue;
	  }
	  if (!preg_match("/".$search_string."/iU" , trim($sentence))) $content .= $sentence."<br />\n";
	  else $content .= $sentence."\n";
	} else if(preg_match("/<\/pre>\$/iU", trim($sentence))) {
	  $preFlag = false;
	  $content .= $sentence."\n";
	  continue;
	} else {
	  $content .= $sentence."\n";
	}
      } else 
	$content .= $sentence;
    }
    return $content;
  }// END FUNC breaker()


  /**
   * Returns true if the $char passed is an alphabetic character and false
   * if it is not.
   *
   * @author Adam Morton <adam@NOSPAM.tux.appstate.edu>
   * @param  string  $char The character to be tested
   * @return boolean TRUE if it's in the english alphabet, FALSE if not
   * @access public
   */
  function isAlpha ($char) {
    return !preg_match("/[^a-zA-Z]/", $char);
  }// END FUNC isAlpha()


  /**
   * Returns a string with backslashes before characters that need
   * to be quoted in database queries.
   *
   * Different than basic command as it checks to see if magic slashes is on.
   * If magic quotes is on, then addslashes will just return the string
   *
   * @author Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param  string $text  Text to addslashes to.
   * @return string Slashed text
   * @access public
   */
  function addslashes($text) {
    if (get_magic_quotes_gpc()) return $text;
    else return addslashes($text);
  }// END FUNC addslashes


  /**
   * Returns a string with backslashes removed BUT ONLY
   * if magic slashes is on.
   *
   * @author Matthew McNaney <matt@NOSPAM.tux.appstatee.edu>
   * @param  string $text Text to be stripped
   * @return string $text Stripped text
   * @access public
   */
  function stripslashes($text) {
    if (get_magic_quotes_gpc()==1) return stripslashes($text);
    else return $text;
  }// END FUNC stripslashes()

  /**
   * An alias for the stripslashes function.
   *
   * Helps to distinguish it between the PHPWS_Text function and the PHP
   * function and how the handle slashes.
   *
   * @param  string $text Text to be stripped
   * @return string $text Stripped text
   * @access public
   */
  function magicstrip($text) {
    return PHPWS_Text::stripslashes($text);
  }

  /**
   * Removes tags from text
   *
   * This function replaces the functionality of the 'parse' function
   * Should be used after a post or get or before saving it to the database
   *
   * @author                       Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param   string  text         Text to parse
   * @param   mixed   allowedTags  The tags that will not be stripped from the text
   * @return  string  text         Stripped text
   */
  function parseInput($text, $allowedTags=NULL){
    $text = PHPWS_Text::stripSlashQuotes($text);

    if(preg_match("/src=([\"']{0,1}).*(?<=[=\"']\?|index.php|module=).*([\"']{0,1})/Ui", $text) ||
       preg_match("/onload=/i", $text))
      $text = preg_replace("/<img.+>/Uei", "", $text);

    if ($allowedTags == "none")
      $allowedTagString = NULL;
    elseif (is_array($allowedTags))
      $allowedTagString = implode("", $allowedTags);
    elseif (is_string($allowedTags))
      $allowedTagString = $allowedTags;
    else {
      $allowedTagString = $GLOBALS["core"]->text->allowed_tags;
    }

    $text = preg_replace("/(\[code\])(.*)(\[\/code\])/seU", "'\\1' . str_replace('\n', '', PHPWS_Text::utfEncode('\\2')) . '\\3'", $text);
    $text = str_replace("'", "&#39;", $text);

    return strip_tags($text, $allowedTagString);
  }

  function utfEncode($text){
    $text = PHPWS_Text::stripSlashQuotes($text);

    $search = array("/</", "/>/");
    $replace = array('&#x003c;', '&#x003e;');

    return preg_replace($search, $replace, $text);
  }

  /**
   * Prepares text for display
   *
   * This function replaces the functionality of the 'parse' and appends
   * the breaker function.
   * Should be used after retrieving data from the database
   *
   * @author                       Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param   string  text         Text to parse
   * @return  string  text         Stripped text
   */
  function parseOutput($text, $printTags=FALSE){
    require_once("HTML/BBCodeParser.php");

    // Set up BBCodeParser
    $config = parse_ini_file(PHPWS_SOURCE_DIR . "/conf/BBCodeParser.ini", true);
    $options = &PEAR::getStaticProperty("HTML_BBCodeParser", "_options");
    $options = $config["HTML_BBCodeParser"];
    unset($options);

    if ($GLOBALS["core"]->text->strip_profanity) 
      $text = PHPWS_Text::profanityFilter($text);

    if ($printTags)
      $text = htmlspecialchars($text);

    $text = preg_replace("/&(?!\w+;)(?!#)/U", "&amp;\\1", $text);
    $text = preg_replace('/{/', '&#x007b;', $text);
    $text = preg_replace('/}/', '&#x007d;', $text);

    $parser = new HTML_BBCodeParser();
    $parser->setText($text);
    $parser->parse();
    $text = $parser->getParsed();
    if(preg_match("/src=([\"']{0,1}).*(?<=[=\"']\?|index.php|module=).*([\"']{0,1})/Ui", $text) ||
       preg_match("/onload=/i", $text))
      $text = preg_replace("/<img.+>/Uei", "", $text);
    
    $text = str_replace("&#39;", "'", $text);
    // Parse BBCode
    return PHPWS_Text::breaker($text);
  }

    function ampersand($text)
    {
        return preg_replace('/&(trade|bull|deg|copy|reg|hellip)/', '&amp;\\1', $text);
    }

    function encodeXHTML($text){
        $text = strtr($text, $xhtml);
        $text = preg_replace('/&(?!\w+;)(?!#)/U', '&amp;\\1', $text);
        
        return $text;
    }

  /**
   * Checks the validity of text based on the type
   *
   * Designed to be a catch all method to parse critical text.
   * - chars_space : input must be alphanumberic. Spaces allowed
   * - number : input must be numeric
   * - email : input must appear to be valid email address
   * - file : input must appear to be a proper file name format
   * - default : alphanumeric and underline ONLY
   *
   * Should be used anytime user input directly affects program logic,
   * is used to pull database data, etc. Also, will ALWAYS return FALSE
   * if it receives blank data. 
   *
   * @author Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param  string  $userEntry Text to be checked
   * @param  string  $type      What type of comparison
   * @return boolean TRUE on valid input, FALSE on invalid input
   * @access public
   */
  function isValidInput($userEntry, $type=NULL) {
    if (empty($userEntry) || !is_string($userEntry)) return FALSE;

    switch ($type) {
    case "chars_space":
    if (eregi("^[a-z_0-9 ]+$",$userEntry)) return TRUE;
    else return FALSE;
    break;

    case "number":
    if (ereg("^[0-9]+$",$userEntry)) return TRUE;
    else return FALSE;
    break;

    case "url":
    if (eregi("^(http:\/\/)[_a-z0-9-]+(\.[_a-z0-9-]+|\/)", $userEntry)) return TRUE;
    else return FALSE;
    break;

    case "email":
    if (eregi("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)+$", $userEntry)) return TRUE;
    else return FALSE;
    break;

    case "file":
    if (eregi("^[a-z_0-9\.]+$",$userEntry)) return TRUE;
    else return FALSE;
    break;

    default:
      if (eregi("^[a-z_0-9]+$",$userEntry)) return TRUE;
      else return FALSE;
    break;
    }
  }// END FUNC validForm()


  /**
   * Returns an image string
   *
   * If width or height are not supplied, the function supplies them for you.
   * This function will attempt to return an empty box if it cannot find the file.
   *
   * @author Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param  string  $address The address of the image
   * @param  string  $alt     The alt text for the image (required)
   * @param  mixed   $width   Width of the graphic
   * @param  mixed   $height  Height of the graphic
   * @param  mixed   $border  Width of the graphics border
   * @param  boolean $blank   Unknown
   * @return string  $image   The html image tag
   * @access public
   */
  function imageTag($address, $alt=NULL, $width=NULL, $height=NULL, $border=0, $blank=FALSE){
    $dimensions = NULL;

    if ($GLOBALS['core']->isHub){
      $checkDir = str_replace(PHPWS_SOURCE_HTTP, PHPWS_SOURCE_DIR, $address);
      $address = str_replace("http://", "", $address);
      $address = str_replace(PHPWS_SOURCE_HTTP, "", $address);
    }
    else {
      if (stristr($address, PHPWS_HOME_HTTP . "images/")) {
	$checkDir = str_replace(PHPWS_HOME_HTTP, PHPWS_HOME_DIR, $address);
	$address = str_replace("http://", "", $address);
	$address = str_replace(PHPWS_HOME_HTTP, "", $address);
      }
      else if($address[0] == '.' && $address[1] == '/') {
	//relative link, no modifications needed       
	
      } else {
	$checkDir = str_replace(PHPWS_SOURCE_HTTP, PHPWS_SOURCE_DIR, $address);
	$address = PHPWS_Text::checkLink($address);
      }
    }

    if (is_null($width) && is_null($height)){
      $size = @getimagesize($checkDir);
      if ($size == FALSE)
	return NULL;
      else
	$dimensions = " " . $size[3];
    } else {
      if (isset($width))
	$dimensions .= " width=\"$width\"";
      if (isset($height))
	$dimensions .= " height=\"$height\"";
    }

    $border = " border=\"$border\"";
    $alt = " alt=\"" . strip_tags($alt) . "\"";
    $image = "<img src=\"$address\"" . $dimensions . $border . $alt . " />";

    return $image;
  }// END FUNC imageTag()


  /**
   * Builds a basic table from an array
   *
   * The array should be 2 dimensional. The indexes are
   * irrelevant. The first indice makes the row, the 
   * second indices create the columns. If $th=1,
   * the table will turn the top row into table headers.
   * If toggle=1, the table will use css standard 'bg_light'
   * to alternately color the rows.
   *
   * @author Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param  array   $data_array  Array of columns and rows to create the table from
   * @param  integer $cellpadding Pixels of cellpadding between contents and border
   * @param  integer $cellspacing Pixels of spacing between borders.
   * @param  integer $border      Pixel width of the border
   * @param  integer $width       Pixel or percentage width of the table
   * @param  boolean $th          If TRUE, make the top row a header
   * @param  boolean $toggle      If TRUE, alternate shading of rows
   * @param  string  $valign      Vertical alignment of the contents of the cells
   * @param  string  $class       Style sheet class to attach to the table
   * @return string  $table       The formatted table
   * @access public
   */
  function ezTable($data_array, $cellpadding=0, $cellspacing=0, $border=0, $width=0, $th=FALSE, $toggle=FALSE, $valign=NULL, $class=NULL){
    $high_column = 0;
    $bg = $cell_width = $background = $table_width = NULL;
    if (!is_array($data_array)) return FALSE;

    foreach ($data_array as $row_count){
      if ($high_column < count($row_count))
        $high_column = count($row_count);      
    }
    reset ($data_array);

    if ($class) $background = " class=\"$class\"";

    if ($width){
      $width_per_cell = (int)floor(100/$high_column);
      $cell_width = " width=\"".$width_per_cell."%\"";
      $table_width = " width=\"".$width."%\"";
    }
    
    if ($valign) $vert = " valign=\"".$valign."\"";
    else $vert = " valign=\"top\"";
    
    if ($high_column){
      $table = "<table cellpadding=\"$cellpadding\" cellspacing=\"$cellspacing\" border=\"$border\"". $table_width . $background.">";
      
      foreach ($data_array as $row_array){
        if ($toggle)
          PHPWS_WizardBag::toggle($bg, " class=\"bg_light\"");

        $table .= "<tr>";
        
        for ($j=0; $j < $high_column; $j++){
          if ($th && !$loop) $table .= "<th" . $cell_width . ">$row_array[$j]</th>";
          else $table .= "<td" . $bg . $cell_width . $vert .">$row_array[$j]</td>";
        }
        $loop = 1;
        $table .= "</tr>";
      }
      $table .= "</table>";

      return $table;
    } else return NULL;
  }// END FUNC ezTable()


  /**
   * Allows a quick link function for phpWebSite modules to the index.php.
   * 
   * A replacement for the clunky function link. This is for modules accessing
   * local information ONLY. It adds the hub web address and index.php automatically.
   * You supply the name of the module and the variables.
   *
   * @author Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param string title String to appear as the 'click on' word(s)
   * @param string module Name of module to access
   * @param array getVars Associative array of GET variable to append to the link
   * @return string The complated link.
   */
  function moduleLink($title, $module=NULL, $getVars=NULL, $target=NULL, $class=NULL){
    $link = "<a href=\"./";

    if ($GLOBALS['core']->moduleExists($module)){
      $link .= "index.php?module=$module";

      if (is_array($getVars)){
	foreach ($getVars as $var_name=>$value){
	  $link .= "&amp;";
	  
	  $link .= $var_name . "=" . $value;
	  $i = 1;
	}
      }
    }

    if ($target=="blank" || $target === TRUE)
      $linkTarget = " target=\"_blank\" ";
    elseif ($target=="index")
      $linkTarget = " target=\"index\" ";
    else
      $linkTarget = NULL;

    $link .= "\"";

    if(isset($class))
      $link .= " class=\"$class\"";

    return $link . $linkTarget . ">".strip_tags($title, "<img>")."</a>";
  }// END FUNC indexLink()

  
  /**
   * Returns a HREF link string
   *
   * If the type is designated "local", the function will
   * write a 'http://' and your source_http value on to the front
   * of you address. For example, send just 'index.php' and if will
   * add your local address automatically for use in phpwebsite.
   * If 'local' is not sent to the type it will assume you are linking
   * off the site and use address as is.
   *
   * Sending an array to $get_var will create a get suffix on
   * to the link. For example:
   *
   * $array["article_number"] = "5";
   * $array["preference"] = "all";
   *
   * $this->link("index.php", "Show All 5 Articles", "local", $array);
   * //Returns: <a href="your_web_site.com/index.php?article_number=5&preference=all">Show All 5 Articles</a>
   *
   * Finally if you use "index" or "blank" for $target, your link will open in 
   * a new window. Blank opens a new window each time. Index opens one new window
   * and any new links will open in that window.
   *
   * @author Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param  string   $address Where the link will go
   * @param  string   $text    Clickable text (or picture) to go to the address
   * @param  string   $type    'local' if linking in pws, blank otherwise
   * @param  resource $get_var Associative array of get values to add to address
   * @param  string   $target  'blank' or 'index' to open a new window
   * @param  string   $onclick Command to execute upon clicking the link
   * @return string   $link    The link string
   * @access public
   */
  function link($address, $text, $type=NULL, $get_var=NULL, $target=NULL, $onclick=NULL){
    if ($type == "local") $address = "http://" . PHPWS_SOURCE_HTTP . $address;
    elseif ($type == "index") $address;
    elseif (!preg_match("/(http:\/\/)/i",$address)) $address = "http://".$address;

    $link = "<a href=\"$address";

    $i = NULL;
    if (is_array($get_var)){
      foreach ($get_var as $var_name=>$value){
        if ($i) $link .= "&amp;";
        else $link .= "?";

        $link .= $var_name . "=" . $value;
        $i = 1;
      }
    }

    if ($onclick) $onclick = " onclick=\"$onclick\"";

    $relay = NULL;
    if ($target=="blank") $relay = " target=\"_blank\"";
    elseif ($target=="index") $relay = " target=\"index\"";

    $link .= "\"" . $relay . $onclick . ">$text</a>";

    return $link;
  }// END FUNC link()


  /**
   * Appends http:// if missing
   *
   * More text detail
   *
   * @author Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param  string $link Link string to check
   * @return string $link Appended string
   * @access public
   */
  function checkLink($link){
    if (!stristr($link, "://")) return "http://".$link;
    else return $link;
  }// END FUNC checkLink()
  


  /**
   * Removes spaces from a string
   *
   * If "replace" is sent, that character will replace the space.
   * Could be useful for filenames
   *
   * @author Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param  string $text    String to remove spaces from
   * @param  string $replace String to use instead of spaces
   * @return string The original string without spaces
   * @access public
   */
  function stripSpaces($text, $replace=NULL) {
    if (is_string($replace)) return str_replace(" ", substr($replace, 0, 1), $text);
    else return str_replace(" ", "", $text);
  }// END FUNC stripSpaces()


  /**
   * alphaNum
   *
   * Removes any character that is not alphanumeric
   *
   * @author Matthew McNaney
   * @param  string $stripper The string to strip non-alphanumeric characters from.
   * @return string The original string with all non-alphanumeric characters removed.
   * @access public
   */
  function alphaNum($stripper) {
    return preg_replace("/[^a-zA-Z0-9]/", "", $stripper);
  }// END FUNC alphaNum()


  /**
   * Returns TRUE if the text appears to have unslashed quotes or apostrophes
   *
   * @author Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param  string  $text Text to be checked for unslashed quotes or apostrophes
   * @return boolean TRUE on success, FALSE on failure
   * @access public
   */
  function checkUnslashed($text){
    if (preg_match("/[^\\\]+[\"']/", $text))
      return TRUE;
    else
      return FALSE;
  }// END FUNC checkUnslashed()

  /**
   * Removes quotes from a string
   *
   * @author Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param  string $text Text to remove quotes from
   * @return string $text Parsed text
   * @access public
   */
  function stripQuotes($text) {
    $text = str_replace("'", "", $text);
    $text = str_replace("\"", "", $text);
    return $text;
  }// END FUNC stripQuotes()

  /**
   * Removes slashes ONLY from quotes or apostrophes, nothing else
   *
   * @author Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param  string $text Text to remove slashes from
   * @return string $text Parsed text
   * @access public
   */
  function stripSlashQuotes($text){
    $text = str_replace("\'", "'", $text);
    $text = str_replace("\\\"", "\"", $text);
    return $text;
  }// END FUNC stripSlashQuotes()
}//END CLASS CLS_text

?>