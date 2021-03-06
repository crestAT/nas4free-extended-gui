<?php
/*
    extended-gui-start.php 

    Copyright (c) 2014 - 2018 Andreas Schmidhuber
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
*/
require_once("config.inc");
require_once("functions.inc");
require_once("install.inc");
require_once("util.inc");

$rootfolder = dirname(__FILE__)."/";
$config_file = "{$rootfolder}ext/extended-gui.conf";
require_once("{$rootfolder}ext/extension-lib.inc");
if (($configuration = ext_load_config($config_file)) === false) {
    exec("logger extended-gui: configuration file {$config_file} not found, startup aborted!");
    exit;    
} 

require_once("{$configuration['rootfolder']}ext/extended-gui_fcopy.inc");
$extension_dir = "/usr/local/www/ext/extended-gui";
if ( !is_dir($extension_dir)) { mwexec("mkdir -p {$extension_dir}", true); }
mwexec("cp {$configuration['rootfolder']}ext/* {$extension_dir}/", true);
// check for product name and eventually rename translation files for new product name (XigmaNAS)
$domain = strtolower(get_product_name());
if ($domain <> "nas4free") mwexec("find {$configuration['rootfolder']}locale-egui -name nas4free.mo -execdir mv nas4free.mo {$domain}.mo \;", true);
mwexec("cp -R {$configuration['rootfolder']}locale-egui /usr/local/share/", true);
mwexec("cp -R {$configuration['rootfolder']}scripts /var/", true);
mwexec("chmod -R 775 /var/scripts", true);                           // to be sure that scripts are executable, o=rx needed for LetsEncrypt curl
if ( !is_link("/usr/local/www/extended-gui.php")) { mwexec("ln -s {$extension_dir}/extended-gui.php /usr/local/www/extended-gui.php", true); }
if ( !is_link("/usr/local/www/extended-gui_tools.php")) { mwexec("ln -s {$extension_dir}/extended-gui_tools.php /usr/local/www/extended-gui_tools.php", true); }
if ( !is_link("/usr/local/www/extended-gui_update_extension.php")) { mwexec("ln -s {$extension_dir}/extended-gui_update_extension.php /usr/local/www/extended-gui_update_extension.php", true); }
if ( !is_link("/usr/bin/curl")) { mwexec("ln -s /var/scripts/bin/curl /usr/bin/curl", true); }					// prerequisites for Telegram
if ( !is_link("/usr/local/bin/curl")) { mwexec("ln -s /var/scripts/bin/curl /usr/local/bin/curl", true); }
if ( !is_link("/usr/bin/telegram-notify")) { mwexec("ln -s /var/scripts/telegram-notify /usr/bin/telegram-notify", true); }
if ( !is_link("/usr/local/bin/telegram-notify")) { mwexec("ln -s /var/scripts/telegram-notify /usr/local/bin/telegram-notify", true); }

if ($configuration['enable']) {
    $saved = $configuration['product_version'];
    $current = get_product_version().'-'.get_product_revision(); 
    if ($saved != $current) {
        mwexec("rm {$backup_path}*"); 
        exec ("logger extended-gui: Saved Release: $saved New Release: $current - new backup of standard GUI files!"); 
        copy_origin2backup($files, $backup_path, $extend_path);
     	$configuration['product_version'] = $current;
    } 
    else exec ("logger extended-gui: saved and current GUI files are identical - OK"); 
    
	if ($configuration['type'] == "Standard" ) { 
        copy_backup2origin ($files, $backup_path, $extend_path); 
        killbypid("/tmp/extended-gui_system_calls.sh.lock");
    }
	else { 
        exec("logger extended-gui: enabled, starting ...");
        copy_extended2origin ($files, $backup_path, $extend_path);
        require_once("{$extension_dir}/extended-gui_create_config2.inc"); 
        killbypid("/tmp/extended-gui_system_calls.sh.lock");
		mwexec("nohup /var/scripts/extended-gui_system_calls.sh >/dev/null 2>&1 &", true);
		sleep(2);													// give time to startup
		if (exec("ps acx | grep -f /tmp/extended-gui_system_calls.sh.lock")) exec("logger extended-gui: startup OK"); 
		else { exec("logger extended-gui: startup NOT ok" ); }
    }
}
else { copy_backup2origin ($files, $backup_path, $extend_path); }   // case extension not enabled at start
ext_save_config($config_file, $configuration);
mwexec("cp {$config_file} {$extension_dir}/", true);
?>
