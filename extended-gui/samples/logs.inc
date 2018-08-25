<?php
/*
    logs.inc
    
    Sample file for Extended GUI

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

$logs_info = [
/* 
 * A simple example to include a UPS logfile which uses the standard log file in /var/log/ups.log
*/
	[
		'visible' => true,
		'desc' => gtext('UPS'),
		'logfile' => "{$clogdir}/ups.log",
		'filename' => 'ups.log',
		'type' => 'plain',
		"size" => '32768',
		'pattern' => '/^(\S+\s+\S+)\s+(\S+)\s+(.*)$/',
		'columns' => [
			['title' => gtext('Date & Time'),'hdrclass' => 'lhell','class' => 'lcell','param' => 'nowrap="nowrap"','pmid' => 1],
			['title' => gtext('User'),'hdrclass' => 'lhell','class' => 'lcell','param' => 'nowrap="nowrap"','pmid' => 2],
			['title' => gtext('Event'),'hdrclass' => 'lhebl','class' => 'lcebl','param' => '','pmid' => 3]
		]],
/* 
 * ProFTP - File transfer log example
*/
	[
		'visible' => true,
		'desc' => gtext('ProFTP - File transfer'),
		'logfile' => "{$g['varlog_path']}/xferlog",
		'filename' => 'xferlog',
		'type' => 'plain',
		'pattern' => '/^(\S+\s+\S+\s+\d+\s+\S+\s+\d+) ((\d+) (\S+) (\d+) (\S+) (a|b) (C|U|T|_) (o|i|d) (a|g|r) (\S+) (\S+) (0|1) (\S+) (c|i))$/',
		'columns' => [
			['title' => gtext('Date & Time'),'hdrclass' => 'lhell','class' => 'lcell','param' => 'nowrap="nowrap"','pmid' => 1],
			['title' => gtext('IP'),'hdrclass' => 'lhell','class' => 'lcell','param' => 'nowrap="nowrap"','pmid' => 4],
			['title' => gtext('User'),'hdrclass' => 'lhell','class' => 'lcell','param' => 'nowrap="nowrap"','pmid' => 11],
			['title' => gtext('File'),'hdrclass' => 'lhell','class' => 'lcell','param' => 'nowrap="nowrap"','pmid' => 6],
			['title' => gtext('Filesize'),'hdrclass' => 'lhelr','class' => 'lcelr','param' => 'nowrap="nowrap"','pmid' => 5],
			['title' => gtext('Type'),'hdrclass' => 'lhelc','class' => 'lcelc','param' => 'nowrap="nowrap"','pmid' => 7],
			['title' => gtext('Event'),'hdrclass' => 'lhell','class' => 'lcell','param' => 'nowrap="nowrap"','pmid' => 8],
			['title' => gtext('Direction'),'hdrclass' => 'lhelc','class' => 'lcelc','param' => 'nowrap="nowrap"','pmid' => 9],
			['title' => gtext('Mode'),'hdrclass' => 'lhelc','class' => 'lcelc','param' => 'nowrap="nowrap"','pmid' => 10],
			['title' => gtext('Service'),'hdrclass' => 'lhelc','class' => 'lcelc','param' => 'nowrap="nowrap"','pmid' => 12],
			['title' => gtext('Authentication'),'hdrclass' => 'lhelc','class' => 'lcelc','param' => 'nowrap="nowrap"','pmid' => 13],
			['title' => gtext('USer ID'),'hdrclass' => 'lhelc','class' => 'lcelc','param' => 'nowrap="nowrap"','pmid' => 14],
			['title' => gtext('Status'),'hdrclass' => 'lhebc','class' => 'lcebc','param' => 'nowrap="nowrap"','pmid' => 15]
		]],
/*
 * Full blown definition for a webserver log which uses the log file /var/log/lighthttp-access.log 
 * Lighttp Log Format - Log lines are defined like that by default:
 * 1: address of remote-host    => (\S+)\s+
 * 2: adress of the server      => (\S+)\s+
 * 3: user                      => (\S+)\s+
 * 4: [timestamp]               => \[(\S+)\s+(\S+)\s+
 * 5: "request"                 => \"(\S+\s+\S+\s+\S+)\"\s+
 * 6: status code               => (\S+)\s+
 * 7: bytes sent                => (\S+)\s+
 * 8: "Referer adress"          => \"(\S+)\"\s+          
 * 9: "User-Agent"              => \"(.*)\"               
 */
	[
		'visible' => true,
		'desc' => gtext('Webserver'),
		'logfile' => "{$clogdir}/lighthttp-access.log",
		'filename' => 'lighthttp-access.log',
		'type' => 'plain',
		'pattern' => '/^(\S+)\s+(\S+)\s+(\S+)\s+\[(\S+)\s+(\S+)\s+\"(\S+\s+\S+\s+\S+)\"\s+(\S+)\s+(\S+)\s+\"(\S+)\"\s+\"(.*)\"$/',
		'columns' => [
			['title' => gtext('Date & Time'),'hdrclass' => 'lhell','class' => 'lcell','param' => 'nowrap="nowrap"','pmid' => 4],
			['title' => gtext('Remote Address'),'hdrclass' => 'lhelc','class' => 'lcelc','param' => 'nowrap="nowrap"','pmid' => 1],
			['title' => gtext('Server'),'hdrclass' => 'lhelc','class' => 'lcelc','param' => 'nowrap="nowrap"','pmid' => 2],
			['title' => gtext('User'),'hdrclass' => 'lhelc','class' => 'lcelc','param' => 'nowrap="nowrap"','pmid' => 3],
			['title' => gtext('Request'),'hdrclass' => 'lhell','class' => 'lcell','param' => 'nowrap="nowrap"','pmid' => 6],
			['title' => gtext('Status'),'hdrclass' => 'lhelc','class' => 'lcelc','param' => 'nowrap="nowrap"','pmid' => 7],
			['title' => gtext('Bytes sent'),'hdrclass' => 'lhelc','class' => 'lcelc','param' => 'nowrap="nowrap"','pmid' => 8],
			['title' => gtext('Referer Adress'),'hdrclass' => 'lhebl','class' => 'lcebl','param' => 'nowrap="nowrap"','pmid' => 9],
			['title' => gtext('User Agent'),'hdrclass' => 'lhebl','class' => 'lcebl','param' => 'nowrap="nowrap"','pmid' => 10]
		]],
];
?>
