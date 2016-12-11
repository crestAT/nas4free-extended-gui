#!/usr/local/bin/php-cgi -f
<?php
/*
    extended-gui_install.php

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
require_once("config.inc");

$branch = "master";    // GIT branch: master, development
$min_release = 10.3032853;

// check FreeBSD release for fetch options >= 9.3
$release = explode("-", exec("uname -r"));
if ($release[0] >= 9.3) $verify_hostname = "--no-verify-hostname";
else $verify_hostname = "";

// create FreeBSD $current_release for min_release check
$product_version = explode(".", get_product_version());                 // p.version = 10.3.0.3, p.revision = 2853
$current_release = $product_version[0].".".$product_version[1].$product_version[2].$product_version[3].get_product_revision();
if ($current_release < floatval($min_release)) {                        // release not supported
    echo "\nThis version of Extended GUI needs NAS4Free release {$min_release} or higher, installation aborted!\n\n";
    exit;
}

$dirname = dirname(__FILE__);
if (!is_dir("{$dirname}/extended-gui/backup")) { mkdir("{$dirname}/extended-gui/backup", 0775, true); }
if (!is_dir("{$dirname}/extended-gui/log")) { mkdir("{$dirname}/extended-gui/log", 0775, true); }
$return_val = mwexec("fetch {$verify_hostname} -vo {$dirname}/extended-gui/extended-gui-install.php https://raw.github.com/crestAT/nas4free-extended-gui/{$branch}/extended-gui/extended-gui-install.php", false);
if ($return_val == 0) { 
    chmod("{$dirname}/extended-gui/extended-gui-install.php", 0775);
    require_once("{$dirname}/extended-gui/extended-gui-install.php"); 
}
else { echo "\nInstallation file 'extended-gui-install.php' not found, installation aborted!\n"; }
?>
