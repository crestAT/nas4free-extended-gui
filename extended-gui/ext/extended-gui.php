<?php
/*
    extended-gui.php

    Copyright (c) 2014 - 2017 Andreas Schmidhuber <info@a3s.at>
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
require("auth.inc");
require("guiconfig.inc");

$config_file = "ext/extended-gui/extended-gui.conf";
require_once("ext/extended-gui/extension-lib.inc");
if (($configuration = ext_load_config($config_file)) === false) $input_errors[] = sprintf(gettext("Configuration file %s not found!"), "extended-gui.conf");
else require_once("{$configuration['rootfolder']}ext/extended-gui_fcopy.inc");
$pidfile = "/tmp/extended-gui_system_calls.sh.lock";

// Dummy standard message gettext calls for xgettext retrieval!!!
$dummy = gettext("The changes have been applied successfully.");
$dummy = gettext("The configuration has been changed.<br />You must apply the changes in order for them to take effect.");
$dummy = gettext("The following input errors were detected");
$dummy = gettext("Purge now all CIFS/SMB recycle bins!");
$dummy = gettext("Purge now");
$dummy = gettext("Unmount all USB-Drives!");
$dummy = gettext("Unmount USB Drives");
$dummy = gettext("Remount all USB-Drives!");
$dummy = gettext("Mount USB Drives");
$dummy = gettext("Clear all CPU and ZFS audible alarms!");
$dummy = gettext("Clear Alarms");
$dummy = gettext("Clear alarm history!");
$dummy = gettext("Clear History");
$dummy = gettext("Alarm message history cleared!");

bindtextdomain("nas4free", "/usr/local/share/locale-egui");
$pgtitle = array(gettext("Extensions"), "Extended GUI ".$configuration['version']);

if ( !isset( $configuration['rootfolder']) && !is_dir( $configuration['rootfolder'] )) $input_errors[] = gettext("Extension installed with fault");
else {
    $config_file = "{$configuration['rootfolder']}ext/extended-gui.conf";
    $configuration = ext_load_config($config_file);
    if (!isset($configuration['system_warnings'])) $configuration['system_warnings'] = true;
} 
    
if ($_POST) {
	if (isset($_POST['Submit']) && ($_POST['Submit'] === gettext("Save"))) { 
		unset($input_errors);
        $configuration['enable'] = isset($_POST['enable']);
        $configuration['type'] = $_POST['type'];
		if ($configuration['type'] == "Extended" ) {
            $configuration['loop_delay'] = !empty($_POST['loop_delay']) ? $_POST['loop_delay'] : 60;
            $configuration['hide_cpu'] = isset($_POST['hide_cpu']);
            $configuration['hide_cpu_usage'] = isset($_POST['hide_cpu_usage']);
            $configuration['hide_cpu_graph'] = isset($_POST['hide_cpu_graph']);
            $configuration['hide_lan_graph'] = isset($_POST['hide_lan_graph']);
            $configuration['boot'] = isset($_POST['boot']);
            $configuration['varfs'] = isset($_POST['varfs']);
            $configuration['usrfs'] = isset($_POST['usrfs']);
            $configuration['zfs'] = isset($_POST['zfs']);
            $configuration['user'] = isset($_POST['user']);
            $configuration['hosts'] = isset($_POST['hosts']);
            $configuration['hosts_network'] = !empty($_POST['hosts_network']) ? $_POST['hosts_network'] : "192.168.1";
            $configuration['hosts_network_start'] = !empty($_POST['hosts_network_start']) ? $_POST['hosts_network_start'] : 1;
            $configuration['hosts_network_end'] = !empty($_POST['hosts_network_end']) ? $_POST['hosts_network_end'] : 254;
            $configuration['services'] = isset($_POST['services']);
            $configuration['buttons'] = isset($_POST['buttons']);
            $configuration['force_standby'] = isset($_POST['force_standby']);
            $configuration['system_warnings'] = isset($_POST['system_warnings']);
            $configuration['beep'] = isset($_POST['beep']);
            $configuration['temp_always'] = isset($_POST['temp_always']);
            $configuration['cpu_temp_warning'] = !empty($_POST['cpu_temp_warning']) ? $_POST['cpu_temp_warning'] : 65;
            $configuration['cpu_temp_severe'] = !empty($_POST['cpu_temp_severe']) ? $_POST['cpu_temp_severe'] : 75;
            $configuration['cpu_temp_hysteresis'] = !empty($_POST['cpu_temp_hysteresis']) ? $_POST['cpu_temp_hysteresis'] : 3;
            $configuration['temp_warning'] = !empty($_POST['temp_warning']) ? $_POST['temp_warning'] : 38;
            $configuration['temp_severe'] = !empty($_POST['temp_severe']) ? $_POST['temp_severe'] : 45;
            $configuration['space_warning'] = !empty($_POST['space_warning']) ? $_POST['space_warning'] : 10000;
            $configuration['space_warning_percent'] = !empty($_POST['space_warning_percent']) ? $_POST['space_warning_percent'] : 10;
            $configuration['space_severe'] = !empty($_POST['space_severe']) ? $_POST['space_severe'] : 5000;
            $configuration['space_severe_percent'] = !empty($_POST['space_severe_percent']) ? $_POST['space_severe_percent'] : 5;
            $configuration['cpu_temp_email'] = isset($_POST['cpu_temp_email']);
            $configuration['space_email'] = isset($_POST['space_email']);
            $configuration['zfs_degraded_email'] = isset($_POST['zfs_degraded_email']);
            $configuration['user_email'] = isset($_POST['user_email']);
            $configuration['space_email_add'] = !empty($_POST['space_email_add']) ? $_POST['space_email_add'] : $config['system']['email']['from'];
		}
        $savemsg = get_std_save_message(ext_save_config($config_file, $configuration));
    }
    if (isset($configuration['enable']) && ($configuration['type'] == "Extended")) {
        require_once("{$configuration['rootfolder']}extended-gui-stop.php");
        require_once("{$configuration['rootfolder']}extended-gui-start.php");
    }
    else require_once("{$configuration['rootfolder']}extended-gui-stop.php");
 
}	

function get_process_info() {
    global $pidfile;
    if (exec("ps acx | grep -f $pidfile")) { $state = '<a style=" background-color: #00ff00; ">&nbsp;&nbsp;<b>'.gettext("running").'</b>&nbsp;&nbsp;</a>'; }
    else { $state = '<a style=" background-color: #ff0000; ">&nbsp;&nbsp;<b>'.gettext("stopped").'</b>&nbsp;&nbsp;</a>'; }
	return ($state);
}

function get_process_pid() {
    global $pidfile;
    exec("cat $pidfile", $state); 
	return ($state[0]);
}

if (is_ajax()) {
	$procinfo['info'] = get_process_info();
	$procinfo['pid'] = get_process_pid();
	render_ajax($procinfo);
}

if (($message = ext_check_version("{$configuration['rootfolder']}log/version.txt", "extended-gui", $configuration['version'], gettext("Maintenance"))) !== false) $savemsg .= $message;
	
bindtextdomain("nas4free", "/usr/local/share/locale");
include("fbegin.inc");
bindtextdomain("nas4free", "/usr/local/share/locale-egui");
?>
<script type="text/javascript">//<![CDATA[
$(document).ready(function(){
	var gui = new GUI;
	gui.recall(0, 2000, 'extended-gui.php', null, function(data) {
		$('#procinfo').html(data.info);
		$('#procinfo_pid').html(data.pid);
	});
});
//]]>
</script>
<script type="text/javascript">
<!--
function enable_change(enable_change) {
    var endis = !((document.iform.enable.checked || enable_change) && (document.iform.type.value == "Extended"));
    if (document.iform.enable.checked) {document.iform.type.disabled = false;}
    else {document.iform.type.disabled = true;}  
	document.iform.loop_delay.disabled = endis;
	document.iform.hide_cpu.disabled = endis;
	document.iform.hide_cpu_usage.disabled = endis;
	document.iform.hide_cpu_graph.disabled = endis;
	document.iform.hide_lan_graph.disabled = endis;
	document.iform.boot.disabled = endis;
	document.iform.usrfs.disabled = endis;
	document.iform.varfs.disabled = endis;
	document.iform.zfs.disabled = endis;
	document.iform.user.disabled = endis;
	document.iform.hosts.disabled = endis;
	document.iform.hosts_network.disabled = endis;
	document.iform.hosts_network_start.disabled = endis;
	document.iform.hosts_network_end.disabled = endis;
	document.iform.services.disabled = endis;
	document.iform.buttons.disabled = endis;
	document.iform.force_standby.disabled = endis;
	document.iform.system_warnings.disabled = endis;
	document.iform.beep.disabled = endis;
	document.iform.temp_always.disabled = endis;
	document.iform.cpu_temp_warning.disabled = endis;
	document.iform.cpu_temp_severe.disabled = endis;
	document.iform.cpu_temp_hysteresis.disabled = endis;
	document.iform.temp_warning.disabled = endis;
	document.iform.temp_severe.disabled = endis;
	document.iform.space_warning.disabled = endis;
	document.iform.space_warning_percent.disabled = endis;
	document.iform.space_severe.disabled = endis;
	document.iform.space_severe_percent.disabled = endis;
	document.iform.cpu_temp_email.disabled = endis;
	document.iform.space_email.disabled = endis;
	document.iform.zfs_degraded_email.disabled = endis;
	document.iform.user_email.disabled = endis;
	document.iform.space_email_add.disabled = endis;

	document.iform.services.disabled = true;
//	document.iform.buttons.disabled = true;
}
function enable_change_hosts() {
	switch (document.iform.hosts.checked) {
		case true:
			showElementById('hosts_network_tr','show');
			showElementById('hosts_network_start_tr','show');
			showElementById('hosts_network_end_tr','show');
			break;

		case false:
			showElementById('hosts_network_tr','hide');
			showElementById('hosts_network_start_tr','hide');
			showElementById('hosts_network_end_tr','hide');
			break;
	}
}
//-->
</script>
<form action="extended-gui.php" method="post" name="iform" id="iform" onsubmit="spinner()">
    <table width="100%" border="0" cellpadding="0" cellspacing="0">
    	<tr><td class="tabnavtbl">
    		<ul id="tabnav">
            <li class="tabact"><a href="extended-gui.php"><span><?=gettext("Configuration");?></span></a></li>
            <?php if ($configuration['enable']) { ?>
                <li class="tabinact"><a href="extended-gui_tools.php"><span><?=gettext("Tools");?></span></a></li>
            <?php } ?>
			<li class="tabinact"><a href="extended-gui_update_extension.php"><span><?=gettext("Extension Maintenance");?></span></a></li>
     		</ul>
    	</td></tr>
        <tr><td class="tabcont">
            <?php if (!empty($input_errors)) print_input_errors($input_errors);?>
            <?php if (!empty($savemsg)) print_info_box($savemsg);?>
            <table width="100%" border="0" cellpadding="6" cellspacing="0">
            	<?php html_titleline_checkbox("enable", gettext("Extended GUI"), $configuration['enable'], gettext("Enable"), "enable_change(false)");?>
                <?php html_text("installation_directory", gettext("Installation directory"), sprintf(gettext("The extension is installed in %s."), $configuration['rootfolder']));?>
            	<?php html_combobox("type", gettext("Type"), !empty($configuration['type']) ? $configuration['type'] : "Standard", array('Standard' =>'Standard','Extended'=> 'Extended'), gettext("Choose view type"), true, false, "enable_change(false)" );?>
                <tr>
                    <td class="vncellt"><?=gettext("System calls service status");?></td>
                    <td class="vtable"><span name="procinfo" id="procinfo"><?=get_process_info()?></span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;PID:&nbsp;<span name="procinfo_pid" id="procinfo_pid"><?=get_process_pid()?></span></td>
                </tr>
            	<?php html_inputbox("loop_delay", gettext("System calls service delay time"), !empty($configuration['loop_delay']) ? $configuration['loop_delay'] : 60, sprintf(gettext("Define the delay between two system calls executed by Extended GUI in seconds. Default delay is %d seconds."), 60), true, 5);?>
			<?php html_separator();?>
			<?php html_titleline(gettext("Status")." | ".gettext("System"));?>
                <?php html_checkbox("hide_cpu", gettext("CPU usage bar"), $configuration['hide_cpu'], gettext("Disable display of CPU usage bar."), "", false);?>
                <?php html_checkbox("hide_cpu_usage", gettext("CPU multicore usage bars"), $configuration['hide_cpu_usage'], gettext("Disable display of multicore CPU usage bars."), "", false);?>
                <?php html_checkbox("hide_cpu_graph", gettext("CPU graph"), $configuration['hide_cpu_graph'], gettext("Disable display of CPU graph."), "", false);?>
                <?php html_checkbox("hide_lan_graph", gettext("LAN graph"), $configuration['hide_lan_graph'], gettext("Disable display of LAN graph."), "", false);?>
                <?php html_checkbox("boot", gettext("Operating system"), $configuration['boot'], gettext("Enable display of Operating System partition (root filesystem named as A_OS)."), "", false);?>
                <?php html_checkbox("usrfs", gettext("USR system"), $configuration['usrfs'], gettext("Enable display of USR partition (/usr/local filesystem named as A_USR)."), "", false);?>
                <?php html_checkbox("varfs", gettext("VAR system"), $configuration['varfs'], gettext("Enable display of VAR partition (/var filesystem named as A_VAR)."), "", false);?>
                <?php html_checkbox("zfs", gettext("ZFS datasets"), $configuration['zfs'], gettext("Enable display of ZFS datasets."), "", false);?>
                <?php html_checkbox("user", gettext("Users"), $configuration['user'], gettext("Enable display of users logged in (CIFS/SMB, SSH, FTP)."), "", false);?>
                <?php html_checkbox("hosts", gettext("Hosts"), $configuration['hosts'], gettext("Enable display of hosts in network."), "", false, "enable_change_hosts()");?>
            	<?php html_inputbox("hosts_network", gettext("Hosts IP address network part"), !empty($configuration['hosts_network']) ? $configuration['hosts_network'] : "192.168.1", sprintf(gettext("Define the IP address network part (first 3 octets) for monitoring. Default is %s"), "192.168.1"), false, 10);?>
            	<?php html_inputbox("hosts_network_start", gettext("Hosts IP address host part start"), !empty($configuration['hosts_network_start']) ? $configuration['hosts_network_start'] : 1, sprintf(gettext("Define the IP address host part (last octet) range start address for monitoring. Default is %d"), 1), false, 5);?>
            	<?php html_inputbox("hosts_network_end", gettext("Hosts IP address host part end"), !empty($configuration['hosts_network_end']) ? $configuration['hosts_network_end'] : 254, sprintf(gettext("Define the IP address host part (last octet) range end address for monitoring. Default is %d"), 254), false, 5);?>
                <?php html_checkbox("services", gettext("Services"), $configuration['services'], gettext("Enable display of services row."), "", false);?>
                <?php html_checkbox("buttons", gettext("Functions"), $configuration['buttons'], gettext("Enable display of function buttons row."), "", false);?>
                <?php html_checkbox("force_standby", gettext("Standby buttons"), $configuration['force_standby'], gettext("Enable display of buttons to force drive standby."), "", false);?>
			<?php html_separator();?>
			<?php html_titleline(gettext("Monitoring and Alarming"));?>
                <?php html_checkbox("system_warnings", gettext("System notifications"), $configuration['system_warnings'], sprintf(gettext("Enable alarms notifications/history on %s."), gettext("Status")." | ".gettext("System")), "", false);?>
                <?php html_checkbox("beep", gettext("System Beep"), $configuration['beep'], gettext("Enable audible alarms for Extended GUI."), "", false);?>
                <?php html_checkbox("temp_always", gettext("Disk temperature"), $configuration['temp_always'], gettext("Enable display of disk temperatures even if disks are in standby mode. If enabled it could happen that disks don't spin down depending on disk/controler combinations!"), "", false);?>
            	<?php html_inputbox("cpu_temp_warning", gettext("CPU temperature warning level"), !empty($configuration['cpu_temp_warning']) ? $configuration['cpu_temp_warning'] : 65, sprintf(gettext("Define the CPU temperature for warning indication in &deg;C. Default warning temperature is %d &deg;C."), 65), true, 5);?>
            	<?php html_inputbox("cpu_temp_severe", gettext("CPU temperature critical level"), !empty($configuration['cpu_temp_severe']) ? $configuration['cpu_temp_severe'] : 75, sprintf(gettext("Define the CPU temperature for error indication in &deg;C. Default critical temperature is %d &deg;C."), 75), true, 5);?>
            	<?php html_inputbox("cpu_temp_hysteresis", gettext("CPU temperature hysteresis"), !empty($configuration['cpu_temp_hysteresis']) ? $configuration['cpu_temp_hysteresis'] : 3, sprintf(gettext("Define the difference to the CPU warning temperature (for how much the CPU temperature must be lower) to clear the alarms in &deg;C. Default hysteresis temperature is %d &deg;C."), 3), true, 5);?>
            	<?php html_inputbox("temp_warning", gettext("Disk temperature warning level"), !empty($configuration['temp_warning']) ? $configuration['temp_warning'] : $config['smartd']['temp']['info'], sprintf(gettext("Define the disk temperature for warning indication in &deg;C. Default warning temperature as defined in <a href='disks_manage_smart.php'>S.M.A.R.T.</a> is %d &deg;C."), $config['smartd']['temp']['info']), true, 5);?>
            	<?php html_inputbox("temp_severe", gettext("Disk temperature critical level"), !empty($configuration['temp_severe']) ? $configuration['temp_severe'] : $config['smartd']['temp']['crit'], sprintf(gettext("Define the critical disk temperature for error indication in &deg;C. Default critical temperature as defined in <a href='disks_manage_smart.php'>S.M.A.R.T.</a> is %d &deg;C."), $config['smartd']['temp']['crit']), true, 5);?>
            	<?php html_inputbox("space_warning", gettext("Disk free space warning level - MB"), !empty($configuration['space_warning']) ? $configuration['space_warning'] : 10000, sprintf(gettext("Define the free space threshold for disks for warning indication in MB. Default warning free space is %d MB."), 10000), true, 5);?>
            	<?php html_inputbox("space_warning_percent", gettext("Disk free space warning level - %"), !empty($configuration['space_warning_percent']) ? $configuration['space_warning_percent'] : 10, sprintf(gettext("Define the free space threshold for disks for warning indication in percent. Default warning free space is %d &#037;."), 10), true, 5);?>
            	<?php html_inputbox("space_severe", gettext("Disk free space critical level - MB"), !empty($configuration['space_severe']) ? $configuration['space_severe'] : 5000, sprintf(gettext("Define the lowest free space threshold for disks before warning and reporting in MB. Default critical free space is %d MB."), 5000), true, 5);?>
            	<?php html_inputbox("space_severe_percent", gettext("Disk free space critical level - %"), !empty($configuration['space_severe_percent']) ? $configuration['space_severe_percent'] : 5, sprintf(gettext("Define the lowest free space threshold for disks before warning and reporting in percent. Default critical free space is %d &#037;."), 5), true, 5);?>
			<?php html_separator();?>
			<?php html_titleline(gettext("Email"));?>
                <?php html_inputbox("space_email_add", gettext("To email"), !empty($configuration['space_email_add']) ? $configuration['space_email_add'] : $config['system']['email']['from'], gettext("Destination email address for warning reports. Default as defined in <a href='system_email.php'>System | Advanced | Email</a> from."), false, 40);?>
                <?php html_checkbox("cpu_temp_email", gettext("CPU temperature critical threshold warning email"), $configuration['cpu_temp_email'], gettext("Enable sending of email reports if CPU reach critical temperature threshold."), "", false);?>
                <?php html_checkbox("space_email", gettext("Disk free space threshold warning email"), $configuration['space_email'], gettext("Enable sending of email reports if disks reach free space thresholds."), "", false);?>
                <?php html_checkbox("zfs_degraded_email", gettext("ZFS pool degraded warning email"), $configuration['zfs_degraded_email'], gettext("Enable sending of email reports if ZFS pools are degraded."), "", false);?>
                <?php html_checkbox("user_email", gettext("User login/logout warning email"), $configuration['user_email'], gettext("Enable sending of email reports if users log in/out."), "", false);?>
            </table>
            <div id="submit">
            	<input name="Submit" type="submit" class="formbtn" value="<?=gettext("Save");?>" />
            </div>
		</td></tr>
	</table>
	<?php include("formend.inc");?>
</form>
<script type="text/javascript">
<!--
enable_change(false);
enable_change_hosts();
//-->
</script>
<?php include("fend.inc");?>
