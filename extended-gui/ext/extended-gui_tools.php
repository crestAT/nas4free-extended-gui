<?php
/*
    extended-gui_tools.php

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
require("auth.inc");
require("guiconfig.inc");

$pgtitle = array(gettext("Extensions"), "Extended GUI ".$config['extended-gui']['version'], gettext("Tools"));

$hours = array(0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23);
$confirm_message = gettext("The selected operation will be completed. Please do not click any other buttons!");
$alert_message = gettext("Please wait for the previous operation to complete!");

function cronjob_process_updatenotification($mode, $data) {
	global $config;
	$retval = 0;
	switch ($mode) {
		case UPDATENOTIFY_MODE_NEW:
		case UPDATENOTIFY_MODE_MODIFIED:
			break;
		case UPDATENOTIFY_MODE_DIRTY:
			if (is_array($config['cron']['job'])) {
				$index = array_search_ex($data, $config['cron']['job'], "uuid");
				if (false !== $index) {
					unset($config['cron']['job'][$index]);
					write_config();
				}
			}
			break;
	}
	return $retval;
}

if ($_POST) {
    if (isset( $_POST['purge_save']) && $_POST['purge_save']) {
		unset($input_errors);
        if (isset($_POST['purge'])) {
            $config['extended-gui']['purge']['enable'] = isset($_POST['purge']) ? true : false;
            $config['extended-gui']['purge']['days'] = !empty($_POST['purge_days']) ? $_POST['purge_days'] : 30;
            $config['extended-gui']['purge']['startup'] = isset($_POST['purge_startup']) ? true : false;
            $config['extended-gui']['purge']['closedown'] = isset($_POST['purge_closedown']) ? true : false;
            $config['extended-gui']['purge']['schedule'] = isset($_POST['purge_schedule']) ? true : false;
            $config['extended-gui']['purge']['schedule_hour'] = $_POST['purge_schedule_hour'];
            // de/activate startup purge
            if ($config['extended-gui']['purge']['startup']) {
                if (is_array($config['rc']['postinit']) && is_array($config['rc']['postinit']['cmd'])) {
                    for ($i = 0; $i < count($config['rc']['postinit']['cmd']);) {
                        if (preg_match('/purge\.sh/', $config['rc']['postinit']['cmd'][$i])) break; ++$i; }
                }
                $config['rc']['postinit']['cmd'][$i] = "/var/scripts/purge.sh";
            }
            else {
                if (is_array($config['rc']['postinit']) && is_array($config['rc']['postinit']['cmd'])) {
            		for ($i = 0; $i < count($config['rc']['postinit']['cmd']);) {
            		if (preg_match('/purge\.sh/', $config['rc']['postinit']['cmd'][$i])) { unset($config['rc']['postinit']['cmd'][$i]);} else{}
            		++$i;
            		}
            	}
            }   // end of de/activate startup purge
            // de/activate closedown purge
            if ($config['extended-gui']['purge']['closedown']) {
                if (is_array($config['rc']['shutdown']) && is_array($config['rc']['shutdown']['cmd'])) {
                    for ($i = 0; $i < count($config['rc']['shutdown']['cmd']); ) {
                        if (preg_match('/purge\.sh/', $config['rc']['shutdown']['cmd'][$i])) break; ++$i; }
                }
                $config['rc']['shutdown']['cmd'][$i] = "/var/scripts/purge.sh";
            }
            else {
            	if (is_array($config['rc']['shutdown']) && is_array($config['rc']['shutdown']['cmd'])) {
            		for ($i = 0; $i < count($config['rc']['shutdown']['cmd']); ) {
             		if (preg_match('/purge\.sh/', $config['rc']['shutdown']['cmd'][$i])) { unset($config['rc']['shutdown']['cmd'][$i]); } else {}
            		++$i;
            		}
            	}
            }   // end of de/activate closedown purge

            // de/activate purge schedule
            if ($config['extended-gui']['purge']['schedule']) {
                $cronjob = array();
                $a_cronjob = &$config['cron']['job'];
                $uuid = isset($config['extended-gui']['purge']['schedule_uuid']) ? $config['extended-gui']['purge']['schedule_uuid'] : false;
                if (isset($uuid) && (FALSE !== ($cnid = array_search_ex($uuid, $a_cronjob, "uuid")))) {
                	$cronjob['enable'] = true;
                	$cronjob['uuid'] = $a_cronjob[$cnid]['uuid'];
                	$cronjob['desc'] = "Purge recycle bins (@ {$config['extended-gui']['purge']['schedule_hour']}:00)";
                	$cronjob['minute'] = $a_cronjob[$cnid]['minute'];
                	$cronjob['hour'] = $config['extended-gui']['purge']['schedule_hour'];
                	$cronjob['day'] = $a_cronjob[$cnid]['day'];
                	$cronjob['month'] = $a_cronjob[$cnid]['month'];
                	$cronjob['weekday'] = $a_cronjob[$cnid]['weekday'];
                	$cronjob['all_mins'] = $a_cronjob[$cnid]['all_mins'];
                	$cronjob['all_hours'] = $a_cronjob[$cnid]['all_hours'];
                	$cronjob['all_days'] = $a_cronjob[$cnid]['all_days'];
                	$cronjob['all_months'] = $a_cronjob[$cnid]['all_months'];
                	$cronjob['all_weekdays'] = $a_cronjob[$cnid]['all_weekdays'];
                	$cronjob['who'] = $a_cronjob[$cnid]['who'];
                	$cronjob['command'] = $a_cronjob[$cnid]['command'];
                } else {
                	$cronjob['enable'] = true;
                	$cronjob['uuid'] = uuid();
                	$cronjob['desc'] = "Purge recycle bins (@ {$config['extended-gui']['purge']['schedule_hour']}:00)";
                	$cronjob['minute'] = 0;
                	$cronjob['hour'] = $config['extended-gui']['purge']['schedule_hour'];
                	$cronjob['day'] = true;
                	$cronjob['month'] = true;
                	$cronjob['weekday'] = true;
                	$cronjob['all_mins'] = 0;
                	$cronjob['all_hours'] = 0;
                	$cronjob['all_days'] = 1;
                	$cronjob['all_months'] = 1;
                	$cronjob['all_weekdays'] = 1;
                	$cronjob['who'] = 'root';
                	$cronjob['command'] = "/var/scripts/purge.sh && logger purge: scheduled cleaning of recycle bins";
                    $config['extended-gui']['purge']['schedule_uuid'] = $cronjob['uuid'];
                }
                if (isset($uuid) && (FALSE !== $cnid)) {
            		$a_cronjob[$cnid] = $cronjob;
            		$mode = UPDATENOTIFY_MODE_MODIFIED;
            	} else {
            		$a_cronjob[] = $cronjob;
            		$mode = UPDATENOTIFY_MODE_NEW;
            	}
                updatenotify_set("cronjob", $mode, $cronjob['uuid']);
                write_config();
            }   // end of enable_schedule
            else {
            	if (is_array($config['cron'])) {
                    updatenotify_set("cronjob", UPDATENOTIFY_MODE_DIRTY, $config['extended-gui']['purge']['schedule_uuid']);
                	if (is_array($config['cron']['job'])) {
        				$index = array_search_ex($data, $config['cron']['job'], "uuid");
        				if (false !== $index) { unset($config['cron']['job'][$index]); }
        			}
        		}
            	write_config();
            }   // end of disable_schedule -> remove cronjob
    		$retval = 0;
    		if (!file_exists($d_sysrebootreqd_path)) {
    			$retval |= updatenotify_process("cronjob", "cronjob_process_updatenotification");
    			config_lock();
    			$retval |= rc_update_service("cron");
    			config_unlock();
    		}
    		$savemsg = get_std_save_message($retval);
    		if ($retval == 0) { updatenotify_delete("cronjob"); }
        }   // end of activate purge
        else {
            // remove purge startup
            if ( is_array($config['rc']['postinit'] ) && is_array( $config['rc']['postinit']['cmd'] ) ) {
        		for ($i = 0; $i < count($config['rc']['postinit']['cmd']);) {
        		if (preg_match('/purge\.sh/', $config['rc']['postinit']['cmd'][$i])) { unset($config['rc']['postinit']['cmd'][$i]);} else{}
        		++$i;
        		}
        	}
        	// remove purge closedown 
            if ( is_array($config['rc']['shutdown'] ) && is_array( $config['rc']['shutdown']['cmd'] ) ) {
        		for ($i = 0; $i < count($config['rc']['shutdown']['cmd']); ) {
         		if (preg_match('/purge\.sh/', $config['rc']['shutdown']['cmd'][$i])) { unset($config['rc']['shutdown']['cmd'][$i]); } else {}
        		++$i;
        		}
        	}
        	//remove purge schedule
           	if (is_array($config['cron'])) {
                updatenotify_set("cronjob", UPDATENOTIFY_MODE_DIRTY, $config['extended-gui']['purge']['schedule_uuid']);
            	if (is_array($config['cron']['job'])) {
    				$index = array_search_ex($data, $config['cron']['job'], "uuid");
    				if (false !== $index) { unset($config['cron']['job'][$index]); }
    			}
    		}
    		$retval = 0;
    		if (!file_exists($d_sysrebootreqd_path)) {
    			$retval |= updatenotify_process("cronjob", "cronjob_process_updatenotification");
    			config_lock();
    			$retval |= rc_update_service("cron");
    			config_unlock();
    		}
    		$savemsg = get_std_save_message($retval);
    		if ($retval == 0) { updatenotify_delete("cronjob"); }
            unset($config['extended-gui']['purge']);
        }   // end of remove purge
        $savemsg = get_std_save_message(write_config());
    }   // end of purge configuration save
    
    if (isset($_POST['purge_now']) && $_POST['purge_now']) {
		unset($input_errors);
       	mwexec("/var/scripts/purge.sh 0", true);
    }   // end of purge_now    

    if (isset($_POST['automount_save']) && $_POST['automount_save']) {
        $config['extended-gui']['automount'] = isset($_POST['automount']) ? true : false;
        $savemsg = get_std_save_message(write_config());
    }   // end of automount_save
}   // end of post	

include("fbegin.inc");?>  
<script type="text/javascript">
<!--
function update_change() {
	// Reload page
	window.document.location.href = 'extended-gui_tools.php?update=' + document.iform.update.value;
}

<!-- This function allows the pages to render the buttons impotent whilst carrying out various functions -->

function fetch_handler() {
    var varConfirm = <?php echo json_encode($confirm_message); ?>;
    var varAlert = <?php echo json_encode($alert_message); ?>;
	if ( document.iform.beenSubmitted ) alert(varAlert);
	else return confirm(varConfirm);
}

function purge_enable_change(enable_change) {
	var endis = !(document.iform.purge.checked || enable_change);
	document.iform.purge_days.disabled = endis;
	document.iform.purge_startup.disabled = endis;
	document.iform.purge_closedown.disabled = endis;
	document.iform.purge_schedule.disabled = endis;
	document.iform.purge_schedule_hour.disabled = endis;
	document.iform.purge_now.disabled = endis;
}
//-->
</script>
<form action="extended-gui_tools.php" method="post" name="iform" id="iform">
    <table width="100%" border="0" cellpadding="0" cellspacing="0">
    	<tr><td class="tabnavtbl">
    		<ul id="tabnav">
    			<li class="tabinact"><a href="extended-gui.php"><span><?=gettext("Configuration");?></span></a></li>
    			<li class="tabact"><a href="extended-gui_tools.php"><span><?=gettext("Tools");?></span></a></li>
                <li class="tabinact"><a href="extended-gui_update_extension.php"><span><?=gettext("Extension Maintenance");?></span></a></li>
    		</ul>
    	</td></tr>
        <tr><td class="tabcont">
            <?php if (!empty($input_errors)) print_input_errors($input_errors);?>
            <?php if (!empty($savemsg)) print_info_box($savemsg);?>
            <table width="100%" border="0" cellpadding="6" cellspacing="0">
            <?php html_titleline_checkbox("purge", gettext("Purge"), isset($config['extended-gui']['purge']['enable']) ? true : false, gettext("Enable"), "purge_enable_change(false)");?>
    			<?php html_text("purge_description", gettext("Description"), gettext("Clean recycle bins of CIFS/SMB shares (.recycle directories) from deleted files. Can be done automatically at system startup, closedown, at a specific hour as a daily schedule and/or on demand."));?>
                <tr><td class="vncell"><?=gettext("Active");?></td>
                <td class="vtable"><span name="purge_run" id="purge_run">
                    <input id="purge_startup" name="purge_startup" type="checkbox" class="checkbox" <?=isset($config['extended-gui']['purge']['startup']) ? 'checked' : '';?> />&nbsp;<?=gettext("at system startup");?>&nbsp;&nbsp;&nbsp;
                    <input id="purge_closedown" name="purge_closedown" type="checkbox" class="checkbox" <?=isset($config['extended-gui']['purge']['closedown']) ? 'checked' : '';?> />&nbsp;<?=gettext("at system closedown");?>&nbsp;&nbsp;&nbsp;
                    <input id="purge_schedule" name="purge_schedule" type="checkbox" class="checkbox" <?=isset($config['extended-gui']['purge']['schedule']) ? 'checked' : '';?> />&nbsp;<?=gettext("as daily schedule");?>&nbsp;&nbsp;&nbsp;
                </span></td></tr>
                <?php html_combobox("purge_schedule_hour", gettext("Daily schedule"), $config['extended-gui']['purge']['schedule_hour'], $hours, gettext("Choose an hour for daily purge of recycle bins."), false);?>
            	<?php html_inputbox("purge_days", gettext("Days"), !empty($config['extended-gui']['purge']['days']) ? $config['extended-gui']['purge']['days'] : 30, sprintf(gettext("Define the number of days after which files will be deleted from recycle bins. Default number of days are %d."), 30), true, 3);?>
    			<?php html_text("purge_bins", gettext("Recycle bins found"), `/var/scripts/purge.sh show`);?>
    			<?php html_separator();?>
            </table>
            <div id="purge_submit">
                <input id="purge_save" name="purge_save" type="submit" class="formbtn" value="<?=gettext("Save");?>" />
                <?php if (isset($config['extended-gui']['purge']['days']) && ($config['extended-gui']['purge']['days'] >= 0)) { ?>        
                    <input id="purge_now" name="purge_now" type="submit" class="formbtn" title="<?=gettext("Purge now all CIFS/SMB recycle bins!");?>" value="<?=gettext("Purge now");?>" onClick="return fetch_handler();" />
                <?php } ?>
            </div>
            <table width="100%" border="0" cellpadding="6" cellspacing="0">
			<?php html_separator();?>
            <?php html_titleline_checkbox("automount", gettext("USB Automount"), isset($config['extended-gui']['automount']) ? true : false, gettext("Enable"), "");?>
    			<?php html_text("automount_description", gettext("Description"), "Automatically mounting of USB drives and CD/DVDs. Un-mount / re-mount these drives via WebGUI function buttons (on ".gettext("Status")." | ".gettext("System").").");?>
    			<?php html_text("automount_prerequisites", gettext("Prerequisite"), "USB drives will be mounted and shown with their device names (e.g. da1s1) on ".gettext("Status")." | ".gettext("System").". Alternatively one can create a file in the root directory of each USB drive with the extension '*.<b>mounted</b>' (e.g. USB2000GB.mounted). The next time this USB drive will be mounted the file name will be used as an alias for the mount point and shown at ".gettext("Status")." | ".gettext("System").".");?>
            </table>
            <br /><?php html_remark("automount_warning", gettext("Warning"), "<b>Always un-mount drives before you detach them from the system, otherwise this could lead to serious problems. Use the USB Automount function on your own risc!</b><br />");?>
            <br /><input id="automount_save" name="automount_save" type="submit" class="formbtn" value="<?=gettext("Save");?>" />
		</td></tr>
	</table>
	<?php include("formend.inc");?>
</form>
<script type="text/javascript">
<!--
purge_enable_change(false);
//-->
</script>
<?php include("fend.inc");?>
