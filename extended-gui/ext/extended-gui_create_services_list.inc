<?php
/*
    extended-gui_create_services_list.inc

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

require 'config.inc';
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
$a_service[] = ['desc' => gtext('NFS'),'link' => 'services_nfs.php','config' => 'nfsd','scriptname' => 'nfsd'];
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
$a_service[] = ['desc' => gtext('Syncthing'),'link' => 'syncthing.php','config' => 'xxx','scriptname' => 'syncthing'];
$a_service[] = ['desc' => gtext('BitTorrent Sync'),'link' => 'btsync.php','config' => 'xxx','scriptname' => 'rslsync'];
$a_service[] = ['desc' => gtext('Downloady'),'link' => 'dly-config.php','config' => 'downloady','scriptname' => 'wget','checkcounter' => '0','returnvalue' => '0'];
$a_service[] = ['desc' => gtext('Plex'),'link' => 'plex-config.php','config' => 'plex','scriptname' => 'plex'];

function get_extension_config($config_name) {
	global $config;
	if (is_array($config['rc']) && is_array($config['rc']['param']['0'])) {
		$rc_param_count = count($config['rc']['param']);
	    for ($i = 0; $i < $rc_param_count; $i++) {
	        if (preg_match("/{$config_name}/", $config['rc']['param'][$i]['value'])) { 
				$parameter = $config['rc']['param'][$i]['value'];
			}
		}
	}
}

$info_log = fopen($info_temp_log_file, "w");
foreach($a_service as $r_service):
		$enable = isset($config[$r_service['config']]['enable']);
		if ($enable) {
			$bg_color = "LightGreen";
			$title = gettext("running");
			if (!isset($r_service['checkname'])) $r_service['checkname'] = $r_service['scriptname'];
			if (!isset($r_service['checkcounter'])) $r_service['checkcounter'] = 1;
echo ($r_service['checkname']." ".$r_service['checkcounter']." ".$r_service['returnvalue']."\n");
			if (!isset($r_service['returnvalue'])) $r_service['returnvalue'] = mwexec("/etc/rc.d/{$r_service['scriptname']} onestatus");
echo ($r_service['checkname']." ".$r_service['checkcounter']." ".$r_service['returnvalue']."\n");
		    if ($r_service['returnvalue'] == 0) {
				$result = exec("ps acx |grep -c {$r_service['checkname']}");
				if ($result > $r_service['checkcounter']) {
					$bg_color = "Gold";
					$title = gettext("executing");
				}
				fwrite($info_log, "<a title='{$title}' style='background-color: {$bg_color};' href='{$r_service['link']}'>&nbsp;&nbsp;&nbsp;<b>{$r_service['desc']}</b>&nbsp;&nbsp;&nbsp;</a>&nbsp;&nbsp;&nbsp;&nbsp;");
			} 
		    else {
				fwrite($info_log, "<a title='stopped' style='background-color: #FF6666;' href=".$r_service['link'].">&nbsp;&nbsp;&nbsp;<b>{$r_service['desc']}</b>&nbsp;&nbsp;&nbsp;</a>&nbsp;&nbsp;&nbsp;&nbsp;");
			}
		}
endforeach;
fclose($info_log);
copy($info_temp_log_file, $info_log_file);
?>
