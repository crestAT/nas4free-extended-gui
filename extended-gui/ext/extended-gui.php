<?php
/*
    extended-gui.php

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
require("auth.inc");
require("guiconfig.inc");
require_once("{$config['extended-gui']['rootfolder']}ext/extended-gui_fcopy.inc");
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

bindtextdomain("nas4free", "/usr/local/share/locale-egui");
$pgtitle = array(gettext("Extensions"), "Extended GUI ".$config['extended-gui']['version']);

if ( !isset( $config['extended-gui']['rootfolder']) && !is_dir( $config['extended-gui']['rootfolder'] )) $input_errors[] = gettext("Extension installed with fault");

if ($_POST) {
	if (isset($_POST['Submit']) && ($_POST['Submit'] === gettext("Save"))) { 
        require_once("{$config['extended-gui']['rootfolder']}extended-gui-stop.php");
		unset($input_errors);
        $config['extended-gui']['enable'] = isset($_POST['enable']) ? true : false;
        $config['extended-gui']['type'] = $_POST['type'];
		if ($config['extended-gui']['type'] == "Extended" ) {
            $config['extended-gui']['loop_delay'] = !empty($_POST['loop_delay']) ? $_POST['loop_delay'] : 60;
            $config['extended-gui']['hide_cpu'] = isset($_POST['hide_cpu']) ? true : false;
            $config['extended-gui']['hide_cpu_usage'] = isset($_POST['hide_cpu_usage']) ? true : false;
            $config['extended-gui']['hide_cpu_graph'] = isset($_POST['hide_cpu_graph']) ? true : false;
            $config['extended-gui']['hide_lan_graph'] = isset($_POST['hide_lan_graph']) ? true : false;
            $config['extended-gui']['boot'] = isset($_POST['boot']) ? true : false;
            $config['extended-gui']['varfs'] = isset($_POST['varfs']) ? true : false;
            $config['extended-gui']['usrfs'] = isset($_POST['usrfs']) ? true : false;
            $config['extended-gui']['zfs'] = isset($_POST['zfs']) ? true : false;
            $config['extended-gui']['user'] = isset($_POST['user']) ? true : false;
            $config['extended-gui']['hosts'] = isset($_POST['hosts']) ? true : false;
            $config['extended-gui']['hosts_network'] = !empty($_POST['hosts_network']) ? $_POST['hosts_network'] : "192.168.1";
            $config['extended-gui']['hosts_network_start'] = !empty($_POST['hosts_network_start']) ? $_POST['hosts_network_start'] : 1;
            $config['extended-gui']['hosts_network_end'] = !empty($_POST['hosts_network_end']) ? $_POST['hosts_network_end'] : 254;
            $config['extended-gui']['services'] = isset($_POST['services']) ? true : false;
            $config['extended-gui']['buttons'] = isset($_POST['buttons']) ? true : false;
            $config['extended-gui']['beep'] = isset($_POST['beep']) ? true : false;
            $config['extended-gui']['temp_always'] = isset($_POST['temp_always']) ? true : false;
            $config['extended-gui']['cpu_temp_warning'] = !empty($_POST['cpu_temp_warning']) ? $_POST['cpu_temp_warning'] : 65;
            $config['extended-gui']['cpu_temp_severe'] = !empty($_POST['cpu_temp_severe']) ? $_POST['cpu_temp_severe'] : 75;
            $config['extended-gui']['cpu_temp_hysteresis'] = !empty($_POST['cpu_temp_hysteresis']) ? $_POST['cpu_temp_hysteresis'] : 3;
            $config['extended-gui']['temp_warning'] = !empty($_POST['temp_warning']) ? $_POST['temp_warning'] : 38;
            $config['extended-gui']['temp_severe'] = !empty($_POST['temp_severe']) ? $_POST['temp_severe'] : 45;
            $config['extended-gui']['space_warning'] = !empty($_POST['space_warning']) ? $_POST['space_warning'] : 10000;
            $config['extended-gui']['space_warning_percent'] = !empty($_POST['space_warning_percent']) ? $_POST['space_warning_percent'] : 10;
            $config['extended-gui']['space_severe'] = !empty($_POST['space_severe']) ? $_POST['space_severe'] : 5000;
            $config['extended-gui']['space_severe_percent'] = !empty($_POST['space_severe_percent']) ? $_POST['space_severe_percent'] : 5;
            $config['extended-gui']['cpu_temp_email'] = isset($_POST['cpu_temp_email']) ? true : false;
            $config['extended-gui']['space_email'] = isset($_POST['space_email']) ? true : false;
            $config['extended-gui']['zfs_degraded_email'] = isset($_POST['zfs_degraded_email']) ? true : false;
            $config['extended-gui']['user_email'] = isset($_POST['user_email']) ? true : false;
            $config['extended-gui']['space_email_add'] = !empty($_POST['space_email_add']) ? $_POST['space_email_add'] : $config['system']['email']['from'];
            $config['extended-gui']['graph_nb_plot'] = !empty($_POST['graph_nb_plot']) ? $_POST['graph_nb_plot'] : 120;
            $config['extended-gui']['graph_time_interval'] = !empty($_POST['graph_time_interval']) ? $_POST['graph_time_interval'] : 1;
		}
        $savemsg = get_std_save_message(write_config());
    }
    if ( isset( $config['extended-gui']['enable'] ) && ( $config['extended-gui']['type'] == "Extended" )) require_once("{$config['extended-gui']['rootfolder']}extended-gui-start.php"); 
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
	document.iform.graph_nb_plot.disabled = endis;
	document.iform.graph_time_interval.disabled = endis;

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
            <?php if (isset($config['extended-gui']['enable'])) { ?>
                <li class="tabinact"><a href="extended-gui_tools.php"><span><?=gettext("Tools");?></span></a></li>
            <?php } ?>
			<li class="tabinact"><a href="extended-gui_update_extension.php"><span><?=gettext("Extension Maintenance");?></span></a></li>
     		</ul>
    	</td></tr>
        <tr><td class="tabcont">
            <?php if (!empty($input_errors)) print_input_errors($input_errors);?>
            <?php if (!empty($savemsg)) print_info_box($savemsg);?>
            <table width="100%" border="0" cellpadding="6" cellspacing="0">
            	<?php html_titleline_checkbox("enable", gettext("Extended GUI"), isset($config['extended-gui']['enable']) ? true : false, gettext("Enable"), "enable_change(false)");?>
                <?php html_text("installation_directory", gettext("Installation directory"), sprintf(gettext("The extension is installed in %s."), $config['extended-gui']['rootfolder']));?>
            	<?php html_combobox("type", gettext("Type"), !empty($config['extended-gui']['type']) ? $config['extended-gui']['type'] : "Standard", array('Standard' =>'Standard','Extended'=> 'Extended'), gettext("Choose view type"), true, false, "enable_change(false)" );?>
                <tr>
                    <td class="vncellt"><?=gettext("System calls service status");?></td>
                    <td class="vtable"><span name="procinfo" id="procinfo"><?=get_process_info()?></span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;PID:&nbsp;<span name="procinfo_pid" id="procinfo_pid"><?=get_process_pid()?></span></td>
                </tr>
            	<?php html_inputbox("loop_delay", gettext("System calls service delay time"), !empty($config['extended-gui']['loop_delay']) ? $config['extended-gui']['loop_delay'] : 60, sprintf(gettext("Define the delay between two system calls executed by Extended GUI in seconds. Default delay is %d seconds."), 60), true, 5);?>
			<?php html_separator();?>
			<?php html_titleline(gettext("Status")." | ".gettext("System"));?>
                <?php html_checkbox("hide_cpu", gettext("CPU usage bar"), isset($config['extended-gui']['hide_cpu']) ? true : false, gettext("Disable display of CPU usage bar."), "", false);?>
                <?php html_checkbox("hide_cpu_usage", gettext("CPU multicore usage bars"), isset($config['extended-gui']['hide_cpu_usage']) ? true : false, gettext("Disable display of multicore CPU usage bars."), "", false);?>
                <?php html_checkbox("hide_cpu_graph", gettext("CPU graph"), isset($config['extended-gui']['hide_cpu_graph']) ? true : false, gettext("Disable display of CPU graph."), "", false);?>
                <?php html_checkbox("hide_lan_graph", gettext("LAN graph"), isset($config['extended-gui']['hide_lan_graph']) ? true : false, gettext("Disable display of LAN graph."), "", false);?>
                <?php html_checkbox("boot", gettext("Operating system"), isset($config['extended-gui']['boot']) ? true : false, gettext("Enable display of Operating System partition (root filesystem named as A_OS)."), "", false);?>
                <?php html_checkbox("usrfs", gettext("USR system"), isset($config['extended-gui']['usrfs']) ? true : false, gettext("Enable display of USR partition (/usr/local filesystem named as A_USR)."), "", false);?>
                <?php html_checkbox("varfs", gettext("VAR system"), isset($config['extended-gui']['varfs']) ? true : false, gettext("Enable display of VAR partition (/var filesystem named as A_VAR)."), "", false);?>
                <?php html_checkbox("zfs", gettext("ZFS datasets"), isset($config['extended-gui']['zfs']) ? true : false, gettext("Enable display of ZFS datasets."), "", false);?>
                <?php html_checkbox("user", gettext("Users"), isset($config['extended-gui']['user']) ? true : false, gettext("Enable display of users logged in (CIFS/SMB, SSH, FTP)."), "", false);?>
                <?php html_checkbox("hosts", gettext("Hosts"), isset($config['extended-gui']['hosts']) ? true : false, gettext("Enable display of hosts in network."), "", false, "enable_change_hosts()");?>
            	<?php html_inputbox("hosts_network", gettext("Hosts IP address network part"), !empty($config['extended-gui']['hosts_network']) ? $config['extended-gui']['hosts_network'] : "192.168.1", sprintf(gettext("Define the IP address network part (first 3 octets) for monitoring. Default is %s"), "192.168.1"), false, 10);?>
            	<?php html_inputbox("hosts_network_start", gettext("Hosts IP address host part start"), !empty($config['extended-gui']['hosts_network_start']) ? $config['extended-gui']['hosts_network_start'] : 1, sprintf(gettext("Define the IP address host part (last octet) range start address for monitoring. Default is %d"), 1), false, 5);?>
            	<?php html_inputbox("hosts_network_end", gettext("Hosts IP address host part end"), !empty($config['extended-gui']['hosts_network_end']) ? $config['extended-gui']['hosts_network_end'] : 254, sprintf(gettext("Define the IP address host part (last octet) range end address for monitoring. Default is %d"), 254), false, 5);?>
                <?php html_checkbox("services", gettext("Services"), isset($config['extended-gui']['services']) ? true : false, gettext("Enable display of services row."), "", false);?>
                <?php html_checkbox("buttons", gettext("Functions"), isset($config['extended-gui']['buttons']) ? true : false, gettext("Enable display of function buttons row."), "", false);?>
			<?php html_separator();?>
			<?php html_titleline(gettext("Status")." | ".gettext("Graph"));?>
            	<?php html_inputbox("graph_nb_plot", gettext("Graph show time"), !empty($config['extended-gui']['graph_nb_plot']) ? $config['extended-gui']['graph_nb_plot'] : 120, sprintf(gettext("Maximum duration for graphs show time in seconds. Default is %d seconds."), 120), true, 5);?>
            	<?php html_inputbox("graph_time_interval", gettext("Graph refresh time"), !empty($config['extended-gui']['graph_time_interval']) ? $config['extended-gui']['graph_time_interval'] : 1, sprintf(gettext("Refresh time for graphs in seconds. Default is %d second."), 1), true, 5);?>
			<?php html_separator();?>
			<?php html_titleline(gettext("Monitoring and Alarming"));?>
                <?php html_checkbox("beep", gettext("System Beep"), isset($config['extended-gui']['beep']) ? true : false, gettext("Enable audible alarms for Extended GUI."), "", false);?>
                <?php html_checkbox("temp_always", gettext("Disk temperature"), isset($config['extended-gui']['temp_always']) ? true : false, gettext("Enable display of disk temperatures even if disks are in standby mode. If enabled it could happen that disks don't spin down depending on disk/controler combinations!"), "", false);?>
            	<?php html_inputbox("cpu_temp_warning", gettext("CPU temperature warning level"), !empty($config['extended-gui']['cpu_temp_warning']) ? $config['extended-gui']['cpu_temp_warning'] : 65, sprintf(gettext("Define the CPU temperature for warning indication in &deg;C. Default warning temperature is %d &deg;C."), 65), true, 5);?>
            	<?php html_inputbox("cpu_temp_severe", gettext("CPU temperature critical level"), !empty($config['extended-gui']['cpu_temp_severe']) ? $config['extended-gui']['cpu_temp_severe'] : 75, sprintf(gettext("Define the CPU temperature for error indication in &deg;C. Default critical temperature is %d &deg;C."), 75), true, 5);?>
            	<?php html_inputbox("cpu_temp_hysteresis", gettext("CPU temperature hysteresis"), !empty($config['extended-gui']['cpu_temp_hysteresis']) ? $config['extended-gui']['cpu_temp_hysteresis'] : 3, sprintf(gettext("Define the difference to the CPU warning temperature (for how much the CPU temperature must be lower) to clear the alarms in &deg;C. Default hysteresis temperature is %d &deg;C."), 3), true, 5);?>
            	<?php html_inputbox("temp_warning", gettext("Disk temperature warning level"), !empty($config['extended-gui']['temp_warning']) ? $config['extended-gui']['temp_warning'] : $config['smartd']['temp']['info'], sprintf(gettext("Define the disk temperature for warning indication in &deg;C. Default warning temperature as defined in <a href='disks_manage_smart.php'>S.M.A.R.T.</a> is %d &deg;C."), $config['smartd']['temp']['info']), true, 5);?>
            	<?php html_inputbox("temp_severe", gettext("Disk temperature critical level"), !empty($config['extended-gui']['temp_severe']) ? $config['extended-gui']['temp_severe'] : $config['smartd']['temp']['crit'], sprintf(gettext("Define the critical disk temperature for error indication in &deg;C. Default critical temperature as defined in <a href='disks_manage_smart.php'>S.M.A.R.T.</a> is %d &deg;C."), $config['smartd']['temp']['crit']), true, 5);?>
            	<?php html_inputbox("space_warning", gettext("Disk free space warning level - MB"), !empty($config['extended-gui']['space_warning']) ? $config['extended-gui']['space_warning'] : 10000, sprintf(gettext("Define the free space threshold for disks for warning indication in MB. Default warning free space is %d MB."), 10000), true, 5);?>
            	<?php html_inputbox("space_warning_percent", gettext("Disk free space warning level - %"), !empty($config['extended-gui']['space_warning_percent']) ? $config['extended-gui']['space_warning_percent'] : 10, sprintf(gettext("Define the free space threshold for disks for warning indication in percent. Default warning free space is %d &#037;."), 10), true, 5);?>
            	<?php html_inputbox("space_severe", gettext("Disk free space critical level - MB"), !empty($config['extended-gui']['space_severe']) ? $config['extended-gui']['space_severe'] : 5000, sprintf(gettext("Define the lowest free space threshold for disks before warning and reporting in MB. Default critical free space is %d MB."), 5000), true, 5);?>
            	<?php html_inputbox("space_severe_percent", gettext("Disk free space critical level - %"), !empty($config['extended-gui']['space_severe_percent']) ? $config['extended-gui']['space_severe_percent'] : 5, sprintf(gettext("Define the lowest free space threshold for disks before warning and reporting in percent. Default critical free space is %d &#037;."), 5), true, 5);?>
			<?php html_separator();?>
			<?php html_titleline(gettext("Email"));?>
                <?php html_inputbox("space_email_add", gettext("To email"), !empty($config['extended-gui']['space_email_add']) ? $config['extended-gui']['space_email_add'] : $config['system']['email']['from'], gettext("Destination email address for warning reports. Default as defined in <a href='system_email.php'>System | Advanced | Email</a> from."), false, 40);?>
                <?php html_checkbox("cpu_temp_email", gettext("CPU temperature critical threshold warning email"), isset($config['extended-gui']['cpu_temp_email']) ? true : false, gettext("Enable sending of email reports if CPU reach critical temperature threshold."), "", false);?>
                <?php html_checkbox("space_email", gettext("Disk free space threshold warning email"), isset($config['extended-gui']['space_email']) ? true : false, gettext("Enable sending of email reports if disks reach free space thresholds."), "", false);?>
                <?php html_checkbox("zfs_degraded_email", gettext("ZFS pool degraded warning email"), isset($config['extended-gui']['zfs_degraded_email']) ? true : false, gettext("Enable sending of email reports if ZFS pools are degraded."), "", false);?>
                <?php html_checkbox("user_email", gettext("User login/logout warning email"), isset($config['extended-gui']['user_email']) ? true : false, gettext("Enable sending of email reports if users log in/out."), "", false);?>
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
