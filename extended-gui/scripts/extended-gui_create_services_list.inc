<?php
/*
    extended-gui_create_services_list.inc

    Copyright (c) 2014 - 2018 Andreas Schmidhuber
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
*/
require 'config.inc';
require '/usr/local/www/ext/extended-gui/extension-lib.inc';
$config_file = "/usr/local/www/ext/extended-gui/extended-gui.conf";
require_once("/usr/local/www/ext/extended-gui/extension-lib.inc");
if (($configuration = ext_load_config($config_file)) === false) {
	mwexec("logger extended-gui:extended-gui_create_services_list.inc Configuration file {$config_file} not found, execution aborted!");
	exit;	
} 

$error_log_file = "/tmp/extended-gui_system_error.msg";
$info_log_file = "/tmp/extended-gui_services_info.log";
$info_temp_log_file = "{$info_log_file}.tmp";

$ups_script = 'nut';
if(isset($config['ups']['mode']) && ($config['ups']['mode'] == 'slave')):
	$ups_script = 'nut_upsmon';
endif;
// get services data 
$a_service[] = ['desc' => gtext('HAST'),'link' => 'services_hast.php','config' => 'hast','scriptname' => 'hastd'];
//	=> SCRIPTNAME has to bee changed to fit for 'ps acx | grep -c check_name' !!!
#$a_service[] = ['desc' => gtext('CIFS/SMB'),'link' => 'services_samba.php','config' => 'samba','scriptname' => 'samba','checkname' => 'smbd','checkcounter' => '0'];
$a_service[] = ['desc' => gtext('CIFS/SMB'),'link' => 'services_samba.php','config' => 'samba','scriptname' => 'samba'];
$a_service[] = ['desc' => gtext('FTP'),'link' => 'services_ftp.php','config' => 'ftpd','scriptname' => 'proftpd'];
$a_service[] = ['desc' => gtext('TFTP'),'link' => 'services_tftp.php','config' => 'tftpd','scriptname' => 'tftpd'];
$a_service[] = ['desc' => gtext('SSH'),'link' => 'services_sshd.php','config' => 'sshd','scriptname' => 'sshd'];
//	=> SCRIPTNAME has to bee changed to fit for 'ps acx | grep -c check_name' !!!
$a_service[] = ['desc' => gtext('NFS'),'link' => 'services_nfs.php','config' => 'nfsd','scriptname' => 'nfsd','checkcounter' => '255'];
$a_service[] = ['desc' => gtext('AFP'),'link' => 'services_afp.php','config' => 'afp','scriptname' => 'netatalk'];
//	=> SCRIPTNAME has to bee changed to fit for 'ps acx | grep -c check_name' !!!
$a_service[] = ['desc' => gtext('RSYNC'),'link' => 'services_rsyncd.php','config' => 'rsyncd','scriptname' => 'rsyncd','checkname' => 'rsync'];
$a_service[] = ['desc' => gtext('Syncthing'),'link' => 'services_syncthing.php','config' => 'syncthing','scriptname' => 'syncthing'];
$a_service[] = ['desc' => gtext('Unison'),'link' => 'services_unison.php','config' => 'unison','scriptname' => 'unison'];
$a_service[] = ['desc' => gtext('iSCSI Target'),'link' => 'services_iscsitarget.php','config' => 'iscsitarget','scriptname' => 'iscsi_target'];
$a_service[] = ['desc' => gtext('DLNA/UPnP Fuppes'),'link' => 'services_fuppes.php','config' => 'upnp','scriptname' => 'fuppes'];
$a_service[] = ['desc' => gtext('DLNA/UPnP MiniDLNA'),'link' => 'services_minidlna.php','config' => 'minidlna','scriptname' => 'minidlna'];
$a_service[] = ['desc' => gtext('iTunes/DAAP'),'link' => 'services_daap.php','config' => 'daap','scriptname' => 'mt-daapd'];
$a_service[] = ['desc' => gtext('Dynamic DNS'),'link' => 'services_dynamicdns.php','config' => 'dynamicdns','scriptname' => 'inadyn'];
$a_service[] = ['desc' => gtext('SNMP'),'link' => 'services_snmp.php','config' => 'snmpd','scriptname' => 'bsnmpd'];
$a_service[] = ['desc' => gtext('UPS'),'link' => 'services_ups.php','config' => 'ups','scriptname' => $ups_script];
$a_service[] = ['desc' => gtext('Webserver'),'link' => 'services_websrv.php','config' => 'websrv','scriptname' => 'websrv'];
$a_service[] = ['desc' => gtext('BitTorrent'),'link' => 'services_bittorrent.php','config' => 'bittorrent','scriptname' => 'transmission'];
$a_service[] = ['desc' => gtext('LCDproc'),'link' => 'services_lcdproc.php','config' => 'lcdproc','scriptname' => 'LCDd'];

// get extensions data
#	'config' => either tag in config.xml OR 'external' config file (btsync, syncthing extension, ...)
#	'configpath' => relative config file path, amended by extension startup command script root directory
#	'checkcounter' => 'n'	... how many processes are running in 'normal' (idle) state to see if there are 'executing' services
#	'returnvalue' => '127'	... for ps check because there is no rc script, '0' not use ps check like wget (only running on demand)
$a_service[] = ['desc' => gtext('Syncthing'),'link' => 'syncthing.php','config' => 'external','configpath' => '/ext/syncthing.conf','scriptname' => 'syncthing','returnvalue' => '127','checkcounter' => '2'];
$a_service[] = ['desc' => gtext('RSLSync'),'link' => 'btsync.php','config' => 'external','configpath' => '/ext/btsync.conf','scriptname' => 'btsync','checkname' => 'rslsync','returnvalue' => '127'];
$a_service[] = ['desc' => gtext('Downloady'),'link' => 'dly-config.php','config' => 'downloady','scriptname' => 'wget','checkcounter' => '0','returnvalue' => '0'];
$a_service[] = ['desc' => gtext('Plex Media Server'),'link' => 'plex-gui.php','config' => 'external','configpath' => '/conf/plex_config','scriptname' => 'plex','checkname' => 'Plex Media Server','returnvalue' => '127'];
$a_service[] = ['desc' => gtext('DNSMASQ'),'link' => 'extensions_dnsmasq_server.php','config' => 'dnsmasq','scriptname' => 'dnsmasq'];

function get_extension_config($config_name) {
	global $config;
	if (is_array($config['rc']) && is_array($config['rc']['param']['0'])) {
		$rc_param_count = count($config['rc']['param']);
	    for ($i = 0; $i < $rc_param_count; $i++) {
	        if (preg_match("/{$config_name}/", $config['rc']['param'][$i]['value'])) { 
				return $config['rc']['param'][$i]['value'];
			}
		}
	}
}

$info_log = fopen($info_temp_log_file, "w");                  			// open log for index.php output
foreach($a_service as $r_service):										// check loop
	$enable = false;
#echo ("Check for ".$r_service['desc']."\n");							// DEBUG
	if (isset($config[$r_service['config']])) {							// built-in services => in config.xml
#echo ($r_service['desc']." stays in config.xml\n");                    // DEBUG
		$enable = isset($config[$r_service['config']]['enable']);		// show only enabled services
	} 
	
	if ($r_service['config'] == "external") {							// now check extensions
#echo ($r_service['desc']." uses an external config file\n");           // DEBUG
	    $result = explode(" ", get_extension_config($r_service['scriptname']));	// check command scripts, check for start commands 
	    $count = count($result);										// read # of params
	    $dir_name = dirname($result[($count - 1)]);						// take last for extension location
	    if (!empty($dir_name)) {
			$config_file = "{$dir_name}{$r_service['configpath']}";		// assemble location of config file		
			if (is_file($config_file)) {								// check for config file
#echo "Config file {$config_file} found\n";	                            // DEBUG
				$ext_configuration = ext_load_config($config_file);         // load config file
				if (is_array($ext_configuration)) $enable = $ext_configuration['enable'];	// show only enabled services in json config file
				else {																// if not json than ...
					if (mwexec("cat {$config_file} | grep 'PLEX_ENABLE=\"YES\"'") === 0) $enable = true;	// ... could be plex
				}
			} 
		} 
	}

// now check if enabled services are running
	if ($enable) {
		$bg_color = "LightGreen";
		$title = gettext("running");
		if (!isset($r_service['checkname'])) $r_service['checkname'] = $r_service['scriptname'];	// if checkname = scriptname
		if (!isset($r_service['checkcounter'])) $r_service['checkcounter'] = 1;						// set checkcounter for 'executing' services
#echo ("CN=".$r_service['checkname']." CK=".$r_service['checkcounter']." RV=".$r_service['returnvalue']."\n");	// DEBUG
		if (!isset($r_service['returnvalue'])) $r_service['returnvalue'] = mwexec("/etc/rc.d/{$r_service['scriptname']} onestatus");	// if rc script exists
		elseif ($r_service['returnvalue'] == 127) {													// if NO rc script exists
			$r_service['returnvalue'] = mwexec("ps acx | grep \"{$r_service['checkname']}\"");		// ... use checkname for ps
#echo ("CN=".$r_service['checkname']." CK=".$r_service['checkcounter']." RV=".$r_service['returnvalue']."\n");  // DEBUG
		}
	    if ($r_service['returnvalue'] == 0) {									// if service is 'running'
			$result = exec("ps acx |grep -c \"{$r_service['checkname']}\"");	// check if 'executing'
			if ($result > $r_service['checkcounter']) {                         // ... and mark it eventually 
				$bg_color = "Gold";
				$title = gettext("executing");
			}
			fwrite($info_log, "<a title='{$title}' style='background-color: {$bg_color};' href='{$r_service['link']}'>&nbsp;&nbsp;&nbsp;<b>{$r_service['desc']}</b>&nbsp;&nbsp;&nbsp;</a>&nbsp;&nbsp;&nbsp; ");
			if (is_file("/tmp/extended-gui_{$r_service['scriptname']}_service_stopped.lock")) unlink("/tmp/extended-gui_{$r_service['scriptname']}_service_stopped.lock");
		} 
	    else {
			if (is_file("/tmp/extended-gui_{$r_service['scriptname']}_schedule_stopped.lock")) {	// check if service is stopped by schedule
				fwrite($info_log, "<a title='scheduled stop' style='background-color: LightGray;' href=".$r_service['link'].">&nbsp;&nbsp;&nbsp;<b>{$r_service['desc']}</b>&nbsp;&nbsp;&nbsp;</a>&nbsp;&nbsp;&nbsp; ");
			}	    	
			else {                                                                                  // else mark it and throw error message 
				fwrite($info_log, "<a title='stopped' style='background-color: #FF6666;' href=".$r_service['link'].">&nbsp;&nbsp;&nbsp;<b>{$r_service['desc']}</b>&nbsp;&nbsp;&nbsp;</a>&nbsp;&nbsp;&nbsp; ");
				if (!is_file("/tmp/extended-gui_{$r_service['scriptname']}_service_stopped.lock")) {
					$error_msg = "ERROR Service {$r_service['desc']} is enabled but not running!"; 
					if ($configuration['services_email'] == true) {
						if ($configuration['telegram_enable'] == true) {
							mwexec("/var/scripts/telegram-notify --html --title ".gethostname()." --text \"{$error_msg}\"", true);
						}
						if ($configuration['email_enable'] == true) {
							mwexec("/var/scripts/email.sh {$configuration['space_email_add']} N4F-SERVICES \"Host: ".gethostname()."\n\n{$error_msg}\"", true);
						}
					}
					if ($configuration['beep'] == true) {
						mwexec("nohup /var/scripts/beep 'CPU_ERROR' >/dev/null 2>&1 &", true);
					}
					touch("/tmp/extended-gui_{$r_service['scriptname']}_service_stopped.lock");
					$error_msg = date("Y.m.d H:i:s")." ".$error_msg."\n";            // create error message for Status > System
					$error_log = fopen($error_log_file, "a");					
					fwrite($error_log, $error_msg);
					fclose($error_log);
				}
			}
		}
	}
endforeach;
fclose($info_log);
copy($info_temp_log_file, $info_log_file);
?>
