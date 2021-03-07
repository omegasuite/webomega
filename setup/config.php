<?php
if (isset($_GET["print_config"])){
  echo nl2br(htmlspecialchars(config_maker($_GET)));
  exit();
}elseif(isset($_GET["save_config"])){
  echo config_maker($_GET);
  exit();
}

function config_maker($data){
return "<?php
\$dbversion = \"".$data["dbversion"]."\";\n
\$dbhost = \"".$data["hostname"]."\";\n
\$dbuser = \"".$data["username"]."\";\n
\$dbpass = \"".$data["password"]."\";\n
\$dbname = \"".$data["database"]."\";\n
\$table_prefix = \"".$data["tbl_prefix"]."\";\n
\$source_http = \"".$data["http_src"]."\";\n
\$source_dir = \"".$data["filepath"]."\";\n
\$hub_hash = \"".$data["hubhash"]."\";\n
\$install_pw = \"".$data["install_pass"]."\";\n
?>";
}

?>