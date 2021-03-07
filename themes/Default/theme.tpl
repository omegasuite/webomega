<!DOCTYPE html>
<html lang="en">
<head>
<meta content="text/html; charset=UTF-8" http-equiv="Content-Type">
<meta charset="utf-8">
<meta name="author" content="Mosaddek"/>
<meta name="description" content=""/>
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<link rel="shortcut icon" href="javascript:;" type="image/png">

<title>{TITLE}</title>
{METATAGS}
{JAVASCRIPT}
{STYLE}
<script src="{THEME_DIRECTORY}js/jquery-1.11.1.min.js"></script>

<link href="js/calendarcontrol2.css" rel="stylesheet" type="text/css">
<script src="js/CalendarControl2.js" language="javascript"></script>

<link href="{THEME_DIRECTORY}js/jquery-ui/jquery-ui-1.10.1.custom.min.css" rel="stylesheet" />

    <!--common style-->
    <link href="{THEME_DIRECTORY}css/style.css" rel="stylesheet">
    <link href="{THEME_DIRECTORY}css/style-responsive.css" rel="stylesheet">

    <link href="{THEME_DIRECTORY}css/iconfont.css" rel="stylesheet">
[EXTRAHEAD]
</head>

<body class="sticky-header">
<style>
    .input-group-addon {
        background: #fff;
        padding: 6px 24px;
    }

    .add-on {
        margin-top: -6px;
    }

    th {
        line-height: 40px !important;
        height: 48px;
        font-size: 16px;
        color: #1f1f1f;
	align: center;
    }

    td {
        line-height: 40px !important;
        height: 48px;
        font-size: 16px;
        color: #6b6b6b;
    }

    thead {
        background: #efefe4;
    }
</style>
    <div class="body-content" >
		{HEAD_SECTION}
        <div id="main" width=100% height=100%>
		{BODY} {HOMEBODY} 
<center>
	{EXTRABODY}
{BODYEND}
	{BUTTS}
</center>
        </div>
        <div class="clr"></div>
        <!--body wrapper end-->
    </div>
    <!-- body content end-->


<script>
    $(document).ready(function () {
        $(".body-content").height($(window).height() - 60);
    });
</script>

</body>
</html>
