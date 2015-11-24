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
// 2015.11.24   v0.5.1b6    N: Web installer
// 2015.11.24   v0.5.1b5    N: CPU temperature monitoring and reporting - monitor CPU temps and optional email reporting like degraded pools etc.
//                          N: buzzer for degraded pools, CPU temperatures
// 2015.11.20   v0.5.1b4    C: updated diag_log.inc & index.php to Page base: r2067
//                          C: STATUS | SYSTEM - correct display of disk columns
//                          N: introduce spinner for USB Automount and CIFS/SMB purge 
//                          N: USB Automount - take care of CD/DVDs
//                          C: USB Automount - allow disks without 'YourMountpointName.mounted' file, but stays though optional
//                          C: STATUS | SYSTEM - display USB mounted devices
// 2015.11.10   v0.5.1b31   F: degraded pool reporting
//                          N: pool busy states (scrub, resilver)
// 2015.11.08   v0.5.1b3    C: updated index.php to Page base: r1962 = r2003
//                          F: display pool values live again
// 2015.11.07   v0.5.1b2b1  C: amendments to purge v03
//                          N: STATUS | SYSTEM - display /usr/local filesystem (as A_USR) 
// 2015.10.24   v0.5.1b2    N: added Raspberry Pi to supported architecture
//                          N: USB Automount: sysid 255 - exFAT
//                          C: STATUS | SYSTEM - display Operating System (root filesystem as A_OS)
//                          C: STATUS | SYSTEM - display /var filesystem (as A_VAR)
//                          C: STATUS | SYSTEM - display temporarely mounted USB devics -> USB Automount
// 2015.10.16   v0.5.1b1    C: updated index.php to Page base: r1906 (includes BHyVe, VBox, UPS live view and code cleanup)
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

$v = "v0.5.1b6";                          // extension version
$appname = "Extended GUI";

require_once("config.inc");

$arch = $g['arch'];
$platform = $g['platform'];
if (($arch != "i386" && $arch != "amd64") && ($arch != "x86" && $arch != "x64" && $arch != "rpi")) { echo "\f{$arch} is an unsupported architecture!\n"; exit(1);  }
if ($platform != "embedded" && $platform != "full" && $platform != "livecd" && $platform != "liveusb") { echo "\funsupported platform!\n";  exit(1); }

// install extension
global $input_errors;
global $savemsg;

$install_dir = dirname(__FILE__)."/";

// check FreeBSD release for fetch options >= 9.3
$release = explode("-", exec("uname -r"));
if ($release[0] >= 9.3) $verify_hostname = "--no-verify-hostname";
else $verify_hostname = "";
// create stripped version name
$vs = str_replace(".", "", $v);
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
    else { $input_errors[] = sprintf(gettext("Archive file %s not found, installation aborted!"), "master.zip corrupt /"); }
}
else { $input_errors[] = sprintf(gettext("Archive file %s not found, installation aborted!"), "master.zip"); }

// install / update application on NAS4Free
if ( !isset($config['extended-gui']) || !is_array($config['extended-gui'])) { $config['extended-gui'] = array(); }     
$config['extended-gui']['appname'] = $appname;
$config['extended-gui']['version'] = exec("cat {$install_dir}version.txt");
$config['extended-gui']['product_version'] = "-----";
$config['extended-gui']['rootfolder'] =  $install_dir;
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
echo "\n".$appname." Version ".$config['extended-gui']['version']." installed";
echo "\n\nInstallation completed, use WebGUI | Extensions | ".$appname." to configure \nthe application (don't forget to refresh the WebGUI before use)!\n";
?>
