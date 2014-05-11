#!/bin/sh
# filename:		extended-gui_system_calls.sh
# author:		Andreas Schmidhuber
# purpose:		executes several scripts every n seconds
# usage:		extended-gui_system_calls.sh (... w/o parameters)
# version:	date:		description:
#	2.0		2014.04.07	C: initial version for Extended GUI
#------------- initialize variables ------------
cd `dirname $0`
. CONFIG
#-----------------------------------------------

LOCK_SCRIPT "ATTEMPT to run the script \"$SCRIPT_NAME\" twice!"
NOTIFY "INFO System call service started with pid ${PID}"

while true
do
RUN_USER=`/usr/local/bin/xml sel -t -v "count(//extended-gui/user)" ${XML_CONFIG_FILE}`
LOOP_DELAY=`/usr/local/bin/xml sel -t -v "//extended-gui/loop_delay" ${XML_CONFIG_FILE}`
if [ "$LOOP_DELAY" == "" ]; then LOOP_DELAY=60; fi

$SYSTEM_SCRIPT_DIR/disk_status.sh
if [ $RUN_USER -gt 0 ]; then $SYSTEM_SCRIPT_DIR/user_check.sh; fi

sleep $LOOP_DELAY
done

UNLOCK_SCRIPT
