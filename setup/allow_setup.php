<?php

// You should change this to FALSE unless you are installing or updating your site

$_SESSION['allow_setup'] = TRUE;

if (!$_SESSION['allow_setup']){
  header("location:../index.php");
  exit();
}

?>