#!/usr/local/bin/php-cgi -f
<?php
require_once("config.inc");
require_once("functions.inc");
require_once("install.inc");
require_once("util.inc");
$extension_dir = "/usr/local/www/ext/extended-gui";
//exec("logger extended-gui: config has been changed, Extended GUI will be restarted ...");
$rootfolder = dirname(__FILE__)."/";
$config_file = "{$rootfolder}extended-gui.conf";
require_once("{$rootfolder}json.inc");
if (($configuration = load_config($config_file)) === false) {
    exec("logger extended-gui: configuration file {$config_file} not found, startup aborted!");
    exit;
}
require("{$configuration['rootfolder']}extended-gui-stop.php");
require("{$configuration['rootfolder']}extended-gui-start.php");
?>
