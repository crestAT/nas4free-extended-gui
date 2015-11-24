#!/usr/local/bin/php-cgi -f
<?php
/*
    extended-gui_start.php

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
require_once("config.inc");
require_once("functions.inc");
require_once("install.inc");
require_once("util.inc");
require_once("{$config['extended-gui']['rootfolder']}ext/extended-gui_fcopy.inc");

$saved = $config['extended-gui']['product_version'];
$current = get_product_version().'-'.get_product_revision(); 
if ($saved != $current) { 
    exec ("logger extended-gui: Saved Release: $saved New Release: $current - new backup of standard GUI files!"); 
    copy_origin2backup($files, $backup_path, $extend_path);
 	$config['extended-gui']['product_version'] = $current;
	write_config();
} 
else exec ("logger extended-gui: saved and current GUI files are identical - OK"); 

if ( !is_dir ( '/usr/local/www/ext/extended-gui')) { exec ("mkdir -p /usr/local/www/ext/extended-gui"); }
exec ("cp ".$config['extended-gui']['rootfolder']."ext/* /usr/local/www/ext/extended-gui/");
// restore logs for embedded systems
exec ("cp ".$config['extended-gui']['rootfolder']."log/* /var/log/ >/dev/null 2>/dev/null");
exec ("cp -R ".$config['extended-gui']['rootfolder']."scripts /var/");
if ( !is_link ( "/usr/local/www/extended-gui.php")) { exec ("ln -s /usr/local/www/ext/extended-gui/extended-gui.php /usr/local/www/extended-gui.php"); }
if ( !is_link ( "/usr/local/www/extended-gui_tools.php")) { exec ("ln -s /usr/local/www/ext/extended-gui/extended-gui_tools.php /usr/local/www/extended-gui_tools.php"); }

if ( isset( $config['extended-gui']['enable'] )) {
	if ($config['extended-gui']['type'] == "Standard" ) { 
        copy_backup2origin ($files, $backup_path, $extend_path); 
        killbypid("/tmp/extended-gui_system_calls.sh.lock");
    }
	else { 
        copy_extended2origin ($files, $backup_path, $extend_path);
        exec("/var/scripts/extended-gui_system_calls.sh >/dev/null 2>/dev/null &");
    }
}
else { copy_backup2origin ($files, $backup_path, $extend_path); }   // case extension not enabled at start
?>
