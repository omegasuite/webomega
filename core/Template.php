<?php

require_once(PHPWS_SOURCE_DIR . "core/Cache.php");

/**
 * This class provides the functions used to parse and interpret templates.  It
 * relies heavily on the PEAR HTML_Template_IT class.
 *
 * @version $Id: Template.php,v 1.31 2005/03/10 18:52:19 steven Exp $
 * @author  Adam Morton <adam@NOSPAM.tux.appstate.edu>
 * @package Core
 */
class PHPWS_Template {

  /**
   * Finds a template for a given module and returns a path to that template file.  This is a helper
   * function for processTemplate().
   *
   * @author Adam Morton <adam@NOSPAM.tux.appstate.edu>
   * @param  string $module       The name of the module calling this function (i.e.: "layout")
   * @param  string $templateFile The name of the file containing the template.  This is NOT the full path, just the file name.
   * @return mixed  Either returns a string containing the path to the template file or a boolean FALSE if if the template file is not found.
   * @see    processTemplate()
   * @access public
   */
  function getTemplateDir($module, $templateFile) {
    if(file_exists($_SESSION["OBJ_layout"]->theme_dir . "templates/" . $module . "/" . $templateFile)) {
	   return $_SESSION["OBJ_layout"]->theme_dir . "templates/" . $module;
	} elseif($module == "core") {
	   return PHPWS_SOURCE_DIR . "templates/";
	} elseif(file_exists(PHPWS_SOURCE_DIR . "mod/" . $module . "/templates/" . $templateFile)) {
	   return PHPWS_SOURCE_DIR . "mod/" . $module . "/templates";
	} elseif(file_exists(PHPWS_SOURCE_DIR . "ui/web/" . $module . "/html/" . $templateFile)) {
	   return PHPWS_SOURCE_DIR . "ui/web/" . $module . "/html";
    } else {
      return FALSE;
    }
  } //END FUNC getTemplate()


  /**
   * Returns an array containing the file names of all templates for a given module.  This function
   * assumes that the programmer is using the correct file structure for storing their templates.
   *
   * @author   Adam Morton <adam@NOSPAM.tux.appstate.edu>
   * @modified Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @modified Eloi George
   * @param    string  $module  The name of the module calling this function (i.e.: "layout")
   * @param    boolean $dirOnly Determines whether listTemplates() returns an array of file names within the templates
   *                            directory or the directory names. [ You're welcome Steven :) ]
   * @param    string  $subdir  The name of a requested subdirectory within the template directory
   * @return   mixed            Either returns an array containing the filenames within the given module's templates
   *                            directory, an array containing the directory names withing the given module's templates
   *                            directory, or a boolean FALSE if the templates directory does not exist for the given module.
   * @see    readDirectory()
   * @access public
   */
  function listTemplates($module, $dirOnly = FALSE, $subdir=NULL) {
    if ($subdir!=NULL)
      $subdir = $subdir . "/";
    if(is_dir($_SESSION["OBJ_layout"]->theme_dir . "/templates/" . $module . "/" . $subdir)) {
      return PHPWS_File::readDirectory($_SESSION["OBJ_layout"]->theme_dir . "/templates/" . $module . "/" . $subdir, $dirOnly);
    } else if(is_dir(PHPWS_SOURCE_DIR . "mod/" . $module . "/templates/" . $subdir)) {
      return PHPWS_File::readDirectory(PHPWS_SOURCE_DIR . "mod/" . $module . "/templates/" . $subdir, $dirOnly);
    } else {
      return FALSE;
    }
  } // END FUNC listTemplates()

  /**
   * Resets the template session
   *
   * If a module title is set, just that module's templates get refreshed.
   * If nothing is set, the whole cache is flushed.
   *
   * PHPWS_Cache::flush should be used instead of this function
   *
   * @author Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param  boolean $module  mod_title of module you wish reset
   */
  function refreshTemplate($module=NULL){
    PHPWS_Cache::flush($module);
  }

  /**
   * This function is based off the PEAR Integrated Template (IT) class though it is
   * written specifically for phpWebSite.  All template syntax should follow the
   * guidelines given here:
   *
   *         http://pear.php.net/manual/en/packages.templates.it.php
   *
   * Addendum: Update 12/19/02 processTemplate now uses the PHPWS_Cache class. See the
   * conf/cache.php file to disable
   * Addendum: Update 1/8/03   tagFlag marks used and unused tags if set to TRUE. See
   * tagFlag function below.
   * Addendum: Update 3/3/03   PAGE_CACHE added
   *
   * @author   Adam Morton <adam@NOSPAM.tux.appstate.edu>
   * @modified Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param    array   $templateArray  An associative array of the replacement variables.
   *                                   The key is the variable name within the template to
   *                                   be replaced and the value is the data to replace the
   *                                   variable with.
   * @param    string   $module        The name of the module calling this function (i.e.: "layout")
   * @param    string   $template      The name of the file containing the template or a
   *                                   complete path to a template file.
   *                                   If sending a normal filename make sure it is NOT the
   *                                   full path, just the filename. If sending a filename with
   *                                   defaultDir FALSE, then use the full path.
   * @param    boolean  $defaultDir    If TRUE, function will compare templates in both the theme
   *                                   and the source. If FALSE, the function will use the exact
   *                                   directory and filename in $template
   * @param    boolean  $tagFlag       If TRUE, NULL tags will add a *_FALSE and used tags add a
   *                                   *_TRUE, where * is the name of the tag.
   * @param    resource $showBlocks    An array of block names to always be shown within the template
   *                                   whether they are empty or not.
   * @param    resource $hideBlocks    An array of block names to always be hidden within the template
   *                                   whether they contain data or not. (NOT YET IMPLEMENTED!)
   * @param    boolean  $suppressError Return an error string instead of template string if this function
   *                                   encounters an error.  If this is set to TRUE and the function
   *                                   encounters an error, a FALSE is returned instead of a string.
   * @return   mixed                   Either returns a string containing the template string with all
   *                                   replacements done, a string containing an error message, or a
   *                                   boolean FALSE if suppress_error = TRUE.
   * @see      getTemplate()
   * @see      tagFlag()
   * @access   public
   */
  function processTemplate($templateArray, $module, $template, $defaultDir = TRUE, $tagFlag=FALSE,
                           $showBlocks = NULL, $hideBlocks = NULL, $suppressError = FALSE) {
    if(!is_array($templateArray)) {
      return "PHPWS_Template ERROR! processTemplate(): templateArray is not an array, it is a(n) ".gettype($templateArray).".";
    }

    if(PAGE_CACHE == TRUE) {
	   $cacheKey = md5(serialize($templateArray) . $template);
	   $cache = PHPWS_Cache::get($cacheKey, $module);
	   if(!empty($cache)) {
			return $cache;
	   }
    }

    /* Check for reserved tags in $templateArray and exit with an error if any of them are set. */
    if(isset($templateArray["PHPWS_IMAGES"]) || isset($templateArray["PHPWS_THEMES"]) ||
       isset($templateArray["PHPWS_SOURCE"])) {
      return "PHPWS_Template ERROR! processTemplate(): Variables PHPWS_IMAGES, PHPWS_THEMES,
              and PHPWS_SOURCE are reserved for use by the core.";
    } else {
      /* Passed error check, set reserved tags in $templateArray */
      $templateArray["PHPWS_IMAGES"] = PHPWS_HOME_DIR . "images/";
      $templateArray["PHPWS_THEMES"] = PHPWS_HOME_DIR . "themes/";
      $templateArray["PHPWS_SOURCE"] = PHPWS_SOURCE_DIR;
    }

    /* Add flags */
    if ($tagFlag) {
		$templateArray = PHPWS_Template::tagFlag($templateArray);
    }

    /* If var $template is a string process the string as the template */
    /* var $template is a filename, so get path to the file */
    if ($defaultDir) {
		$templateDir = PHPWS_Template::getTemplateDir($module, $template);
		if($templateDir) {
			$tpl = new HTML_Template_IT($templateDir, array('preserve_data'=> true));
			$tpl->loadTemplatefile($template, TRUE, TRUE);
			PHPWS_Cache::set($tpl->getFile($template), $template, $module);
		}
		else return "ERROR: Module <b>$module</b> - Template file <b>$templateDir</b> not found!";
	}
	elseif (file_exists($template)) {
		$tpl = new HTML_Template_IT('', array('preserve_data'=> true));
		$cache = $tpl->getFile($template);
		$tpl->setTemplate($cache);
	}
	else {
		return "ERROR: Module <b>$module</b> - Template file <b>$template</b> not found!";
    }

    phpws_array::dropNulls($templateArray);
    $templateArray = str_replace("\$", "&#36;", $templateArray);

    $tpl->setVariable($templateArray);

    if(is_array($showBlocks)) {
		foreach($showBlocks as $blockName)	$tpl->touchBlock($blockName);
    }

    $data = $tpl->get();

    if (PAGE_CACHE == TRUE) PHPWS_Cache::set($data, $cacheKey, $module);

    return $data;
  } // END FUNC processTemplate()

    /**
     * processBlockTemplate
     *
     * This function is very much like the plain processTemplate function except it adds the ability
     * to parse a template block more than once.  Allowing the developer to add multiple rows to a list
     * without having to use a seperate template which was common practice until we got smarter :)
     *
     * @author   Steven Levin <steven [at] tux dot appstate.edu>
     *
     * @param array  $blockTags    a 2-D array which consists of an array containing associative arrays of tags
     *                             that are to be replaced into a block seperately
     * @param string $blockName    the name of block that is to be repeated while processing the template
     *                             usually a row in a list of items
     * @param array  $templateTags an associative array of tags to be parsed outside of the block
     * @param string $module       the name of the module calling the process function
     * @param string $template     the name of the template which needs to be parsed
     */
    function processBlockTemplate($blockTags, $blockName, $templateTags, $module, $template) {
	$templateDir = PHPWS_Template::getTemplateDir($module, $template);

	/* Check for reserved tags in $templateArray and exit with an error if any of them are set. */
	if (isset($templateTags['PHPWS_IMAGES']) || isset($templateTags['PHPWS_THEMES']) ||
	    isset($templateTags['PHPWS_SOURCE'])) {
	    return 'PHPWS_Template ERROR! processTemplate(): Variables PHPWS_IMAGES, PHPWS_THEMES,
              and PHPWS_SOURCE are reserved for use by the core.';
	} else {
	    /* Passed error check, set reserved tags in $templateTags */
	    $templateTags['PHPWS_IMAGES'] = PHPWS_HOME_DIR .'images/';
	    $templateTags['PHPWS_THEMES'] = PHPWS_HOME_DIR .'themes/';
	    $templateTags['PHPWS_SOURCE'] = PHPWS_SOURCE_DIR;
	}

	$tpl = new HTML_Template_IT($templateDir, array('preserve_data'=> true));
	$tpl->loadTemplatefile($template, true, true);

	if (is_array($blockTags)) {
	    foreach ($blockTags as $block) {
		$tpl->setCurrentBlock($blockName);
		$tpl->setVariable($block);
		$tpl->parseCurrentBlock($blockName);
	    }
	}

	$tpl->setVariable($templateTags);
	$tpl->parse();

	return $tpl->get();
    } // END FUNC processBlockTemplate

  /**
   * Flags template tags as to whether they are full or not
   *
   * If processTemplate is told to flag its tags, the resultant template will contain
   * flags to cause action separate from the initial tag. For example, if you sent a tag
   * for an image, but wanted a different table structure depending upon its existance, you 
   * could set it different trigger configurations.
   *
   * <table>
   * <tr>
   * <!-- BEGIN image --><td>{IMAGE}</td><!-- END image -->
   * <td <!-- BEGIN noImage -->{IMAGE_FALSE}colspan="2"<!-- END noImage -->>Information</td>
   * </tr>
   * </table>
   *
   * In this example, if the time is missing, the 'colspan' is added to the td tag.
   * 
   * The flags are always NAME_OF_TAG + _TRUE or NAME_OF_TAG + _FALSE  
   * @author                      Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param  array templateArray  Array of template tags
   * @return array templateArray  Template array with filled and empty tags flagged
   */
  function tagFlag($templateArray){
    foreach ($templateArray as $tagName=>$value){
      if ($tagName == "PHPWS_IMAGES" || $tagName == "PHPWS_THEMES" || $tagName == "PHPWS_SOURCE") 
	continue;

      if (is_null($value) || $value == "")
	$templateArray[$tagName . "_FALSE"] = " ";
      else
	$templateArray[$tagName . "_TRUE"] = " ";
    }

    return $templateArray;
  }// END FUNC tagFlag()

}

?>