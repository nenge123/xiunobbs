<?php

/**
 * @author N <m@nenge.net>
 * 起始页
 * phpinfo信息
 */
!defined('APP_PATH') and exit('Access Denied.');
unset($_SERVER['conf']);
ob_start();
phpinfo();
$data = ob_get_clean();
include _include(ADMIN_PATH . "view/htm/new-header.htm");
if (preg_match('/\<body[^>]*\>(.+?)\<\/body\>/is', $data, $matches)):
	echo $matches[1];
	echo '<style type="text/css">
.lyear-layout-content .container-fluid {background-color: #fff; color: #222; font-family: sans-serif;}
.lyear-layout-content .container-fluid pre {margin: 0; font-family: monospace;}
.lyear-layout-content .container-fluid a:link {color: #009; text-decoration: none; background-color: #fff;}
.lyear-layout-content .container-fluid a:hover {text-decoration: underline;}
.lyear-layout-content .container-fluid table {border-collapse: collapse; border: 0; width: 100%; box-shadow: 1px 2px 3px #ccc;}
.lyear-layout-content .container-fluid .center {text-align: center;}
.lyear-layout-content .container-fluid .center table {margin: 1em auto; text-align: left;}
.lyear-layout-content .container-fluid .center th {text-align: center !important;}
.lyear-layout-content .container-fluid td,
.lyear-layout-content .container-fluid th {border: 1px solid #666; font-size: 75%; vertical-align: baseline; padding: 4px 5px;}
.lyear-layout-content .container-fluid th {position: sticky; top: 0; background: inherit;}
.lyear-layout-content .container-fluid h1 {font-size: 150%;}
.lyear-layout-content .container-fluid h2 {font-size: 125%;}
.lyear-layout-content .container-fluid .p {text-align: left;}
.lyear-layout-content .container-fluid .e {background-color: #ccf; width: 300px; font-weight: bold;}
.lyear-layout-content .container-fluid .h {background-color: #99c; font-weight: bold;}
.lyear-layout-content .container-fluid .v {background-color: #ddd; max-width: 300px; overflow-x: auto; word-wrap: break-word;}
.lyear-layout-content .container-fluid .v i {color: #999;}
.lyear-layout-content .container-fluid hr {width: 934px; background-color: #ccc; border: 0; height: 1px;}
.lyear-layout-content .container-fluid pre {background-color: transparent;color: currentColor;}
</style>';
endif;
include _include(ADMIN_PATH . "view/htm/new-footer.htm");
exit;
