<?php
/*
	system.inc

	Part of NAS4Free (http://www.nas4free.org).
	Copyright (c) 2012-2013 The NAS4Free Project <info@nas4free.org>.
	All rights reserved.

	Portions of freenas (http://www.freenas.org).
	Copyright (c) 2005-2011 by Olivier Cochard (olivier@freenas.org).
	All rights reserved.

	Portions of m0n0wall (http://m0n0.ch/wall).
	Copyright (c) 2003-2006 Manuel Kasper <mk@neon1.net>.
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
require_once("functions.inc");
require_once("util.inc");
require_once("rc.inc");

function system_reboot() {
	// Initiate halt. Everything will be done automatically
	// in /etc/rc.shutdown by executing rc-init scripts in
	// reverse order (the 'KEYWORD: shutdown' must be defined).
	mwexec("/sbin/shutdown -r now");
}

function system_halt() {
	// Initiate halt. Everything will be done automatically
	// in /etc/rc.shutdown by executing rc-init scripts in
	// reverse order (the 'KEYWORD: shutdown' must be defined).
	mwexec("/sbin/shutdown -p now");
}

/* Init language environment */
function system_language_load()
{
	global $config, $g_languages;

	/* Get the language configured. */
	$language = $config['system']['language'];
	$domain = strtolower(get_product_name());
	$codeset = $g_languages[$language]['codeset'];

	putenv("LANG=$language");
	setlocale(LC_MESSAGES, $language);
	bindtextdomain($domain, "/usr/local/share/locale");
	bind_textdomain_codeset($domain, $codeset);
	textdomain($domain);
}

/* Get the codeset of the current configured language. */
/* Return: String containing codeset of current laguage. */
function system_get_language_codeset() {
	global $config, $g_languages;

	$language = $config['system']['language'];
	$codeset = $g_languages[$language]['codeset'];

	if (empty($codeset))
		$codeset = "UTF-8"; // Set default codeset.

	return $codeset;
}

/* Get the entity of LanguageCode of the current configured language. */
/* Return: String containing language code of current laguage. */
function system_get_language_code() {
	global $config, $g_languages;

	// a language code, as per [RFC3066]
	$language = $config['system']['language'];
	//$code = $g_languages[$language]['code'];
	$code = str_replace("_", "-", $language);

	if (empty($code))
		$code = "en-US"; // Set default code.

	return $code;
}

// Get list of available groups from /etc/group.
// Result:
//	Array (
//		[wheel] => 0
//		[sshd] => 22
//		[www] => 80
//		[nobody] => 65534
//		[admin] => 1000
//		...
//	)
function system_get_group_list() {
	// List of groups to filter from result list.
	$filterlist = array("_dhcp", "_pflogd");

	$grouplist = array();

	preg_match_all("/(\S+):\*:(\d+):.*\n/", @file_get_contents("/etc/group"), $matches, PREG_SET_ORDER);

	if (is_array($matches)) {
		foreach ($matches as $group) {
			if (false === in_array($group[1], $filterlist)) {
				$grouplist[$group[1]] = $group[2];
			}
		}

		ksort($grouplist);
	}

	return $grouplist;
}

// Get list of available users from /etc/master.passwd.
// Result:
//  Array (
//    [test] => Array ( [name] => test
//                      [password] => $1$yuQLaTPN$lkwYlZEB7B8n85flXPkHd0
//                      [uid] => 1001
//                      [gid] => 1001
//                      [class] =>
//                      [change] => 0
//                      [expire] => 0
//                      [gecos] => test
//                      [home_dir] => /mnt
//                      [shell] => /usr/local/bin/scponly
//                    )
//    [root] => ...
//    [toor] => ..
//    [daemon] => ...
//    [operator] => ...
//    ...
//  )
function system_get_user_list() {
	$userlist = array();

	foreach (explode("\n", @file_get_contents("/etc/master.passwd")) as $userinfov) {
		if (empty($userinfov))
			continue;

		// Extract user information
		$userinfo = explode(":", $userinfov);

		$user = array();
		$user['name'] = $userinfo[0];
		$user['password'] = $userinfo[1];
		$user['uid'] = $userinfo[2];
		$user['gid'] = $userinfo[3];
		$user['class'] = $userinfo[4];
		$user['change'] = $userinfo[5];
		$user['expire'] = $userinfo[6];
		$user['gecos'] = $userinfo[7];
		$user['home_dir'] = $userinfo[8];
		$user['shell'] = $userinfo[9];

		$userlist[$user['name']] = $user;
	}

	return $userlist;
}

// SMBIOS get various info
function get_smbios_info() {
	$smbios = array();
	$cmd = "/bin/kenv -q";
	unset($rawdata, $rc);
	mwexec2($cmd, $rawdata, $rc);
	if ($rc != 0) {
		return $smbios;
	}
	foreach ($rawdata as $line) {
		if (preg_match("/^smbios\.([^\.=]+)\.([^\.=]+)=\"(.*)\"/", $line, $match)) {
			$smbios[$match[1]][$match[2]] = $match[3];
		}
	}
	return $smbios;
}

// Get number of CPUs
function system_get_cpus() {
	$cpus = @exec("/sbin/sysctl -q -n kern.smp.cpus");
	if ($cpus == '' || $cpus < 1) $cpus = 1;
	return $cpus;
}

// Get current CPU usage.
// Return current CPU usage in percent.
function cb_map_array_diff2($a, $b) {
	return $b - $a;
}
function system_get_smp_cpu_usage() {
	$cpus = system_get_cpus();
	$a_stat1 = explode(" ", @exec("/sbin/sysctl -q -n kern.cp_times"));
	sleep(1);
	$a_stat2 = explode(" ", @exec("/sbin/sysctl -q -n kern.cp_times"));
	$a_dstat = array_map("cb_map_array_diff2", $a_stat1, $a_stat2);

	$a_usage = array_fill_keys(range(0, $cpus - 1), 0);
	for ($i = 0; $i < $cpus; $i++) {
		// user, nice, system, interrupt, idle
		$a = array_splice($a_dstat, 0, 5);
		$idle = $a[4];
		$total = array_sum($a);
		$a_usage[$i] = floor(100 * (($total - $idle) / $total));
	}
	return $a_usage;
}

function system_get_cpu_usage() {
	$cpuTicks1 = explode(" ", `/sbin/sysctl -n kern.cp_time`);
	sleep(1);
	$cpuTicks2 = explode(" ", `/sbin/sysctl -n kern.cp_time`);

	$diff = array();
	$diff['user'] = ($cpuTicks2[0] - $cpuTicks1[0]);
	$diff['nice'] = ($cpuTicks2[1] - $cpuTicks1[1]);
	$diff['sys'] = ($cpuTicks2[2] - $cpuTicks1[2]);
	$diff['intr'] = ($cpuTicks2[3] - $cpuTicks1[3]);
	$diff['idle'] = ($cpuTicks2[4] - $cpuTicks1[4]);

	$totalDiff = $diff['user'] + $diff['nice'] + $diff['sys'] + $diff['intr'] + $diff['idle'];
	$totalused = $diff['user'] + $diff['nice'] + $diff['sys'] + $diff['intr'];

	if (isset($totalused) && $totalused <= 0) {
		$totalused = 0.001;
	}

	return floor(100 * ($totalused / $totalDiff));
}

// Get CPUID
function system_get_cpuid($cpuidx, $level) {
	$r = explode(" ", @exec("/usr/sbin/cpucontrol -i $level /dev/cpuctl${cpuidx} | awk '{ print $4, $5, $6, $7 }'"));
	$a = array();
	$a['eax'] = $r[0];
	$a['ebx'] = $r[1];
	$a['ecx'] = $r[2];
	$a['edx'] = $r[3];
	return $a;
}

// Get various CPU informations.
// Result:
//  Array (
//    [number] => 1
//    [model] => AMD Athlon(tm) 64 Processor 3200+
//    [clockrate] => 1994
//    [temperature] => 65C !!! May be empty if sensor is not available. !!!
//    [freq] => 995
//    [freqlevels] => 1990/67000 1791/64700 995/28600
//  )
function system_get_cpu_info() {
	$cpuinfo = array();

	$cpuinfo['number'] = chop(`/sbin/sysctl -n hw.ncpu`);
	$cpuinfo['model'] = chop(`/sbin/sysctl -n hw.model`);
	$cpuinfo['clockrate'] = chop(`/sbin/sysctl -n hw.clockrate`);
	$cpuinfo['temperature'] = chop(`/sbin/sysctl -n hw.acpi.thermal.tz0.temperature`);
	$cpus = system_get_cpus();
	$cpuinfo['temperature2'] = array();
	$temp = @exec("/sbin/sysctl -q -n dev.cpu.0.temperature");
	if ($temp != '' && $temp != '-1') {
		$adj = 0;
		$cpuid = system_get_cpuid(0, "0x0");
		// AuthenticAMD ?
		if ($cpuid['ebx'] == 0x68747541 && $cpuid['ecx'] == 0x444d4163 && $cpuid['edx'] == 0x69746e65) {
			$cpuid2 = system_get_cpuid(0, "0x80000001");
			// Brisbane BH-G1/BH-G2 ?
			if ($cpuid2['eax'] == 0x00060fb1 || $cpuid2['eax'] == 0x00060fb2) {
// for freebsd 9.x $adj = 21.0; set to $adj = 0.0; | $adj = 21.0; is needed on freebsd 7 series!
				$adj = 0.0;
			}

		}
		for ($i = 0; $i < $cpus; $i++) {
			$temp = @exec("/sbin/sysctl -q -n dev.cpu.{$i}.temperature");
			if ($temp != '') {
				$temp = sprintf("%.1f", ($temp + $adj));
			}
			$cpuinfo['temperature2'][$i] = $temp;
		}
	}
	$cpuinfo['freq'] = chop(`/sbin/sysctl -n dev.cpu.0.freq`);
	$cpuinfo['freqlevels'] = chop(`/sbin/sysctl -n dev.cpu.0.freq_levels`);

	return $cpuinfo;
}

/**
 * Get the system uptime (how long the system is already running).
 * @return The current uptime as string.
 */
function system_get_uptime() {
	exec("/sbin/sysctl -n kern.boottime", $boottime);
	preg_match("/sec = (\d+)/", $boottime[0], $matches);
	$boottime = $matches[1];
	$uptime = time() - $boottime;

	if ($uptime > 60) $uptime += 30;
	$updays = (int)($uptime / 86400);
	$uptime %= 86400;
	$uphours = (int)($uptime / 3600);
	$uptime %= 3600;
	$upmins = (int)($uptime / 60);
	$upsecs = $uptime % 60;

	if (($updays < 1) && ($uphours < 1)) {
		$uptime = sprintf("%d %s %d %s", $upmins, gettext("minute(s)"), $upsecs, gettext("second(s)"));
	} else if ($updays < 1) {
		$uptime = sprintf("%d %s %d %s %d %s", $uphours, gettext("hour(s)"),
			$upmins, gettext("minute(s)"), $upsecs, gettext("second(s)"));
	} else {
		$uptime = sprintf("%d %s %d %s %d %s %d %s", $updays, gettext("day(s)"),
			$uphours, gettext("hour(s)"), $upmins, gettext("minute(s)"), $upsecs,
			gettext("second(s)"));
	}

	return htmlspecialchars($uptime);
}

// Get the current RAM information.
// Returns an array listing the amount of memory installed in the hardware.
function system_get_ram_info() {
	exec("/sbin/sysctl -n vm.stats.vm.v_active_count vm.stats.vm.v_inactive_count vm.stats.vm.v_wire_count vm.stats.vm.v_cache_count vm.stats.vm.v_free_count hw.physmem", $memory);
	exec("/sbin/sysctl -n hw.realmem", $hwmemory);

	$raminfo = array();
	$raminfo['real'] = $hwmemory[0];
	$raminfo['physical'] = $memory[5];
	$raminfo['total'] = $memory[0] + $memory[1] + $memory[2] + $memory[3] + $memory[4];
	$raminfo['free'] = $memory[4] + $memory[1];
	$raminfo['used'] = $raminfo['total'] - $raminfo['free'];

	return $raminfo;
}

// Get the current swap information.
// Result:
//  Array (
//    [device] => "/dev/ad0s2b"
//    [total] => "20M"
//    [used] => "0B"
//    [avail] => "20M"
//    [capacity] => "0%"
//  )
function system_get_swap_info() {
	exec("/usr/sbin/swapinfo", $swap);

	// blocks
	$val = 512;
	if (preg_match("/(?:Device)(?:\s+)(\d+)(?:k-blocks|-blocks)(?:\s+)(?:Used)(?:\s+)(?:Avail)(?:\s)(?:Capacity)/", $swap[0], $matches)) {
		if ($matches[1] == 1) {
			$val = $matches[1] * 1024;
		} else {
			$val = $matches[1];
		}
	}

	array_shift($swap);
	$swapinfo = array();

	foreach ($swap as $swapv) {
		if (preg_match("/(\/dev\/.*)(?:\s+)(\d+)(?:\s+)(\d+)(?:\s+)(\d+)(?:\s)(.+)/", $swapv, $matches)) {
			$devswap = array();
			$devswap['device'] = str_replace("/dev/", "", trim($matches[1]));
			$devswap['total'] = decode_size(trim($matches[2])*$val);
			$devswap['used'] = decode_size(trim($matches[3])*$val);
			$devswap['avail'] = decode_size(trim($matches[4])*$val);
			$devswap['capacity'] = trim($matches[5]);

			$swapinfo[] = $devswap;
		}
	}

	return $swapinfo;
}

/**
 * Get the system hostname.
 * @return The hostname
 */
function system_get_hostname() {
	return @exec("hostname");
}

/**
 * Get device I/O statistics.
 * @param[in] device	The device name, e.g. ad1
 * @return	An array containing the I/O statistics kpt (kilobytes per transfer),
 *					tps (transfers per second) and mps (megabytes per second). On
 *					failure, FALSE will be returned.
 * @code
 * array(
 *   [kpt] => 11.93
 *   [tps] => 61
 *   [mps] => 0.71)
 * @endcode
 */
function system_get_device_iostat($device) {
	$result = FALSE;

	mwexec2("iostat -d {$device}", $rawdata);
	if (preg_match("/^\s*(\S+)\s*(\S+)\s*(\S+)/", $rawdata[2], $matches)) {
		$result = array();
		$result['kpt'] = $matches[1];
		$result['tps'] = $matches[2];
		$result['mps'] = $matches[3];
	}

	return $result;
}

/**
 * Get device temperature.
 * @param[in] device	The device name, e.g. ad1
 * @return	The temperature in C if available, otherwise FALSE.
 */
function system_get_device_temp($device) {
	$result = FALSE;
    $device = str_replace("/dev/", "", $device);
	/* without smart */
	mwexec2("ataidle /dev/{$device} | grep 'SMART Enabled'", $rawdata);
	if (!empty($rawdata) && 1 === preg_match("/^SMART Enabled:\s+(\S+)/", $rawdata[0], $matches)) {
		if (strcasecmp($matches[1], "no") == 0) {
			/* SMART disabled */
			return $result;
		}
	}
	/* with smart */
	mwexec2("smartctl -A /dev/{$device}", $rawdata);
	foreach ($rawdata as $rawdatav) {
		$arawdatav = preg_split("/\s+/", $rawdatav);
		if (!empty($arawdatav[1]) && (0 == strncmp(trim($arawdatav[1]), "Temperature_", 12)) &&
			(0 != strcmp(trim($arawdatav[0]), "190"))) {
			$result = chop($arawdatav[9]);
			break;
		} else if (preg_match("/Current Drive Temperature:\s+(\d+) C.*/", $rawdatav, $matches)) {
			$result = $matches[1];
			break;
		}
	}

	return $result;
}

/**
 * Get volume serial number.
 * @param[in] device	The device name, e.g. /dev/ad1
 * @return The disk device serial number.
 */
function system_get_volume_serial($devicefile) {
	/* without smart */
	mwexec2("ataidle {$devicefile} | grep 'Serial'", $rawdata);
	if (!empty($rawdata) && 1 === preg_match("/^Serial:\s+(.+)$/", $rawdata[0], $matches)) {
		$serial = preg_replace("/[\x01-\x1f\x7f]/", "", $matches[1]);
		if (!empty($serial))
			return $serial;
	}
	/* failback to old method */
	mwexec2("smartctl -n standby -i {$devicefile} | grep -i 'Serial Number'", $rawdata1);
	if (!empty($rawdata1) && 1 === preg_match("/^Serial Number:\s+(.+)$/i", $rawdata1[0], $matches)) {
		$serial = preg_replace("/[\x01-\x1f\x7f]/", "", $matches[1]);
		if (!empty($serial))
			return $serial;
	}
/**
 *	mwexec2("camcontrol inquiry {$devicefile} -S", $rawdata2);
 *	if (!empty($rawdata2) && !empty($rawdata2[0])) {
 *		$serial = preg_replace("/[\x01-\x1f\x7f]/", "", $rawdata2[0]);
 *		if (!empty($serial))
 *			return $serial;
 *	}
 */
	return gettext("n/a");
}

/**
 * Get volume device model.
 * @param[in] device	The device name, e.g. /dev/ad1
 * @return The disk device model.
 */
function system_get_volume_model($devicefile) {
	/* without smart */
	mwexec2("ataidle {$devicefile} | grep 'Model'", $rawdata);
	if (!empty($rawdata) && 1 === preg_match("/^Model:\s+(.+)$/", $rawdata[0], $matches)) {
		$model = preg_replace("/[\x01-\x1f\x7f]/", "", $matches[1]);
		if (!empty($model))
			return $model;
	}
	/* failback to old method */
	mwexec2("smartctl -n standby -i {$devicefile} | grep 'Device Model'", $rawdata1);
	if (!empty($rawdata1) && 1 === preg_match("/^Device Model:\s+(.+)$/", $rawdata1[0], $matches)) {
		$model = preg_replace("/[\x01-\x1f\x7f]/", "", $matches[1]);
		if (!empty($model))
			return $model;
	}
/**
 *	mwexec2("camcontrol inquiry {$devicefile} -D", $rawdata2);
 *	if (!empty($rawdata2) && 1 === preg_match("/<([^>]+)>/", $rawdata2[0], $matches)) {
 *		$model = preg_replace("/[\x01-\x1f\x7f]/", "", $matches[1]);
 *		if (!empty($model))
 *			return $model;
 *	}
 */
	return gettext("n/a");
}

/* 
 * Get device free space indication for Extended GUI
 * @param[in] device    The share name
 * @return warning indication (HTML coded)  
 */
function extension_get_device_space($idx) {
	global $config;
    $output = explode(" ", exec("df -k {$idx} | awk '/\// {print $4,$5}'"));
    $space = $output[0];
    $space_percent = 100 - str_replace("%", "", $output[1]);
    $space_warning = $config['extended-gui']['space_warning'] * 1000;           // threshold in MB * 1000
    $space_warning_percent = $config['extended-gui']['space_warning_percent'];  // threshold in percent
    $space_severe = $config['extended-gui']['space_severe'] * 1000;             // threshold in MB * 1000
    $space_severe_percent = $config['extended-gui']['space_severe_percent'];    // threshold in percent
    if (($space > $space_warning) || ($space_percent > $space_warning_percent)) {
        $space = "<img src='ext/extended-gui/state_ok.png' alt='Space OK' title='Free space on device is ok.'/>";
    } elseif (($space > $space_severe) || ($space_percent > $space_severe_percent)) {
        $space = "<img src='ext/extended-gui/state_warning.png' alt='Space LOW' title='Free space on device is very low (below {$config['extended-gui']['space_warning']} MB & {$config['extended-gui']['space_warning_percent']} %)!'/>";        
    } else {
        $space = "<img src='ext/extended-gui/state_error.png' alt='Device FULL' title='Device is (almost) full (below {$config['extended-gui']['space_severe']} MB & {$config['extended-gui']['space_severe_percent']} %)!'/>";
    }   
    return $space;    
}

/*
 * Get device SMART states for Extended GUI
 * @param[in] device    The device name, e.g. ada1
 * @return states (HTML coded) 
 * @code
 *   array(
 *      device => ada1
 *      smart_state => Standby
 *      temp) => 35 °C 
 * @endcode
 */
function extension_get_smart_states($idx) {
	global $config;
	unset($rawdata1);
	unset($exitval);
    $idx = str_replace(".eli", "", $idx);
	if (isset($config['extended-gui']['temp_always'])) {
        exec("smartctl -n standby -q silent -A {$idx}", $rawdata1, $exitval);
        switch ($exitval) {
            case 4: $smart_state = "<font color='red'>SMART&nbsp;n/a</font>"; break;
            case 2: $smart_state = "<font color='green'>Standby</font>"; break;
            case 1: $smart_state = "<font color='orange'>Unknown</font>"; break;
            case 0: $smart_state = "<font color='red'>Spinning</font>"; break;
            default: $smart_state = "<font color='red'>exit: {$exitval}</font>"; break;
        }
        $temp = system_get_device_temp($idx);
        if (!is_bool($temp)) {
            switch ($temp) {
                case ($temp >= $config['extended-gui']['temp_severe']): $temp = "<font color='red'>{$temp}&nbsp;&deg;C</font>"; break;
                case ($temp >= $config['extended-gui']['temp_warning']): $temp = "<font color='orange'>{$temp}&nbsp;&deg;C</font>"; break;
                default: $temp = "<font color='blue'>{$temp}&nbsp;&deg;C</font>"; break;
            }
        }
        else $temp = "n/a"; 
        return array('device' => str_replace("/dev/", "", $idx), 'smart_state' => $smart_state, 'temp' => $temp);
    }
    else {
    	$temp = "n/a";
        exec("smartctl -n standby -q silent -A {$idx}", $rawdata1, $exitval); 
        switch ($exitval) {
            case 4: $smart_state = "<font color='red'>SMART&nbsp;n/a</font>"; break;
            case 2: $smart_state = "<font color='green'>Standby</font>"; break;
            case 1: $smart_state = "<font color='orange'>Unknown</font>"; break;
            case 0: $smart_state = "<font color='red'>Spinning</font>";
                	unset($rawdata1);
                	unset($exitval);
                    exec("smartctl -n standby -A {$idx} | awk '/Temperature_Celsius/ {print $10}'", $rawdata1, $exitval);
                    switch ($rawdata1[0]) {
                        case ($rawdata1[0] >= $config['extended-gui']['temp_severe']): $temp = "<font color='red'>{$rawdata1[0]}&nbsp;&deg;C</font>"; break;
                        case ($rawdata1[0] >= $config['extended-gui']['temp_warning']): $temp = "<font color='orange'>{$rawdata1[0]}&nbsp;&deg;C</font>"; break;
                        default: $temp = "<font color='blue'>{$rawdata1[0]}&nbsp;&deg;C</font>"; break;
                    }
                    break;
            default: $smart_state = "<font color='red'>exit: {$exitval}</font>"; break;
        }
        $device = explode("/", $idx);
        return array('device' => $device[2], 'smart_state' => $smart_state, 'temp' => $temp);    
    }
}

/**
 * Get mount point usage information.
 * @return An array containg the requested informations:
 * @code
 * [mountpoint] => array(
 *   [mountpoint] => /mnt/xyz
 *   [name] => xyz
 *   [filesystem] => /dev/ad0a
 *   [capacity] => 48%
 *   [used] => 2.4G
 *   [avail] => 2.6G
 *   [size] => 5.4G)
 *   [device] => adaXY
 *   [smart_state] => N
 *   [temp] => NN          (°C)
 *   [space] => X          (state_error, state_warning, state_ok)
* @endcode
 */
function system_get_mount_usage() {
	global $config, $g;

	$result = array();

	exec("/bin/df -h", $rawdata);
    exec("df -h | awk '/\/dev\// && /\/mnt\// && !/\/dev\/zvol\// {print $6}'| cut -d/ -f3", $sharenames);
    if (isset($config['extended-gui']['boot'])) { $sharenames[] = 'A_OS'; }
    if (isset($config['extended-gui']['varfs'])) { $sharenames[] = 'A_VAR'; }
	foreach ($rawdata as $line) {
		if (0 == preg_match("/^(\S+)\s+(\S+)\s+(\S+)\s+(\S+)\s+(\d+%)\s+(.+)/", $line, $aline))
			continue;
		$filesystem = chop($aline[1]);
		$size = chop($aline[2]);
		$used = chop($aline[3]);
		$avail = chop($aline[4]);
		$capacity = chop($aline[5]);
		$mountpoint = chop($aline[6]);

		if (is_array($config['mounts']) && is_array($config['mounts']['mount'])) {
			foreach ($sharenames as $mountcfg) {
                if (isset($config['extended-gui']['boot']) && ($mountpoint == "/")) { $mountpoint = "/mnt/A_OS"; }
                if (isset($config['extended-gui']['varfs']) && ($mountpoint == "/var")) { $mountpoint = "/mnt/A_VAR"; }
				if (0 == strcmp($mountpoint, "{$g['media_path']}/{$mountcfg}")) {
					$result[$mountpoint] = array();
					$result[$mountpoint]['mountpoint'] = $mountpoint;
					$result[$mountpoint]['name'] = $mountcfg;
					$result[$mountpoint]['filesystem'] = $filesystem;
					$result[$mountpoint]['capacity'] = $capacity;
					$result[$mountpoint]['avail'] = $avail;
					$result[$mountpoint]['used'] = $used;
					$result[$mountpoint]['size'] = $size;
//@afs1                                            
                    $file_system = explode("/", $filesystem);
                    // check if we need glabel to get the partition name 
                    if ($file_system[2] == 'ufsid') {
                        unset($hd_output);
                        exec("glabel status | awk '/{$file_system[3]}/{print $3}'", $hd_output);
                        $file_system[2] = $hd_output[0];
                    }                                        
                    unset($hd_output);
                    // get the device from the partition for SMART
                    $hd_output = preg_split('/[ps][0-9]/', $file_system[2]);
                    $file_system[2] = $hd_output[0];
                    $hd_status = extension_get_smart_states("/dev/".$file_system[2]);
					$result[$mountpoint]['device'] = $hd_status['device'];
					$result[$mountpoint]['smart_state'] = $hd_status['smart_state'];
					$result[$mountpoint]['temp'] = $hd_status['temp'];
					$result[$mountpoint]['space'] = extension_get_device_space($filesystem);
 				}
			}
		}
	}
	return $result;
}

function disks_gpt_partition_to_device($gpt_partition) {
        $labels = disks_get_labels();
        foreach($labels as $key => $disk_partition) {
                if (strcmp($gpt_partition, $key) == 0) {
                        return exec("gpart status -s | grep {$disk_partition} | awk '{print $3}'");
                }
        }
        return $gpt_partition;
}

/** @afs2
 * Get list of available zpools with zpool usage warning indicator and associated devices with SMART state and temperature
 * @return An array containg the requested informations:
 * @code
 * [poolname] => array(
 *   [name] => pool
 *   [size] => 4.66G
 *   [used] => 112K
 *   [avail] => 4.66G
 *   [cap] => 0%
 *   [dedup] => 1.00x
 *   [health] => ONLINE
 *   [altroot] => -
 *   [devs] => array(
 *      [device] => adaXY
 *      [smart_state] => N
 *      [temp] => NN          (°C)
 *   )            
 *   [space] => X          (state_error, state_warning, state_ok)
 * )
 * @endcode
 */
function zfs_get_pool_devs_list() {
	global $config;
	$result = array();
    $result = zfs_get_pool_list();
    $test = get_physical_disks_list();    
	foreach ($result as $idx => &$pools) {
    	unset($rawdata);
    	mwexec2("zpool list -vH {$idx}", $rawdata);
    	// get all devices attached to the pool
        foreach ($rawdata as $line) {
    		$aline = preg_split("/\s+/", $line);
             // zfs pool on raw disks
            if (array_key_exists($aline[1], $test)) {
                // device name is taken directly from zpool output
		$device = "/dev/".$aline[1];
		$result[$idx]['devs'][] = extension_get_smart_states($device);
            // zfs pool on disk labels
            } else if (strpos($aline[1], 'label') !== false) {
                // getting device name from drive label
                $device = disks_label_to_device("/dev/".$aline[1]);
                $result[$idx]['devs'][] = extension_get_smart_states($device);
             // zfs pool on gpt partitions 
             } else if (strpos($aline[1], 'gpt') !== false) {
		// getting device name from gpt partition label
                $device = "/dev/".disks_gpt_partition_to_device($aline[1]);
                $result[$idx]['devs'][] = extension_get_smart_states($device);
             } else if (strpos($aline[1], '.nop') !== false) {
		// getting device name for .nop device
                $device = "/dev/".str_replace(".nop", "", $aline[1]);
                $result[$idx]['devs'][] = extension_get_smart_states($device);
            }
        }
        $result[$idx]['space'] = extension_get_device_space($idx);
	}
	unset ($pools);
	return $result;
}

/**
 * Get various system informations.
 * @return An array containing the system informations.
 */
function system_get_sysinfo() {
	$value['hostname'] = system_get_hostname();

	// Get CPU counts.
	$cpus = system_get_cpus();
	$value['cpus'] = $cpus;

	// Get uptime and date.
	$value['uptime'] = system_get_uptime();
	$value['date'] = shell_exec("date");

	// Get RAM usage.
	$raminfo = system_get_ram_info();
	$percentage = round(($raminfo['used'] * 100) / $raminfo['total'], 0);
	$value['memusage']['percentage'] = $percentage;
	$value['memusage']['caption'] = sprintf(gettext("%d%% of %dMiB"), $percentage, round($raminfo['physical'] / 1024 / 1024));

	// Get load average.
	exec("uptime", $result);
	$value['loadaverage'] = substr(strrchr($result[0], "load averages:"), 15);
	// Get up-to-date CPU informations.
	$cpuinfo = system_get_cpu_info();
	$value['cputemp'] = $cpuinfo['temperature'];
	$value['cpufreq'] = $cpuinfo['freq'];
	if (!empty($cpuinfo['temperature2'])) {
		$value['cputemp2'] = $cpuinfo['temperature2'];
	}

	// Get CPU usage.
	if ($cpus > 1) {
		$value['cpuusage2'] = system_get_smp_cpu_usage();
		$value['cpuusage'] = floor(array_sum($value['cpuusage2']) / $cpus);
	} else {
		$value['cpuusage'] = system_get_cpu_usage();
		$value['cpuusage2'] = array($value['cpuusage']);
	}

	// Get disk usage.
	$a_diskusage = system_get_mount_usage();
	if (is_array($a_diskusage) && (0 < count($a_diskusage))) {
		foreach ($a_diskusage as $diskusagek => $diskusagev) {
			$fsid = get_mount_fsid($diskusagev['filesystem'], $diskusagek);
			$diskinfo = array();
			$diskinfo['id'] = $fsid;
			$diskinfo['mountpoint'] = $diskusagev['mountpoint'];
			$diskinfo['name'] = $diskusagev['name'];
			$diskinfo['capacity'] = $diskusagev['capacity'];
			$diskinfo['percentage'] = rtrim($diskusagev['capacity'], "%");
			$diskinfo['size'] = $diskusagev['size'];
			$diskinfo['used'] = $diskusagev['used'];
			$diskinfo['avail'] = $diskusagev['avail'];
			$diskinfo['tooltip']['used'] = sprintf(gettext("%sB used of %sB"), $diskusagev['used'], $diskusagev['size']);
			$diskinfo['tooltip']['available'] = sprintf(gettext("%sB available of %sB"), $diskusagev['avail'], $diskusagev['size']);
//@afs1_1
			$diskinfo['device'] = $diskusagev['device'];
			$diskinfo['smart_state'] = $diskusagev['smart_state'];
			$diskinfo['temp'] = $diskusagev['temp'];
			$diskinfo['space'] = $diskusagev['space'];
			$value['diskusage'][] = $diskinfo;
		}
	}

	// Get pool usage.
	unset ($a_diskusage); 
	$a_diskusage = zfs_get_pool_devs_list();
	if (is_array($a_diskusage) && (0 < count($a_diskusage))) {
		foreach ($a_diskusage as $diskusagek => $diskusagev) {
			$diskinfo = array();
			$diskinfo['id'] = $diskusagev['name'];
			$diskinfo['name'] = $diskusagev['name'];
			$diskinfo['cap'] = $diskusagev['cap'];
			$diskinfo['percentage'] = rtrim($diskusagev['cap'], "%");
			$diskinfo['size'] = $diskusagev['size'];
			$diskinfo['used'] = $diskusagev['used'];
			$diskinfo['avail'] = $diskusagev['avail'];
			$diskinfo['tooltip']['used'] = sprintf(gettext("%sB used of %sB"), $diskusagev['used'], $diskusagev['size']);
			$diskinfo['tooltip']['available'] = sprintf(gettext("%sB available of %sB"), $diskusagev['avail'], $diskusagev['size']);
			$diskinfo['devs'] = $diskusagev['devs'];
			$diskinfo['space'] = $diskusagev['space'];
			$diskinfo['health'] = $diskusagev['health'];
			$value['poolusage'][] = $diskinfo;
		}
	}

	// Get swap info.
	$swapinfo = system_get_swap_info();
	if (is_array($swapinfo) && (0 < count($swapinfo))) {
		$id = 0;
		foreach ($swapinfo as $swapv) {
			$swapinfo = array();
			$swapinfo['id'] = $id++;
			$swapinfo['device'] = $swapv['device'];
			$swapinfo['capacity'] = $swapv['capacity'];
			$swapinfo['percentage'] = rtrim($swapv['capacity'], "%");
			$swapinfo['total'] = $swapv['total'];
			$swapinfo['used'] = $swapv['used'];
			$swapinfo['avail'] = $swapv['avail'];
			$swapinfo['tooltip']['used'] = sprintf(gettext("%sB used of %sB"), $swapv['used'], $swapv['total']);
			$swapinfo['tooltip']['available']  = sprintf(gettext("%sB available of %sB"), $swapv['avail'], $swapv['total']);

			$value['swapusage'][]= $swapinfo;
		}
	}

	return $value;
}
?>
