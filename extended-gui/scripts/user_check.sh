#!/bin/sh
# filename:		user_check.sh
# author:		Andreas Schmidhuber
# purpose:		monitoring of users in the home network
# usage:		user_check.sh (... w/o parameters) 
# version:	date:		description:
#	3.1		2015.09.27	N: check if SMB / FTP are enabled to prevent error messages and laggs
#	3.0		2015.04.16	C: get extension variables from CONFIG2 instead of reading from config.xml
#	2.1		2014.06.16	C: give user names a color
#	2.0		2014.04.07	C: initial version for Extended GUI
#------------- initialize variables ------------
cd `dirname $0`
. CONFIG
USER_ONLINE=$LOCK_DIR/extended-gui_user_online.log
USER_LOG_NEW=$LOCK_DIR/extended-gui_user_new.log
USER_LOG_OLD=$LOCK_DIR/extended-gui_user_old.log
EMAIL_FILE=$LOCK_DIR/extended-gui_user_email.log
#-----------------------------------------------

# user online
if [ $SMB_ENABLED -gt 0 ]; then
    smbstatus -b | awk '/\(/ {print "<font color=blue><b>"$2"</b></font>@"$4$5"&nbsp;(CIFS/SMB)"}' > $USER_ONLINE.tmp
fi
w -hn | awk '{print "<font color=blue><b>"$1"</b></font>@"$3"@"$2"&nbsp;(SSH)"}' | grep -v '<font color=blue><b>root</b></font>@-@' >> $USER_ONLINE.tmp
if [ $FTP_ENABLED -gt 0 ]; then
    ftpwho -v -o oneline | grep -v 'standalone FTP' | grep -v 'Service class' | grep -v 'no users connected' | awk '{print "<font color=blue><b>"$2"</b></font>@"$8"&nbsp;(FTP)"}' >> $USER_ONLINE.tmp
fi

cat $USER_ONLINE.tmp | awk 'BEGIN {ORS=""} {print} {print "&nbsp; "}' > $USER_ONLINE

# user login / logout
cp $USER_ONLINE.tmp $USER_LOG_NEW
if [ ! -e $USER_LOG_OLD ]; then cp $USER_LOG_NEW $USER_LOG_OLD; fi
USER_DIFF=`diff --suppress-common-lines $USER_LOG_NEW $USER_LOG_OLD `
if [ $? != 0 ]; then
	echo "Host: $HOST\n" >> $EMAIL_FILE;
	for NAME in $USER_DIFF
	do
		if [ "$NAME" == "<" ]; then LOG_RECORD="User logged in: "; 
		else if [ "$NAME" == ">" ]; then LOG_RECORD="User logged out: "
			else if [ "`echo $NAME | awk '/@/ {print $1}'`" != "" ]; then 
					LOG_RECORD="$LOG_RECORD `echo $NAME | awk '{gsub("color=blue><b>",""); gsub("</b></font>",""); gsub("&nbsp;"," ");print}'`";
					echo $LOG_RECORD >> $EMAIL_FILE;
					logger -p local3.notice "*** $SCRIPT_NAME $LOG_RECORD"
					NOTIFY "WARNING $LOG_RECORD"
				fi
			fi
		fi
	done
	if [ $EMAIL_ENABLED -gt 0 ]; then $SYSTEM_SCRIPT_DIR/email.sh "$EMAIL_TO" "N4F-USR Log" $EMAIL_FILE; fi
	$SYSTEM_SCRIPT_DIR/beep USER_LOGGED_IN
	rm $EMAIL_FILE
	cp $USER_LOG_NEW $USER_LOG_OLD
fi
