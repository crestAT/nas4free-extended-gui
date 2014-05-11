#!/bin/bash
# filename:		email.sh
# author:		Andreas Schmidhuber
# purpose:		send email w/o body text or file content
# usage:		email [ sender@host.at recipiant@host.at ] "subject" [ "message text" | "filename" ]
# version:	date:		description:
#	2.0		2014.04.07	C: initial version for Extended GUI
#	1.1		2013.04.13	C: for universal use on different NAS systems 
#	1.0		2010.02.13	initial version
#------------- initialize variables ------------
cd `dirname $0`
. CONFIG
EMAIL_FROM=`/usr/local/bin/xml sel -t -v "//system/email/from" ${XML_CONFIG_FILE}`
#-----------------------------------------------

case $# in 
	4)	if [ -r "$4" ]; then BODY=`cat "$4"`; else BODY="$4"; fi
		echo -e "From: $1\nTo: $2\nSubject: $3\n\n$BODY\n\n---- End of Message ----\n`date`" | /usr/local/bin/msmtp --file=/var/etc/msmtp.conf -t
		if [ $? != 0 ]; then NOTIFY "ERROR sending email: $@ not successful, see System log for details!"; else NOTIFY "INFO email: $@ successfully sent"; fi;;
	3)	if [ -r "$3" ]; then BODY=`cat "$3"`; else BODY="$3"; fi
		echo -e "From: $EMAIL_FROM\nTo: $1\nSubject: $2\n\n$BODY\n\n---- End of Message ----\n`date`" | /usr/local/bin/msmtp --file=/var/etc/msmtp.conf -t
		if [ $? != 0 ]; then NOTIFY "ERROR sending email: $@ not successful, see System log for details!"; else NOTIFY "INFO email: $@ successfully sent"; fi;;
	2)	if [ -r "$2" ]; then BODY=`cat "$2"`; else BODY="$2"; fi
		echo -e "From: $EMAIL_FROM\nTo: $EMAIL_FROM\nSubject: $1\n\n$BODY\n\n---- End of Message ----\n`date`" | /usr/local/bin/msmtp --file=/var/etc/msmtp.conf -t
		if [ $? != 0 ]; then NOTIFY "ERROR sending email: $@ not successful, see System log for details!"; else NOTIFY "INFO email: $@ successfully sent"; fi;;
	1)	echo -e "From: $EMAIL_FROM\nTo: $EMAIL_FROM\nSubject: $1\n\n\n\n---- End of Message ----\n`date`" | /usr/local/bin/msmtp --file=/var/etc/msmtp.conf -t
		if [ $? != 0 ]; then NOTIFY "ERROR sending email: $@ not successful, see System log for details!"; else NOTIFY "INFO email: $@ successfully sent"; fi;;
	*)	NOTIFY 'WARNING wrong parameter list, usage: email [ sender@host.at recipiant@host.at ] "subject" [ "message text" | "filename" ]';;
esac;
