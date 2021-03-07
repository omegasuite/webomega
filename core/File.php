<?php

/* Set mod that directories and files will get */
/* Users is a hosted environment might want to set this to 0757 */
define("PHPWS_DIR_PERMISSIONS", 0755);
/* Users is a hosted environment might want to set this to 0746 */
define("PHPWS_FILE_PERMISSIONS", 0644);

/**
 * The PHPWS_File class supplies functions to allow easy access when reading, writing,
 * or copying files.  More functions will be added as they are deemed neccessary.
 *
 * @version $Id: File.php,v 1.58 2005/08/17 12:50:44 matt Exp $
 * @author  Matt McNaney <matt@NOSPAM.tux.appstate.edu>
 * @author  Adam Morton <adam@NOSPAM.tux.appstate.edu>
 * @author  Steven Levin <steven@NOSPAM.tux.appstate.edu>
 * @package Core
 */
class PHPWS_File {

  /**
   * Returns the contents of a directory in an array
   * 
   * If directoriesOnly is TRUE, then only directories will be listed.
   * If filesOnly is TRUE, then only files will be listed.
   * Function returns directory names and file names by default.
   * Special directories '.', '..', and 'CVS' are not returned.
   *
   * @author                            Matt McNaney <matt@NOSPAM.tux.appstate.edu>
   * @modified                          Adam Morton <adam@NOSPAM.tux.appstate.edu>
   * @param    string   path            The path to directory to be read
   * @param    boolean  directoriesOnly If TRUE, return directory names only
   * @param    boolean  filesOnly       If TRUE, return file names only
   * @param    boolean  recursive       If TRUE, readDirectory will recurse through the given directory and all directories "beneath" it.
   * @param    array    extensions      An array containing file extensions of files you wish to have returned.
   * @param    boolean  appendPath      Whether or not to append the full path to all entries returned
   * @return   array    directory       An array containing the names of directories and/or files in the specified directory.
   * @access   public
   */
  function readDirectory($path, $directoriesOnly=FALSE, $filesOnly=FALSE, $recursive=FALSE, $extensions=array(), $appendPath=FALSE){
    if($directoriesOnly && $filesOnly) {
      $directoriesOnly = FALSE;
      $filesOnly = FALSE;
    }

    if (!is_dir($path))
      return FALSE;

    $dir = dir($path);
    while ($file = $dir->read()){
      $fullpath = $path . "/" . $file;
      if ($directoriesOnly && !$filesOnly && @is_dir($fullpath) && $file != "." && $file != ".." && $file != "CVS") {
	if($appendPath)
	  $directory[] = $fullpath;
	else
	  $directory[] = $file;
      } else if(!$directoriesOnly && $filesOnly && !is_dir($fullpath) && $file != "CVS" && $file != "." && $file != "..") {
	//	if (is_dir($path.$file))
	if(is_array($extensions) && count($extensions) > 0) {
	  $extTest = explode(".", $file);
	  if(in_array($extTest[1], $extensions)) {
	    if($appendPath)
	      $directory[] = $fullpath;
	    else
	      $directory[] = $file;
	  }
	} else if($appendPath)
	  $directory[] = $fullpath;
	else
	  $directory[] = $file;
      } else if(!$directoriesOnly && !$filesOnly && $file != "." && $file != ".." && $file != "CVS") {
	if(!is_dir($path . $file) && is_array($extensions) && count($extensions) > 0) {
	  $extTest = explode(".", $file);
	  if(in_array($extTest[1], $extensions)) {
	    if($appendPath)
	      $directory[] = $fullpath;
	    else
	      $directory[] = $file;
	  } else if($appendPath)
	    $directory[] = $fullpath;
	  else
	    $directory[] = $file;
	} else {
	    if($appendPath)
	      $directory[] = $fullpath;
	    else
	      $directory[] = $file;
	}
      }

      if($recursive && is_dir($fullpath) && $file != "CVS" && $file != "." && $file != "..")
	$directory = array_merge($directory, PHPWS_File::readDirectory($fullpath . "/", $directoriesOnly, $filesOnly, $recursive, $extensions, $appendPath));
    }
    $dir->close();

    if (isset($directory))
      return $directory;
    else
      return NULL;
  }// END FUNC readDirectory()


    /**
     * Recursively copies files from one directory ($fromPath) to another ($toPath)
     *
     * @author   junk@NOSPAM.steti.com <junk@NOSPAM.steti.com>
     * @modified Matt McNaney <matt@NOSPAM.tux.appstate.edu>
     * @modified Adam Morton <adam@NOSPAM.tux.appstate.edu>
     * @param    string  $fromPath The path where the files to be copied reside
     * @param    string  $toPath   The path to copy the files to
     * @return   boolean TRUE on success and FALSE on failure
     * @access   public
     */
    function recursiveFileCopy ($fromPath, $toPath) {
        $start_path =  getcwd();
	if (PHPWS_File::makeDir($toPath)){
	    if (is_dir($fromPath)) {
		chdir($fromPath);
		$handle = opendir('.');
		while (($file = readdir($handle)) !== FALSE) {
		    if (($file != ".") && ($file != "..") && ($file != "CVS")) {
			if (is_dir($file)) {
			    PHPWS_File::recursiveFileCopy ($fromPath . $file . "/", $toPath . $file . "/");
			    chdir($fromPath);
			}
			if (is_file($file)) {
			    @copy($fromPath . $file, $toPath . $file);
			    PHPWS_File::setFilePermissions($toPath . $file);
			}
		    }
		}
		closedir($handle);
		chdir($start_path);
		return TRUE;
	    } else {
	        chdir($start_path);
		return FALSE;
	    }
	}
    }// END FUNC recursiveFileCopy()


  /**
   * Writes text to a file specified with $fileName
   *
   * If allow_overwrite is TRUE, all files will be overwritten.
   *
   * @author   Matt McNaney <matt@NOSPAM_tux.appstate.edu>
   * @modified Adam Morton <adam@NOSPAM.tux.appstate.edu>
   * @param    string  $fileName       Full path and filename of new file
   * @param    string  $text           Text to be written to the file
   * @param    boolean $allowOverwrite If TRUE and a file exists at the path given, writeFile() overwrites the file
   * @param    boolean $errorReport    Echoes any errors encountered if TRUE
   * @return   boolean Returns TRUE on success and FALSE on failure
   * @access   public
   */
  function writeFile($fileName, $text, $allowOverwrite=FALSE, $errorReport=FALSE, $permissions=NULL){
    if (!$allowOverwrite){
      if (@is_writable($fileName)){
	if($errorReport)
	  echo "<b>ERROR:</b> $fileName exist. Cannot overwrite.<br /><br />";
	return FALSE;
      }
    }

    if($fp = @fopen ($fileName, "wb")){
      fwrite($fp, $text);
      PHPWS_File::setFilePermissions($fileName, $permissions);
      fclose($fp);
      return TRUE;
    } else {
      if ($errorReport)
	echo "<b>ERROR:</b> unable to open file at $fileName<br /><br />";
      return FALSE;
    }
  }// END FUNC writeFile()

    /**
     * Makes a new directory given a path name
     *
     * @author   Darren Greene <dg49379@NOSPAM.tux.appstate.edu>
     * @param    string  $pathname     name of the path to create directory
     * @param    string  $permissions  octal Unix Permissions
     * @return   boolean $dirCreated   true if directory was created
     * @access   public
     */
    function makeDir($pathname, $permissions=NULL) {
	if(is_dir($pathname)) {
	    return true;
	}

	$dirCreated = false;
	$oldMask = umask(0);
	
	if ($permissions != NULL)
	    $dirCreated = @mkdir($pathname, $permissions);
	else
	    $dirCreated = @mkdir($pathname, PHPWS_DIR_PERMISSIONS);
	
	umask($oldMask);
	
	return $dirCreated;
    }

  /**
   * Reads a text file and returns the data
   *
   * If error report is triggered, any file errors will be echoed
   *
   * @author   Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @modified Adam Morton <adam@NOSPAM.tux.appstate.edu>
   * @param    string  $filename     Name of the file and its directory.
   * @param    boolean $error_report Echo file errors if TRUE.
   * @return   string  $data         The text of the file.
   * @access   public
   */
  function readFile($filename, $error_report=FALSE){
    if ($error_report)
      $fd = fopen ($filename, "rb");
    else
      $fd = @fopen($filename, "rb");

    if ($fd && filesize ($filename)>0){
      $data = trim(fread ($fd, filesize ($filename)));
      fclose ($fd);
      return $data;
    } else {
      return NULL;
    }
  }// END FUNC readFile()

    /**
     * Sets the permissions for a file
     *
     * @author   Darren Greene <dg49379@NOSPAM.tux.appstate.edu>
     * @param    string  $filename     Name of the file 
     * @param    string  $permissions  Octal Unix Permission
     * @return   boolean               True if chmod was a sucess
     * @access   public
     */
    function setFilePermissions($filename, $permissions=NULL) {
	if ($permissions != NULL)
	    return @chmod($filename, $permissions);
	else
	    return @chmod($filename, PHPWS_FILE_PERMISSIONS);
    }

    /**
     * Copies a file from one directory to another
     *
     * This function comes from php.net by jacob@keystreams.com.
     *
     * Example Usage:
     * $copy = fileCopy("/path/to/original.file", "/path/to/", "destination.file", 1, 1);
     *
     * @author   jacob@NOSPAM.keystreams.com <jacob@NOSPAM.keystreams.com>
     * @modified Adam Morton <adam@NOSPAM.tux.appstate.edu>
     * @param    string  $file_origin           Path to file to be copied
     * @param    string  $destination_directory Directory to copy to
     * @param    string  $file_destination      Name to be given to copied file
     * @param    boolean $overwrite             If TRUE overwrite any file in the destination directory
     * @param    boolean $fatal                 If TRUE echo an error if the file does not exist.
     * @return   boolean TRUE on success, FALSE on failure
     * @access   public
     */
    function fileCopy($file_origin, $destination_directory, $file_destination, $overwrite, $fatal, $permissions=NULL) {
	if ($fatal) {
	    $error_prefix = 'FATAL: File copy of \'' . $file_origin . '\' to \''. $destination_directory . $file_destination . '\' failed.';
	    $fp = @fopen($file_origin, "rb");
	    if (!$fp) {
		echo $error_prefix . ' Originating file cannot be read or does not exist.';
		exit();
	    }
      
	    $dir_check = @is_writeable($destination_directory);
	    if (!$dir_check) {
		echo $error_prefix . ' Destination directory is not writeable or does not exist.';
		exit();
	    }
	    
	    $dest_file_exists = file_exists($destination_directory . $file_destination);
	    
	    if ($dest_file_exists) { 
		if ($overwrite) {
		    $fp = @is_writeable($destination_directory . $file_destination);
		    if (!$fp) {
			echo  $error_prefix . ' Destination file is not writeable [OVERWRITE].';
			exit();
		    }
		    if($copy_file = @copy($file_origin, $destination_directory . $file_destination)) {
			PHPWS_File::setFilePermissions(
			    $destination_directory . $file_destination,
			    $permissions);
			return TRUE;
		    } else
			return FALSE;
		}                                       
	    } else {
		if($copy_file = @copy($file_origin, $destination_directory . $file_destination)) {
		    PHPWS_File::setFilePermissions(
			$destination_directory . $file_destination,
			$permissions);
		    return TRUE;
		} else
		    return FALSE;
	    }
	} else {
	    if($copy_file = @copy($file_origin, $destination_directory . $file_destination)) {
		PHPWS_File::setFilePermissions(
		    $destination_directory . $file_destination,
		    $permissions);
		return TRUE;
	    }else
		return FALSE;
	}
    }// END FUNC fileCopy()
    
  /**
   * Creates a thumbnail of a jpeg, gif or png image.  (Gif images are converted to
   * jpeg thumbnails due to licensing issues.)  The thumbnail file is created as
   * a separate "_tn" file or, if desired, as a replacement for the original.
   *
   * @author   Jeremy Agee <jagee@NOSPAM.tux.appstate.edu>
   * @modified Adam Morton <adam@NOSPAM.tux.appstate.edu>
   * @modified Steven Levin <steven@NOSPAM.tux.appstate.edu>
   * @modified George Brackett <gbrackett@NOSPAM.luceatlux.com>
   * @modified Matt McNaney <matt at tux dot appstate dot edu>
   * @param    string  $fileName          The file name of the image you want thumbnailed.
   * @param    string  $directory         Path to the file you want thumbnailed
   * @param    string  $tndirectory       The path to where the new thumbnail file is stored
   * @param    integer $maxHeight         Set width of the thumbnail if you do not want to use the default 
   * @param    integer $maxWidth          Set height of the thumbnail if you do not want to use the default
   * @param	   boolean $replaceFile		  Set TRUE if thumbnail should replace original file
   * @return   array   0=>thumbnailFileName, 1=>thumbnailWidth, 2=>thumbnailHeight 
   * @access   public
   */
  function makeThumbnail($fileName, $directory, $tndirectory, $maxWidth=125, $maxHeight=125, $replaceFile=FALSE) {

    $image = $directory . $fileName;
    $imageInfo = getimagesize($image);

    // Check to make sure gd will support the specified type
    $supported = FALSE;
    // Index 2 is a flag indicating the type of the image: 1 = GIF, 2 = JPG, 3 = PNG, 
    // 4 = SWF, 5 = PSD, 6 = BMP, 7 = TIFF(intel byte order), 8 = TIFF(motorola byte order), 
    // 9 = JPC, 10 = JP2, 11 = JPX, 12 = JB2, 13 = SWC, 14 = IFF, 15 = WBMP, 16 = XBM.
    switch($imageInfo[2]) {
        case 1:		// we're converting GIF to JPG
        case 2:
            if(imagetypes() & IMG_JPG)
                $supported = TRUE;
            break;
        case 3:
            if(imagetypes() & IMG_PNG)
                $supported = TRUE;
            break;
    }
    
    if (!$supported) {
        $error = new PHPWS_Error("core", "PHPWS_File::saveImage()", "Submitted image type (" . $imageInfo["mime"] . ") does not have support built for the GD libraries.  You will need to recompile PHP and/or GD.");
        return $error;
    }

    $currentWidth = &$imageInfo[0];
    $currentHeight = &$imageInfo[1];

    if(($currentWidth < $maxWidth) && ($currentHeight < $maxHeight)) {
	return array($fileName, $currentWidth, $currentHeight);
    } else {
      $widthScale  = $maxWidth / $currentWidth;
      $heightScale = $maxHeight / $currentHeight;

      $adjusted_h_to_w = floor($currentHeight * $widthScale);
      $adjusted_w_to_w = floor($currentWidth * $widthScale);

      if ( ($adjusted_h_to_w <= $maxHeight) &&
	   ($adjusted_w_to_w <= $maxWidth)) {
	$finalScale = $widthScale;
      } else {
	$finalScale = $heightScale;
      }
    }

    $thumbnailWidth = round($finalScale * $currentWidth);
    $thumbnailHeight = round($finalScale * $currentHeight);
    $thumbnailImage = NULL;
      
    // create image space in memory
    if(PHPWS_File::chkgd2()) {
        $thumbnailImage = ImageCreateTrueColor($thumbnailWidth, $thumbnailHeight);
        imageAlphaBlending($thumbnailImage, false);
        imageSaveAlpha($thumbnailImage, true);
    } else {
        $thumbnailImage = ImageCreate($thumbnailWidth, $thumbnailHeight);
    }
    // now pull in image data
    switch($imageInfo[2]) {
	case 1:
	    $fullImage = ImageCreateFromGIF($image);
	    break;
	case 2:
	    $fullImage = ImageCreateFromJPEG($image);
	    break;
	case 3:
	    $fullImage = ImageCreateFromPNG($image);
    }
    
    // now create the thumbnail image in memory
    if(PHPWS_File::chkgd2()) {
	ImageCopyResampled($thumbnailImage, $fullImage, 0, 0, 0, 0, $thumbnailWidth, $thumbnailHeight, ImageSX($fullImage), ImageSY($fullImage));
    } else {
	ImageCopyResized($thumbnailImage, $fullImage, 0, 0, 0, 0, $thumbnailWidth, $thumbnailHeight, ImageSX($fullImage), ImageSY($fullImage));
    }
    
    ImageDestroy($fullImage);
    $thumbnailFileName = explode('.', $fileName);
    
    if($replaceFile) {
    	unlink($image);
    }
    
    switch($imageInfo[2]) {
	case 1:		// convert gif to jpg
	case 2:
	    $thumbnailFileName = $thumbnailFileName[0] . ($replaceFile ? ".jpg" : "_tn.jpg");
	    imagejpeg($thumbnailImage, $tndirectory . $thumbnailFileName);
	    break;
	case 3:
	    $thumbnailFileName = $thumbnailFileName[0] . ($replaceFile ? ".png" : "_tn.png");
	    imagepng($thumbnailImage, $tndirectory . $thumbnailFileName);
	    break;
    }

    PHPWS_File::setFilePermissions($tndirectory . $thumbnailFileName);
    return array($thumbnailFileName, $thumbnailWidth, $thumbnailHeight);
    
  } // END FUNC makeThumbnail()

  function rmdir($dir) {
    if (!preg_match("/\/$/", $dir))
      $dir .= "/";

    if(is_dir($dir)) {
      $handle = opendir($dir);
      while($file = readdir($handle)) {
	if($file == "." || $file == "..") {
	  continue;
	} elseif(is_dir($dir . $file)) {
	  PHPWS_File::rmdir($dir . $file . "/");
	} elseif(is_file($dir . $file)) {
	  unlink($dir . "/" . $file);
	}
      }
      closedir($handle);
      $tempDir = explode("/", $dir);
      array_pop($tempDir);
      $sourceDir = implode("/", $tempDir);

      if (is_writable($sourceDir))
	return rmdir($dir);
      else
	return FALSE;
    } else {
      return FALSE;
    }
  }// END FUNC rmdir()

    function chkgd2(){
        if(function_exists("gd_info")) {
            $gdver = gd_info();
            if(strstr($gdver["GD Version"], "1.") != FALSE) {
                return FALSE;
            } else {
                return TRUE;
            }
        } else {
            ob_start();
            phpinfo(8);
            $phpinfo=ob_get_contents();
            ob_end_clean();
            $phpinfo=strip_tags($phpinfo);
            $phpinfo=stristr($phpinfo,"gd version");
            $phpinfo=stristr($phpinfo,"version");
            $end=strpos($phpinfo," ");
            $phpinfo=substr($phpinfo,0,$end);
            $phpinfo=substr($phpinfo,7);
            if(version_compare("2.0", "$phpinfo")==1)
                return FALSE;
            else
                return TRUE;
        }
    }// END FUNC chkgd2()


}// END CLASS PHPWS_File

?>