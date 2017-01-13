<?php
/*
    logs.inc
    
    Sample file for Extended GUI

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
*/

$logs_info = array(
	array(
/* 
 * A simple example to include a UPS logfile which uses the standard log file in /var/log/ups.log
*/
		"visible" => TRUE,
		"desc" => gettext("UPS"),
		"logfile" => "/var/log/ups.log",
		"filename" => "ups.log",
		"type" => "plain",
		"pattern" => "/^(\S+\s+\S+)\s+(\S+)\s+(.*)$/",
		"columns" => array(
			array("title" => gettext("Date & Time"), "class" => "listlr", "param" => "nowrap=\"nowrap\"", "pmid" => 1),
			array("title" => gettext("User"), "class" => "listr", "param" => "", "pmid" => 2),
			array("title" => gettext("Event"), "class" => "listr", "param" => "", "pmid" => 3)
		)),
	array(
/*
 * Full blown definition for a webserver log: 
 * Lighttp Log Format - Log lines are defined like that by default:
 * 1: address of remote-host    => (\S+)\s+
 * 2: adress of the server      => (\S+)\s+
 * 3: user                      => (\S+)\s+
 * 4: [timestamp]               => \[(\S+\s+\S+)\]\s+
 * 5: "request"                 => \"(\S+\s+\S+\s+\S+)\"\s+
 * 6: status code               => (\S+)\s+
 * 7: bytes sent                => (\S+)\s+
 * 8: "Referer adress"          => \"(\S+)\"\s+          
 * 9: "User-Agent"              => \"(.*)\"               
 */
		"visible" => TRUE,
		"desc" => gettext("Webserver"),
		"logfile" => "/mnt/DATA/log/lighthttp-access.log",
		"filename" => "lighthttp-access.log",
		"type" => "plain",
        "pattern" => '/^(\S+)\s+(\S+)\s+(\S+)\s+\[(\S+\s+\S+)\]\s+\"(\S+\s+\S+\s+\S+)\"\s+(\S+)\s+(\S+)\s+\"(\S+)\"\s+\"(.*)\"$/',
		"columns" => array(
			array("title" => gettext("Date & Time"), "class" => "listlr", "param" => "nowrap=\"nowrap\"", "pmid" => 4),
			array("title" => gettext("Remote Address"), "class" => "listr", "param" => "align=\"center\"", "pmid" => 1),
			array("title" => gettext("Server"), "class" => "listr", "param" => "align=\"center\"", "pmid" => 2),
			array("title" => gettext("User"), "class" => "listr", "param" => "align=\"center\"", "pmid" => 3),
			array("title" => gettext("Request"), "class" => "listr", "param" => "nowrap=\"nowrap\"", "pmid" => 5),
			array("title" => gettext("Status"), "class" => "listr", "param" => "align=\"center\" nowrap=\"nowrap\"", "pmid" => 6),
			array("title" => gettext("Bytes sent"), "class" => "listr", "param" => "align=\"center\" nowrap=\"nowrap\"", "pmid" => 7),
			array("title" => gettext("Referer Adress"), "class" => "listr", "param" => "nowrap=\"nowrap\"", "pmid" => 8)/* ,
			array("title" => gettext("User Agent"), "class" => "listr", "param" => "nowrap=\"nowrap\"", "pmid" => 9) */
		)),
);
?>
