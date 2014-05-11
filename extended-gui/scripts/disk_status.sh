#!/bin/sh
# filename:		disk_status.sh
# author:		Andreas Schmidhuber
# purpose:		check disk state (capacity)
# usage:		disk_status.sh w/o params
# version:	date:		description:
#	2.0		2014.04.07	C: for Extended GUI
#	1.2		2013.04.13	C: for universal use on different NAS systems 
#	1.1		2010.05.10	initial version
#------------- initialize variables -------------
cd `dirname $0`
. CONFIG
SPACE_WARNING_MB=`/usr/local/bin/xml sel -t -v "//extended-gui/space_warning" ${XML_CONFIG_FILE}`
SPACE_SEVERE_MB=`/usr/local/bin/xml sel -t -v "//extended-gui/space_severe" ${XML_CONFIG_FILE}`
EMAIL_ENABLED=`/usr/local/bin/xml sel -t -v "count(//extended-gui/space_email)" ${XML_CONFIG_FILE}`
EMAIL_TO=`/usr/local/bin/xml sel -t -v "//extended-gui/space_email_add" ${XML_CONFIG_FILE}`
#-----------------------------------------------

REPORT_DISK ()
{
	if [ ! -e $CTRL_FILE"_"$2".lock" ]; then 
		echo "Host: $HOST" > $CTRL_FILE"_"$2.lock
		echo "\\nDisk Space on device $DEVICE is $2 (below $3 MB).\n" >> $CTRL_FILE"_"$2.lock
		df -h /mnt/$DEVICE >> $CTRL_FILE"_"$2.lock
		NOTIFY "WARNING Space on device $DEVICE is $2 (below $3 MB)"
		if [ $EMAIL_ENABLED -gt 0 ]; then $SYSTEM_SCRIPT_DIR/email.sh "$EMAIL_TO" "N4F-DISK $DISK_NAME $2" $CTRL_FILE"_"$2.lock
		fi
	fi
}

SPACE_WARNING=$(($SPACE_WARNING_MB * 1000))
SPACE_SEVERE=$(($SPACE_SEVERE_MB * 1000))
for DEVICE in `df -k | awk '/\/mnt\// {print $6}' | awk -F/ '{print $3}'`
do
	CTRL_FILE=$LOCK_DIR/extended-gui_$DEVICE
	WERT=`df -k /mnt/$DEVICE | awk '/mnt/ {print $4}'`
	if [ $WERT -lt $SPACE_SEVERE ]; then REPORT_DISK $DEVICE full $SPACE_SEVERE_MB
	else 
		if [ -e $CTRL_FILE"_full.lock" ]; then rm $CTRL_FILE"_full.lock"; fi
		if [ $WERT -lt $SPACE_WARNING ]; then REPORT_DISK $DEVICE low $SPACE_WARNING_MB
		else if [ -e $CTRL_FILE"_low.lock" ]; then rm $CTRL_FILE"_low.lock"; fi
		fi
	fi
done
