#!/bin/bash
# filename:		extended-gui_system_calls.sh
# author:		Andreas Schmidhuber
# purpose:		executes several scripts every n seconds
# usage:		extended-gui_system_calls.sh (... w/o parameters)
# version:	date:		description:
#	4.0		2015.11.22	N: CPU temperature monitoring and reporting (cpu_check)
#	3.1		2015.04.16	C: get extension variables from CONFIG2 instead of reading from config.xml
#	3.0		2015.04.09	C: for Extended GUI version 0.5
#	2.0		2014.04.07	C: initial version for Extended GUI
#------------- initialize variables ------------
cd `dirname $0`
. CONFIG
#-----------------------------------------------

LOCK_SCRIPT "ATTEMPT to run the script \"$SCRIPT_NAME\" twice!"
NOTIFY "INFO System call service started with pid ${PID}"
change_config=`/usr/local/bin/xml sel -t -v "//lastchange" ${XML_CONFIG_FILE}`

while true
do
#NOTIFY "INFO system_calls start"
	lastchange_config=`/usr/local/bin/xml sel -t -v "//lastchange" ${XML_CONFIG_FILE}`
    if [ "$change_config" != "$lastchange_config" ]; then
#NOTIFY "INFO2 system_calls start ALT: $change_config NEU: $lastchange_config"
        su root -c "/usr/local/www/ext/extended-gui/extended-gui_create_config2.php"
    fi

    if [ "$LOOP_DELAY" == "" ]; then LOOP_DELAY=60; fi

	$SYSTEM_SCRIPT_DIR/cpu_check.sh
	$SYSTEM_SCRIPT_DIR/disk_check.sh
	if [ $RUN_USER -gt 0 ]; then $SYSTEM_SCRIPT_DIR/user_check.sh; fi
	if [ $RUN_HOSTS -gt 0 ]; then $SYSTEM_SCRIPT_DIR/hosts_check.sh; fi
	if [ $RUN_AUTOMOUNT -gt 0 ]; then $SYSTEM_SCRIPT_DIR/automount_usb.sh; fi
#NOTIFY "INFO system_calls end"
	sleep $LOOP_DELAY
done

UNLOCK_SCRIPT
