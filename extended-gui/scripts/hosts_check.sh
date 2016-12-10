#!/bin/sh
# filename:		hosts_check.sh
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
# purpose:		monitoring of hosts in network
# usage:		hosts_check.sh (... w/o parameters) 
# version:	date:		description:
#	3.0		2015.04.16	C: get extension variables from CONFIG2 instead of reading from config.xml
#	2.3		2014.06.15	F: let grep -w search for whole words
#	2.2		2014.06.06	C: display host names if given in /etc/hosts - don't use the own automatically generated name
#	2.1		2014.05.31	C: display host names if given in /etc/hosts
#	2.0		2014.05.05	C: initial version for Extended GUI
#------------- initialize variables ------------
cd `dirname $0`
. CONFIG
ONLINE_LOG=$LOCK_DIR/extended-gui_hosts_online.log
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
