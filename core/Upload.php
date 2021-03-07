<?php

  function uploadfile($entry, $root = NULL, $idx = 0) {
	  $file_size_max = @get_cfg_var("upload_max_filesize");			// ���ϵͳ�ϴ����ֵ����2M
	  $file_size_max *= 1024*1024;									// convert from M to B
	  if ($root) $store_dir = $root;
	  else $store_dir = HALLROOT;									// �ϴ��ļ��Ĵ���λ��  //Ҫ���

	  $accept_overwrite = 1;//�Ƿ���������ͬ�ļ�

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
				// ����ļ���С
				$upload_file_size = $_FILES[$attach]['size'];
				if ($upload_file_size > $file_size_max) {
//					echo "�ļ�" . $upload_file_name . "��С��������.<br>";
					return false;
				}

				//�����ļ���ָ��Ŀ¼
				if (!move_uploaded_file($upload_file, $store_dir. "/" . $rname)) {	// put it in a temp dir for now. move to final dir only when we commit it to database
//					echo "�����ļ�" . $upload_file_name . "ʧ��.<br>";//"�����ļ�ʧ��";
					return false;
				}
				chmod($store_dir . "/" . $rname, 0644);
				return $store_dir . "/" . $rname;
				break;
			case 1:
//				Echo  "�ϴ����ļ�" . $upload_file_name . "������php.ini��upload_max_filesize ѡ�����Ƶ�ֵ.<br>";//"�ϴ����ļ������� php.ini �� upload_max_filesize ѡ�����Ƶ�ֵ."; 
				return false;
				break;
			case 2:
//				Echo  "�ϴ��ļ�" . $upload_file_name . "�Ĵ�С������ HTML ���� MAX_FILE_SIZE ѡ��ָ����ֵ<br>";//"�ϴ��ļ��Ĵ�С������ HTML ���� MAX_FILE_SIZE ѡ��ָ����ֵ��";  
				return false;
				break;
			case 3:
//				Echo  "�ļ�" . $upload_file_name . "ֻ�в��ֱ��ϴ�.<br>";//"�ļ�ֻ�в��ֱ��ϴ�";
				return false;
				break;
			case 4:
//				Echo  "�ļ�" . $upload_file_name . "û�б��ϴ�.<br>";//"û���ļ����ϴ�";
				return false;
				break;
			default:
//				Echo  "�ļ�û�б��ϴ�.<br>";//"û���ļ����ϴ�";
				return false;
				break;
		  }
	  }
	  else {
//		Echo  "$entry: û���ļ����ϴ�.<br>";//"û���ļ����ϴ�";
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