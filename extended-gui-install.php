#!/usr/local/bin/php-cgi -f
<?php
/* 
    extended-gui-install.php
    
    based on silent_disk extension for NAS4Free created by Kruglov Alexey
    extended by Andreas Schmidhuber
    
    Copyright (c) 2014 - 2015 Andreas Schmidhuber
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
// 2015.10.08   v0.5.0.1    F: (some) disk SMART values were not shown correctly in rare/special cases  
// 2015.10.04   v0.5        N: combined Install / Update option
//                          N: check if SMB / FTP are enabled to prevent error messages and lags
//                          N: autodetect config.xml change
//                          N: USB Automount: new sysid 6
//                          N: disk_check.sh includes SMART, SPACE and degraded POOLS checks
//                          N: STATUS | SYSTEM - support for all RAID variants
//                          C: installer: always take the current installation directory as rootfolder -> for restored config.xml
//                          C: take care of systems without ZFS pools
//                          C: clogdir for CONFIG, notifications.log etc
//                          C: live view of smart values
//                          C: extension start/stop procedure
//                          C: system.inc -> swapdevice, removed /dev/
//                          C: changed exec -> mwexec in extended-gui_start.php for debuging
//                          C: removed system.inc -> ZFS pool health status -> replaced in r1395
//                          C: removed guiconfig.inc because of:
//                              - F: r1349 incompatibility -> new function verify_xz_file
//                              - new releases now contains the changes for function print_config_change_box,
//                                no longer needed -> replaced in r1349
//                          C: removed disks_zfs_dataset.php and disks_zfs_volume.php replaced in r1349 
//                              - includes fixes for error traps, no longer needed
//                          C: removed access_users.php replaced in r1349 - includes fix for user groups,
//                                no longer needed
//                          C: removed status_disks.php due to amendments in r1349
//                          F: if there is more than one vdevice per pool
//                          F: pools on GPT
//                          F: chmod for /var/scripts to be sure that scripts are executable
//                          F: STATUS | GRAPH - take care about installed RRDGraphs extension
//                          F: Purge - change file find from mtime (modification time) to atime (access time)
// 2015.01.06   v0.4.4.4    C: STATUS | SYSTEM - UPS display for UPS slave
//                          C: STATUS | SYSTEM - CPU temperatures in one row 
//                          F: STATUS | SYSTEM - support .eli encrypted devices
// 2014.07.16   v0.4.4.3    C: STATUS | SYSTEM - UPS display
// 2014.07.11   v0.4.4.2    F: USB Automount: drives with more than one partition
// 2014.06.16   v0.4.4.1    N: USB Automount: new sysid 12
//                          C: STATUS | SYSTEM - Users monitor, display user names in color
//                          C: STATUS | SYSTEM - Network hosts monitor, display host names in color
//                          F: STATUS | SYSTEM - Network hosts monitor, search for whole IP@ for correct display
// 2014.06.06   v0.4.4      N: TOOLS section in EXTENSIONS | EXTENDED GUI for Purge and USB Automount
//                          N: Purge: clean recycle bins of CIFS/SMB shares (.recycle directories) from deleted files 
//                          N: USB Automount: for USB disks and Flash drives 
//                          N: STATUS | SYSTEM - Function buttons
//                              - Purge 1 day
//                              - unmount/remount USB disks
//                          N: STATUS | SYSTEM - display Operating System (root filesystem as A_OS)
//                          N: STATUS | SYSTEM - display /var filesystem (as A_VAR)
//                          N: STATUS | SYSTEM - enable/disable LAN graph
//                          N: STATUS | SYSTEM - show disk temperatures even if disks are in standby (enable/disable experimental switch in configuration)
//                          C: STATUS | SYSTEM - Network hosts monitor, display IP@s AND host names (as defined in /etc/hosts) but not the own automatically generated hostname
//                          F: STATUS | SYSTEM - support ZFS .nop devices
//                          F: check for special case -> zvol from jail
// 2014.05.06   v0.4.3.3    N: ZFS degraded warning email
//                          N: STATUS | SYSTEM - Network hosts monitor
//                          N: EXTENSIONS | EXTENDED GUI - system calls service status
//                          N: save/restore autoshutdown/notification log for embedded systems
//                          C: STATUS | SYSTEM - disk space warning logic now based on used size AND used percent
//                          F: DIAGNOSTICS | INFORMATION | DISKS: temperature shows allways n/a (started with 9.2.0.1-943)
//                          F: STATUS | SYSTEM - rowspan calculation for CPU temperature
//                          F: STATUS | SYSTEM - support now ZFS pools on gpt partitions and device labels
// 2014.04.17   v0.4.3.1    F: disks view temperature, temperature typo
//                          N: UPS view on/off if UPS is enabled/disabled 
// 2014.04.15   v0.4.3      first public release

$version = "v0.5.0.1";
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
$amenuitem['1']['item'] = "Install / Update {$appname}";
$amenuitem['2']['tag'] = "2";
$amenuitem['2']['item'] = "Uninstall {$appname}";
$result = tui_display_menu(" ".$appname." Extension ".$version." ", "Select Install / Update or Uninstall", 60, 10, 6, $amenuitem, $installopt);
if (0 != $result) { echo "\fInstallation aborted!\n"; exit(0);}

// remove application section
if ( $installopt == 2 ) { 
    if ( is_array($config['extended-gui'] ) ) {
        $cwdir = getcwd();
        $path1 = pathinfo($cwdir);
        $config['extended-gui']['rootfolder'] =  $path1['dirname']."/".$path1['basename']."/extended-gui/";
        $cwdir = $config['extended-gui']['rootfolder'];

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

// install / update application on NAS4Free
if ( $installopt == 1 )  {
    $cwdir = getcwd();
    if ( !isset($config['extended-gui']) || !is_array($config['extended-gui'])) {
        $config['extended-gui'] = array();
    }
    $path1 = pathinfo($cwdir);
    $config['extended-gui']['version'] = $version;
    $config['extended-gui']['product_version'] = "-----";
    $config['extended-gui']['rootfolder'] =  $path1['dirname']."/".$path1['basename']."/extended-gui/";
    $cwdir = $config['extended-gui']['rootfolder'];
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
    require_once("{$config['extended-gui']['rootfolder']}extended-gui_stop.php");
    require_once("{$config['extended-gui']['rootfolder']}extended-gui_start.php");
    echo "Installation / Update completed!\n";
} // END of install / update application on NAS4Free
?>
