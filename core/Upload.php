<?php

  function uploadfile($entry, $root = NULL, $idx = 0) {
	  $file_size_max = @get_cfg_var("upload_max_filesize");			// 获得系统上传最大值，如2M
	  $file_size_max *= 1024*1024;									// convert from M to B
	  if ($root) $store_dir = $root;
	  else $store_dir = HALLROOT;									// 上传文件的储存位置  //要变的

	  $accept_overwrite = 1;//是否允许覆盖相同文件

	  if (is_array($_FILES[$entry]['tmp_name'])) {
		  $upload_file = $_FILES[$entry]['tmp_name'][$idx];
		  $upload_file_name = $_FILES[$entry]['name'][$idx];
	  }
	  else {
		  $upload_file = $_FILES[$entry]['tmp_name'];
		  $upload_file_name = $_FILES[$entry]['name'];
	  }

	  if ($upload_file) {
		  $path = split('[/\\]', $upload_file_name);
		  $ext = $path[sizeof($path) - 1];
		  $ext = explode(".", $ext);
//		  foreach ($path as $rname) {	};

		  $path = split('[/\\]', $upload_file);
		  $rname = $path[sizeof($path) - 1];

		  if (sizeof($ext) > 1) {
			  $ext = "." . $ext[sizeof($ext) - 1];
			  if (strstr($rname, ".")) $rname = preg_replace("/[.]([^.]*)$/", "", $rname);
		  }
		  else $ext = NULL;
//		  $path = split('[/\\]', substr($store_dir, strlen(PHPWS_SOURCE_DIR)));
		  $path = split('[/\\]', $store_dir);
		  $tp = PHPWS_SOURCE_DIR; $g = '';
		  foreach ($path as $tn) {
			  $tp .= $g . "$tn"; $g = "/";
			  if (!file_exists($tp)) {
				  mkdir($tp);
			  }
		  }

		  $n = 0; $suff = NULL;
		  while (file_exists($store_dir . "/" . $rname . $suff . $ext)) {
			  $n++;
			  $suff = "_$n";
		  }

		  $rname .= $suff . $ext;

//		  if (strlen($rname) > 30) $rname = substr($rname, -30);

		  if (is_array($_FILES[$entry]['error']))
			  $Error = $_FILES[$entry]['error'][$idx];
		  else $Error = $_FILES[$entry]['error'];
		  switch($Error){
			case 0:
				// 检查文件大小
				$upload_file_size = $_FILES[$attach]['size'];
				if ($upload_file_size > $file_size_max) {
//					echo "文件" . $upload_file_name . "大小超过上限.<br>";
					return false;
				}

				//复制文件到指定目录
				if (!move_uploaded_file($upload_file, $store_dir. "/" . $rname)) {	// put it in a temp dir for now. move to final dir only when we commit it to database
//					echo "复制文件" . $upload_file_name . "失败.<br>";//"复制文件失败";
					return false;
				}
				chmod($store_dir . "/" . $rname, 0644);
				return $store_dir . "/" . $rname;
				break;
			case 1:
//				Echo  "上传的文件" . $upload_file_name . "超过了php.ini中upload_max_filesize 选项限制的值.<br>";//"上传的文件超过了 php.ini 中 upload_max_filesize 选项限制的值."; 
				return false;
				break;
			case 2:
//				Echo  "上传文件" . $upload_file_name . "的大小超过了 HTML 表单中 MAX_FILE_SIZE 选项指定的值<br>";//"上传文件的大小超过了 HTML 表单中 MAX_FILE_SIZE 选项指定的值。";  
				return false;
				break;
			case 3:
//				Echo  "文件" . $upload_file_name . "只有部分被上传.<br>";//"文件只有部分被上传";
				return false;
				break;
			case 4:
//				Echo  "文件" . $upload_file_name . "没有被上传.<br>";//"没有文件被上传";
				return false;
				break;
			default:
//				Echo  "文件没有被上传.<br>";//"没有文件被上传";
				return false;
				break;
		  }
	  }
	  else {
//		Echo  "$entry: 没有文件被上传.<br>";//"没有文件被上传";
	  }
	  return false;
  }

  function loadImage($rname) {
	if (eregi("[.]gd2$", $rname))
		return imagecreatefromgd2($rname);
	elseif (eregi("[.]gd$", $rname))
		return imagecreatefromgd($rname);
	elseif (eregi("[.]gif$", $rname))
		return imagecreatefromgif($rname);
	elseif (eregi("[.]jpg$", $rname))
		return imagecreatefromjpeg($rname);
	elseif (eregi("[.]png$", $rname))
		return imagecreatefrompng($rname);
	elseif (eregi("[.]gif$", $rname))
		return imagecreatefromgif($rname);
	else return NULL;
  }

?>