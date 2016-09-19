<?php
/* 
    extended-gui-install.php
    
    Copyright (c) 2014 - 2016 Andreas Schmidhuber
    All rights reserved.

	Portions of NAS4Free (http://www.nas4free.org).
	Copyright (c) 2012-2016 The NAS4Free Project <info@nas4free.org>.
	All rights reserved.

	Portions of freenas (http://www.freenas.org).
	Copyright (c) 2005-2011 by Olivier Cochard <olivier@freenas.org>.
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
	either expressed or implied, of the NAS4Free Project.
*/
$v = "v0.6-b3";              // extension version
$appname = "Extended GUI";
$min_release = 10.3032853;  // minimal OS release

require_once("config.inc");

$arch = $g['arch'];
$platform = $g['platform'];
// no check necessary since the extension is for all archictectures/platforms
//if ($arch != "i386" && $arch != "amd64" && $arch != "x86" && $arch != "x64" && $arch != "rpi" && $arch != "rpi2" && $arch != "rpi3" && $arch != "bpi") { echo "\f{$arch} is an unsupported architecture!\n"; exit(1);  }
//if ($platform != "embedded" && $platform != "full" && $platform != "livecd" && $platform != "liveusb") { echo "\funsupported platform!\n";  exit(1); }

// install extension
global $input_errors;
global $savemsg;

$install_dir = dirname(__FILE__)."/";                           // get directory where the installer script resides
if (!is_dir("{$install_dir}backup")) { mkdir("{$install_dir}backup", 0775, true); }
if (!is_dir("{$install_dir}log")) { mkdir("{$install_dir}log", 0775, true); }

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

// create stripped version name
$vs = str_replace(".", "", $v);
// fetch release archive
$return_val = mwexec("fetch {$verify_hostname} -vo {$install_dir}master.zip 'https://github.com/crestAT/nas4free-extended-gui/releases/download/{$v}/extended-gui-{$vs}.zip'", true);
if ($return_val == 0) {
    $return_val = mwexec("tar -xf {$install_dir}master.zip -C {$install_dir} --exclude='.git*' --strip-components 2", true);
    if ($return_val == 0) {
        exec("rm {$install_dir}master.zip");
        exec("chmod -R 775 {$install_dir}");
        if (is_file("{$install_dir}version.txt")) { $file_version = exec("cat {$install_dir}version.txt"); }
        else { $file_version = "n/a"; }
        $savemsg = sprintf(gettext("Update to version %s completed!"), $file_version);
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
if ( !isset($config['extended-gui']) || !is_array($config['extended-gui'])) { 
// new installation
    $config['extended-gui'] = array();      
    $config['extended-gui']['appname'] = $appname;
    $config['extended-gui']['version'] = exec("cat {$install_dir}version.txt");
    $config['extended-gui']['product_version'] = "-----";
    $config['extended-gui']['rootfolder'] = $install_dir;
    $i = 0;
    if ( is_array($config['rc']['postinit'] ) && is_array( $config['rc']['postinit']['cmd'] ) ) {
        for ($i; $i < count($config['rc']['postinit']['cmd']);) {
            if (preg_match('/extended-gui/', $config['rc']['postinit']['cmd'][$i])) break; ++$i; }
    }
    $config['rc']['postinit']['cmd'][$i] = $config['extended-gui']['rootfolder']."extended-gui_start.php";
    $i =0;
    if ( is_array($config['rc']['shutdown'] ) && is_array( $config['rc']['shutdown']['cmd'] ) ) {
        for ($i; $i < count($config['rc']['shutdown']['cmd']); ) {
            if (preg_match('/extended-gui/', $config['rc']['shutdown']['cmd'][$i])) break; ++$i; }
    }
    $config['rc']['shutdown']['cmd'][$i] = $config['extended-gui']['rootfolder']."extended-gui_stop.php";
    write_config();
    require_once("{$config['extended-gui']['rootfolder']}extended-gui-start.php");
    echo "\n".$appname." Version ".$config['extended-gui']['version']." installed";
    echo "\n\nInstallation completed, use WebGUI | Extensions | ".$appname." to configure \nthe application (don't forget to refresh the WebGUI before use)!\n";
}
else {
// update release
    $config['extended-gui']['version'] = exec("cat {$install_dir}version.txt");
    $config['extended-gui']['product_version'] = "-----";
    $config['extended-gui']['rootfolder'] = $install_dir;
    $i = 0;
    if ( is_array($config['rc']['postinit'] ) && is_array( $config['rc']['postinit']['cmd'] ) ) {
        for ($i; $i < count($config['rc']['postinit']['cmd']);) {
            if (preg_match('/extended-gui/', $config['rc']['postinit']['cmd'][$i])) break; ++$i; }
    }
    $config['rc']['postinit']['cmd'][$i] = $config['extended-gui']['rootfolder']."extended-gui_start.php";
    $i =0;
    if ( is_array($config['rc']['shutdown'] ) && is_array( $config['rc']['shutdown']['cmd'] ) ) {
        for ($i; $i < count($config['rc']['shutdown']['cmd']); ) {
            if (preg_match('/extended-gui/', $config['rc']['shutdown']['cmd'][$i])) break; ++$i; }
    }
    $config['rc']['shutdown']['cmd'][$i] = $config['extended-gui']['rootfolder']."extended-gui_stop.php";
    write_config();
    require_once("{$config['extended-gui']['rootfolder']}extended-gui-stop.php");
    require_once("{$config['extended-gui']['rootfolder']}extended-gui-start.php");
}
?>
