#!/bin/sh
# filename:		user_check.sh
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
# purpose:		monitoring of users in the home network
# usage:		user_check.sh (... w/o parameters) 
# version:	date:		description:
#	4.1		2017.06.19	C: change SSH log entry from 3 -> 1
#	4.0		2017.06.15	N: introduced Telegram as new notification service
#	3.3		2017.01.24	F: display of FTP user
#	3.2		2015.12.01	F: change check order, start with SSH to avoid multiple SSH entries if CIFS/SMB is disabled
#                       C: remove logger -p local3.notice 
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
w -h | awk '{print "<font color=blue><b>"$1"</b></font>@"$3"@"$2"&nbsp;(<a href='diag_log.php?log=1'>SSH</a>)"}' | grep -v '<font color=blue><b>root</b></font>@-@' > $USER_ONLINE.tmp
if [ $SMB_ENABLED -gt 0 ]; then
    smbstatus -b | awk '/\(/ {print "<font color=blue><b>"$2"</b></font>@"$4$5"&nbsp;(<a href='diag_infos_samba.php'>CIFS/SMB</a>)"}' >> $USER_ONLINE.tmp
fi
if [ $FTP_ENABLED -gt 0 ]; then
    ftpwho -v  | awk '!/standalone FTP daemon/ && !/Service class/ && !/no users connected/ && /\[/{ 
        user=$2
        getline
        if ($1 == "client:") client=$2
        else {
            getline
            client=$2
        }
        print "<font color=blue><b>"user"</b></font>@"client"&nbsp;(<a href='diag_infos_ftpd.php'>FTP</a>)"
    }' >> $USER_ONLINE.tmp
fi

cat $USER_ONLINE.tmp | awk 'BEGIN {ORS=""} {print} {print "&nbsp; "}' > $USER_ONLINE

# user login / logout
cat $USER_ONLINE.tmp | cut -d "(" -f1 > $USER_LOG_NEW
if [ ! -e $USER_LOG_OLD ]; then cp $USER_LOG_NEW $USER_LOG_OLD; fi
USER_DIFF=`diff --suppress-common-lines $USER_LOG_NEW $USER_LOG_OLD `
if [ $? != 0 ]; then
	echo "Host: $HOST\n" >> $EMAIL_FILE;
	for NAME in $USER_DIFF
	do
		if [ "$NAME" == "<" ]; then LOG_RECORD="LOGIN User logged in: "; 
		else if [ "$NAME" == ">" ]; then LOG_RECORD="LOGOUT User logged out: "
			else if [ "`echo $NAME | awk '/@/ {print $1}'`" != "" ]; then 
					LOG_RECORD="$LOG_RECORD `echo $NAME | awk '{gsub("color=blue><b>",""); gsub("</b></font>",""); gsub("&nbsp;"," ");print}'`";
					echo $LOG_RECORD >> $EMAIL_FILE;
					NOTIFY "$LOG_RECORD"
                    if [ $TELEGRAM_NOTIFICATIONS -eq 1 ] && [ $EMAIL_ENABLED -eq 1 ]; then      # call Telegram if enabled
                        TELEGRAM "$LOG_RECORD"
                    fi
				fi
			fi
		fi
	done
	if [ $EMAIL_NOTIFICATIONS -eq 1 ] && [ $EMAIL_ENABLED -eq 1 ]; then $SYSTEM_SCRIPT_DIR/email.sh "$EMAIL_TO" "N4F-USR Log" $EMAIL_FILE; fi
	$SYSTEM_SCRIPT_DIR/beep USER_LOGGED_IN
	rm $EMAIL_FILE
	cp $USER_LOG_NEW $USER_LOG_OLD
fi
