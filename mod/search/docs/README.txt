-----------------------------------------------------------------------------------
phpWebSite Menu Manager README
-----------------------------------------------------------------------------------
Author: Steven Levin <steven@NOSPAM.tux.appstate.com>
Version: 2.1.0 03/10/2004

-------------------------------
REQUIREMENTS:
-------------------------------
phpWebSite v0.9.3-3

-------------------------------
DELEVOPER API
-------------------------------
Put the file search.php in your modules conf/ directory.
This file should contain something like this:

<?php
$module          = "announce";
$search_class    = "PHPWS_AnnouncementManager";
$search_function = "search";
$search_cols     = "subject, summary, body";
$view_string     = "&amp;ANN_user_op=view&amp;ANN_id=";
$show_block      = 1;
$block_title     = "Announcements";
$class_file      = "AnnouncementManager.php";
?>

Then the function search needs to be implemented in the class
PHPWS_AnnouncementManager

How do I register with search?

Handled by boost if your conf/search.php file is in place.

How do I unregister with search?

Handled by boost if your conf/search.php file is in place.

How can I add a site search to a template?

Site Search
<form method="post" action="index.php">
<input type="hidden" name="module" value="search" />
<input type="hidden" name="search_op" value="search" />
<input type="hidden" name="mod" value="all" />
<input type="text" name="query" />
<input type="submit" name="search" value="Search" />
</form>

How can I add an individual search to a template?

Search _module_
<form method="post" action="index.php">
<input type="hidden" name="module" value="search" />
<input type="hidden" name="search_op" value="search" />
<input type="hidden" name="mod" value="_module_" />
<input type="text" name="query" />
<input type="submit" name="search" value="Search" />
</form>

How can I add a search chooser?
Site Search
<form method="post" action="index.php">
<input type="hidden" name="module" value="search" />
<input type="hidden" name="search_op" value="search" />
<select name="mod">
<option value="announce">Announcements</option>
<option value="comments">Comments</option>
<option value="calendar">Events</option>
<option value="pagemaster">WebPages</option>
<option value="faq">Faqs</option>
<option value="linkman">Web Links</option>
<option value="documents">Documents</option>
</select>
<input type="text" name="query" />
<input type="submit" name="search" value="Search" />
</form>
