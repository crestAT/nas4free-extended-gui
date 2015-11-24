<?php
/*
    extended-gui_create_config2.inc

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
$raid_variants = array('gmirror', 'graid5', 'gconcat', 'gvinum', 'gstripe');
$eguiconfig = fopen("/var/scripts/CONFIG2", "w");                                                       // create disk/share config file
    $clogdir = rc_getenv_ex("clog_logdir", $g['varlog_path']);                                          // get logdir from environment and
    fwrite($eguiconfig, "SYSTEM_LOG_DIR={$clogdir}"."\n");                                              // write it to CONFIG2
    $txt = isset($config['samba']['enable']) ? "1" : "0";                                               // for user_check script
    fwrite($eguiconfig, "SMB_ENABLED=".$txt."\n");
    $txt = isset($config['ftpd']['enable']) ? "1" : "0";                                                // for user_check script
    fwrite($eguiconfig, "FTP_ENABLED=".$txt."\n");
    $txt = isset($config['extended-gui']['beep']) ? "1" : "0";                                          // for beep script
    fwrite($eguiconfig, "RUN_BEEP=".$txt."\n");
    $txt = isset($config['extended-gui']['temp_always']) ? "1" : "0";                                   // for disk_check.sh script
    fwrite($eguiconfig, "TEMP_ALWAYS=".$txt."\n");
    fwrite($eguiconfig, "TEMP_WARNING='{$config['extended-gui']['temp_warning']}'"."\n");               // for disk_check.sh script
    fwrite($eguiconfig, "TEMP_SEVERE='{$config['extended-gui']['temp_severe']}'"."\n");
    fwrite($eguiconfig, "SPACE_WARNING_MB='{$config['extended-gui']['space_warning']}'"."\n");          // for disk_check.sh script
    fwrite($eguiconfig, "SPACE_WARNING_PC='{$config['extended-gui']['space_warning_percent']}'"."\n");
    fwrite($eguiconfig, "SPACE_SEVERE_MB='{$config['extended-gui']['space_severe']}'"."\n");
    fwrite($eguiconfig, "SPACE_SEVERE_PC='{$config['extended-gui']['space_severe_percent']}'"."\n");
    $txt = isset($config['extended-gui']['space_email']) ? "1" : "0";
    fwrite($eguiconfig, "EMAIL_SPACE_ENABLED=".$txt."\n");
    $txt = isset($config['extended-gui']['zfs_degraded_email']) ? "1" : "0";
    fwrite($eguiconfig, "EMAIL_DEGRADED_ENABLED=".$txt."\n");
    fwrite($eguiconfig, "EMAIL_TO='{$config['extended-gui']['space_email_add']}'"."\n");
    fwrite($eguiconfig, "EMAIL_FROM='{$config['system']['email']['from']}'"."\n");                      // for email.sh script
    $txt = isset($config['extended-gui']['user']) ? "1" : "0";                                          // for extended-gui_system_calls.sh script
    fwrite($eguiconfig, "RUN_USER=".$txt."\n");
    $txt = isset($config['extended-gui']['hosts']) ? "1" : "0";
    fwrite($eguiconfig, "RUN_HOSTS=".$txt."\n");
    $txt = isset($config['extended-gui']['automount']) ? "1" : "0";
    fwrite($eguiconfig, "RUN_AUTOMOUNT=".$txt."\n");
    fwrite($eguiconfig, "LOOP_DELAY='{$config['extended-gui']['loop_delay']}'"."\n");
    fwrite($eguiconfig, "SUBNET='{$config['extended-gui']['hosts_network']}'"."\n");                    // for hosts_check.sh script
    fwrite($eguiconfig, "START_IP='{$config['extended-gui']['hosts_network_start']}'"."\n");
    fwrite($eguiconfig, "END_IP='{$config['extended-gui']['hosts_network_end']}'"."\n");
    fwrite($eguiconfig, "DAYSOLD='{$config['extended-gui']['purge']['days']}'"."\n");                    // for purge.sh script
    $txt = isset($config['extended-gui']['user_email']) ? "1" : "0";                                     // for user_check.sh script
    fwrite($eguiconfig, "EMAIL_ENABLED=".$txt."\n");

    // get all mountpoints + pools including all disks for eGUI index.php
    $i = 0;                                                                                             // set to initialize if there are no mounts
    if (is_array($config['mounts']) && is_array($config['mounts']['mount'])) {
        for ($i = 0; $i < count($config['mounts']['mount']); ++$i) {
            $is_raid = 0;                                                                               // set to check if this is a raid
            fwrite($eguiconfig, "MOUNT{$i}={$config['mounts']['mount'][$i]['sharename']}"."\n");        // get sharenames
            foreach($raid_variants as $rv) {                                                            // check raid variants
                if (is_array($config[$rv]) && is_array($config[$rv]['vdisk'])) {
                    for ($k = 0; $k < count($config[$rv]['vdisk']); ++$k) {                             // get vdevice
                		if (preg_match("[{$config['mounts']['mount'][$i]['mdisk']}]", $config[$rv]['vdisk'][$k]['devicespecialfile'])) {
                            $is_raid = 1;
                            for ($l = 0; $l < count($config[$rv]['vdisk'][$k]['device']); ++$l) {
                                $device_raw = explode(".", str_replace("/dev/", "", $config[$rv]['vdisk'][$k]['device'][$l]));    // get rid of .eli .nop etc and /dev/
                                fwrite($eguiconfig, "MOUNT{$i}DISK{$l}={$device_raw[0]}"."\n");         // get vdevice disks
                            }
                        }
                    }
                }
            }
            if ($is_raid == 0) {
                $device_raw = explode(".", str_replace("/dev/", "", $config['mounts']['mount'][$i]['mdisk']));    // get rid of .eli .nop etc and /dev/
                fwrite($eguiconfig, "MOUNT{$i}DISK0={$device_raw[0]}"."\n");            // set disk to mdisk if there is no raid
            }
        }
    }
    $i1 = $i;                                                                           // use $i1 to continue the mount index for pools
    if (is_array($config['zfs']) && is_array($config['zfs']['pools']) && is_array($config['zfs']['pools']['pool'])) {       // get pools
        exec("geom label status -s", $rawdatageom);
        exec("gpart status -s", $rawdatagpart);
        for ($i = 0; $i < count($config['zfs']['pools']['pool']); ++$i) {
            fwrite($eguiconfig, "MOUNT{$i1}={$config['zfs']['pools']['pool'][$i]['name']}"."\n");                           // get pool name
            $disk_count = 0;          // necessary if there is more than one vdevice per pool to get consecutive disknumber in CONFIG2
                for ($j = 0; $j < count($config['zfs']['pools']['pool'][$i]['vdevice']); ++$j) {
                    fwrite($eguiconfig, "MOUNT{$i1}VDEVICE{$j}={$config['zfs']['pools']['pool'][$i]['vdevice'][$j]}"."\n"); // get pool vdevices
                    if (is_array($config['zfs']) && is_array($config['zfs']['vdevices']) && is_array($config['zfs']['vdevices']['vdevice'])) {
                        for ($k = 0; $k < count($config['zfs']['vdevices']['vdevice']); ++$k) {                             // get vdevice
                    		if (preg_match("/^{$config['zfs']['pools']['pool'][$i]['vdevice'][$j]}$/", $config['zfs']['vdevices']['vdevice'][$k]['name'])) {
                                for ($l = 0; $l < count($config['zfs']['vdevices']['vdevice'][$k]['device']); ++$l) {
                                    $device_raw = explode(".", str_replace("/dev/", "", $config['zfs']['vdevices']['vdevice'][$k]['device'][$l]));    // get rid of .eli .nop etc and /dev/
                                    $dev = $device_raw[0];
                                    foreach($rawdatageom as $key) {                                                         // get disks on GPT
                                        $key = trim($key);
                                        $device = preg_split("/[\s]+/", $key);
                                        if (strcmp($dev, $device[0]) == 0) { $dev = $device[2]; 
                                        }
                                    }
                                    foreach($rawdatagpart as $key) {                                                        // get disks on label and/or partitions
                                        $key = trim($key);
                                        $device = preg_split("/[\s]+/", $key);
                                        if (strcmp($dev, $device[0]) == 0) { $dev = $device[2];
                                        }
                                    }
                                    fwrite($eguiconfig, "MOUNT{$i1}DISK{$disk_count}={$dev}"."\n");                         // get vdevice disks
                                    ++$disk_count;
                                }
                            }
                        }
                    }
                }
            ++$i1;
        }
    }
fclose($eguiconfig);
//mwexec("logger extended-gui: CONFIG2 created");
?>
