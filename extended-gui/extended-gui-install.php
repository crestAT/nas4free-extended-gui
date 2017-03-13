<?php
/* 
    extended-gui-install.php
    
    Copyright (c) 2014 - 2017 Andreas Schmidhuber <info@a3s.at>
    All rights reserved.

	Portions of NAS4Free (http://www.nas4free.org).
	Copyright (c) 2012-2017 The NAS4Free Project <info@nas4free.org>.
	All rights reserved.

    Redistribution and use in source and binary forms, with or without
    modification, are permitted provided that the following conditions are met:

    1. Redistributions of source code must retain the above copyright notice, this
       list of conditions and the following disclaimer.
    2. Redistributions in binary form must reproduce the above copyright notice,
       this list of conditions and the following disclaimer in the documentation
       and/or other materials provided with the distribution.

    THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
    ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
    WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
    DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
    ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
    (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
    LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
    ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
    (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
    SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

    The views and conclusions contained in the software and documentation are those
    of the authors and should not be interpreted as representing official policies,
    either expressed or implied, of the FreeBSD Project.
*/
$version = "v0.6.2";              // extension version
$appname = "Extended-GUI";
$min_release = 10.3032853;  // minimal OS release

require_once("config.inc");

$install_dir = dirname(__FILE__)."/";                           // get directory where the installer script resides
if (!is_dir("{$install_dir}backup")) { mkdir("{$install_dir}backup", 0775, true); }
if (!is_dir("{$install_dir}log")) { mkdir("{$install_dir}log", 0775, true); }
$config_name = strtolower($appname);
$version_striped = str_replace(".", "", $version);

$arch = $g['arch'];
$platform = $g['platform'];
// no check necessary since the extension is for all archictectures/platforms
//if ($arch != "i386" && $arch != "amd64" && $arch != "x86" && $arch != "x64" && $arch != "rpi" && $arch != "rpi2" && $arch != "rpi3" && $arch != "bpi") { echo "\f{$arch} is an unsupported architecture!\n"; exit(1);  }
//if ($platform != "embedded" && $platform != "full" && $platform != "livecd" && $platform != "liveusb") { echo "\funsupported platform!\n";  exit(1); }

// install extension
global $input_errors;
global $savemsg;

// check FreeBSD release for fetch options >= 9.3
$release = explode("-", exec("uname -r"));
if ($release[0] >= 9.3) $verify_hostname = "--no-verify-hostname";
else $verify_hostname = "";

// create FreeBSD $current_release for min_release check
$product_version = explode(".", get_product_version());                 // p.version = 10.3.0.3, p.revision = 2853
$current_release = $product_version[0].".".$product_version[1].$product_version[2].$product_version[3].get_product_revision();
if ($current_release < floatval($min_release)) {                        // release not supported
    $input_errors[] = sprintf(gettext("This version of %s needs NAS4Free release %s or higher, installation aborted!"), $appname, $min_release);
    return;
}

// fetch release archive
$return_val = mwexec("fetch {$verify_hostname} -vo {$install_dir}master.zip 'https://github.com/crestAT/nas4free-extended-gui/releases/download/{$version}/extended-gui-{$version_striped}.zip'", false);
if ($return_val == 0) {
    $return_val = mwexec("tar -xf {$install_dir}master.zip -C {$install_dir} --exclude='.git*' --strip-components 2", true);
    if ($return_val == 0) {
        exec("rm {$install_dir}master.zip");
        exec("chmod -R 775 {$install_dir}");
        require_once("{$install_dir}ext/extension-lib.inc");
        $config_file = "{$install_dir}ext/{$config_name}.conf";
        if (is_file("{$install_dir}version.txt")) { $file_version = exec("cat {$install_dir}version.txt"); }
        else { $file_version = "n/a"; }
    }
    else { 
        $input_errors[] = sprintf(gettext("Archive file %s not found, installation aborted!"), "master.zip corrupt /"); 
        return;
    }
}
else { 
    $input_errors[] = sprintf(gettext("Archive file %s not found, installation aborted!"), "master.zip"); 
    return;
}

// install / update application on NAS4Free
if (($configuration = ext_load_config($config_file)) === false) {
    $configuration = array();             // new installation or first time with json config
    $new_installation = true;
}
else $new_installation = false;

// check for $config['extended-gui'] entry in config.xml, convert it to new config file and remove it 
if (isset($config['extended-gui']) && is_array($config['extended-gui'])) {
    $configuration = $config['extended-gui'];                           // load config
    unset($config['extended-gui']);                                     // remove old config
}

$configuration['appname'] = $appname;
$configuration['version'] = exec("cat {$install_dir}version.txt");
$configuration['product_version'] = "-----";
$configuration['rootfolder'] = $install_dir;
$configuration['postinit'] = "/usr/local/bin/php-cgi -f {$install_dir}{$config_name}-start.php";
$configuration['shutdown'] = "/usr/local/bin/php-cgi -f {$install_dir}{$config_name}-stop.php";

ext_remove_rc_commands($config_name);                                   
$configuration['rc_uuid_start'] = $configuration['postinit'];
$configuration['rc_uuid_stop'] = $configuration['shutdown'];
ext_create_rc_commands($appname, $configuration['rc_uuid_start'], $configuration['rc_uuid_stop']);
write_config();
ext_save_config($config_file, $configuration);

if ($new_installation) echo "\nInstallation completed, use WebGUI | Extensions | ".$appname." to configure the application!\n";
else {
    $savemsg = sprintf(gettext("Update to version %s completed!"), $file_version);
    require_once("{$install_dir}{$config_name}-stop.php");
}
require_once("{$install_dir}{$config_name}-start.php");
?>
