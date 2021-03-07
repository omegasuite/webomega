<?php

$keyword = "Keywords";
$keyword_content = "Many search engines use keywords to index your web site.
Enter your keywords in the text box separated by spaces or commas.";

$refresh = "Refresh Page";
$refresh_content = "By setting this value, a user's browser will refresh to the web site specified. The mininium value of seconds must be over 10 seconds.<br />Don't set this value unless necessary.";

$description = "Description";
$description_content = "Description is used by some search engines to index your page. Other engines also use this line to summarize your content. Keep it short and direct.";

$robots = "Robots";
$robots_content = "
Robot metatags direct search engines within your site (if they read the robot tag that is). Setting the metatag here will be the default value for EVERY PAGE served from your site. 
The exception to this rule would be when a module developer sent a different robot meta tag to the layout object. This would be useful in say a calendar module where the links
could become infinite.<br />
Here are the options:
<ul>
<li><b>all</b> - allows all of the files to be indexed</li>
<li><b>none</b> - does not allow any file to be indexed, do not follow any hyperlinks</li>
<li><b>index</b> - this page may be indexed</li>
<li><b>noindex</b> - this page may not be indexed</li>
<li><b>follow</b> - allows hyperlinks from this page to be followed</li>
<li><b>nofollow</b> - no hyperlinks from this page may be followed</li>
</ul>
";


$settings = "Layout Settings";
$settings_content = "Layout controls the arrangement of your site.

<b>Panel</b>
The floating panel at the top follows you until you close it.
<ul>
<li><b>Move Boxes:</b> Turning this option 'On' will allow you to move sections of the page around. Click the up, down, left, and right arrows to move each box.</li>
<li><b>Change Box Style:</b> Enabling this option lets you change each box to a different style. The available styles depend upon what box styles were included with your theme.</li>
<li><b>Set Default Theme:</b> Changing this picks the default theme that users will see.</li>
<li><b>Settings:</b> Clicking this brings you to the Settings page. (see below)</li>
<li><b>Close Panel:</b> Closes this panel when you are finished making site adjustments.</li>
</ul>

<b>Settings</b>
<ul>
<li><b>Change Page Title:</b> Enter the title you want to appear at the top of the user's browser.</li>
<li><b>Edit Metatags:</b> Lets you change the meta tags for your site. Meta tags communicate your site's properties to search engines.</li>
<li><b>Re-Initialize Default Theme:</b> If your default theme needs to be returned to its default settings, click this button.</li>
<li><b>Refresh Boxes:</b> Goes through the current themes and compares them to the boxes in the database. Boxes missing themes will be purged.</li>
<li><b>User can change theme:</b> If set to 'Yes', then users will be able to choose another theme to view the site under. This will NOT have an effect on your default theme.</li>
</ul>

";
?>