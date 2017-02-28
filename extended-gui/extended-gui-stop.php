<?php
/*
    extended-gui-stop.php

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
require_once("config.inc");
require_once("functions.inc");
require_once("install.inc");
require_once("util.inc");

$rootfolder = dirname(__FILE__)."/";
$config_file = "{$rootfolder}ext/extended-gui.conf";
require_once("{$rootfolder}ext/extension-lib.inc");
if (($configuration = ext_load_config($config_file)) === false) {
    exec("logger extended-gui: configuration file {$config_file} not found, stopping aborted!");
    exit;
}
require_once("{$configuration['rootfolder']}ext/extended-gui_fcopy.inc");

killbypid("/tmp/extended-gui_system_calls.sh.lock");
// restore original files by shutdown
copy_backup2origin($files, $backup_path, $extend_path);
// save logs for embedded systems
exec ("cp /var/log/autoshutdown.log ".$configuration['rootfolder']."log/autoshutdown.log >/dev/null 2>/dev/null");
exec ("cp /var/log/notifications.log ".$configuration['rootfolder']."log/notifications.log >/dev/null 2>/dev/null");
exec("logger extended-gui: stopped");
?>
