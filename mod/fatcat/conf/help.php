<?php

$fatcatAdmin = "FatCat Administration Menu";
$fatcatAdmin_content = "
<ul>
<li>When creating a new category, it will be placed under the category chosen from the drop down box.</li>
<li>If you want to create a major category (one at the top of the list) then pick &lt;Top Level&gt; from the drop down box.</li>
</ul>
";

$fatcatNoImage = "Where's the Image Box?";
$fatcatNoImage_content = "
You will need to create a image directory for FatCat.<br />
<br />
Under the phpWebSite root directory, go to your <b>images</b> directory  and create
a directory name <b>fatcat</b>. Now make a directory under that one named <b>images</b>.<br />
Make <b>sure</b> it is writable by the web server.
";

$catFormImage = "Uploading Images";
$catFormImage_content = "
Images appear on the category description page. If you do not use an image, it won't appear on that page.
<ul>
<li>If you wish to change to a different image, pick one of the pictures from the drop down box.</li>
<li>An upload image will supercede the choice from the selection box.</li>
<li>If you wish to not use an image for your category, choose \"None\" from the drop down box.</li>
<li>To remove an image from the server completely, click on the \"Remove Image\" button.</li>
</ul>";

$fatcatNoIcon = "Where's the Icon Box?";
$fatcatNoIcon_content = "
You will need to create a icon directory for FatCat.<br />
<br />
Under the phpWebSite root directory, go to your <b>images</b> directory  and create
a directory name <b>fatcat</b>. Now make a directory under that one named <b>icons</b>.<br />
Make <b>sure</b> it is writable by the web server.
";

$catFormIcon = "Uploading Icons";
$catFormIcon_content = "
Icons are called by other modules. The icon is a small image representive of your category.
When a module asks for it, it will link to the category's description page. If you do not
use an icon, the name of the Category will be sent instead. Icons may not be larger than
50 by 50 pixels.
<ul>
<li>If you wish to change to a different icon, pick one of the pictures from the drop down box.</li>
<li>An upload icon will supercede the choice from the selection box.</li>
<li>If you wish to not use an icon for your category, choose \"None\" from the drop down box.</li>
<li>To remove an icon from the server completely, click on the \"Remove Icon\" button.</li>
<li>Click on \"Create Icon from Image\" to create an icon from your image submission</li>
</ul>";

$fatcatDefIcon = "Default Icon";
$fatcatDefIcon_content = "You may choose an icon to appear for categories that were not assigned one.
It would be best to make it fairly generic.";

$relatedLimit = "What's Related Limit";
$relatedLimit_content = "When elements in a module share a category with other elements, often a <b>What's Related</b> box will show up these other items. Choose how many items you want to appear from each category.

Note: Sticky items will appear no matter what limit you assign.";

?>