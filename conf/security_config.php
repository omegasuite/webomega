<?php

// Do not edit this file if you do not know what you are doing

// If this is true, all files uploaded to the system will
// be parsed for the embedded text below.
$parse_all_files = FALSE;

// The entries in the list below will be verified against
// uploaded files if above is true. Make sure to make preg
// complaint (ie watch your '(', '|', '$', etc.)
$embedded_text = array('dl(', 'exec\(', 'passthru\(', 'proc_open\(',
		       'unlink\(', 'chown\(', 'chmod\(', 'chgrp',
			'proc_close', 'shell_exec', 'system\(', 'rmdir',
			'\$_GET', '\$_POST', '\$_REQUEST', 'fopen'
		       );


// Add any script or executible files you want
// to prevent from uploading
$forbidden_extensions = array('pl',
			      'php',
			      'py',
			      'php3',
			      'exe',
			      'bat'
			      );

?>