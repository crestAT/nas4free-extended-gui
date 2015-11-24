#!/usr/local/bin/php-cgi -f
<?php
require_once("config.inc");
require_once("functions.inc");
require_once("install.inc");
require_once("util.inc");
$extension_dir = "/usr/local/www/ext/extended-gui";
//exec("logger extended-gui: config has been changed, Extended GUI will be restarted ...");
require("{$config['extended-gui']['rootfolder']}extended-gui-stop.php");
require("{$config['extended-gui']['rootfolder']}extended-gui-start.php");
?>
