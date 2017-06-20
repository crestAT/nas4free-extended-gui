#!/bin/sh
# filename:		autoshutdown.sh
# author:		Andreas Schmidhuber
# purpose:		toggle / check running jobs and display / shutdown server
# usage:		autoshutdown.sh [ toggle | check ]
# version:	date:		description:
#	5.1     2017.06.07	N: run services checks for eGUI -> $SERVICES_INFO
#	5.0     2017.06.03	C: Service name abbreviations
#	4.10	2015.03.06	N: Syncthing
#	4.9		2014.08.07	C: insert SUBNET, START_IP, END_IP for mixed use (w/o eGUI)
#	4.8		2014.07.14	C: remove miniDLNA state warning -> IJ
#	4.7		2013.11.26	N: BitTorrent Sync
#						C: change MY_IP identification string
#	4.6		2013.10.06	C: improve udpxrex information output
#	4.5		2013.09.24	C: miniDLNA streaming count (differenciate between N4F1 & 2)
#	4.4		2013.09.12	N: jails check
#	4.3.1	2013.08.31	N: count # of streaming sessions for miniDLNA -> vN4F2 mit $MY_IP für miniDLNA WebGUI
#	4.3		2013.08.31	C: differentiate between streaming and rescan for miniDLNA
#	4.2		2013.08.16	F: delete &nbsp;&nbsp; in display after "|"
#	4.1		2013.08.14	N: check for miniDLNA rescan
#	4.0		2013.08.11	C: get IP data from CONFIG-file
#	4.0.e	2013.08.11	C: include functions of autoshutdown, autoshutdown_status and wget_check in one script
#	3.7.2	2013.05.02	C: for rsync - universal LOCK_SCRIPT
#	3.7.1	2013.04.26	N: $MY_IP for universal use on different NAS systems 
#	3.7		2013.04.13	C: for universal use on different NAS systems 
#	2.6		2013.03.16	C: take care of idle/running udpxrec (PVR)
#	2.5		2013.01.24	N: add links to service pages	=> <a href="buch.url">buch.url</a>
#	2.4		2013.01.22	C: occurences of &nbsp; for smaller browser window
#	2.3		2013.01.02	N: subsonic, minidlna, UMS check
#	2.2		2012.12.25	N: pyLoad check
#	2.1		2012.11.23	N: e2fsck check
# 	2.0		2012.04.29	N: with udpxrec
#	1.2		2010.11.07	initial from FreeNAS
#------------- initialize variables ------------
cd `dirname $0`
. CONFIG
SUBNET=10.0.0									# replace these with the actual IP data
START_IP=1;		END_IP=179
START_IP2=180; 	END_IP2=254
MY_IP=`ifconfig | grep -m1 inet | awk '{ print $2 }'`	# ifconfig | awk '/inet/ && /10.0.0.255/ { print $2 }'
SERVICES_INFO=$LOCK_DIR/extended-gui_services_info.log
CTRL_FILE=$LOCK_DIR/wget_running.job
CTRL_FILE_as=$LOCK_DIR/autoshutdown_off.job
CTRL_FILE_asnc=$LOCK_DIR/autoshutdown_nc.job
CTRL_FILE_udp=$LOCK_DIR/udp_running.job
LOG_MSG=$SYSTEM_LOG_DIR/autoshutdown.log
TASK_LOG=$SYSTEM_LOG_DIR/task.log
REPEATED="- repeated message - swallowing"
WGET_MSG=""; ACTIVE=""; ACTIVE_CLIENT=""
#-----------------------------------------------

#--- FUNCTIONS ---------------------------------

FORM () { if [ "$3" == "" ]; then TEXT="active"; else TEXT=$3; fi
if [ "$2" == "ON" ]; then 				#&nbsp;|&nbsp;&nbsp;
	WGET_MSG="$WGET_MSG "'<a style=" background-color: #c0c0c0; " href="'$3'" target="_blank">&nbsp;&nbsp;<b>'$1'</b>&nbsp;&nbsp;</a><a style=" background-color: #00ff00; ">&nbsp;&nbsp;<b>ON</b>&nbsp;&nbsp;</a>'"&nbsp;&nbsp;&nbsp;";
else if [ "$2" == "OFF" ]; then WGET_MSG="$WGET_MSG "'<a style=" background-color: #c0c0c0; ">&nbsp;&nbsp;<b>'$1'</b>&nbsp;&nbsp;</a><a style=" background-color: #ff0000; ">&nbsp;&nbsp;<b>OFF</b>&nbsp;&nbsp;</a>'"&nbsp;&nbsp;&nbsp;";
	else WGET_MSG="$WGET_MSG &nbsp;&nbsp;<b>"$1"</b>&nbsp;=&nbsp;<font color="$2"><b>"$TEXT"</b></font>&nbsp;&nbsp;&nbsp;|"; ACTIVE="$ACTIVE $1 - "; fi
fi
}

MSG_OUT () {
   echo `date +"$DT_STR"` "$1" >> $LOG_MSG
   echo "$1" > $TASK_LOG
}

MSG () {
	if [ ! -r $TASK_LOG ]; then MSG_OUT "$1"
	else
		TASKS=`cat $TASK_LOG`
		if [ "$TASKS" != "$1 $REPEATED" ]; then
			if [ "$TASKS" = "$1" ]; then MSG_OUT "$1 $REPEATED"; else MSG_OUT "$1"; fi
		fi
	fi
}

DISP () {
	ps acx | grep $2; if [ $? -eq 0 ]; then FORM "$1" ON "$3"; else FORM "$1" OFF ; fi
}

#--- START CODE --------------------------------

if [ "$1" == "toggle" ]; then if [ -r $CTRL_FILE_as ]; then rm $CTRL_FILE_as; NOTIFY "INFO Autoshutdown activated"; MSG "$SCRIPT_NAME Autoshutdown activated";
	else touch $CTRL_FILE_as; NOTIFY "INFO Autoshutdown deactivated"; MSG "$SCRIPT_NAME Autoshutdown deactivated"; fi
fi

#= Services begin ==================================================================================================================
if [ -r $CTRL_FILE_as ]; then FORM Autoshutdown OFF ; else FORM Autoshutdown ON "https://$MY_IP:296"; fi				
DISP Subsonic   "-f /jail/proto/var/run/subsonic.pid"			"https://10.0.0.131:4041"
DISP DLNA 		"minidlna"										"http://10.0.0.131:8200/"
DISP PyLoad    	"-f /jail/proto/root/pyload/pyload.pid"			"http://10.0.0.131:8000/"
DISP udpxy		"udpxy"											"http://$MY_IP:4022/status"
DISP Webserver  "-f /var/run/websrv.pid"						"http://$MY_IP:4321"
DISP BitTorrent "transmission-daemon"							"http://$MY_IP:9091"
DISP RSLSync    "rslsync"										"http://$MY_IP:8888"
DISP Syncthing  "syncthing"										"http://$MY_IP:9999"
#DISP FUPPES		"fuppesd"										"http://$MY_IP:49152"
#ps acx | grep `tr -d '\015' < /jail/proto/root/ums-2.2.1/pms.pid` ; if [ $? -eq 0 ]; then FORM "UMSDLNA" ON 
#   "http://10.0.0.131:5001/console/home"; else FORM "UMSDLNA" OFF ; fi
#jls 1>/dev/null 2>&1; if [ $? -eq 0 ]; then FORM Jails ON; else FORM Jails OFF; fi
#= Services end ====================================================================================================================
#WGET_MSG="$WGET_MSG <br />"
WGET_MSG="$WGET_MSG"

# check fsck
FSCK=`ps acx | grep fsck | awk '{print $5}'`; pgrep fsck ; if [ $? -eq 0 ]; then FORM $FSCK red ; fi

# check rsync
ls $LOCK_DIR/rsync_*.lock; if [ $? -eq 0 ]; then FORM RSYNC darkgreen ; fi

# check transmission
ps acx | grep transmission ; if [ $? -eq 0 ]; then ls /mnt/DATA/ftp/bittorrent/progress/* ; if [ $? -eq 0 ]; then FORM Bittorrent darkgreen ; fi ; fi

# check pyLoad
ls -R /jail/proto/root/pyload/Downloads/*.chunk* ; if [ $? -eq 0 ]; then FORM pyLoad darkgreen ; fi

# check miniDLNA streaming | rescanning
CNT=`ps acx | grep minidlna | grep -c SJ`; if [ $CNT -gt 0 ]; then FORM miniDLNA darkgreen $CNT"x streaming"; fi					# -> N4F1
CNT=`ps acx | awk '/minidlna/ {print $3}' | grep -cx 'S'`; if [ $CNT -gt 0 ]; then FORM miniDLNA darkgreen $CNT"x streaming"; fi	# -> N4F2
ps acx | grep minidlna | grep -v Ss | grep -v SJ | grep -v IJ | grep -v S; if [ $? -eq 0 ]; then FORM miniDLNA '#F11FC1' rescanning; fi
#ps acx | grep minidlna | grep IJ; if [ $? -eq 0 ]; then NOTIFY WARNING "miniDLNA -> IJ = unknown state"; fi

# check fetch
ps acx | grep fetch; if [ $? -eq 0 ]; then FORM Fetch darkgreen ; fi

# check udpxrec
ps acx | grep udpxrec
if [ $? -eq 0 ]; then 
	CNT=`ps acx | grep udpxrec | awk '{print $3}'| grep -vcx 'I'`; if [ $CNT -gt 0 ]; then FORM PVR red recording; fi
	CNT=`ps acx | grep udpxrec | awk '{print $3}'| grep -cx 'I'`; if [ $CNT -gt 0 ]; then FORM PVR darkgreen $CNT"x waiting"; fi
	if [ ! -r $CTRL_FILE_udp ]; then touch $CTRL_FILE_udp ; NOTIFY "INFO A1 TV recording started"; fi
else
	if [ -r $CTRL_FILE_udp ]; then NOTIFY "INFO A1 TV recording finished"; rm $CTRL_FILE_udp; /mnt/DATA/www/PVR/start.sh RESTART; fi
fi

# check wget
ps acx | grep wget
if [ $? -eq 0 ]; then 
	if [ ! -r $CTRL_FILE ]; then touch $CTRL_FILE; NOTIFY "INFO WGET started downloading"; fi
else
	if [ -r $CTRL_FILE ]; then NOTIFY "INFO WGET finished downloading"; $SYSTEM_SCRIPT_DIR/email.sh "FN-WGET DLF"; rm $CTRL_FILE; fi
fi
if [ -e $CTRL_FILE ]; then FORM WGET darkgreen ; fi

# check for autoshutdown
if [ "$1" = "check" ]; then 
	if [ ! -e $CTRL_FILE_as ]; then 				# check only if AS is activated
# LOCK FÜR FUPPES, miniDLNA, Subsonic ??? => check auf LOCK-File if FUPPESD update is running
		PROCESS=fuppesd ; PROC_IDLE_STATE=S
		C1=`ps acx | grep $PROCESS | awk '{print $3,$4}'`
		sleep 2
		C2=`ps acx | grep $PROCESS | awk '{print $3,$4}'`
		if [ "$C1" != "$C2" ]; then FORM Fuppes darkgreen; fi

		rm $SYSTEM_LOG_DIR/$SCRIPT_NAME.tmp 1>/dev/null 2>&1
		x=$START_IP2
		while [ $x -le $END_IP2 ]
		do
			(	
			ping -c 1 -t 1 $SUBNET.$x 1>/dev/null 2>&1
			if [ $? -eq 0 ]; then echo "$SUBNET.$x" >> $SYSTEM_LOG_DIR/$SCRIPT_NAME.tmp; fi
			) &
			x=$((x+1));
		done
	sleep 1
		if [ "$ACTIVE" == "" ] && [ ! -e $SYSTEM_LOG_DIR/$SCRIPT_NAME.tmp ]; then 
			if [ -e $CTRL_FILE_asnc ]; then MSG "$SCRIPT_NAME no user online - Server will shutdown immediately"; shutdown -p now; exit 1
			else touch $CTRL_FILE_asnc; fi
		else
			rm $CTRL_FILE_asnc 1>/dev/null 2>&1; 
			if [ -e $SYSTEM_LOG_DIR/$SCRIPT_NAME.tmp ]; then 
				for NAME in `sort $SYSTEM_LOG_DIR/$SCRIPT_NAME.tmp`
				do ACTIVE_CLIENT="$ACTIVE_CLIENT $NAME - "; done
			fi
			MSG "$SCRIPT_NAME $ACTIVE $ACTIVE_CLIENT active"
		fi
	fi
fi
echo $WGET_MSG > $SERVICES_INFO
