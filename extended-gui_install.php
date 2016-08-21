#!/usr/local/bin/php-cgi -f
<?php
require_once("config.inc");

// check FreeBSD release for fetch options >= 9.3
$release = explode("-", exec("uname -r"));
if ($release[0] >= 9.3) $verify_hostname = "--no-verify-hostname";
else $verify_hostname = "";

$min_release = 10.3032853;
// create FreeBSD $current_release for min_release check
$product_version = explode(".", get_product_version());                 // p.version = 10.3.0.3, p.revision = 2853
$current_release = $product_version[0].".".$product_version[1].$product_version[2].$product_version[3].get_product_revision();
if ($current_release < floatval($min_release)) {                        // release not supported
    echo "\nThis version of Extended GUI needs NAS4Free release {$min_release} or higher, installation aborted!\n\n";
    exit;
}

$dirname = dirname(__FILE__);
if (!is_dir("{$dirname}/extended-gui/backup")) { mkdir("{$dirname}/extended-gui/backup", 0775, true); }
if (!is_dir("{$dirname}/extended-gui/log")) { mkdir("{$dirname}/extended-gui/log", 0775, true); }
$return_val = mwexec("fetch {$verify_hostname} -vo {$dirname}/extended-gui/extended-gui-install.php 'https://raw.github.com/crestAT/nas4free-extended-gui/master/extended-gui/extended-gui-install.php'", true);
if ($return_val == 0) { 
    chmod("{$dirname}/extended-gui/extended-gui-install.php", 0775);
    require_once("{$dirname}/extended-gui/extended-gui-install.php"); 
}
else { echo "\nInstallation file 'extended-gui-install.php' not found, installation aborted!\n"; }
?>
