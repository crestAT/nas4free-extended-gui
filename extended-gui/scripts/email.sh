#!/bin/bash
# filename:		email.sh
#
#    Copyright (c) 2013 - 2017 Andreas Schmidhuber <info@a3s.at>
#    All rights reserved.
#
#    Redistribution and use in source and binary forms, with or without
#    modification, are permitted provided that the following conditions are met:
#
#    1. Redistributions of source code must retain the above copyright notice, this
#       list of conditions and the following disclaimer.
#    2. Redistributions in binary form must reproduce the above copyright notice,
#       this list of conditions and the following disclaimer in the documentation
#       and/or other materials provided with the distribution.
#
#    THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
#    ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
#    WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
#    DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
#    ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
#    (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
#    LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
#    ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
#    (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
#    SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
#
# author:		Andreas Schmidhuber
# purpose:		send email w/o body text or file content
# usage:		email [ sender@host.at recipiant@host.at ] "subject" [ "message text" | "filename" ]
# version:	date:		description:
#	3.0		2015.04.16	C: get extension variables from CONFIG2 instead of reading from config.xml
#	2.1		2014.04.21	C: removed positive notification
#	2.0		2014.04.07	C: initial version for Extended GUI
#	1.1		2013.04.13	C: for universal use on different NAS systems 
#	1.0		2010.02.13	initial version
#------------- initialize variables ------------
cd `dirname $0`
. CONFIG
#-----------------------------------------------

case $# in 
	4)	if [ -r "$4" ]; then BODY=`cat "$4"`; else BODY="$4"; fi
		echo -e "From: $1\nTo: $2\nSubject: $3\n\n$BODY\n\n---- End of Message ----\n`date`" | /usr/local/bin/msmtp --file=/var/etc/msmtp.conf -t
		if [ $? != 0 ]; then NOTIFY "ERROR sending email: $@ not successful, see System log for details!"; fi;;
	3)	if [ -r "$3" ]; then BODY=`cat "$3"`; else BODY="$3"; fi
		echo -e "From: $EMAIL_FROM\nTo: $1\nSubject: $2\n\n$BODY\n\n---- End of Message ----\n`date`" | /usr/local/bin/msmtp --file=/var/etc/msmtp.conf -t
		if [ $? != 0 ]; then NOTIFY "ERROR sending email: $@ not successful, see System log for details!"; fi;;
	2)	if [ -r "$2" ]; then BODY=`cat "$2"`; else BODY="$2"; fi
		echo -e "From: $EMAIL_FROM\nTo: $EMAIL_FROM\nSubject: $1\n\n$BODY\n\n---- End of Message ----\n`date`" | /usr/local/bin/msmtp --file=/var/etc/msmtp.conf -t
		if [ $? != 0 ]; then NOTIFY "ERROR sending email: $@ not successful, see System log for details!"; fi;;
	1)	echo -e "From: $EMAIL_FROM\nTo: $EMAIL_FROM\nSubject: $1\n\n\n\n---- End of Message ----\n`date`" | /usr/local/bin/msmtp --file=/var/etc/msmtp.conf -t
		if [ $? != 0 ]; then NOTIFY "ERROR sending email: $@ not successful, see System log for details!"; fi;;
	*)	NOTIFY 'WARNING wrong parameter list, usage: email [ sender@host.at recipiant@host.at ] "subject" [ "message text" | "filename" ]';;
esac;
