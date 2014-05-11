<?php
/*
    extended-gui.php

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
require("auth.inc");
require("guiconfig.inc");
require_once("{$config['extended-gui']['rootfolder']}ext/extended-gui_fcopy.inc");

$pgtitle = array(gettext("Extensions"), "Extended GUI ".$config['extended-gui']['version']);

if ( !isset( $config['extended-gui']['rootfolder']) && !is_dir( $config['extended-gui']['rootfolder'] )) {
	$input_errors[] = "Extension installed with fault";
} 

if ($_POST) {
	if (isset($_POST['Submit']) && ($_POST['Submit'] === gettext("Save"))) { 
		unset($input_errors);
        $config['extended-gui']['enable'] = isset($_POST['enable']) ? true : false;
        $config['extended-gui']['type'] = $_POST['type'];
		if ($config['extended-gui']['type'] == "Extended" ) {
            $config['extended-gui']['loop_delay'] = !empty($_POST['loop_delay']) ? $_POST['loop_delay'] : 60;
            $config['extended-gui']['hide_cpu'] = isset($_POST['hide_cpu']) ? true : false;
            $config['extended-gui']['hide_cpu_usage'] = isset($_POST['hide_cpu_usage']) ? true : false;
            $config['extended-gui']['hide_cpu_graph'] = isset($_POST['hide_cpu_graph']) ? true : false;
            $config['extended-gui']['boot'] = isset($_POST['boot']) ? true : false;
            $config['extended-gui']['user'] = isset($_POST['user']) ? true : false;
            $config['extended-gui']['services'] = isset($_POST['services']) ? true : false;
            $config['extended-gui']['buttons'] = isset($_POST['buttons']) ? true : false;
            $config['extended-gui']['temp_warning'] = !empty($_POST['temp_warning']) ? $_POST['temp_warning'] : 38;
            $config['extended-gui']['temp_severe'] = !empty($_POST['temp_severe']) ? $_POST['temp_severe'] : 45;
            $config['extended-gui']['space_warning'] = !empty($_POST['space_warning']) ? $_POST['space_warning'] : 10000;
            $config['extended-gui']['space_severe'] = !empty($_POST['space_severe']) ? $_POST['space_severe'] : 5000;
            $config['extended-gui']['space_email'] = isset($_POST['space_email']) ? true : false;
            $config['extended-gui']['user_email'] = isset($_POST['user_email']) ? true : false;
            $config['extended-gui']['space_email_add'] = !empty($_POST['space_email_add']) ? $_POST['space_email_add'] : $config['system']['email']['from'];
            $config['extended-gui']['graph_nb_plot'] = !empty($_POST['graph_nb_plot']) ? $_POST['graph_nb_plot'] : 120;
            $config['extended-gui']['graph_time_interval'] = !empty($_POST['graph_time_interval']) ? $_POST['graph_time_interval'] : 1;
		}
        $savemsg = get_std_save_message(write_config());
    }
    if ( isset( $config['extended-gui']['enable'] ) && ( $config['extended-gui']['type'] == "Extended" )) {
        copy_extended2origin ($files, $backup_path, $extend_path);	
        killbypid("/tmp/extended-gui_system_calls.sh.lock");
        exec("/var/scripts/extended-gui_system_calls.sh >/dev/null 2>/dev/null &");
    }
    else { 
        copy_backup2origin ($files, $backup_path, $extend_path); 
        killbypid("/tmp/extended-gui_system_calls.sh.lock");
    } 
}	

include("fbegin.inc");?>  
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
	document.iform.boot.disabled = endis;
	document.iform.user.disabled = endis;
	document.iform.services.disabled = endis;
	document.iform.buttons.disabled = endis;
	document.iform.temp_warning.disabled = endis;
	document.iform.temp_severe.disabled = endis;
	document.iform.space_warning.disabled = endis;
	document.iform.space_severe.disabled = endis;
	document.iform.space_email.disabled = endis;
	document.iform.user_email.disabled = endis;
	document.iform.space_email_add.disabled = endis;
	document.iform.graph_nb_plot.disabled = endis;
	document.iform.graph_time_interval.disabled = endis;

	document.iform.boot.disabled = true;
	document.iform.services.disabled = true;
	document.iform.buttons.disabled = true;
}
//-->
</script>
<form action="extended-gui.php" method="post" name="iform" id="iform">
    <table width="100%" border="0" cellpadding="0" cellspacing="0">
        <tr><td class="tabcont">
            <?php if (!empty($input_errors)) print_input_errors($input_errors);?>
            <?php if (!empty($savemsg)) print_info_box($savemsg);?>
            <table width="100%" border="0" cellpadding="6" cellspacing="0">
            	<?php html_titleline_checkbox("enable", gettext("Extended GUI"), isset($config['extended-gui']['enable']) ? true : false, gettext("Enable"), "enable_change(false)");?>
            	<?php html_combobox("type", gettext("Type"), !empty($config['extended-gui']['type']) ? $config['extended-gui']['type'] : "Standard", array('Standard' =>'Standard','Extended'=> 'Extended'), "Choose view type", true, false, "enable_change(false)" );?>
			<?php html_separator();?>
			<?php html_titleline(gettext("Status")." | ".gettext("System"));?>
            	<?php html_inputbox("loop_delay", gettext("System check delay time"), !empty($config['extended-gui']['loop_delay']) ? $config['extended-gui']['loop_delay'] : 60, sprintf(gettext("Define the delay between two system checks executed by Extended GUI in seconds. Default delay is %d seconds."), 60), true, 5);?>
                <?php html_checkbox("hide_cpu", gettext("CPU usage"), isset($config['extended-gui']['hide_cpu']) ? true : false, gettext("Disable display of CPU usage bar."), "", false);?>
                <?php html_checkbox("hide_cpu_usage", gettext("CPU multicore usage"), isset($config['extended-gui']['hide_cpu_usage']) ? true : false, gettext("Disable display of multicore CPU usage bars."), "", false);?>
                <?php html_checkbox("hide_cpu_graph", gettext("CPU graph"), isset($config['extended-gui']['hide_cpu_graph']) ? true : false, gettext("Disable display of CPU graph."), "", false);?>
                <?php html_checkbox("boot", gettext("System"), isset($config['extended-gui']['boot']) ? true : false, gettext("Enable display of OS disk."), "", false);?>
                <?php html_checkbox("user", gettext("Users"), isset($config['extended-gui']['user']) ? true : false, gettext("Enable display of users logged in (CIFS/SMB, SSH, FTP)."), "", false);?>
                <?php html_checkbox("services", gettext("Services"), isset($config['extended-gui']['services']) ? true : false, gettext("Enable display of services row."), "", false);?>
                <?php html_checkbox("buttons", gettext("Functions"), isset($config['extended-gui']['buttons']) ? true : false, gettext("Enable display of function buttons row."), "", false);?>
            	<?php html_inputbox("temp_warning", gettext("Disk temperature warning level"), !empty($config['extended-gui']['temp_warning']) ? $config['extended-gui']['temp_warning'] : $config['smartd']['temp']['info'], sprintf(gettext("Define the disk temperature for warning indication in &deg;C. Default warning temperature as defined in <a href='disks_manage_smart.php'>S.M.A.R.T.</a> is %d &deg;C."), $config['smartd']['temp']['info']), true, 5);?>
            	<?php html_inputbox("temp_severe", gettext("Disk temperature critical level"), !empty($config['extended-gui']['temp_severe']) ? $config['extended-gui']['temp_severe'] : $config['smartd']['temp']['crit'], sprintf(gettext("Define the critical disk temperature for error indication in &deg;C. Default critical temperature as defined in <a href='disks_manage_smart.php'>S.M.A.R.T.</a> is %d &deg;C."), $config['smartd']['temp']['crit']), true, 5);?>
            	<?php html_inputbox("space_warning", gettext("Disk free space warning level"), !empty($config['extended-gui']['space_warning']) ? $config['extended-gui']['space_warning'] : 10000, sprintf(gettext("Define the free space threshold for disks for warning indication in MB. Default warning free space is %d MB."), 10000), true, 5);?>
            	<?php html_inputbox("space_severe", gettext("Disk free space critical level"), !empty($config['extended-gui']['space_severe']) ? $config['extended-gui']['space_severe'] : 5000, sprintf(gettext("Define the lowest free space threshold for disks before warning and reporting in MB. Default critical free space is %d MB."), 5000), true, 5);?>
                <?php html_checkbox("space_email", gettext("Disk free space threshold warning email"), isset($config['extended-gui']['space_email']) ? true : false, gettext("Enable sending of email report that disks reached free space thresholds."), "", false);?>
                <?php html_checkbox("user_email", gettext("User login/logout warning email"), isset($config['extended-gui']['user_email']) ? true : false, gettext("Enable sending of email report that users logged in / out."), "", false);?>
                <?php html_inputbox("space_email_add", gettext("To email"), !empty($config['extended-gui']['space_email_add']) ? $config['extended-gui']['space_email_add'] : $config['system']['email']['from'], gettext("Destination email address for warning reports. Default as defined in <a href='system_email.php'>System | Advanced | Email</a> from."), false, 40);?>
			<?php html_separator();?>
			<?php html_titleline(gettext("Status")." | ".gettext("Graph"));?>
            	<?php html_inputbox("graph_nb_plot", gettext("Graph show time"), !empty($config['extended-gui']['graph_nb_plot']) ? $config['extended-gui']['graph_nb_plot'] : 120, sprintf(gettext("Maximum duration for graphs show time in seconds. Default is %d seconds."), 120), true, 5);?>
            	<?php html_inputbox("graph_time_interval", gettext("Graph refresh time"), !empty($config['extended-gui']['graph_time_interval']) ? $config['extended-gui']['graph_time_interval'] : 1, sprintf(gettext("Refresh time for graphs in seconds. Default is %d second."), 1), true, 5);?>
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
//-->
</script>
<?php include("fend.inc");?>
