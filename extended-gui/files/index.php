<?php
/*
	index.php

	Part of NAS4Free (http://www.nas4free.org).
	Copyright (c) 2012-2013 The NAS4Free Project <info@nas4free.org>.
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
// Configure page permission
$pgperm['allowuser'] = TRUE;

require("auth.inc");
require("guiconfig.inc");
require("zfs.inc");

$pgtitle = array(gettext("System information"));
$pgtitle_omit = true;

if (!isset($config['vinterfaces']['carp']) || !is_array($config['vinterfaces']['carp']))
	$config['vinterfaces']['carp'] = array();

$smbios = get_smbios_info();
$cpuinfo = system_get_cpu_info();

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

function get_userinfo() {
    $userinfo = exec("cat /tmp/extended-gui_user_online.log");
    return $userinfo;
}

function get_hostsinfo() {
    $hostsinfo = exec("cat /tmp/extended-gui_hosts_online.log");
    return $hostsinfo;
}

if (is_ajax()) {
	$sysinfo = system_get_sysinfo();
	$vipstatus = get_vip_status();
	$sysinfo['userinfo'] = get_userinfo();
	$sysinfo['hostsinfo'] = get_hostsinfo();
	$sysinfo['vipstatus'] = $vipstatus;
    if (is_array($sysinfo['diskusage'])) {
        for ($i = 0; $i < count($sysinfo['diskusage']); ++$i) {
            $mountpoint_details = explode('##', exec("cat /tmp/extended-gui_{$sysinfo['diskusage'][$i]['name']}.smart"));
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
            $mountpoint_details = explode('##', exec("cat /tmp/extended-gui_{$sysinfo['poolusage'][$i]['name']}.smart"));
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

function tblrow ($name, $value, $symbol = null, $id = null) {
	if(!$value) return;

	if($symbol == '&deg;')
		$value = sprintf("%.1f", $value);

	if($symbol == 'Hz')
		$value = sprintf("%d", $value);

	if ($symbol == ' seconds'
			&& $value > 60) {
		$minutes = (int) ($value / 60);
		$seconds = $value % 60;

		if ($minutes > 60) {
			$hours = (int) ($minutes / 60);
			$minutes = $minutes % 60;
			$value = $hours;
			$symbol = ' hours '.$minutes.' minutes '.$seconds.$symbol;
		} else {
			$value = $minutes;
			$symbol = ' minutes '.$seconds.$symbol;
		}
	}
	
	if ($symbol == 'pre') {
		$value = '<pre>'.$value;
		$symbol = '</pre>';
	}

	print(<<<EOD
  <td style='white-space:nowrap;'>
		<div id='ups_status'>
			<span name='ups_status_name' id='ups_status_name' class='name'> || <b>{$name}:</b></span>
<!-- 
  </td>
  <td style='white-space:nowrap;' colspan="3">
 -->
			{$value}{$symbol}
		</div>
	</td>
EOD
	."\n");
}

function tblrowbar ($name, $value, $symbol, $red, $yellow, $green) {
	if(!$value) return;

	$value = sprintf("%.1f", $value);

	$red = explode('-', $red);
	$yellow = explode('-', $yellow);
	$green = explode('-', $green);

	sort($red);
	sort($yellow);
	sort($green);

	if($value >= $red[0] && $value <= ($red[0]+9)) {
		$color = 'black';
		$bgcolor = 'red';
	}
	if($value >= ($red[0]+10) && $value <= $red[1]) {
		$color = 'white';
		$bgcolor = 'red';
	}
	if($value >= $yellow[0] && $value <= $yellow[1]) {
		$color = 'black';
		$bgcolor = 'yellow';
	}
	if($value >= $green[0] && $value <= ($green[0]+9)) {
		$color = 'black';
		$bgcolor = 'green';
	}	
	if($value >= ($green[0]+10) && $value <= $green[1]) {
		$color = 'white';
		$bgcolor = 'green';
	}

	$available = 100 - $value;
	$tooltip_used = sprintf("%s%%", $value);
	$tooltip_available = sprintf(gettext("%s%% available"), $available);
	$span_used = sprintf("%s%%", "<span name='ups_status_used' id='ups_status_used' class='capacity'>".$value."</span>");
	
	print(<<<EOD
<!--   <td style='white-space:nowrap; height:18px;'> -->
  <td style='white-space:nowrap;'>
	<div id='ups_status'>
		<span name='ups_status_name' id='ups_status_name' class='name'><b>{$name}</b>&nbsp;&nbsp;</span>
  </td>
  <td style='white-space:nowrap; width:1px;'>
		<img src="bar_left.gif" class="progbarl" alt="" /><img src="bar_blue.gif" name="ups_status_bar_used" id="ups_status_bar_used" width="{$value}" class="progbarcf" title="{$tooltip_used}" alt="" /><img src="bar_gray.gif" name="ups_status_bar_free" id="ups_status_bar_free" width="{$available}" class="progbarc" title="{$tooltip_available}" alt="" /><img src="bar_right.gif" class="progbarr" alt="" />
  <td style='white-space:nowrap; width:1px; text-align:right;'>
		&nbsp;&nbsp;{$span_used}
<!--   
  </td>
  <td>
 -->
   </td>
	</div>
  </td>
EOD
	."\n");
}

if(function_exists("date_default_timezone_set") and function_exists("date_default_timezone_get"))
     @date_default_timezone_set(@date_default_timezone_get());

/* === BEGIN ============================================================== */
/* @AFS																		*/
if ($_POST['amount']) { exec("/var/scripts/automount_usb.sh amount"); }
if ($_POST['umount']) { exec("/var/scripts/automount_usb.sh umount"); }
if ($_POST['rmount']) { exec("/var/scripts/automount_usb.sh rmount"); }
if ($_POST['auto_shutdown']) { exec("/var/scripts/autoshutdown.sh toggle"); }
if ($_POST['ai_00']) { exec("ataidle -S 20 /dev/ada0"); }
if ($_POST['ai_01']) { exec("ataidle -S 20 /dev/ada1"); }
if ($_POST['ai_02']) { exec("ataidle -S 20 /dev/ada2"); }
if ($_POST['ai_03']) { exec("ataidle -S 20 /dev/ada3"); }
if ($_POST['ai_04']) { exec("ataidle -S 20 /dev/ada4"); }
if ($_POST['ai_05']) { exec("ataidle -S 20 /dev/ada5"); }
if ($_POST['purge']) { exec("/var/scripts/purge.sh 1"); }
if ($_POST['fsck_all']) { exec("/var/scripts/fsck_all.sh &"); }
/* === ENDE ============================================================== */
?>
<?php include("fbegin.inc");?>
<script type="text/javascript">//<![CDATA[
$(document).ready(function(){
	var gui = new GUI;
	gui.recall(5000, 5000, 'index.php', null, function(data) {
		if ($('#userinfo').size() > 0)
    		$('#userinfo').html(data.userinfo);
		if ($('#hostsinfo').size() > 0)
    		$('#hostsinfo').html(data.hostsinfo);

		if ($('#vipstatus').size() > 0)
			$('#vipstatus').text(data.vipstatus);
		if ($('#uptime').size() > 0)
			$('#uptime').text(data.uptime);
		if ($('#date').size() > 0)
			$('#date').val(data.date);
		if ($('#memusage').size() > 0) {
			$('#memusage').val(data.memusage.caption);
			$('#memusageu').attr('width', data.memusage.percentage + 'px');
			$('#memusagef').attr('width', (100 - data.memusage.percentage) + 'px');
		}
		if ($('#loadaverage').size() > 0)
			$('#loadaverage').val(data.loadaverage);
		if (typeof(data.cputemp) != 'undefined')
			if ($('#cputemp').size() > 0)
				$('#cputemp').val(data.cputemp);
		if (typeof(data.cputemp2) != 'undefined') {
			for (var idx = 0; idx < data.cputemp2.length; idx++) {
				if ($('#cputemp'+idx).size() > 0)
					$('#cputemp'+idx).val(data.cputemp2[idx]);
			}
		}
		if (typeof(data.cpufreq) != 'undefined')
			if ($('#cpufreq').size() > 0)
				$('#cpufreq').val(data.cpufreq + 'MHz');
		if (typeof(data.cpuusage) != 'undefined') {
			if ($('#cpuusage').size() > 0) {
				$('#cpuusage').val(data.cpuusage + '%');
				$('#cpuusageu').attr('width', data.cpuusage + 'px');
				$('#cpuusagef').attr('width', (100 - data.cpuusage) + 'px');
			}
		}
		if (typeof(data.cpuusage2) != 'undefined') {
			for (var idx = 0; idx < data.cpuusage2.length; idx++) {
				if ($('#cpuusage'+idx).size() > 0) {
					$('#cpuusage'+idx).val(data.cpuusage2[idx] + '%');
					$('#cpuusageu'+idx).attr('width', data.cpuusage2[idx] + 'px');
					$('#cpuusagef'+idx).attr('width', (100 - data.cpuusage2[idx]) + 'px');
				}
			}
		}

		if (typeof(data.diskusage) != 'undefined') {
			for (var idx = 0; idx < data.diskusage.length; idx++) {
				var du = data.diskusage[idx];
				if ($('#diskusage_'+du.id+'_bar_used').size() > 0) {
					$('#diskusage_'+du.id+'_name').text(du.name);
					$('#diskusage_'+du.id+'_bar_used').attr('width', du.percentage + 'px');
					$('#diskusage_'+du.id+'_bar_used').attr('title', du['tooltip'].used);
					$('#diskusage_'+du.id+'_bar_free').attr('width', (100 - du.percentage) + 'px');
					$('#diskusage_'+du.id+'_bar_free').attr('title', du['tooltip'].available);
					$('#diskusage_'+du.id+'_capacity').text(du.capacity);
					$('#diskusage_'+du.id+'_total').text(du.size);
					$('#diskusage_'+du.id+'_used').text(du.used);
					$('#diskusage_'+du.id+'_free').text(du.avail);
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
				var du = data.poolusage[idx];
				if ($('#poolusage_'+du.id+'_bar_used').size() > 0) {
					$('#poolusage_'+du.id+'_name').text(du.name);
					$('#poolusage_'+du.id+'_bar_used').attr('width', du.percentage + 'px');
					$('#poolusage_'+du.id+'_bar_used').attr('title', du['tooltip'].used);
					$('#poolusage_'+du.id+'_bar_free').attr('width', (100 - du.percentage) + 'px');
					$('#poolusage_'+du.id+'_bar_free').attr('title', du['tooltip'].available);
					$('#poolusage_'+du.id+'_capacity').text(du.capacity);
					$('#poolusage_'+du.id+'_total').text(du.size);
					$('#poolusage_'+du.id+'_used').text(du.used);
					$('#poolusage_'+du.id+'_free').text(du.avail);
          			for (var idx1 = 0; idx1 < du.devs.length; idx1++) {
        				var devs = du.devs[idx1];
     					$('#poolusage_'+du.id+'_'+idx1+'_device').html(devs.device);
    					$('#poolusage_'+du.id+'_'+idx1+'_smart_state').html(devs.smart_state);
    					$('#poolusage_'+du.id+'_'+idx1+'_temp').html(devs.temp);
                    }
					$('#poolusage_'+du.id+'_space').html(du.space); 
					$('#poolusage_'+du.id+'_state').html(du.health);
				}
			}
		}

		if (typeof(data.swapusage) != 'undefined') {
			for (var idx = 0; idx < data.swapusage.length; idx++) {
				var su = data.swapusage[idx];
				if ($('#swapusage_'+su.id+'_bar_used').size() > 0) {
					$('#swapusage_'+su.id+'_bar_used').attr('width', su.percentage + 'px');
					$('#swapusage_'+su.id+'_bar_used').attr('title', su['tooltip'].used);
					$('#swapusage_'+su.id+'_bar_free').attr('width', (100 - su.percentage) + 'px');
					$('#swapusage_'+su.id+'_bar_free').attr('title', su['tooltip'].available);
					$('#swapusage_'+su.id+'_capacity').text(su.capacity);
					$('#swapusage_'+su.id+'_total').text(su.total);
					$('#swapusage_'+su.id+'_used').text(su.used);
					$('#swapusage_'+su.id+'_free').text(su.avail);
				}
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

$rowcounter = 13;       //@afs2 set/get # of rows for graphs
if (!empty($config['vinterfaces']['carp'])) { ++$rowcounter; }
if (!empty($cpuinfo['temperature']) || !empty($cpuinfo['temperature2'])) { ++$rowcounter; }
if (!empty($cpuinfo['freq'])) { ++$rowcounter; } 
$swapinfo = system_get_swap_info(); 
if (!empty($swapinfo)) { ++$rowcounter; }	
if (isset($config['extended-gui']['hide_cpu'])) { --$rowcounter; }	 
?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td class="tabcont">
    	<table width="100%" border="0" cellspacing="0" cellpadding="0">
 			  <tr>
			    <td colspan="3" class="listtopic"><?=gettext("System information");?></td>
			  </tr>          
			  <?php if (!empty($config['vinterfaces']['carp'])):?>
			  <tr>
			    <td width="25%" class="vncellt"><?=gettext("Virtual IP address");?></td>
			    <td class="listr"><span id="vipstatus"><?php echo htmlspecialchars(get_vip_status()); ?></vip></td>
			  </tr>
			  <?php endif;?>
			  <tr>
			    <td width="25%" class="vncellt"><?=gettext("Hostname");?></td>
			    <td class="listr"><?=system_get_hostname();?></td>
                <?php if (Session::isAdmin()):?>
<?php if (!isset($config['extended-gui']['hide_cpu_graph']) || !isset($config['extended-gui']['hide_lan_graph'])) { ?>
                    <td align="center" width="300" rowspan="<?=$rowcounter;?>" class="listr">
<?php if (!isset($config['extended-gui']['hide_cpu_graph'])) { ?>
                        <object id="graph" data="graph_index_cpu.php" type="image/svg+xml" width="300" height="150">
                            <param name="src" value="index.php" />
                        </object><br />
<?php } ?>
<?php if (!isset($config['extended-gui']['hide_lan_graph'])) { ?>
                        <br />
                        <form name="form2" action="index.php" method="get">
                            <select name="if" class="formfld" onchange="submit()">
                                <?php
                                    $curif = "lan";
                                    if (isset($_GET['if']) && $_GET['if'])
                                    	$curif = $_GET['if'];
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
<?php } ?>
                    </td>
<?php } ?>
                <?php endif;?>
			  </tr>
			  <tr>
			    <td width="25%" valign="top" class="vncellt"><?=gettext("Version");?></td>
			    <td class="listr"><strong><?=get_product_version();?> <?=get_product_versionname();?></strong> (<?=gettext("revision");?> <?=get_product_revision();?>)</td>
			  </tr>
			  <tr>
			    <td width="25%" valign="top" class="vncellt"><?=gettext("Build date");?></td>
			    <td class="listr"><?=get_product_buildtime();?>
			    </td>
			  </tr>
			  <tr>
			    <td width="25%" valign="top" class="vncellt"><?=gettext("Platform OS");?></td>
			    <td class="listr">
			      <?
			        exec("/sbin/sysctl -n kern.ostype", $ostype);
			        exec("/sbin/sysctl -n kern.osrelease", $osrelease);
			        exec("/sbin/sysctl -n kern.osreldate", $osreldate);
			        echo("$ostype[0] $osrelease[0] (kern.osreldate: $osreldate[0])");
			      ?>
			    </td>
			  </tr>
			  <tr>
			    <td width="25%" class="vncellt"><?=gettext("Platform");?></td>
			    <td class="listr">
			    	<?=sprintf(gettext("%s on %s"), $g['fullplatform'], $cpuinfo['model']);?>
			    </td>
			  </tr>
			  <tr>
			    <td width="25%" class="vncellt"><?=gettext("System");?></td>
			    <td class="listr"><?=htmlspecialchars($smbios['planar']['maker']);?> <?=htmlspecialchars($smbios['planar']['product']);?></td>
			    </td>
			  </tr>
			  <tr>
			    <td width="25%" class="vncellt"><?=gettext("System bios");?></td>
			    <td class="listr"><?=htmlspecialchars($smbios['bios']['vendor']);?> <?=sprintf(gettext("version:"));?> <?=htmlspecialchars($smbios['bios']['version']);?> <?=htmlspecialchars($smbios['bios']['reldate']);?></td>
			    </td>
			  </tr>
			  <tr>
			    <td width="25%" class="vncellt"><?=gettext("System time");?></td>
			    <td class="listr">
			      <input style="padding: 0; border: 0;" size="30" name="date" id="date" value="<?=htmlspecialchars(shell_exec("date"));?>" />
			    </td>
			  </tr>
			  <tr>
			    <td width="25%" class="vncellt"><?=gettext("System uptime");?></td>
			    <td class="listr">
						<?php $uptime = system_get_uptime();?>
						<span name="uptime" id="uptime"><?=htmlspecialchars($uptime);?></span>
			    </td>
			  </tr>
			  <?php if (Session::isAdmin()):?>
			  <?php if ($config['lastchange']):?>
		    <tr>
		      <td width="25%" class="vncellt"><?=gettext("Last config change");?></td>
		      <td class="listr">
						<input style="padding: 0; border: 0;" size="30" name="lastchange" id="lastchange" value="<?=htmlspecialchars(date("D M j G:i:s T Y", $config['lastchange']));?>" />
		      </td>
		    </tr>
				<?php endif;?>
				<?php if (!empty($cpuinfo['temperature2'])):
					echo "<tr>";
					echo "<td width='25%' class='vncellt'>".gettext("CPU temperature")."</td>";
					echo "<td class='listr'>";
					echo "<table width='100%' border='0' cellspacing='0' cellpadding='0'><tr><td>\n";
					$cpus = system_get_cpus();
					for ($idx = 0; $idx < $cpus; $idx++) {
						echo "<input style='padding: 0; border: 0;' size='2' name='cputemp${idx}' id='cputemp${idx}' value='".htmlspecialchars($cpuinfo['temperature2'][$idx])."' />";
    					echo $idx['temperature2']."&deg;C &nbsp;&nbsp;";	
					}
					echo "</table></td>";
					echo "</tr>\n";
				?>
				<?php elseif (!empty($cpuinfo['temperature'])):?>
				<tr>
					<td width="25%" class="vncellt"><?=gettext("CPU temperature");?></td>
					<td class="listr">
						<input style="padding: 0; border: 0;" size="30" name="cputemp" id="cputemp" value="<?=htmlspecialchars($cpuinfo['temperature']);?>" />
					</td>
				</tr>
				<?php endif;?>
				<?php if (!empty($cpuinfo['freq'])):?>
				<tr>
					<td width="25%" class="vncellt"><?=gettext("CPU frequency");?></td>
					<td class="listr">
						<input style="padding: 0; border: 0;" size="30" name="cpufreq" id="cpufreq" value="<?=htmlspecialchars($cpuinfo['freq']);?>MHz" title="<?=sprintf(gettext("Levels (MHz/mW): %s"), $cpuinfo['freqlevels']);?>" />
					</td>
				</tr>
				<?php endif;?>
<?php if (!isset($config['extended-gui']['hide_cpu'])) { ?>
				<tr>
					<td width="25%" class="vncellt"><?=gettext("CPU usage");?></td>
					<td class="listr">
				    	<table width="100%" border="0" cellspacing="0" cellpadding="0"><tr><td>
						<?php
						$percentage = 0;
						echo "<img src='bar_left.gif' class='progbarl' alt='' />";
						echo "<img src='bar_blue.gif' name='cpuusageu' id='cpuusageu' width='" . $percentage . "' class='progbarcf' alt='' />";
						echo "<img src='bar_gray.gif' name='cpuusagef' id='cpuusagef' width='" . (100 - $percentage) . "' class='progbarc' alt='' />";
						echo "<img src='bar_right.gif' class='progbarr' alt='' /> ";
						?>
						<input style="padding: 0; border: 0;" size="30" name="cpuusage" id="cpuusage" value="<?=gettext("Updating in 5 seconds.");?>" />
					</td></tr>
						<?php
    if (!isset($config['extended-gui']['hide_cpu_usage'])) {
						$cpus = system_get_cpus();
						if ($cpus > 1) {
							echo "<tr><td><hr size='1' /></td></tr>";
							for ($idx = 0; $idx < $cpus; $idx++) {
								$percentage = 0;
								echo "<tr><td>";
								echo "<img src='bar_left.gif' class='progbarl' alt='' />";
								echo "<img src='bar_blue.gif' name='cpuusageu${idx}' id='cpuusageu${idx}' width='" . $percentage . "' class='progbarcf' alt='' />";
								echo "<img src='bar_gray.gif' name='cpuusagef${idx}' id='cpuusagef${idx}' width='" . (100 - $percentage) . "' class='progbarc' alt='' />";
								echo "<img src='bar_right.gif' class='progbarr' alt='' /> ";
								echo "<input style='padding: 0; border: 0;' size='30' name='cpuusage${idx}' id='cpuusage${idx}' value=\"".gettext("Updating in 5 seconds.")."\" />";
								echo "</td></tr>";
							}
						}
    }
						?>
					</table>
					</td>
				</tr>
<?php } ?>
			  <tr>
			    <td width="25%" class="vncellt"><?=gettext("Memory usage");?></td>
			    <td class="listr">
						<?php
						$raminfo = system_get_ram_info();
						$percentage = round(($raminfo['used'] * 100) / $raminfo['total'], 0);
						echo "<img src='bar_left.gif' class='progbarl' alt='' />";
						echo "<img src='bar_blue.gif' name='memusageu' id='memusageu' width='" . $percentage . "' class='progbarcf' alt='' />";
						echo "<img src='bar_gray.gif' name='memusagef' id='memusagef' width='" . (100 - $percentage) . "' class='progbarc' alt='' />";
						echo "<img src='bar_right.gif' class='progbarr' alt='' /> ";
						?>
						<input style="padding: 0; border: 0;" size="30" name="memusage" id="memusage" value="<?=sprintf(gettext("%d%% of %dMiB"), $percentage, round($raminfo['physical'] / 1024 / 1024));?>" />
			    </td>
			  </tr>
				<?php $swapinfo = system_get_swap_info(); if (!empty($swapinfo)):?>
				<tr>
					<td width="25%" class="vncellt"><?=gettext("Swap usage");?></td>
					<td class="listr" nowrap >
						<table width="100%" border="0" cellspacing="0" cellpadding="1">
							<?php
							array_sort_key($swapinfo, "device");
							$ctrlid = 0;
							foreach ($swapinfo as $swapk => $swapv) {
                                $swapv['device'] = str_replace("/dev/", "", trim($swapv['device'])); 						      
								$percent_used = rtrim($swapv['capacity'], "%");
								$tooltip_used = sprintf(gettext("%sB used of %sB"), $swapv['used'], $swapv['total']);
								$tooltip_available = sprintf(gettext("%sB available of %sB"), $swapv['avail'], $swapv['total']);

								echo "<tr><td><div id='swapusage'>";
								echo "<img src='bar_left.gif' class='progbarl' alt='' />";
								echo "<img src='bar_blue.gif' name='swapusage_{$ctrlid}_bar_used' id='swapusage_{$ctrlid}_bar_used' width='{$percent_used}' class='progbarcf' title='{$tooltip_used}' alt='' />";
								echo "<img src='bar_gray.gif' name='swapusage_{$ctrlid}_bar_free' id='swapusage_{$ctrlid}_bar_free' width='" . (100 - $percent_used) . "' class='progbarc' title='{$tooltip_available}' alt='' />";
								echo "<img src='bar_right.gif' class='progbarr' alt='' /> ";
								echo sprintf(gettext("%s of %sB"),
									"&nbsp;<span name='swapusage_{$ctrlid}_capacity' id='swapusage_{$ctrlid}_capacity' class='capacity'>{$swapv['capacity']}</span>",
									$swapv['total']);
								echo " || ";
								echo sprintf(gettext("Device: %s | Total: %s | Used: %s | Free: %s"),
									"<span name='swapusage_{$ctrlid}_device' id='swapusage_{$ctrlid}_device' class='device'>{$swapv['device']}</span>",
									"<span name='swapusage_{$ctrlid}_total' id='swapusage_{$ctrlid}_total' class='total'>{$swapv['total']}</span>",
									"<span name='swapusage_{$ctrlid}_used' id='swapusage_{$ctrlid}_used' class='used' style='color:blue'>{$swapv['used']}</span>",
									"<span name='swapusage_{$ctrlid}_free' id='swapusage_{$ctrlid}_free' class='free' style='color:green'>{$swapv['avail']}</span>");
								echo "</div></td></tr>";

								$ctrlid++;
								if ($ctrlid < count($swapinfo))
										echo "<tr><td><hr size='1' /></td></tr>";
							}?>
						</table>
					</td>
				</tr>
				<?php endif;?>
				<tr>
			  	<td width="25%" class="vncellt"><?=gettext("Load averages");?></td>
					<td class="listr">
						<?php
						exec("uptime", $result);
						$loadaverage = substr(strrchr($result[0], "load averages:"), 15);
						?>
						<input style="padding: 0; border: 0;" size="14" name="loadaverage" id="loadaverage" value="<?=$loadaverage;?>" />
						<?="<small>[<a href='status_process.php'>".gettext("Show process information")."</a></small>]";?>
			    </td>
			  </tr>
				<tr>
			    <td width="25%" class="vncellt"><?=gettext("Disk space usage");?></td>
			    <td class="listr" colspan="2">
                <table border="0" cellspacing="0" cellpadding="1">
				<?php
				    $diskusage = system_get_mount_usage();
				    if (!empty($diskusage)) {
				      	array_sort_key($diskusage, "name");
				      	$index = 0;
						foreach ($diskusage as $diskusagek => $diskusagev) {
							$ctrlid = get_mount_fsid($diskusagev['filesystem'], $diskusagek);
                            $mountpoint_details = explode('##', exec("cat /tmp/extended-gui_{$diskusagev['name']}.smart"));
                            $diskusagev['space'] = $mountpoint_details[1];
                            $pool_details = explode('#', $mountpoint_details[0]);
                            foreach($pool_details as $a_pool) {
                                $smart_details = explode('|', $a_pool);
                                $a_pool_details['device'] = $smart_details[0];
                                $a_pool_details['smart_state'] = $smart_details[1];
                                $a_pool_details['temp'] = $smart_details[2];
                                $diskusagev['devs'][] = $a_pool_details;
                            }
							$percent_used = rtrim($diskusagev['capacity'],"%");
							$tooltip_used = sprintf(gettext("%sB used of %sB"), $diskusagev['used'], $diskusagev['size']);
							$tooltip_available = sprintf(gettext("%sB available of %sB"), $diskusagev['avail'], $diskusagev['size']);
							echo "<tr style='height:22px'><td><div id='diskusage'>";
							echo "<span name='diskusage_{$ctrlid}_name' id='diskusage_{$ctrlid}_name' class='name'>{$diskusagev['name']}</span>";
							echo "</td><td nowrap>&nbsp;&nbsp;<img src='bar_left.gif' class='progbarl' alt='' />";
							echo "<img src='bar_blue.gif' name='diskusage_{$ctrlid}_bar_used' id='diskusage_{$ctrlid}_bar_used' width='{$percent_used}' class='progbarcf' title='{$tooltip_used}' alt='' />";
							echo "<img src='bar_gray.gif' name='diskusage_{$ctrlid}_bar_free' id='diskusage_{$ctrlid}_bar_free' width='" . (100 - $percent_used) . "' class='progbarc' title='{$tooltip_available}' alt='' />";
							echo "<img src='bar_right.gif' class='progbarr' alt='' /> ";
							echo sprintf(gettext("%s of %sB"),
								"&nbsp;&nbsp;</td><td nowrap align='left'><span name='diskusage_{$ctrlid}_capacity' id='diskusage_{$ctrlid}_capacity' class='capacity'>{$diskusagev['capacity']}</span>", $diskusagev['size']);
							echo "</td><td nowrap> || ";
							echo sprintf(gettext("Total: %s | Used: %s | Free: %s"),
								"<span name='diskusage_{$ctrlid}_total' id='diskusage_{$ctrlid}_total' class='total' style='display:inline-block; width:35px; text-align:right; font-weight:bold'>{$diskusagev['size']}</span>",
								"<span name='diskusage_{$ctrlid}_used' id='diskusage_{$ctrlid}_used' class='used' style='display:inline-block; width:35px; text-align:right; font-weight:bold; color:blue'>{$diskusagev['used']}</span>",
								"<span name='diskusage_{$ctrlid}_free' id='diskusage_{$ctrlid}_free' class='free' style='display:inline-block; width:35px; text-align:right; font-weight:bold; color:green'>{$diskusagev['avail']}</span>");
							echo " ||";
                            echo "</td><td><table style='width:200px'>";
                            foreach($diskusagev['devs'] as $idx => $devs) {
                                echo "<tr><td style='white-space:nowrap; width:34px;'>";
								echo " <span name='diskusage_{$ctrlid}_{$idx}_device' id='diskusage_{$ctrlid}_{$idx}_device' class='device'>{$devs['device']}</span>";
								echo "</td><td style='white-space:nowrap; width:70px;'>-> <span name='diskusage_{$ctrlid}_{$idx}_smart_state' id='diskusage_{$ctrlid}_{$idx}_smart_state' class='state'>{$devs['smart_state']}</span>";
								echo "</td><td style='white-space:nowrap;'> | Temp: <span name='diskusage_{$ctrlid}_{$idx}_temp' id='diskusage_{$ctrlid}_{$idx}_temp' class='temp' style='font-weight:bold'>{$devs['temp']}</span>";
                                echo "</td></tr>";
                            }
                            echo "</table>";
                            echo "</td><td style='width:60px;'><span name='diskusage_{$ctrlid}_space' id='diskusage_{$ctrlid}_space'>{$diskusagev['space']}</span>";
							echo "</td><td style='white-space:nowrap; width:70px;'>&nbsp;</td><td width='500px'>&nbsp;</div></td></tr>";
						}
					}
					$zfspools = zfs_get_pool_list();
					if (!empty($zfspools)) {
						if (!empty($diskusage)) {echo "<tr><td colspan='8'><hr size='1' /></td></tr>";}
						array_sort_key($zfspools, "name");
						$index = 0;
						foreach ($zfspools as $poolk => $poolv) {
							$ctrlid = $poolv['name'];
                            $mountpoint_details = explode('##', exec("cat /tmp/extended-gui_{$poolv['name']}.smart"));
                            $poolv['space'] = $mountpoint_details[1];
                            $pool_details = explode('#', $mountpoint_details[0]);
                            foreach($pool_details as $a_pool) {
                                $smart_details = explode('|', $a_pool);
                                $a_pool_details['device'] = $smart_details[0];
                                $a_pool_details['smart_state'] = $smart_details[1];
                                $a_pool_details['temp'] = $smart_details[2];
                                $poolv['devs'][] = $a_pool_details;
                            }
							$percent_used = rtrim($poolv['cap'],"%");
							$tooltip_used = sprintf(gettext("%sB used of %sB"), $poolv['used'], $poolv['size']);
							$tooltip_available = sprintf(gettext("%sB available of %sB"), $poolv['avail'], $poolv['size']);
							echo "<tr><td><div id='diskusage'>";
							echo "<span name='poolusage_{$ctrlid}_name' id='poolusage_{$ctrlid}_name' class='name'>{$poolv['name']}</span>";
							echo " </td><td nowrap>&nbsp;&nbsp;<img src='bar_left.gif' class='progbarl' alt='' />";
							echo "<img src='bar_blue.gif' name='poolusage_{$ctrlid}_bar_used' id='poolusage_{$ctrlid}_bar_used' width='{$percent_used}' class='progbarcf' title='{$tooltip_used}' alt='' />";
							echo "<img src='bar_gray.gif' name='poolusage_{$ctrlid}_bar_free' id='poolusage_{$ctrlid}_bar_free' width='" . (100 - $percent_used) . "' class='progbarc' title='{$tooltip_available}' alt='' />";
							echo "<img src='bar_right.gif' class='progbarr' alt='' /> ";
							echo sprintf(gettext("%s of %sB"),
								"&nbsp;&nbsp;</td><td nowrap align='left'><span name='poolusage_{$ctrlid}_capacity' id='poolusage_{$ctrlid}_capacity' class='capacity'>{$poolv['cap']}</span>", $poolv['size']);
							echo "</td><td nowrap> || ";
							echo sprintf(gettext("Total: %s | Used: %s | Free: %s"),
								"<span name='poolusage_{$ctrlid}_total' id='poolusage_{$ctrlid}_total' class='total' style='display:inline-block; width:35px; text-align:right; font-weight:bold'>{$poolv['size']}</span>",
								"<span name='poolusage_{$ctrlid}_used' id='poolusage_{$ctrlid}_used' class='used' style='display:inline-block; width:35px; text-align:right; font-weight:bold; color:blue'>{$poolv['used']}</span>",
								"<span name='poolusage_{$ctrlid}_free' id='poolusage_{$ctrlid}_free' class='free' style='display:inline-block; width:35px; text-align:right; font-weight:bold; color:green'>{$poolv['avail']}</span>");
							echo " ||";
                            echo "</td><td><table style='width:200px'>";
                            foreach($poolv['devs'] as $idx => $devs) {
                                echo "<tr><td style='white-space:nowrap; width:34px;'>";
								echo " <span name='poolusage_{$ctrlid}_{$idx}_device' id='poolusage_{$ctrlid}_{$idx}_device' class='device'>{$devs['device']}</span>";
								echo "</td><td style='white-space:nowrap; width:70px;'>-> <span name='poolusage_{$ctrlid}_{$idx}_smart_state' id='poolusage_{$ctrlid}_{$idx}_smart_state' class='state'>{$devs['smart_state']}</span>";
								echo "</td><td style='white-space:nowrap;'> | Temp: <span name='poolusage_{$ctrlid}_{$idx}_temp' id='poolusage_{$ctrlid}_{$idx}_temp' class='temp' style='font-weight:bold'>{$devs['temp']}</span>";
                                echo "</td></tr>";
                            }
                            echo "</table>";
							echo "</td><td style='width:60px;'><span name='poolusage_{$ctrlid}_space' id='poolusage_{$ctrlid}_space'>{$poolv['space']}</span>";
							echo "</td><td style='white-space:nowrap; width:70px;'><a href='disks_zfs_zpool_info.php?pool={$poolv['name']}'><span name='poolusage_{$ctrlid}_state' id='poolusage_{$ctrlid}_state' class='state'>{$poolv['health']}</span></a>";
							echo "</td><td width='500px'>&nbsp;</div></td></tr>";

							if (++$index < count($zfspools))
								echo "<tr><td colspan='8'><hr size='1' /></td></tr>";
						}
					}
					if (empty($diskusage) && empty($zfspools)) {
						echo "<tr><td>";
						echo gettext("No disk configured");
						echo "</td></tr>";
					}
				?>
				</table>
				</td>                                                                          
				</tr>
<?php if (isset($config['ups']['enable'])) { ?>
				<tr>
					<td width="25%" class="vncellt"><?=gettext("UPS Status");?></td>
					<td class="listr" colspan="2">
						<table border="0" cellspacing="0" cellpadding="2">
							<?php if (!isset($config['ups']['enable'])):?>
								<tr>
									<td>
										<input style="padding: 0; border: 0;" size="17" name="upsstatus" id="upsstatus" value="<?=gettext("UPS disabled");?>" />
										<?=" <small> [<a href='diag_infos_ups.php'>".gettext("Show ups information")."</a></small>]";?>
									</td>
								</tr>
							<?php else:?>
								<?php
								$cmd = "/usr/local/bin/upsc {$config['ups']['upsname']}@{$config['ups']['ip']}";
								$handle = popen($cmd, 'r');
								
								if($handle) {
									$read = fread($handle, 4096);
									pclose($handle);

									$lines = explode("\n", $read);
									$ups = array();
									foreach($lines as $line) {
										$line = explode(':', $line);
										$ups[$line[0]] = trim($line[1]);
									}

									if(count($lines) == 1)
										tblrow('ERROR:', 'Data stale!');

									$status = explode(' ', $ups['ups.status']);
									foreach($status as $condition) {
										if($disp_status) $disp_status .= ', ';
										switch ($condition) {
											case 'WAIT':
												$disp_status .= "<font color=orange><b>".gettext('UPS Waiting')."</b></font>";
												break;
										case 'OFF':
												$disp_status .= "<font color=red><b>".gettext('UPS Off Line')."</b></font>";
												break;
										case 'OL':
												$disp_status .= "<font color=green><b>".gettext('UPS On Line')."</b></font>";
												break;
										case 'OB':
												$disp_status .= "<font color=red><b>".gettext('UPS On Battery')."</b></font>";
												break;
										case 'TRIM':
												$disp_status .= "<font color=orange><b>".gettext('SmartTrim')."</b></font>";
												break;
										case 'BOOST':
												$disp_status .= "<font color=orange><b>".gettext('SmartBoost')."</b></font>";
												break;
										case 'OVER':
												$disp_status .= "<font color=red><b>".gettext('Overload')."</b></font>";
												break;
										case 'LB':
												$disp_status .= "<font color=red><b>".gettext('Battery Low')."</b></font>";
												break;
										case 'RB':
												$disp_status .= "<font color=red><b>".gettext('Replace Battery UPS')."</b></font>";
												break;
										case 'CAL':
												$disp_status .= "<font color=orange><b>".gettext('Calibration Battery')."</b></font>";
												break;
										case 'CHRG':
												$disp_status .= "<font color=orange><b>".gettext('Charging Battery')."</b></font>";
												break;
										default:
												$disp_status .= $condition;
												break;
									}
								}
/* 
echo "<tr>";
 									tblrow(gettext('Status'), $disp_status. " <small>[<a href='diag_infos_ups.php'>".gettext("Show ups information")."</a></small>]");
echo "</tr>";
 */
echo "<tr>";
									tblrowbar(gettext('Battery Level'), $ups['battery.charge'], '%', '0-29' ,'30-79', '80-100');
 									tblrow(gettext('Status'), $disp_status. " <small>[<a href='diag_infos_ups.php'>".gettext("Show ups information")."</a></small>]");
echo "</tr>";
echo "<tr>";
									tblrowbar(gettext('Load'), $ups['ups.load'], '%', '100-80', '79-60', '59-0');
            						tblrow(gettext('Remaining battery runtime'), $ups['battery.runtime'], ' seconds');
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
						</table>
					</td>
				</tr>
<?php } ?>
<?php if (isset($config['extended-gui']['user'])) { ?>
			  <tr>
			    <td width="25%" valign="top" class="vncellt"><?=gettext("Users");?></td>
			    <td class="listr" colspan="2"><span name="userinfo" id="userinfo"><?php echo get_userinfo(); ?></span></td>
			  </tr>
<?php } ?>
<?php if (isset($config['extended-gui']['hosts'])) { ?>
			  <tr>
			    <td width="25%" valign="top" class="vncellt"><?=gettext("Hosts");?></td>
			    <td class="listr" colspan="2"><span name="hostsinfo" id="hostsinfo"><?php echo get_hostsinfo(); ?></span></td>
			  </tr>
<?php } ?>
<?php if (isset($config['extended-gui']['services'])) { ?>
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
	<form action="index.php" method="post" name="iform" id="iform">
		<br>
<?php if (isset($config['extended-gui']['buttons'])) { ?>
<!--
		<input name="ai_00" type="submit" class="formbtn" value="<?=gettext("AI 0");?>">
		<input name="ai_01" type="submit" class="formbtn" value="<?=gettext("AI 1");?>">
		<input name="ai_02" type="submit" class="formbtn" value="<?=gettext("AI 2");?>">
		<input name="ai_03" type="submit" class="formbtn" value="<?=gettext("AI 3");?>">
		<input name="ai_04" type="submit" class="formbtn" value="<?=gettext("AI 4");?>">
		<input name="ai_05" type="submit" class="formbtn" value="<?=gettext("AI 5");?>">
 		<input name="amount" type="submit" class="formbtn" value="<?=gettext("Un-mount ATA-Drives");?>">
 		<input name="fsck_all" type="submit" class="formbtn" value="<?=gettext("FSCK All");?>"> 
--> 		
<?php if (isset($config['extended-gui']['purge']['enable'])) { ?>
		<input name="purge" type="submit" class="formbtn" value="<?=gettext("Purge 1 Day");?>">
<?php } ?>
<?php } ?>
<?php if (isset($config['extended-gui']['automount'])) { ?>
		<input name="umount" type="submit" class="formbtn" value="<?=gettext("Un-mount USB-Drives");?>">
		<input name="rmount" type="submit" class="formbtn" value="<?=gettext("Re-mount USB-Drives");?>">
<?php } ?>
<!--     	<input name="auto_shutdown" type="submit" class="formbtn" value="<?=gettext("Autoshutdown");?>"> -->
 		<?php include("formend.inc"); ?>
	</form>
	</td>
</center>
<?php endif;?>
<?php include("fend.inc");?>