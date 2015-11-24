#!/bin/sh
# filename:		hosts_check.sh
# author:		Andreas Schmidhuber
# purpose:		monitoring of hosts in network
# usage:		hosts_check.sh (... w/o parameters) 
# version:	date:		description:
#	2.3		2014.06.15	F: let grep -w search for whole words
#	2.2		2014.06.06	C: display host names if given in /etc/hosts - don't use the own automatically generated name
#	2.1		2014.05.31	C: display host names if given in /etc/hosts
#	2.0		2014.05.05	C: initial version for Extended GUI
#------------- initialize variables ------------
cd `dirname $0`
. CONFIG
ONLINE_LOG=$LOCK_DIR/extended-gui_hosts_online.log
SUBNET=`/usr/local/bin/xml sel -t -v "//extended-gui/hosts_network" ${XML_CONFIG_FILE}`
START_IP=`/usr/local/bin/xml sel -t -v "//extended-gui/hosts_network_start" ${XML_CONFIG_FILE}`
END_IP=`/usr/local/bin/xml sel -t -v "//extended-gui/hosts_network_end" ${XML_CONFIG_FILE}`
#-----------------------------------------------

CHECK_CLIENTS ()
{
	if [ -e $ONLINE_LOG.tmp ]; then rm $ONLINE_LOG.tmp; fi
	x=$START_IP
	while [ $x -le $END_IP ]
	do
		(	
		ping -c 1 -t 1 $SUBNET.$x 1>/dev/null 2>&1
		if [ $? -eq 0 ]; then echo "$SUBNET.$x" >> $ONLINE_LOG.tmp ; fi
		) &
		x=$((x+1));
	done
	sleep 1
}

# hosts in network ----------------------
NAMES=""
CHECK_CLIENTS
if [ -e $ONLINE_LOG.tmp ]; then 
	for NAME in `cat $ONLINE_LOG.tmp | sort -t. -k1,1n -k2,2n -k3,3n -k4,4n`; do 
		HNAME=`cat /etc/hosts | grep -v $HOST | grep -w $NAME | awk 'BEGIN {ORS=""} {print "<font color=blue><b>"$2"</b></font>&nbsp;("$1")"}'`
		if [ "$HNAME" == "" ]; then NAMES="$NAMES <font color=red><b>${NAME}</b></font>&nbsp;&nbsp;"; 
		else NAMES="$NAMES ${HNAME}&nbsp;&nbsp;"; fi
	done
	echo "<b>Network ($SUBNET.$START_IP - $SUBNET.$END_IP):</b>&nbsp; $NAMES" > $ONLINE_LOG
else rm $ONLINE_LOG
fi
