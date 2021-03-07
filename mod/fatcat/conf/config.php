<?php

define("FATCAT_MAX_IMAGE_WIDTH", 800);
define("FATCAT_MAX_IMAGE_HEIGHT", 600);
define("FATCAT_MAX_ICON_WIDTH", 150);
define("FATCAT_MAX_ICON_HEIGHT", 150);

// The cutoff title size before '...' are added to the end
define("FATCAT_LINK_CUTOFF", 15);

// The buffer size between the max length and the next space in a string
define("FATCAT_LINK_BUFFER", 3);

// This is used with the whats related box to specify the module links that should open in a new window.
//  Seperate entries with commas.
define("WHATSRELATED_MODULES_NW", "linkman");

// Alternate What's Related Titles
// If you don't like the title of a module's What's Related
// items, just add your preferred title into the array below.
// You text will be translated automatically.
// Examples (uncomment the below to see it 'in action'):
//
//$GLOBALS['whatsRelated_alts']['announce']   = "Announce Alternate Text";
//$GLOBALS['whatsRelated_alts']['pagemaster'] = "Pages / Articles";


?>