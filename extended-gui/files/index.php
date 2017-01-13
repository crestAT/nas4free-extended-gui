<?php
/*
	index.php

    Copyright (c) 2014 - 2017 Andreas Schmidhuber
    All rights reserved.

	Portions of NAS4Free (http://www.nas4free.org).
	Copyright (c) 2012-2016 The NAS4Free Project <info@nas4free.org>.
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
// Configure page permission
$pgperm['allowuser'] = TRUE;

require("auth.inc");
require("guiconfig.inc");
require("zfs.inc");

// Page base: r3305 => 3400
if (is_file("/usr/local/www/bar_left.gif")) $image_path = "";
else $image_path = "images/";
$EGUI_PREFIX = "/tmp/extended-gui_"; 
$config_file = "ext/extended-gui/extended-gui.conf";
require_once("ext/extended-gui/extension-lib.inc");
bindtextdomain("nas4free", "/usr/local/share/locale-egui");
if (($configuration = ext_load_config($config_file)) === false) $input_errors[] = sprintf(gettext("Configuration file %s not found!"), "extended-gui.conf");
bindtextdomain("nas4free", "/usr/local/share/locale");

$pgtitle = array(gettext("System Information"));
$pgtitle_omit = true;

if (!isset($config['vinterfaces']['carp']) || !is_array($config['vinterfaces']['carp']))
	$config['vinterfaces']['carp'] = array();

$smbios = get_smbios_info();
$cpuinfo = system_get_cpu_info();
/*	in some special cases for one-core CPUs $cpuinfo['temperature'] is empty 
 *  but $cpuinfo['temperature2'][0] holds the temperature !  
$errormsg .= "1: ".$cpuinfo['temperature']."<br />";
$errormsg .= "2: ".$cpuinfo['temperature2']."<br />";
$errormsg .= "3: ".$cpuinfo['temperature2'][0]."<br />";
$errormsg .= "4: ".$cpuinfo['temperature2'][1]."<br />";
 */
 
function get_vip_status() {
	global $config;

	if (empty($config['vinterfaces']['carp']))
		return "";

	$a_vipaddrs = array();
	foreach ($config['vinterfaces']['carp'] as $carp) {
		$ifinfo = get_carp_info($carp['if']);
		//$a_vipaddrs[] = $carp['vipaddr']." ({$ifinfo['state']},{$ifinfo['advskew']})";
		$a_vipaddrs[] = $carp['vipaddr']." ({$ifinfo['state']})";
	}
	return join(', ', $a_vipaddrs);
}

function get_ups_disp_status($ups_status) {
	if (empty($ups_status))
		return "";
	$status = explode(' ', $ups_status);
	foreach ($status as $condition) {
		if ($disp_status) $disp_status .= ', ';
		switch ($condition) {
		case 'WAIT':
			$disp_status .= gettext('UPS Waiting');
            report_ups($disp_status);
			break;
		case 'OFF':
			$disp_status .= gettext('UPS Off Line');
            report_ups($disp_status);
			break;
		case 'OL':
			$disp_status .= gettext('UPS On Line');
            report_ups($disp_status);
			break;
		case 'OB':
			$disp_status .= gettext('UPS On Battery');
            report_ups($disp_status);
			break;
		case 'TRIM':
			$disp_status .= gettext('SmartTrim');
			break;
		case 'BOOST':
			$disp_status .= gettext('SmartBoost');
			break;
		case 'OVER':
			$disp_status .= gettext('Overload');
            report_ups($disp_status);
			break;
		case 'LB':
			$disp_status .= gettext('Battery Low');
            report_ups($disp_status);
			break;
		case 'RB':
			$disp_status .= gettext('Replace Battery UPS');
            report_ups($disp_status);
			break;
		case 'CAL':
			$disp_status .= gettext('Calibration Battery');
			break;
		case 'CHRG':
			$disp_status .= gettext('Charging Battery');
			break;
		default:
			$disp_status .= $condition;
			break;
		}
	}
	return $disp_status;
}

function get_upsinfo() {
	global $config;

	if (!isset($config['ups']['enable']))
		return NULL;
	$ups = array();
	$cmd = "/usr/local/bin/upsc {$config['ups']['upsname']}@{$config['ups']['ip']}";
	exec($cmd,$rawdata);
	foreach($rawdata as $line) {
		$line = explode(':', $line);
		$ups[$line[0]] = trim($line[1]);
	}
	$disp_status = get_ups_disp_status($ups['ups.status']);
	$ups['disp_status'] = !empty($disp_status) ? "<font color=green><b>".$disp_status."&nbsp;&nbsp;</b></font>" : "<font color=red><b>".gettext("Data stale!")."&nbsp;&nbsp;</b></font>"; 
	$value = !empty($ups['ups.load']) ? $ups['ups.load'] : 0;
	$ups['load'] = array(
		"percentage" => $value,
		"used" => sprintf("%.1f", $value),
		"tooltip_used" => sprintf("%s%%", $value),
		"tooltip_available" => sprintf(gettext("%s%% available"), 100 - $value),
	);
	$value = !empty($ups['battery.charge']) ? $ups['battery.charge'] : 0;
	$ups['battery'] = array(
		"percentage" => $value,
		"used" => sprintf("%.1f", $value),
		"tooltip_used" => sprintf("%s%%", $value),
		"tooltip_available" => sprintf(gtext("%s%% available"), 100 - $value),
		"runtime" => convert_seconds($ups['battery.runtime']),
	);
	return $ups;
}

function get_upsinfo2() {
	global $config;

	if (!isset($config['ups']['enable']) || !isset($config['ups']['ups2']))
		return NULL;
	$ups = array();
	$cmd = "/usr/local/bin/upsc {$config['ups']['ups2_upsname']}@{$config['ups']['ip']}";
	exec($cmd,$rawdata);
	foreach($rawdata as $line) {
		$line = explode(':', $line);
		$ups[$line[0]] = trim($line[1]);
	}
	$disp_status = get_ups_disp_status($ups['ups.status']);
	$ups['disp_status'] = $disp_status;
	$ups['disp_status'] = !empty($disp_status) ? "<font color=green><b>".$disp_status."&nbsp;&nbsp;</b></font>" : "<font color=red><b>".gettext("Data stale!")."&nbsp;&nbsp;</b></font>";
	$value = !empty($ups['ups.load']) ? $ups['ups.load'] : 0;
	$ups['load'] = array(
		"percentage" => $value,
		"used" => sprintf("%.1f", $value),
		"tooltip_used" => sprintf("%s%%", $value),
		"tooltip_available" => sprintf(gtext("%s%% available"), 100 - $value),
	);
	$value = !empty($ups['battery.charge']) ? $ups['battery.charge'] : 0;
	$ups['battery'] = array(
		"percentage" => $value,
		"used" => sprintf("%.1f", $value),
		"tooltip_used" => sprintf("%s%%", $value),
		"tooltip_available" => sprintf(gtext("%s%% available"), 100 - $value),
		"runtime" => convert_seconds($ups['battery.runtime']),
	);
	return $ups;
}

function get_vbox_vminfo($user, $uuid) {
	$vminfo = array();
	unset($rawdata);
	mwexec2("/usr/local/bin/sudo -u {$user} /usr/local/bin/VBoxManage showvminfo --machinereadable {$uuid}", $rawdata);
	foreach ($rawdata as $line) {
		if (preg_match("/^([^=]+)=(\"([^\"]+)\"|[^\"]+)/", $line, $match)) {
			$a = array();
			$a['raw'] = $match[0];
			$a['key'] = $match[1];
			$a['value'] = isset($match[3]) ? $match[3] : $match[2];
			$vminfo[$a['key']] = $a;
		}
	}
	return $vminfo;
}

function get_xen_info() {
	$info = array();
	unset($rawdata);
	mwexec2("/usr/local/sbin/xl info", $rawdata);
	foreach ($rawdata as $line) {
		if (preg_match("/^([^:]+)\s+:\s+(.+)\s*$/", $line, $match)) {
			$a = array();
			$a['raw'] = $match[0];
			$a['key'] = trim($match[1]);
			$a['value'] = trim($match[2]);
			$info[$a['key']] = $a;
		}
	}
	return $info;
}

function get_xen_console($domid) {
	$info = array();
	unset($rawdata);
	mwexec2("/usr/local/bin/xenstore-ls /local/domain/{$domid}/console", $rawdata);
	foreach ($rawdata as $line) {
		if (preg_match("/^([^=]+)\s+=\s+\"(.+)\"$/", $line, $match)) {
			$a = array();
			$a['raw'] = $match[0];
			$a['key'] = trim($match[1]);
			$a['value'] = trim($match[2]);
			$info[$a['key']] = $a;
		}
	}
	return $info;
}

function report_ups($disp_status) {
    global $EGUI_PREFIX;
     if (($disp_status != gettext('UPS On Line')) && !is_file("{$EGUI_PREFIX}UPS_error.lock")) {
        $now = str_replace("\n", "", shell_exec('date "+%Y.%m.%d %H:%M:%S"'));
        file_put_contents("{$EGUI_PREFIX}system_error.msg", "{$now} ERROR UPS {$disp_status}\n", FILE_APPEND );    # create system error message
        touch("{$EGUI_PREFIX}UPS_error.lock");
    }
    else if (is_file("{$EGUI_PREFIX}UPS_error.lock") && ($disp_status == gettext('UPS On Line'))) {
        unlink("{$EGUI_PREFIX}UPS_error.lock");   # to reset UPS error display lock
    }
}

function egui_get_indexrefresh() {
    global $EGUI_PREFIX, $config, $configuration;
    $indexrefresh['reason'] = "";
    $indexrefresh['message'] = "";
    if (is_file("{$EGUI_PREFIX}index.refresh")) {
        unlink("{$EGUI_PREFIX}index.refresh");
        write_log("extended-gui: autmount refresh");
        $indexrefresh['reason'] = "automount";
        $indexrefresh['message'] = "USB auto-mounted";
    }
    if ($configuration['system_warnings']) {
        if (is_file("{$EGUI_PREFIX}system_error.msg")) {
            $indexrefresh['reason'] = "system_error";
            $indexrefresh['message'] = str_replace("degreeC", " C ", shell_exec("cat {$EGUI_PREFIX}system_error.msg"));
            $a_search = array("\n", " C ");
            $a_replace = array("<br />", "&deg;C");
            file_put_contents("{$EGUI_PREFIX}system_error.msg.locked", str_replace($a_search, $a_replace, $indexrefresh['message']), FILE_APPEND );
            unlink("{$EGUI_PREFIX}system_error.msg");
        }
    }
    return $indexrefresh;
}

function egui_get_userinfo() {
    global $EGUI_PREFIX;
    $userinfo = exec("cat {$EGUI_PREFIX}user_online.log");
    return $userinfo;
}

function egui_get_hostsinfo() {
    global $EGUI_PREFIX;
    $hostsinfo = exec("cat {$EGUI_PREFIX}hosts_online.log");
    return $hostsinfo;
}

function egui_get_mount_usage() {
	global $config, $g, $configuration;

	$result = array();
	exec("/bin/df -h", $rawdata);
    exec("df -h | awk '/^\/dev\// && /\/mnt\// {print $6}'| cut -d/ -f3", $sharenames);
    if (isset($configuration['shares'])) foreach($configuration['shares'] as $a_share) $sharenames[] = $a_share; // put file systems and datasets in sharenames
	foreach ($rawdata as $line) {
		if (0 == preg_match("/^(\S+)\s+(\S+)\s+(\S+)\s+(\S+)\s+(\d+%)\s+(.+)/", $line, $aline)) continue;
		$filesystem = chop($aline[1]);
		$size = chop($aline[2]);
		$used = chop($aline[3]);
		$avail = chop($aline[4]);
		$capacity = chop($aline[5]);
		$mountpoint = chop($aline[6]);
		foreach ($sharenames as $mountcfg) {
            if ($configuration['boot'] && ($mountpoint == "/")) { $mountpoint = "/mnt/A_OS"; }
            if ($configuration['varfs'] && ($mountpoint == "/var")) { $mountpoint = "/mnt/A_VAR"; }
            if ($configuration['usrfs'] && ($mountpoint == "/usr/local")) { $mountpoint = "/mnt/A_USR"; }
			if (0 == strcmp($mountpoint, "{$g['media_path']}/{$mountcfg}")) {
				$result[$mountpoint] = array();
                $result[$mountpoint]['id'] = str_replace('/', '', $mountcfg);;  // derived from get_disk_usage
				$result[$mountpoint]['mountpoint'] = $mountpoint;
				$result[$mountpoint]['name'] = $mountcfg;
				$result[$mountpoint]['filesystem'] = $filesystem;
				$result[$mountpoint]['capacity'] = $capacity;
                $result[$mountpoint]['percentage'] = rtrim($capacity, "%");     // derived from get_disk_usage
				$result[$mountpoint]['size'] = $size;
				$result[$mountpoint]['used'] = $used;
				$result[$mountpoint]['avail'] = $avail;
                $result[$mountpoint]['capofsize'] = sprintf(gettext("%s of %sB"), $result[$mountpoint]['capacity'], $result[$mountpoint]['size']);
                $result[$mountpoint]['tooltip']['used'] = sprintf(gettext("%sB used of %sB"), $result[$mountpoint]['used'], $result[$mountpoint]['size']);
                $result[$mountpoint]['tooltip']['avail'] = sprintf(gettext("%sB available of %sB"), $result[$mountpoint]['avail'], $result[$mountpoint]['size']);
			}
		}
	}
	return $result;
}
 
function egui_get_disk_usage() {
	$value = array();
	$a_diskusage = egui_get_mount_usage();
	if (is_array($a_diskusage) && (0 < count($a_diskusage))) {
		foreach ($a_diskusage as $diskusagek => $diskusagev) {
			$value[] = $diskusagev;
		}
	}
	return $value;
}

if (is_ajax()) {
	$sysinfo = system_get_sysinfo();
	$vipstatus = get_vip_status();
	$sysinfo['vipstatus'] = $vipstatus;
	$upsinfo = get_upsinfo();
	$upsinfo2 = get_upsinfo2();
	$sysinfo['upsinfo'] = $upsinfo;
	$sysinfo['upsinfo2'] = $upsinfo2;
	$sysinfo['indexrefresh'] = egui_get_indexrefresh();
	$sysinfo['userinfo'] = egui_get_userinfo();
	$sysinfo['hostsinfo'] = egui_get_hostsinfo();
	$sysinfo['diskusage'] = egui_get_disk_usage();
    if (is_array($sysinfo['diskusage'])) {
        for ($i = 0; $i < count($sysinfo['diskusage']); ++$i) {
            $mountpoint_details = explode('##', exec("cat {$EGUI_PREFIX}{$sysinfo['diskusage'][$i]['name']}.smart"));
            $sysinfo['diskusage'][$i]['space'] = $mountpoint_details[1];
            $pool_details = explode('#', $mountpoint_details[0]);
            foreach($pool_details as $a_pool) {
                $smart_details = explode('|', $a_pool);
                $a_pool_details['device'] = $smart_details[0];
                $a_pool_details['smart_state'] = $smart_details[1];
                $a_pool_details['temp'] = $smart_details[2];
                $sysinfo['diskusage'][$i]['devs'][] = $a_pool_details;
            }
        }
    }
    if (is_array($sysinfo['poolusage'])) {
         for ($i = 0; $i < count($sysinfo['poolusage']); ++$i) {
            $mountpoint_details = explode('##', exec("cat {$EGUI_PREFIX}{$sysinfo['poolusage'][$i]['name']}.smart"));
            $sysinfo['poolusage'][$i]['space'] = $mountpoint_details[1];
            $pool_details = explode('#', $mountpoint_details[0]);
            foreach($pool_details as $a_pool) {
                $smart_details = explode('|', $a_pool);
                $a_pool_details['device'] = $smart_details[0];
                $a_pool_details['smart_state'] = $smart_details[1];
                $a_pool_details['temp'] = $smart_details[2];
                $sysinfo['poolusage'][$i]['devs'][] = $a_pool_details;
            }
        }
    }
	render_ajax($sysinfo);
}

function convert_seconds ($value) {
    if ($value > 60) {
		$minutes = (int) ($value / 60);
		$seconds = $value % 60;

		if ($minutes > 60) {
			$hours = (int) ($minutes / 60);
			$minutes = $minutes % 60;
			$value = $hours.' hours '.$minutes.' minutes '.$seconds;
		} else {
			$value = $minutes.' minutes '.$seconds;
		}
    }
    return $value.' seconds';
}

function tblrow ($name, $value, $symbol = null, $id = null) {
	if(!$value) return;

   	if($symbol == '&deg;C')
		$value = sprintf("%.1f", $value);

	if($symbol == 'Hz')
		$value = sprintf("%d", $value);

	
	if ($symbol == 'pre') {
		$value = '<pre>'.$value;
		$symbol = '</pre>';
	}

	print(<<<EOD
	<td>
        <div id='ups_status'>
			<span name='ups_status_name' id='ups_status_name' class='name'> || <b>{$name}:&nbsp;</b></span>
            {$value}{$symbol}
        </div>
    </td>
EOD
	."\n");
}

function tblrowbar ($id, $name, $value) {
    global $image_path;
		if(is_null($value)) return;
		$available = 100 - $value;
		$tooltip_used = sprintf("%s%%", $value);
		$tooltip_available = sprintf(gettext("%s%% available"), $available);
		$span_used = sprintf("%s%%", "<span name='ups_status_used' id='ups_status_{$id}_used' class='capacity'>".$value."</span>");

	print(<<<EOD
  <td>
	<div id='ups_status'>
		<span name='ups_status_name' id='ups_status_{$id}_name' class='name'><b>{$name}</b>&nbsp;&nbsp;</span>
  </td><td>
        <img src="{$image_path}bar_left.gif" class="progbarl" alt="" /><img src="{$image_path}bar_blue.gif" name="ups_status_bar_used" id="ups_status_{$id}_bar_used" width="{$value}" class="progbarcf" title="{$tooltip_used}" alt="" /><img src="{$image_path}bar_gray.gif" name="ups_status_bar_free" id="ups_status_{$id}_bar_free" width="{$available}" class="progbarc" title="{$tooltip_available}" alt="" /><img src="{$image_path}bar_right.gif" class="progbarr" alt="" />
		<td style='text-align:right;'>&nbsp;&nbsp;{$span_used}</td>
	</div>
  </td>
EOD
	."\n");
}

if(function_exists("date_default_timezone_set") and function_exists("date_default_timezone_get"))
     @date_default_timezone_set(@date_default_timezone_get());

if ($_POST['clear_alarms']) {
    if (file_exists("{$EGUI_PREFIX}cpu.alarm")) { unlink("{$EGUI_PREFIX}cpu.alarm"); }
    if (file_exists("{$EGUI_PREFIX}zfs.alarm")) { unlink("{$EGUI_PREFIX}zfs.alarm"); }
}

if ($_POST['clear_history']) {
    if (is_file("{$EGUI_PREFIX}system_error.msg.locked")) unlink("{$EGUI_PREFIX}system_error.msg.locked");
}

bindtextdomain("nas4free", "/usr/local/share/locale-egui");
if ($_POST['umount']) {
    $retval = mwexec("/var/scripts/automount_usb.sh umount", true);
    if ($retval <> 0) {
        $errormsg .= gettext("Cannot unmount USB device(s), device(s) is/are busy (maybe open files or directories)!");
		$errormsg .= "<br />\n";
    }
}
if ($_POST['rmount']) {
    $retval = mwexec("/var/scripts/automount_usb.sh rmount", true);
    if ($retval > 20) {
        $errormsg .= gettext("Cannot remount USB device(s), look at <b>Diagnose | Log | Notifications</b> for more information!");
		$errormsg .= "<br />\n";
    }
}    
if ($_POST['purge']) { exec("/var/scripts/purge.sh 0"); }

if ($configuration['system_warnings'] && is_file("{$EGUI_PREFIX}system_error.msg.locked")) { 
    $errormsg .= shell_exec("cat {$EGUI_PREFIX}system_error.msg.locked");
}

if (isset($_GET['standby_drive'])) {
    $retval = mwexec("camcontrol standby {$_GET['standby_drive']} && /var/scripts/disk_check.sh", true);        
    if ($retval <> 0) {
        $errormsg .= sprintf(gettext("Cannot set drive %s to standby!"), $_GET['standby_drive']);
    	$errormsg .= "<br />\n";
    }
}
bindtextdomain("nas4free", "/usr/local/share/locale");

?>
<?php include("fbegin.inc");?>
<script type="text/javascript">//<![CDATA[
function set_standby(drive) {
    location.href = "index.php?standby_drive="+drive;
}

$(document).ready(function(){
	var gui = new GUI;
	gui.recall(5000, 5000, 'index.php', null, function(data) {
		if (data.indexrefresh.reason == 'automount') location.assign("index.php");
		if (data.indexrefresh.reason == 'system_error') {
            alert(data.indexrefresh.message);
            location.assign("index.php");
        }
		if ($('#userinfo').length > 0) $('#userinfo').html(data.userinfo);
		if ($('#hostsinfo').length > 0)$('#hostsinfo').html(data.hostsinfo);

		if ($('#vipstatus').length > 0)
			$('#vipstatus').text(data.vipstatus);
		if ($('#system_uptime').length > 0)
			$('#system_uptime').text(data.uptime);
		if ($('#system_datetime').length > 0)
			$('#system_datetime').text(data.date);
		if ($('#memusage').length > 0) {
			$('#memusage').val(data.memusage.caption);
			$('#memusageu').attr('width', data.memusage.percentage + 'px');
			$('#memusagef').attr('width', (100 - data.memusage.percentage) + 'px');
		}
		if ($('#loadaverage').length > 0)
			$('#loadaverage').val(data.loadaverage);
		if (typeof(data.cputemp) != 'undefined')
			if ($('#cputemp').length > 0)
				$('#cputemp').val(data.cputemp);
		if (typeof(data.cputemp2) != 'undefined') {
			for (var idx = 0; idx < data.cputemp2.length; idx++) {
				if ($('#cputemp'+idx).length > 0)
					$('#cputemp'+idx).val(data.cputemp2[idx]);
			}
		}
		if (typeof(data.cpufreq) != 'undefined')
			if ($('#cpufreq').length > 0)
				$('#cpufreq').val(data.cpufreq + 'MHz');
		if (typeof(data.cpuusage) != 'undefined') {
			if ($('#cpuusage').length > 0) {
				$('#cpuusage').val(data.cpuusage + '%');
				$('#cpuusageu').attr('width', data.cpuusage + 'px');
				$('#cpuusagef').attr('width', (100 - data.cpuusage) + 'px');
			}
		}
		if (typeof(data.cpuusage2) != 'undefined') {
			for (var idx = 0; idx < data.cpuusage2.length; idx++) {
				if ($('#cpuusage'+idx).length > 0) {
					$('#cpuusage'+idx).val(data.cpuusage2[idx] + '%');
					$('#cpuusageu'+idx).attr('width', data.cpuusage2[idx] + 'px');
					$('#cpuusagef'+idx).attr('width', (100 - data.cpuusage2[idx]) + 'px');
				}
			}
		}

		if (typeof(data.diskusage) != 'undefined') {
			for (var idx = 0; idx < data.diskusage.length; idx++) {
				var du = data.diskusage[idx];
				if ($('#diskusage_'+du.id+'_bar_used').length > 0) {
					$('#diskusage_'+du.id+'_name').text(du.name);
					$('#diskusage_'+du.id+'_bar_used').attr('width', du.percentage + 'px');
					$('#diskusage_'+du.id+'_bar_used').attr('title', du['tooltip'].used);
					$('#diskusage_'+du.id+'_bar_free').attr('width', (100 - du.percentage) + 'px');
					$('#diskusage_'+du.id+'_bar_free').attr('title', du['tooltip'].avail);
					$('#diskusage_'+du.id+'_capacity').text(du.capacity);
					$('#diskusage_'+du.id+'_capofsize').text(du.capofsize);
					$('#diskusage_'+du.id+'_size').text(du.size);
					$('#diskusage_'+du.id+'_used').text(du.used);
					$('#diskusage_'+du.id+'_avail').text(du.avail);
          			for (var idx1 = 0; idx1 < du.devs.length; idx1++) {
        				var devs = du.devs[idx1];
     					$('#diskusage_'+du.id+'_'+idx1+'_device').html(devs.device);
    					$('#diskusage_'+du.id+'_'+idx1+'_smart_state').html(devs.smart_state);
    					$('#diskusage_'+du.id+'_'+idx1+'_temp').html(devs.temp);
                    }
					$('#diskusage_'+du.id+'_space').html(du.space);
				}
			}
		}
		if (typeof(data.poolusage) != 'undefined') {
			for (var idx = 0; idx < data.poolusage.length; idx++) {
				var pu = data.poolusage[idx];
				if ($('#poolusage_'+pu.id+'_bar_used').length > 0) {
					$('#poolusage_'+pu.id+'_name').text(pu.name);
					$('#poolusage_'+pu.id+'_bar_used').attr('width', pu.percentage + 'px');
					$('#poolusage_'+pu.id+'_bar_used').attr('title', pu['tooltip'].used);
					$('#poolusage_'+pu.id+'_bar_free').attr('width', (100 - pu.percentage) + 'px');
					$('#poolusage_'+pu.id+'_bar_free').attr('title', pu['tooltip'].avail);
					$('#poolusage_'+pu.id+'_capacity').text(pu.capacity);
					$('#poolusage_'+pu.id+'_capofsize').text(pu.capofsize);
					$('#poolusage_'+pu.id+'_size').text(pu.size);
					$('#poolusage_'+pu.id+'_used').text(pu.used);
					$('#poolusage_'+pu.id+'_avail').text(pu.avail);
          			for (var idx1 = 0; idx1 < pu.devs.length; idx1++) {
        				var devs = pu.devs[idx1];
     					$('#poolusage_'+pu.id+'_'+idx1+'_device').html(devs.device);
    					$('#poolusage_'+pu.id+'_'+idx1+'_smart_state').html(devs.smart_state);
    					$('#poolusage_'+pu.id+'_'+idx1+'_temp').html(devs.temp);
                    }
					$('#poolusage_'+pu.id+'_space').html(pu.space);
					$('#poolusage_'+pu.id+'_state').text(pu.health);
				}
			}
		}
		if (typeof(data.swapusage) != 'undefined') {
			for (var idx = 0; idx < data.swapusage.length; idx++) {
				var su = data.swapusage[idx];
				if ($('#swapusage_'+su.id+'_bar_used').length > 0) {
//					$('#swapusage_'+su.id+'_name').text(su.name);
					$('#swapusage_'+su.id+'_bar_used').attr('width', su.percentage + 'px');
					$('#swapusage_'+su.id+'_bar_used').attr('title', su['tooltip'].used);
					$('#swapusage_'+su.id+'_bar_free').attr('width', (100 - su.percentage) + 'px');
					$('#swapusage_'+su.id+'_bar_free').attr('title', su['tooltip'].avail);
					$('#swapusage_'+su.id+'_capacity').text(su.capacity);
					$('#swapusage_'+su.id+'_capofsize').text(su.capofsize);
					$('#swapusage_'+su.id+'_size').text(su.size);
					$('#swapusage_'+su.id+'_used').text(su.used);
					$('#swapusage_'+su.id+'_avail').text(su.avail);
				}
			}
		}
		if (typeof(data.upsinfo) != 'undefined' && data.upsinfo !== null) {
			if ($('#ups_status_disp_status').length > 0)
				$('#ups_status_disp_status').html(data.upsinfo.disp_status);    //@AFS 'html' to display colored output
			var ups_id = "load";
			var ui = data.upsinfo[ups_id];
			if ($('#ups_status_'+ups_id+'_bar_used').length > 0) {
				$('#ups_status_'+ups_id+'_bar_used').attr('width', ui.percentage + 'px');
				$('#ups_status_'+ups_id+'_bar_used').attr('title', ui.tooltip_used);
				$('#ups_status_'+ups_id+'_bar_free').attr('width', (100 - ui.percentage) + 'px');
				$('#ups_status_'+ups_id+'_bar_free').attr('title', ui.tooltip_available);
				$('#ups_status_'+ups_id+'_used').text(ui.used);
			}
			var ups_id = "battery";
			var ui = data.upsinfo[ups_id];
			if ($('#ups_status_'+ups_id+'_bar_used').length > 0) {
				$('#ups_status_'+ups_id+'_bar_used').attr('width', ui.percentage + 'px');
				$('#ups_status_'+ups_id+'_bar_used').attr('title', ui.tooltip_used);
				$('#ups_status_'+ups_id+'_bar_free').attr('width', (100 - ui.percentage) + 'px');
				$('#ups_status_'+ups_id+'_bar_free').attr('title', ui.tooltip_available);
				$('#ups_status_'+ups_id+'_used').text(ui.used);
				$('#ups_status_'+ups_id+'_runtime').text(ui.runtime);
			}
		}
		if (typeof(data.upsinfo2) != 'undefined' && data.upsinfo2 !== null) {
			if ($('#ups_status_disp_status2').length > 0)
				$('#ups_status_disp_status2').html(data.upsinfo2.disp_status);    //@AFS 'html' to display colored output
			var ups_id = "load2";
			var ui = data.upsinfo2["load"];
			if ($('#ups_status_'+ups_id+'_bar_used').length > 0) {
				$('#ups_status_'+ups_id+'_bar_used').attr('width', ui.percentage + 'px');
				$('#ups_status_'+ups_id+'_bar_used').attr('title', ui.tooltip_used);
				$('#ups_status_'+ups_id+'_bar_free').attr('width', (100 - ui.percentage) + 'px');
				$('#ups_status_'+ups_id+'_bar_free').attr('title', ui.tooltip_available);
				$('#ups_status_'+ups_id+'_used').text(ui.used);
			}
			var ups_id = "battery2";
			var ui = data.upsinfo2["battery"];
			if ($('#ups_status_'+ups_id+'_bar_used').length > 0) {
				$('#ups_status_'+ups_id+'_bar_used').attr('width', ui.percentage + 'px');
				$('#ups_status_'+ups_id+'_bar_used').attr('title', ui.tooltip_used);
				$('#ups_status_'+ups_id+'_bar_free').attr('width', (100 - ui.percentage) + 'px');
				$('#ups_status_'+ups_id+'_bar_free').attr('title', ui.tooltip_available);
				$('#ups_status_'+ups_id+'_used').text(ui.used);
				$('#ups_status_'+ups_id+'_runtime').text(ui.runtime);
			}
		}
	});
});
//]]>
</script>
<table width="100%" border="0" cellspacing="0" cellpadding="0">
<td>&nbsp;</td>
</table>
<?php
	// make sure normal user such as www can write to temporary
	$perms = fileperms("/tmp");
	if (($perms & 01777) != 01777) {
		$errormsg .= sprintf(gettext("Wrong permission on %s."), "/tmp");
		$errormsg .= "<br />\n";
	}
	$perms = fileperms("/var/tmp");
	if (($perms & 01777) != 01777) {
		$errormsg .= sprintf(gettext("Wrong permission on %s."), "/var/tmp");
		$errormsg .= "<br />\n";
	}
	// check DNS
	list($v4dns1,$v4dns2) = get_ipv4dnsserver();
	list($v6dns1,$v6dns2) = get_ipv6dnsserver();
	if (empty($v4dns1) && empty($v4dns2) && empty($v6dns1) && empty($v6dns2)) {
		// need by service/firmware check?
		if (!isset($config['system']['disablefirmwarecheck'])
		   || isset($config['ftpd']['enable'])) {
			$errormsg .= gettext("No DNS setting found.");
			$errormsg .= "<br />\n";
		}
	}
	if (!empty($errormsg)) print_error_box($errormsg);

$swapinfo = system_get_swap_info();
$cpus = system_get_cpus();
$rowcounter = 13;       // set/get # of rows for graphs
if (!empty($config['vinterfaces']['carp'])) { ++$rowcounter; }
if (!empty($cpuinfo['freq'])) { ++$rowcounter; } 
if (!empty($swapinfo)) { ++$rowcounter; }	
if (($cpus == 1) && (!empty($cpuinfo['temperature']) || !empty($cpuinfo['temperature2']))) { ++$rowcounter; }
if ($cpus > 1) { ++$rowcounter; }
if ($configuration['hide_cpu'] && ($cpus > 1)) { $rowcounter = $rowcounter - 2; }
if ($configuration['hide_cpu'] && ($cpus == 1)) { --$rowcounter; }
if ($configuration['hide_cpu_usage']  && ($cpus > 1) && !$configuration['hide_cpu']) { --$rowcounter; }
if ($configuration['user_defined']['use_buttons'] && !is_file($configuration['user_defined']['buttons_file'])) {
	bindtextdomain("nas4free", "/usr/local/share/locale-egui");
	print_error_box(sprintf(gettext("Configuration file %s not found!"), $configuration['user_defined']['buttons_file']));
	bindtextdomain("nas4free", "/usr/local/share/locale");
}
 ?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td class="tabcont">
    <table width="100%" border="0" cellspacing="0" cellpadding="0">
    <tr>
        <td colspan="3" class="listtopic"><?=gettext("System Information");?></td>
    </tr>
    <tr>
        <?php if ($configuration['hide_cpu_graph'] && $configuration['hide_lan_graph']) :?>
            <td width="25%" class="vncellt"><?=gettext("Hostname");?></td>
        <?php else :?>
            <td width="35%" class="vncellt"><?=gettext("Hostname");?></td>
        <?php endif;?>
        <td class="listr"><?=system_get_hostname();?></td>
        <?php if (Session::isAdmin()):?>
            <?php if (!$configuration['hide_cpu_graph'] || !$configuration['hide_lan_graph']) { ?>
                <td height="100%" align="center" rowspan="<?=$rowcounter;?>" class="listr">
                    <table width="100%" border="0" cellpadding="0" cellspacing="0">
                    <?php if (!$configuration['hide_cpu_graph']) { ?>
                        <tr><td style="text-center; vertical-align: middle; height: 200px" align="center">
                            <object id="graph" data="graph_index_cpu.php" type="image/svg+xml" width="300" height="150">
                                <param name="src" value="index.php" />
                            </object>
                        </td></tr>
                    <?php } ?>
                    <?php if (!$configuration['hide_lan_graph']) { ?>
                        <tr><td style="text-center; vertical-align: middle; height: 200px" align="center">
                            <form name="form2" action="index.php" method="get">
                            <select name="if" class="formfld" onchange="submit()">
                            <?php
                                $curif = "lan";
                                if (isset($_GET['if']) && $_GET['if']) $curif = $_GET['if'];
                                $ifnum = get_ifname($config['interfaces'][$curif]['if']);
                                $ifdescrs = array('lan' => 'LAN');
                                for ($j = 1; isset($config['interfaces']['opt' . $j]); $j++) {
                                	$ifdescrs['opt' . $j] = $config['interfaces']['opt' . $j]['descr'];
                                }
                                foreach ($ifdescrs as $ifn => $ifd) {
                                	echo "<option value=\"$ifn\"";
                                	if ($ifn == $curif) echo " selected=\"selected\"";
                                	echo ">" . htmlspecialchars($ifd) . "</option>\n";
                                }
                            ?>
                            </select>
                            </form>
                            <object id="graph1" align="center" data="graph_index.php?ifnum=<?=$ifnum;?>&amp;ifname=<?=rawurlencode($ifdescrs[$curif]);?>" type="image/svg+xml" width="300" height="150">
                                <param name="src" value="index.php?ifnum=<?=$ifnum;?>&amp;ifname=<?=rawurlencode($ifdescrs[$curif]);?>" />
                            </object>
                        </td></tr>
                    <?php } ?>
                    </table>
                </td>
            <?php } ?>
        <?php endif;?>
    </tr>
	<?php if (!empty($config['vinterfaces']['carp'])):?>
	<?php html_textinfo("vipstatus", gettext("Virtual IP address"), htmlspecialchars(get_vip_status()));?>
	<?php endif;?>
	<?php html_textinfo("version", gettext("Version"), sprintf("<strong>%s %s</strong> (%s %s)", get_product_version(), get_product_versionname(), gettext("revision"), get_product_revision()));?>
	<?php html_textinfo("builddate", gettext("Compiled"), htmlspecialchars(get_datetime_locale(get_product_buildtimestamp())));?>
	<?php
		exec("/sbin/sysctl -n kern.version", $osversion);
	?>
    <?php html_textinfo("platform_os", gtext("Platform OS"), sprintf("%s", $osversion[0]));?>
	<?php html_textinfo("platform", gettext("Platform"), sprintf(gettext("%s on %s"), $g['fullplatform'], $cpuinfo['model']));?>
	<?php
		if (!empty($smbios['planar'])) {
			html_textinfo("system", gettext("System"), sprintf("%s %s", htmlspecialchars($smbios['planar']['maker']), htmlspecialchars($smbios['planar']['product'])));
		} else {
			html_textinfo("system", gettext("System"), sprintf("%s %s", htmlspecialchars($smbios['system']['maker']), htmlspecialchars($smbios['system']['product'])));
		}
	?>
	<?php html_textinfo("system_bios", gettext("System BIOS"), sprintf("%s %s %s %s", htmlspecialchars($smbios['bios']['vendor']), gettext("version:"), htmlspecialchars($smbios['bios']['version']), htmlspecialchars($smbios['bios']['reldate'])));?>
	<?php html_textinfo("system_datetime", gettext("System Time"), htmlspecialchars(get_datetime_locale()));?>
	<?php html_textinfo("system_uptime", gettext("System Uptime"), htmlspecialchars(system_get_uptime()));?>
    <?php if (Session::isAdmin()):?>
		<?php if ($config['lastchange']):?>
			<?php html_textinfo("last_config_change", gettext("Last Configuration Change"), htmlspecialchars(get_datetime_locale($config['lastchange'])));?>
		<?php endif;?>
		<?php 
			if (($cpus == 1) && (!empty($cpuinfo['temperature']) || !empty($cpuinfo['temperature2']))) { 
				echo "<tr><td width='25%' class='vncellt'>".gettext("CPU Temperature")."</td>";
				echo "<td class='listr'>";
				if (!empty($cpuinfo['temperature'])) {
					echo "<input style='padding: 0; border: 0;' size='1' id='cputemp' value='".htmlspecialchars($cpuinfo['temperature'])."' />&deg;C";
				}
				else echo "<input style='padding: 0; border: 0;' size='1' id='cputemp0' value='".htmlspecialchars($cpuinfo['temperature2'][0])."' />&deg;C";				    
				echo "</td></tr>";
			}
		?>
		<?php if (!empty($cpuinfo['freq'])):?>
			<tr>
				<td width="25%" class="vncellt"><?=gettext("CPU Frequency");?></td>
				<td width="75%" class="listr">
					<input style="padding: 0; border: 0; background-color:#FCFCFC;" size="30" name="cpufreq" id="cpufreq" value="<?=htmlspecialchars($cpuinfo['freq']);?>MHz" title="<?=sprintf(gettext("Levels (MHz/mW): %s"), $cpuinfo['freqlevels']);?>" />
				</td>
			</tr>
		<?php endif;?>
<?php if (!$configuration['hide_cpu']) { ?>
		<tr>
			<td width="25%" class="vncellt"><?=gettext("CPU Usage");?></td>
			<td width="75%" class="listr">
			<?php
				$gt_core = gettext('Core');
				$gt_temp = gettext('Temp');
				$percentage = 0;
				echo "<img src='{$image_path}bar_left.gif' class='progbarl' alt='' />";
				echo "<img src='{$image_path}bar_blue.gif' name='cpuusageu' id='cpuusageu' width='" . $percentage . "' class='progbarcf' alt='' />";
				echo "<img src='{$image_path}bar_gray.gif' name='cpuusagef' id='cpuusagef' width='" . (100 - $percentage) . "' class='progbarc' alt='' />";
				echo "<img src='{$image_path}bar_right.gif' class='progbarr' alt='' /> ";
				echo "<input style='padding: 0; border: 0;' name='cpuusage' id='cpuusage' value='???' />";
			?>
			</td>
		</tr>
			<?php
            if (!$configuration['hide_cpu_usage']) {
				$cpus = system_get_cpus();
				if ($cpus > 1) {
					echo "<tr><td width='25%' class='vncellt'>".gettext('Core Usage')."</td><td width='75%' class='listr'>";
					for ($idx = 0; $idx < $cpus; $idx++) {
						$percentage = 0;
						echo "<span style='white-space:nowrap; display:inline-block; width:300px'>";
						echo "<img src='{$image_path}bar_left.gif' class='progbarl' alt='' />";
						echo "<img src='{$image_path}bar_blue.gif' name='cpuusageu${idx}' id='cpuusageu${idx}' width='" . $percentage . "' class='progbarcf' alt='' />";
						echo "<img src='{$image_path}bar_gray.gif' name='cpuusagef${idx}' id='cpuusagef${idx}' width='" . (100 - $percentage) . "' class='progbarc' alt='' />";
						echo "<img src='{$image_path}bar_right.gif' class='progbarr' alt='' /> ";
						echo "{$gt_core} {$idx}: ";
						echo "<input style='padding: 0; border: 0;' size='3' name='cpuusage${idx}' id='cpuusage${idx}' value='???' />";
                        if (!empty($cpuinfo['temperature2'][$idx])) {
							echo "{$gt_temp}: ";
							echo "<input style='padding: 0; border: 0; text-align:right' size='1' id='cputemp${idx}' value='???' />&deg;C";
						}
						echo "</span>";
					}
					echo "</td></tr>";
				}
			}
			?>
		</tr>
<?php } ?>
		<tr>
			<td width="25%" class="vncellt"><?=gettext("Memory Usage");?></td>
			<td width="75%" class="listr">
			<?php
				$raminfo = system_get_ram_info();
				$percentage = round(($raminfo['used'] * 100) / $raminfo['total'], 0);
				echo "<img src='{$image_path}bar_left.gif' class='progbarl' alt='' />";
				echo "<img src='{$image_path}bar_blue.gif' name='memusageu' id='memusageu' width='" . $percentage . "' class='progbarcf' alt='' />";
				echo "<img src='{$image_path}bar_gray.gif' name='memusagef' id='memusagef' width='" . (100 - $percentage) . "' class='progbarc' alt='' />";
				echo "<img src='{$image_path}bar_right.gif' class='progbarr' alt='' /> ";
			?>
			<input style="padding: 0; border: 0; background-color:#FCFCFC;" size="30" name="memusage" id="memusage" value="<?=sprintf(gettext("%d%% of %dMiB"), $percentage, round($raminfo['physical'] / 1024 / 1024));?>" />
			</td>
		</tr>
		<?php $a_swapusage = get_swap_usage(); if (!empty($a_swapusage)):?>
		<tr>
			<td width="25%" class="vncellt"><?=gettext("Swap Usage");?></td>
			<td width="75%" class="listr">
			<table width="100%" border="0" cellspacing="0" cellpadding="1">
			<?php
				$index = 0;
				foreach ($a_swapusage as $r_swapusage) {
					$ctrlid = $r_swapusage['id'];
					$percent_used = $r_swapusage['percentage'];
					$tooltip_used = $r_swapusage['tooltip']['used'];
					$tooltip_avail = $r_swapusage['tooltip']['avail'];
					$r_swapusage['name'] = str_replace("/dev/", "", trim($r_swapusage['name']));

					echo "<tr><td nowrap><div id='swapusage'>";
					echo "<img src='{$image_path}bar_left.gif' class='progbarl' alt='' />";
					echo "<img src='{$image_path}bar_blue.gif' name='swapusage_{$ctrlid}_bar_used' id='swapusage_{$ctrlid}_bar_used' width='{$percent_used}' class='progbarcf' title='{$tooltip_used}' alt='' />";
					echo "<img src='{$image_path}bar_gray.gif' name='swapusage_{$ctrlid}_bar_free' id='swapusage_{$ctrlid}_bar_free' width='" . (100 - $percent_used) . "' class='progbarc' title='{$tooltip_avail}' alt='' />";
					echo "<img src='{$image_path}bar_right.gif' class='progbarr' alt='' /> ";
					echo "<span name='swapusage_{$ctrlid}_capofsize' id='swapusage_{$ctrlid}_capofsize' class='capofsize'>{$r_swapusage['capofsize']}</span>";
					echo " || ";
					echo sprintf(gettext("Device: %s | Total: %s | Used: %s | Free: %s"),
						"<span name='swapusage_{$ctrlid}_name' id='swapusage_{$ctrlid}_name' class='name'>{$r_swapusage['name']}</span>",
						"<span name='swapusage_{$ctrlid}_size' id='swapusage_{$ctrlid}_size' class='size'>{$r_swapusage['size']}</span>",
						"<span name='swapusage_{$ctrlid}_used' id='swapusage_{$ctrlid}_used' class='used' style='color:blue'>{$r_swapusage['used']}</span>",
						"<span name='swapusage_{$ctrlid}_avail' id='swapusage_{$ctrlid}_avail' class='avail' style='color:green'>{$r_swapusage['avail']}</span>");
					echo "</div></td></tr>";

					if (++$index < count($a_swapusage))
						echo "<tr><td><hr size='1' /></td></tr>\n";
				}
			?>
			</table></td>
		</tr>
		<?php endif;?>
		<tr>
			<td width="25%" class="vncellt"><?=gettext("Load averages");?></td>
			<td width="75%" class="listr">
			<?php
				exec("uptime", $result);
				$loadaverage = substr(strrchr($result[0], "load averages:"), 15);
				?>
				<input style="padding: 0; border: 0; background-color:#FCFCFC;" size="14" name="loadaverage" id="loadaverage" value="<?=$loadaverage;?>" />
				<?="<small>[<a href='status_process.php'>".gettext("Show Process Information")."</a></small>]";?>
			</td>
		</tr>
		<tr>
    	    <td width="25%" class="vncellt"><?=gettext("Disk Space Usage");?></td>
    	    <td class="listr" colspan="2">
            <table border="0" cellspacing="0" cellpadding="1">
    		<?php
    		    $a_diskusage = egui_get_mount_usage();
    		      	array_sort_key($a_diskusage, "name");
    		      	$index = 0;
    				foreach ($a_diskusage as $diskusagek => $diskusagev) {
			            $ctrlid = str_replace('/', '', $diskusagev['name']);     // remove '/' to get javascript running
                        $mountpoint_details = explode('##', exec("cat {$EGUI_PREFIX}{$diskusagev['name']}.smart"));
                        $diskusagev['space'] = $mountpoint_details[1];
                        $pool_details = explode('#', $mountpoint_details[0]);
                        foreach($pool_details as $a_pool) {
                            $smart_details = explode('|', $a_pool);
                            $a_pool_details['device'] = $smart_details[0];
                            $a_pool_details['smart_state'] = $smart_details[1];
                            $a_pool_details['temp'] = $smart_details[2];
                            $diskusagev['devs'][] = $a_pool_details;
                        }
    					$percent_used = $diskusagev['percentage'];
						$tooltip_used = $diskusagev['tooltip']['used'];
						$tooltip_avail = $diskusagev['tooltip']['avail'];
    					echo "<tr style='height:22px'><td><div id='diskusage'>";
    					echo "<span name='diskusage_{$ctrlid}_name' id='diskusage_{$ctrlid}_name' class='name'>{$diskusagev['name']}</span>";
    					echo "</td><td nowrap>&nbsp;&nbsp;<img src='{$image_path}bar_left.gif' class='progbarl' alt='' />";
    					echo "<img src='{$image_path}bar_blue.gif' name='diskusage_{$ctrlid}_bar_used' id='diskusage_{$ctrlid}_bar_used' width='{$percent_used}' class='progbarcf' title='{$tooltip_used}' alt='' />";
    					echo "<img src='{$image_path}bar_gray.gif' name='diskusage_{$ctrlid}_bar_free' id='diskusage_{$ctrlid}_bar_free' width='" . (100 - $percent_used) . "' class='progbarc' title='{$tooltip_avail}' alt='' />";
    					echo "<img src='{$image_path}bar_right.gif' class='progbarr' alt='' /> ";
    					echo "&nbsp;&nbsp;</td><td nowrap align='left'><span name='diskusage_{$ctrlid}_capofsize' id='diskusage_{$ctrlid}_capofsize' class='capofsize'>{$diskusagev['capofsize']}</span>";
    					echo "</td><td nowrap> || ";
    					echo sprintf(gettext("Total: %s | Used: %s | Free: %s"),
    						"<span name='diskusage_{$ctrlid}_size' id='diskusage_{$ctrlid}_size' class='size' style='display:inline-block; width:35px; text-align:right; font-weight:bold'>{$diskusagev['size']}</span>",
    						"<span name='diskusage_{$ctrlid}_used' id='diskusage_{$ctrlid}_used' class='used' style='display:inline-block; width:35px; text-align:right; font-weight:bold; color:blue'>{$diskusagev['used']}</span>",
    						"<span name='diskusage_{$ctrlid}_avail' id='diskusage_{$ctrlid}_avail' class='avail' style='display:inline-block; width:35px; text-align:right; font-weight:bold; color:green'>{$diskusagev['avail']}</span>");
    					echo " ||";
                        echo "</td><td><table style='width:230px'>";
                        foreach($diskusagev['devs'] as $idx => $devs) {
                            echo "<tr><td style='white-space:nowrap; width:60px;'>";
    						echo " <span name='diskusage_{$ctrlid}_{$idx}_device' id='diskusage_{$ctrlid}_{$idx}_device' class='device'>{$devs['device']}</span>";
    						echo "</td><td style='white-space:nowrap; width:80px;'>= <span name='diskusage_{$ctrlid}_{$idx}_smart_state' id='diskusage_{$ctrlid}_{$idx}_smart_state' class='state'>{$devs['smart_state']}</span>";
    						echo "</td><td style='white-space:nowrap; width:80px;'> | Temp: <span name='diskusage_{$ctrlid}_{$idx}_temp' id='diskusage_{$ctrlid}_{$idx}_temp' class='temp' style='font-weight:bold'>{$devs['temp']}</span>";
                            echo "</td></tr>";
                        }
                        echo "</table>";
                        echo "</td><td style='white-space:nowrap; width:60px;'><span name='diskusage_{$ctrlid}_space' id='diskusage_{$ctrlid}_space'>{$diskusagev['space']}</span>";
    					echo "</td><td style='white-space:nowrap; width:50%;'>&nbsp;&nbsp;</td></tr>";
    				}

				$zfspools = get_pool_usage();
				if (!empty($zfspools)) {
					if (!empty($a_diskusage)) {echo "<tr><td colspan='7'><hr size='1' /></td></tr>";}
					array_sort_key($zfspools, "name");
					$index = 0;
					foreach ($zfspools as $poolk => $poolv) {
						$ctrlid = $poolv['name'];
						$ctrlid = preg_replace('/[-\.: ]/', '_', $ctrlid);
                        $mountpoint_details = explode('##', exec("cat {$EGUI_PREFIX}{$poolv['name']}.smart"));
                        $poolv['space'] = $mountpoint_details[1];
                        $pool_details = explode('#', $mountpoint_details[0]);
                        foreach($pool_details as $a_pool) {
                            $smart_details = explode('|', $a_pool);
                            $a_pool_details['device'] = $smart_details[0];
                            $a_pool_details['smart_state'] = $smart_details[1];
                            $a_pool_details['temp'] = $smart_details[2];
                            $poolv['devs'][] = $a_pool_details;
                        }
						$percent_used = $poolv['percentage'];
						$tooltip_used = $poolv['tooltip']['used'];
						$tooltip_avail = $poolv['tooltip']['avail'];
						echo "<tr><td><div id='poolusage'>";
						echo "<span name='poolusage_{$ctrlid}_name' id='poolusage_{$ctrlid}_name' class='name'>{$poolv['name']}</span>";
						echo " </td><td nowrap>&nbsp;&nbsp;<img src='{$image_path}bar_left.gif' class='progbarl' alt='' />";
						echo "<img src='{$image_path}bar_blue.gif' name='poolusage_{$ctrlid}_bar_used' id='poolusage_{$ctrlid}_bar_used' width='{$percent_used}' class='progbarcf' title='{$tooltip_used}' alt='' />";
						echo "<img src='{$image_path}bar_gray.gif' name='poolusage_{$ctrlid}_bar_free' id='poolusage_{$ctrlid}_bar_free' width='" . (100 - $percent_used) . "' class='progbarc' title='{$tooltip_avail}' alt='' />";
						echo "<img src='{$image_path}bar_right.gif' class='progbarr' alt='' /> ";
						echo "&nbsp;&nbsp;</td><td nowrap align='left'><span name='poolusage_{$ctrlid}_capofsize' id='poolusage_{$ctrlid}_capofsize' class='capofsize'>{$poolv['capofsize']}</span>";
						echo "</td><td nowrap> || ";
    					echo sprintf(gettext("Total: %s | Used: %s | Free: %s"),
							"<span name='poolusage_{$ctrlid}_size' id='poolusage_{$ctrlid}_size' class='size' style='display:inline-block; width:35px; text-align:right; font-weight:bold'>{$poolv['size']}</span>",
							"<span name='poolusage_{$ctrlid}_used' id='poolusage_{$ctrlid}_used' class='used' style='display:inline-block; width:35px; text-align:right; font-weight:bold; color:blue'>{$poolv['used']}</span>",
							"<span name='poolusage_{$ctrlid}_avail' id='poolusage_{$ctrlid}_avail' class='avail' style='display:inline-block; width:35px; text-align:right; font-weight:bold; color:green'>{$poolv['avail']}</span>");
						echo " ||";
                        echo "</td><td><table style='width:230px'>";
                        foreach($poolv['devs'] as $idx => $devs) {
                            echo "<tr><td style='white-space:nowrap; width:60px;'>";
							echo " <span name='poolusage_{$ctrlid}_{$idx}_device' id='poolusage_{$ctrlid}_{$idx}_device' class='device'>{$devs['device']}</span>";
							echo "</td><td style='white-space:nowrap; width:80px;'>= <span name='poolusage_{$ctrlid}_{$idx}_smart_state' id='poolusage_{$ctrlid}_{$idx}_smart_state' class='state'>{$devs['smart_state']}</span>";
							echo "</td><td style='white-space:nowrap; width:80px;'> | Temp: <span name='poolusage_{$ctrlid}_{$idx}_temp' id='poolusage_{$ctrlid}_{$idx}_temp' class='temp' style='font-weight:bold'>{$devs['temp']}</span>";
                            echo "</td></tr>";
                        }
                        echo "</table>";
						echo "</td><td style='white-space:nowrap; width:60px;'><span name='poolusage_{$ctrlid}_space' id='poolusage_{$ctrlid}_space'>{$poolv['space']}</span>";
						echo "</td><td style='white-space:nowrap; width:50%;'>&nbsp;&nbsp;<a href='disks_zfs_zpool_info.php?pool={$poolv['name']}'><span name='poolusage_{$ctrlid}_state' id='poolusage_{$ctrlid}_state' class='state'>{$poolv['health']}</span></a>";
						echo "</td></tr>";

						if (++$index < count($zfspools))
							echo "<tr><td colspan='7'><hr size='1' /></td></tr>";
					}
				}

				if (empty($a_diskusage) && empty($zfspools)) {
					echo "<tr><td>";
					echo gettext("No disk configured");
					echo "</td></tr>";
				}
			?>
			</table></td>
		</tr>
<?php if (isset($config['ups']['enable'])) { ?>
		<tr>
			<td width="25%" class="vncellt"><?=gtext("UPS Status")." ".$config["ups"]["upsname"];?></td>
			<td class="listr" colspan="2">
            <table border="0" cellspacing="0" cellpadding="2">
			<?php if (!isset($config['ups']['enable'])):?>
				<tr>
					<td>
						<input style="padding: 0; border: 0; background-color:#FCFCFC;" size="18" name="upsstatus" id="upsstatus" value="<?=gettext("UPS disabled");?>" />
					</td>
				</tr>
			<?php else:?>
			<?php
				$cmd = "/usr/local/bin/upsc {$config['ups']['upsname']}@{$config['ups']['ip']}";
				$handle = popen($cmd, 'r');

				if ($handle) {
					$read = fread($handle, 4096);
					pclose($handle);

					$lines = explode("\n", $read);
					$ups = array();
					foreach($lines as $line) {
						$line = explode(':', $line);
						$ups[$line[0]] = trim($line[1]);
					}
					$disp_status = get_ups_disp_status($ups['ups.status']);
                    $disp_status = !empty($disp_status) ? "<font color=green><b>".$disp_status."&nbsp;&nbsp;</b></font>" : "<font color=red><b>".gettext("Data stale!")."&nbsp;&nbsp;</b></font>";
                    echo "<tr>";
    				tblrowbar("battery", gettext('Battery Level'), sprintf("%.1f", $ups['battery.charge']), '%', '0-29' ,'30-79', '80-100');
    				tblrow(gettext('Status'), '<span id="ups_status_disp_status">'.$disp_status."</span>". "  <small>[<a href='diag_infos_ups.php'>" . gettext("Show ups information")."</a></small>]");
                    echo "</tr><tr>";
					tblrowbar("load", gettext('Load'), sprintf("%.1f", $ups['ups.load']), '%', '100-80', '79-60', '59-0');
                    tblrow(gettext('Remaining battery runtime'), '<span id="ups_status_battery_runtime">'.convert_seconds($ups['battery.runtime']).'</span>');
                    echo "</tr>";                        
				}

				unset($handle);
				unset($read);
				unset($lines);
				unset($status);
				unset($disp_status);
				unset($ups);
				unset($cmd);
			?>
			<?php endif;?>
			</table></td>
		</tr>
<?php } ?>

		<?php
        	if (isset($config['ups']['enable']) && isset($config['ups']['ups2'])) { ?>
			<td width="25%" class="vncellt"><?=gtext("UPS Status")." ".$config["ups"]["ups2_upsname"];?></td>
			<td class="listr" colspan="2">
            <table border="0" cellspacing="0" cellpadding="2">
		    <?php
                $cmd = "/usr/local/bin/upsc {$config['ups']['ups2_upsname']}@{$config['ups']['ip']}";
                $handle = popen($cmd, 'r');
                
                if ($handle) {
                	$read = fread($handle, 4096);
                	pclose($handle);
                
                	$lines = explode("\n", $read);
                	$ups = array();
                	foreach($lines as $line) {
                		$line = explode(':', $line);
                		$ups[$line[0]] = trim($line[1]);
                	}
					$disp_status = get_ups_disp_status($ups['ups.status']);
                    $disp_status = !empty($disp_status) ? "<font color=green><b>".$disp_status."&nbsp;&nbsp;</b></font>" : "<font color=red><b>".gettext("Data stale!")."&nbsp;&nbsp;</b></font>";
                    echo "<tr>";
    				tblrowbar("battery2", gettext('Battery Level'), sprintf("%.1f", $ups['battery.charge']), '%', '0-29' ,'30-79', '80-100');
    				tblrow(gettext('Status'), '<span id="ups_status_disp_status2">'.$disp_status."</span>". "  <small>[<a href='diag_infos_ups.php'>" . gettext("Show ups information")."</a></small>]");
                    echo "</tr><tr>";
					tblrowbar("load2", gettext('Load'), sprintf("%.1f", $ups['ups.load']), '%', '100-80', '79-60', '59-0');
                    tblrow(gettext('Remaining battery runtime'), '<span id="ups_status_battery_runtime">'.convert_seconds($ups['battery.runtime']).'</span>');
                    echo "</tr>";
                }
                
                unset($handle);
                unset($read);
                unset($lines);
                unset($status);
                unset($disp_status);
                unset($ups);
                unset($cmd);
                echo('</table></td>');
	            echo('</tr>');
            }
        ?>
		<?php
			unset($vmlist);
			mwexec2("/usr/bin/find /dev/vmm -type c", $vmlist);
			unset($vmlist2);
			$vbox_user = "vboxusers";
			$vbox_if = get_ifname($config['interfaces']['lan']['if']);
			$vbox_ipaddr = get_ipaddr($vbox_if);
			if (isset($config['vbox']['enable'])) {
				mwexec2("/usr/local/bin/sudo -u {$vbox_user} /usr/local/bin/VBoxManage list runningvms", $vmlist2);
			} else {
				$vmlist2 = array();
			}
			unset($vmlist3);
			if ($g['arch'] == "dom0") {
				$xen_if = get_ifname($config['interfaces']['lan']['if']);
				$xen_ipaddr = get_ipaddr($xen_if);
				$vmlist_json = shell_exec("/usr/local/sbin/xl list -l");
				$vmlist3 = json_decode($vmlist_json, true);
			} else {
				$vmlist3 = array();
			}
			if (!empty($vmlist) || !empty($vmlist2) || !empty($vmlist3)):
		?>
		<tr>
			<td width="25%" class="vncellt"><?=gettext("Virtual Machine");?></td>
			<td width="75%" class="listr" colspan="2">
			<table width="100%" border="0" cellspacing="0" cellpadding="1">
			<?php
				$vmtype = "BHyVe";
				$index = 0;
				foreach ($vmlist as $vmpath) {
					$vm = basename($vmpath);
					unset($temp);
					exec("/usr/sbin/bhyvectl ".escapeshellarg("--vm=$vm")." --get-lowmem | sed -e 's/.*\\///'", $temp);
					$vram = $temp[0] / 1024 / 1024;
					echo "<tr><td><div id='vminfo_$index'>";
					echo htmlspecialchars("$vmtype: $vm ($vram MiB)");
					echo "</div></td></tr>\n";
					if (++$index < count($vmlist))
						echo "<tr><td><hr size='1' /></td></tr>\n";
				}

				$vmtype = "VBox";
				$index = 0;
				foreach ($vmlist2 as $vmline) {
					$vm = "";
					if (preg_match("/^\"(.+)\"\s*\{(\S+)\}$/", $vmline, $match)) {
						$vm = $match[1];
						$uuid = $match[2];
					}
					if ($vm == "")
						continue;
					$vminfo = get_vbox_vminfo($vbox_user, $uuid);
					$vram = $vminfo['memory']['value'];
					echo "<tr><td><div id='vminfo2_$index'>";
					echo htmlspecialchars("$vmtype: $vm ($vram MiB)");
					if (isset($vminfo['vrde']) && $vminfo['vrde']['value'] == "on") {
						$vncport = $vminfo['vrdeport']['value'];
						$url = htmlspecialchars("/novnc/vnc.html?host={$vbox_ipaddr}&port={$vncport}");
						echo " <a href='{$url}' target=_blank>";
						echo htmlspecialchars("vnc://{$vbox_ipaddr}:{$vncport}/");
						echo "</a>";
					}
					echo "</div></td></tr>\n";
					if (++$index < count($vmlist2))
						echo "<tr><td><hr size='1' /></td></tr>\n";
				}

				$vmtype = "Xen";
				$index = 0;
				$vncport_unused = 5900;
				foreach ($vmlist3 as $k => $v) {
					$domid = $v['domid'];
					$type = $v['config']['c_info']['type'];
					$vm = $v['config']['c_info']['name'];
					$vram = (int)(($v['config']['b_info']['target_memkb'] + 1023 ) / 1024);
					$vcpus = 1;
					if ($domid == 0) {
						$vcpus = @exec("/sbin/sysctl -q -n hw.ncpu");
						$info = get_xen_info();
						$cpus = $info['nr_cpus']['value'];
						$th = $info['threads_per_core']['value'];
						if (empty($th)) {
							$th = 1;
						}
						$core = (int)($cpus / $th);
						$mem = $info['total_memory']['value'];
						$ver = $info['xen_version']['value'];
					} else if (!empty($v['config']['b_info']['max_vcpus'])) {
						$vcpus = $v['config']['b_info']['max_vcpus'];
					}
					echo "<tr><td><div id='vminfo3_$index'>";
					echo htmlspecialchars("$vmtype $type: $vm ($vram MiB / $vcpus VCPUs)");
					if ($domid == 0) {
						echo " ";
						echo htmlspecialchars("Xen version {$ver} / {$mem} MiB / {$core} core".($th > 1 ? "/HT" : ""));
					} else if ($type == 'pv' && isset($v['config']['vfbs']) && isset($v['config']['vfbs'][0]['vnc'])) {
						$vnc = $v['config']['vfbs'][0]['vnc'];
						$vncport = "unknown";
						/*
						if (isset($vnc['display'])) {
							$vncdisplay = $vnc['display'];
							$vncport = 5900 + $vncdisplay;
						} else if (isset($vnc['findunused'])) {
							$vncport = $vncport_unused;
							$vncport_unused++;
						}
						*/
						$console = get_xen_console($domid);
						if (!empty($console) && isset($console['vnc-port'])) {
							$vncport = $console['vnc-port']['value'];
						}

						echo " ";
						echo htmlspecialchars("vnc://{$xen_ipaddr}:{$vncport}/");
					} else if ($type == 'hvm' && isset($v['config']['b_info']['type.hvm']['vnc']['enable'])) {
						$vnc = $v['config']['b_info']['type.hvm']['vnc'];
						$vncport = "unknown";
						/*
						if (isset($vnc['display'])) {
							$vncdisplay = $vnc['display'];
							$vncport = 5900 + $vncdisplay;
						} else if (isset($vnc['findunused'])) {
							$vncport = $vncport_unused;
							$vncport_unused++;
						}
						*/
						$console = get_xen_console($domid);
						if (!empty($console) && isset($console['vnc-port'])) {
							$vncport = $console['vnc-port']['value'];
						}

						echo " ";
						echo htmlspecialchars("vnc://{$xen_ipaddr}:{$vncport}/");
					}
					echo "</div></td></tr>\n";
					if (++$index < count($vmlist3))
						echo "<tr><td><hr size='1' /></td></tr>\n";
				}
			?>
			</table></td>
		</tr>
		<?php endif;?>
<?php if ($configuration['user']) { ?>
			  <tr>
			    <td width="25%" valign="top" class="vncellt"><?=gettext("Users");?></td>
			    <td class="listr" colspan="2"><span name="userinfo" id="userinfo"><?php echo egui_get_userinfo(); ?></span></td>
			  </tr>
<?php } ?>
<?php if ($configuration['hosts']) { ?>
			  <tr>
			    <td width="25%" valign="top" class="vncellt"><?=gettext("Hosts");?></td>
			    <td class="listr" colspan="2"><span name="hostsinfo" id="hostsinfo"><?php echo egui_get_hostsinfo(); ?></span></td>
			  </tr>
<?php } ?>
<?php if ($configuration['services']) { ?>
			  <tr>
			    <td width="25%" valign="top" class="vncellt"><?=gettext("Services");?></td>
			    <td class="listr" colspan="2">
                <?php echo exec("/var/scripts/autoshutdown.sh"); ?>

                </td>
			  </tr>
<?php } ?>
				<?php endif;?>
			</table>
		</td>                                                                                                                                                   
	</tr>
</table>
<?php if (Session::isAdmin()):?>
<center>
	<form action="index.php" method="post" name="iform" id="iform" onsubmit="spinner()">
		<br>
<?php bindtextdomain("nas4free", "/usr/local/share/locale-egui"); ?>
<?php if ($configuration['automount']) { ?>
    <input name="umount" type="submit" class="formbtn" title="<?=gettext("Unmount all USB-Drives!");?>" value="<?=gettext("Unmount USB Drives");?>">
    <input name="rmount" type="submit" class="formbtn" title="<?=gettext("Remount all USB-Drives!");?>" value="<?=gettext("Mount USB Drives");?>">
<?php } ?>
<?php if ($configuration['beep']) { ?>
    <input name="clear_alarms" type="submit" class="formbtn" title="<?=gettext("Clear all CPU and ZFS audible alarms!");?>" value="<?=gettext("Clear Alarms");?>">
<?php } ?>
<?php if ($configuration['system_warnings']) { ?>
    <input name="clear_history" type="submit" class="formbtn" title="<?=gettext("Clear alarm history!");?>" value="<?=gettext("Clear History");?>">
<?php } ?>
<?php if ($configuration['buttons']) { ?>
    <?php if ($configuration['purge']['enable']) { ?>
    		<input name="purge" type="submit" class="formbtn" title="<?=gettext("Purge now all CIFS/SMB recycle bins!");?>" value="<?=gettext("Purge now");?>">
    <?php } ?>
<?php } ?>
<?php 
	if ($configuration['user_defined']['use_buttons'] && is_file($configuration['user_defined']['buttons_file'])) {
		include_once($configuration['user_defined']['buttons_file']);
	}
?>
<?php bindtextdomain("nas4free", "/usr/local/share/locale"); ?>
 		<?php include("formend.inc"); ?>
	</form>
	</td>
</center>
<?php endif;?>
<?php include("fend.inc");?>
