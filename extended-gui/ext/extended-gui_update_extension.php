<?php
/*
    extended-gui_update_extension.php
    
    Copyright (c) 2015 Andreas Schmidhuber
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

bindtextdomain("nas4free", "/usr/local/share/locale-rrd");
$pgtitle = array(gettext("Extensions"), "Extended GUI ".$config['extended-gui']['version'], gettext("Extension Maintenance"));

if (is_file("{$config['extended-gui']['log']}oneload")) { require_once("{$config['extended-gui']['log']}oneload"); }

$return_val = mwexec("fetch -o {$config['extended-gui']['log']}version.txt https://raw.github.com/crestAT/nas4free-extended-gui/master/extended-gui/version.txt", true);
if ($return_val == 0) { 
    $server_version = exec("cat {$config['extended-gui']['log']}version.txt"); 
    if ($server_version != $config['extended-gui']['version']) { $savemsg = sprintf(gettext("New extension version %s available, push '%s' button to install the new version!"), $server_version, gettext("Update Extension")); }
    mwexec("fetch -o {$config['extended-gui']['rootfolder']}release_notes.txt https://raw.github.com/crestAT/nas4free-extended-gui/master/extended-gui/release_notes.txt", false);
}
else { $server_version = gettext("Unable to retrieve version from server!"); }

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

if (isset($_POST['ext_remove']) && $_POST['ext_remove']) {
// remove start/stop commands
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
// unlink created links and remove extension pages
	if (is_dir ("/usr/local/www/ext/extended-gui")) {
	foreach ( glob( "{$config['extended-gui']['rootfolder']}ext/*.php" ) as $file ) {
	$file = str_replace("{$config['extended-gui']['rootfolder']}ext/", "/usr/local/www/", $file);         // trailing backslash !!!
	if ( is_link( $file ) ) { unlink( $file ); } else {} }
	mwexec ("rm -rf /usr/local/www/ext/extended-gui");
	}
// remove additional *.php files
	foreach ( glob( "{$config['extended-gui']['rootfolder']}files/*.php" ) as $file ) {
	   $file = str_replace("{$config['extended-gui']['rootfolder']}files/", "/usr/local/www/", $file);    // trailing backslash !!!
        if (is_file($file)) { unlink($file); } 
    }
// restore original pages
    require_once("{$config['extended-gui']['rootfolder']}extended-gui-stop.php");
// remove cronjobs
	if (is_array($config['cron']['job'])) {                                                            // check if cron jobs exists !!!
        $a_cronjob = &$config['cron']['job'];
        $uuid = isset($config['extended-gui']['schedule_uuid']) ? $config['extended-gui']['schedule_uuid'] : false;
        if (isset($uuid) && (FALSE !== ($cnid = array_search_ex($uuid, $a_cronjob, "uuid")))) {
        	$a_cronjob[$cnid]['enable'] = false;
        }
        if (isset($uuid) && (FALSE !== $cnid)) {
    		$mode = UPDATENOTIFY_MODE_DIRTY;
    
            updatenotify_set("cronjob", $mode, $uuid);
    
    		$retval = 0;
    		if (!file_exists($d_sysrebootreqd_path)) {
    			$retval |= updatenotify_process("cronjob", "cronjob_process_updatenotification");
    			config_lock();
    			$retval |= rc_update_service("cron");
    			config_unlock();
    		}
    		$savemsg = get_std_save_message($retval);
    		if ($retval == 0) {
    			updatenotify_delete("cronjob");
    		}
        }
	}
// remove application section from config.xml
	if ( is_array($config['extended-gui'] ) ) { unset( $config['extended-gui'] ); write_config();}
	header("Location:index.php");
}

if (isset($_POST['ext_update']) && $_POST['ext_update']) {
// download installer & install
    $return_val = mwexec("fetch -vo {$config['extended-gui']['rootfolder']}extended-gui-install.php 'https://raw.github.com/crestAT/nas4free-extended-gui/master/extended-gui/extended-gui-install.php'", true);
    if ($return_val == 0) {
        require_once("{$config['extended-gui']['rootfolder']}extended-gui-stop.php");
        require_once("{$config['extended-gui']['rootfolder']}extended-gui-install.php"); 
        require_once("{$config['extended-gui']['rootfolder']}extended-gui-start.php");
        header("Refresh:8");;
    }
    else { $input_errors[] = sprintf(gettext("Archive file %s not found, installation aborted!"), "{$config['extended-gui']['rootfolder']}extended-gui-install.php"); }
}
bindtextdomain("nas4free", "/usr/local/share/locale");
include("fbegin.inc");?>

<form action="extended-gui_update_extension.php" method="post" name="iform" id="iform">
<?php bindtextdomain("nas4free", "/usr/local/share/locale-rrd"); ?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr><td class="tabnavtbl">
		<ul id="tabnav">
			<li class="tabinact"><a href="extended-gui.php"><span><?=gettext("Configuration");?></span></a></li>
			<li class="tabinact"><a href="extended-gui_tools.php"><span><?=gettext("Tools");?></span></a></li>
            <li class="tabact"><a href="extended-gui_update_extension.php"><span><?=gettext("Extension Maintenance");?></span></a></li>
		</ul>
	</td></tr>
	<tr><td class="tabcont">
        <?php if (!empty($input_errors)) print_input_errors($input_errors);?>
        <?php if (!empty($savemsg)) print_info_box($savemsg);?>
        <table width="100%" border="0" cellpadding="6" cellspacing="0">
            <?php html_titleline(gettext("Extension Update"));?>
			<?php html_text("ext_version_current", gettext("Installed version"), $config['extended-gui']['version']);?>
			<?php html_text("ext_version_server", gettext("Latest version"), $server_version);?>
			<?php html_separator();?>
        </table>
        <div id="update_remarks">
            <?php html_remark("note_remove", gettext("Note"), gettext("Removing Extended GUI integration from NAS4Free will leave the installation folder untouched - remove the files using Windows Explorer, FTP or some other tool of your choice. <br /><b>Please note: this page will no longer be available.</b> You'll have to re-run Extended GUI extension installation to get it back on your NAS4Free."));?>
            <br />
            <input id="ext_update" name="ext_update" type="submit" class="formbtn" value="<?=gettext("Update Extension");?>" onclick="return confirm('<?=gettext("The selected operation will be completed. Please do not click any other buttons!");?>')" />
            <input id="ext_remove" name="ext_remove" type="submit" class="formbtn" value="<?=gettext("Remove Extension");?>" onclick="return confirm('<?=gettext("Do you really want to remove the extension from the system?");?>')" />
        </div>
        <table width="100%" border="0" cellpadding="6" cellspacing="0">
			<?php html_separator();?>
			<?php html_separator();?>
			<?php html_titleline(gettext("Extension")." ".gettext("Release Notes"));?>
			<tr>
                <td class="listt">
                    <div>
                        <textarea style="width: 98%;" id="content" name="content" class="listcontent" cols="1" rows="25" readonly="readonly"><?php unset($lines); exec("/bin/cat {$config['extended-gui']['rootfolder']}release_notes.txt", $lines); foreach ($lines as $line) { echo $line."\n"; }?></textarea>
                    </div>
                </td>
			</tr>
        </table>
        <?php include("formend.inc");?>
    </td></tr>
</table>
</form>
<?php include("fend.inc");?>
