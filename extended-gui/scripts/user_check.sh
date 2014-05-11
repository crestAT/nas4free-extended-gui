#!/bin/sh
# filename:		user_check.sh
# author:		Andreas Schmidhuber
# purpose:		monitoring of users in the home-network
# usage:		user_check.sh.sh (... w/o parameters) 
# version:	date:		description:
#	2.0		2014.04.07	C: initial version for Extended GUI
#------------- initialize variables ------------
cd `dirname $0`
. CONFIG
USER_ONLINE=$LOCK_DIR/extended-gui_user_online.log
USER_LOG_NEW=$LOCK_DIR/user_new.log
USER_LOG_OLD=$LOCK_DIR/user_old.log
EMAIL_FILE=$LOCK_DIR/user_email.log
EMAIL_ENABLED=`/usr/local/bin/xml sel -t -v "count(//extended-gui/user_email)" ${XML_CONFIG_FILE}`
EMAIL_TO=`/usr/local/bin/xml sel -t -v "//extended-gui/space_email_add" ${XML_CONFIG_FILE}`
#-----------------------------------------------

# user online
smbstatus -b | awk '/\(/ {print "<b>"$2"</b>@"$4$5"&nbsp;(CIFS/SMB)"}' > $USER_ONLINE.tmp
w -hn | awk '{print "<b>"$1"</b>@"$3"@"$2"&nbsp;(SSH)"}' | grep -v '<b>root</b>@-@' >> $USER_ONLINE.tmp
ftpwho -v -o oneline | grep -v 'standalone FTP' | grep -v 'Service class' | grep -v 'no users connected' | awk '{print "<b>"$2"</b>@"$8"&nbsp;(FTP)"}' >> $USER_ONLINE.tmp

NAMES=""
for NAME in `cat $USER_ONLINE.tmp`
do NAMES="$NAMES $NAME&nbsp;"; done
echo "$NAMES " > $USER_ONLINE

# user login / logout
cp $USER_ONLINE.tmp $USER_LOG_NEW
if [ ! -e $USER_LOG_OLD ]; then cp $USER_LOG_NEW $USER_LOG_OLD; fi
USER_DIFF=`diff --suppress-common-lines $USER_LOG_NEW $USER_LOG_OLD `
if [ $? != 0 ]; then
	echo Host: $HOST >> $EMAIL_FILE;
	for NAME in $USER_DIFF
	do
		if [ "$NAME" == "<" ]; then LOG_RECORD="User logged in: "; 
		else if [ "$NAME" == ">" ]; then LOG_RECORD="User logged out: "
			else if [ "`echo $NAME | awk '/@/ {print $1}'`" != "" ]; then 
					LOG_RECORD="$LOG_RECORD $NAME";
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
