#!/usr/local/bin/php-cgi -f
<?php
/* 
    extended-gui-install.php
    
    based on silent_disk extension for NAS4Free created by Kruglov Alexey
    extended by Andreas Schmidhuber
    
    Copyright (c) 2014, Andreas Schmidhuber
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
// 2014.04.15   v0.4.3      C: ZFS disks view, CPU graph and bar switchable
//                          first public release

$version = "v0.4.3 (+ ZFS fix)";
$appname = "Extended GUI";

require_once("config.inc");
require_once("functions.inc");
require_once("install.inc");
require_once("util.inc");
require_once("tui.inc");

$arch = $g['arch'];
$platform = $g['platform'];
if (($arch != "i386" && $arch != "amd64") && ($arch != "x86" && $arch != "x64")) { echo "\funsupported architecture!\n"; exit(1);  }
if ($platform != "embedded" && $platform != "full" && $platform != "livecd" && $platform != "liveusb") { echo "\funsupported platform!\n";  exit(1); }

// display installation option
$amenuitem['1']['tag'] = "1";
$amenuitem['1']['item'] = "Install {$appname}";
$amenuitem['2']['tag'] = "2";
$amenuitem['2']['item'] = "Update {$appname}";
$amenuitem['3']['tag'] = "3";
$amenuitem['3']['item'] = "Uninstall {$appname}";
$result = tui_display_menu(" ".$appname." Extension ".$version." ", "Select Install, Update or Uninstall", 60, 10, 6, $amenuitem, $installopt);
if (0 != $result) { echo "\fInstallation aborted!\n"; exit(0);}

// remove application section
if ( $installopt == 3 ) { 
    if ( is_array($config['extended-gui'] ) ) {
        if ( is_array($config['rc']['postinit'] ) && is_array( $config['rc']['postinit']['cmd'] ) ) {
    		for ($i = 0; $i < count($config['rc']['postinit']['cmd']);) {
    		if (preg_match('/extended-gui/', $config['rc']['postinit']['cmd'][$i])) { unset($config['rc']['postinit']['cmd'][$i]);} else{}
    		++$i;
    		}
    	}
    	if ( is_array($config['rc']['shutdown'] ) && is_array( $config['rc']['shutdown']['cmd'] ) ) {
    		for ($i = 0; $i < count($config['rc']['shutdown']['cmd']); ) {
     		if (preg_match('/extended-gui/', $config['rc']['shutdown']['cmd'][$i])) { unset($config['rc']['shutdown']['cmd'][$i]); } else {}
    		++$i;	
    		}
    	}
    	// unlink created  links
    	if (is_dir ("/usr/local/www/ext/extended-gui")) {
        	foreach ( glob( "{$config['extended-gui']['rootfolder']}ext/*.php" ) as $file ) {
            	$file = str_replace("{$config['extended-gui']['rootfolder']}ext/", "/usr/local/www", $file);
            	if ( is_link( $file ) ) { unlink( $file ); } else {}
            }
        	mwexec ("rm -rf /usr/local/www/ext/extended-gui");
    	}
    	
        // restore originals from backup
        require_once("{$config['extended-gui']['rootfolder']}extended-gui_stop.php");
    
        // remove application section from config.xml
    	if ( is_array($config['extended-gui'] ) ) { unset( $config['extended-gui'] ); write_config();}
    	echo $appname." entries removed. Remove files manually!\n";
    }
    else { echo $appname." is not installed!\n"; exit; }
} // END of remove application section  

// install application on NAS4Free
if ( $installopt == 1 )  {
    $cwdir = getcwd();
    if ( !isset($config['extended-gui']) || !is_array($config['extended-gui'])) {
        $config['extended-gui'] = array();
        $path1 = pathinfo($cwdir);
        $config['extended-gui']['version'] = $version;
        $config['extended-gui']['product_version'] = "-----";
        $config['extended-gui']['rootfolder'] =  $path1['dirname']."/".$path1['basename']."/extended-gui/";
        $cwdir = $config['extended-gui']['rootfolder'];
        write_config();
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
    }
    else { echo $appname." is already installed!\n"; exit; }
    require_once("{$config['extended-gui']['rootfolder']}extended-gui_start.php");
    echo "Installation completed!\n";
} // END of install application on NAS4Free

// update application on NAS4Free
if ( $installopt == 2 ) {
    if ( !isset($config['extended-gui']) || !is_array($config['extended-gui'])) {
        echo $appname." is not installed!\n"; exit;        
    }
    else {
        $config['extended-gui']['version'] = $version;
        $config['extended-gui']['product_version'] = "-----";
        write_config();
        require_once("{$config['extended-gui']['rootfolder']}extended-gui_stop.php");        
    }
    require_once("{$config['extended-gui']['rootfolder']}extended-gui_start.php");
    echo "Update installation completed!\n";
} // END of update application on NAS4Free
?>
