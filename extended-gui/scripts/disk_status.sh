#!/bin/sh
# filename:		disk_status.sh
# author:		Andreas Schmidhuber
# purpose:		check disk state (capacity)
# usage:		disk_status.sh w/o params
# version:	date:		description:
#	2.1		2014.04.23	C: space warning logic
#						N: ZFS degraded pool warning		
#	2.0		2014.04.07	C: for Extended GUI
#	1.2		2013.04.13	C: for universal use on different NAS systems 
#	1.1		2010.05.10	initial version
#------------- initialize variables -------------
cd `dirname $0`
. CONFIG
SPACE_WARNING_MB=`/usr/local/bin/xml sel -t -v "//extended-gui/space_warning" ${XML_CONFIG_FILE}`
SPACE_WARNING_PC=`/usr/local/bin/xml sel -t -v "//extended-gui/space_warning_percent" ${XML_CONFIG_FILE}`
SPACE_SEVERE_MB=`/usr/local/bin/xml sel -t -v "//extended-gui/space_severe" ${XML_CONFIG_FILE}`
SPACE_SEVERE_PC=`/usr/local/bin/xml sel -t -v "//extended-gui/space_severe_percent" ${XML_CONFIG_FILE}`
EMAIL_SPACE_ENABLED=`/usr/local/bin/xml sel -t -v "count(//extended-gui/space_email)" ${XML_CONFIG_FILE}`
EMAIL_DEGRADED_ENABLED=`/usr/local/bin/xml sel -t -v "count(//extended-gui/zfs_degraded_email)" ${XML_CONFIG_FILE}`
EMAIL_TO=`/usr/local/bin/xml sel -t -v "//extended-gui/space_email_add" ${XML_CONFIG_FILE}`
#-----------------------------------------------

REPORT_DISK ()
{
	if [ ! -e $CTRL_FILE"_"$2".lock" ]; then 
		NOTIFY "$3 $4"
		echo "Host: $HOST" > $CTRL_FILE"_"$2.lock
		echo "\n${4}.\n" >> $CTRL_FILE"_"$2.lock
		if [ "$2" == "degraded" ]; then 
			zpool status -v $1 >> $CTRL_FILE"_"$2.lock
			if [ $EMAIL_DEGRADED_ENABLED -gt 0 ]; then $SYSTEM_SCRIPT_DIR/email.sh "$EMAIL_TO" "N4F-DISK $2" $CTRL_FILE"_"$2.lock; fi
		else 
			df -h /mnt/$1 >> $CTRL_FILE"_"$2.lock
			if [ $EMAIL_SPACE_ENABLED -gt 0 ]; then $SYSTEM_SCRIPT_DIR/email.sh "$EMAIL_TO" "N4F-DISK $2" $CTRL_FILE"_"$2.lock; fi
		fi
	fi
}

# check for disk space
SPACE_WARNING=$(($SPACE_WARNING_MB * 1000))
SPACE_SEVERE=$(($SPACE_SEVERE_MB * 1000))
for DEVICE in `df -k | awk '/\/mnt\// {print $6}' | awk -F/ '{print $3}'`
do
	CTRL_FILE=$LOCK_DIR/extended-gui_$DEVICE
	SPACE=`df -k /mnt/$DEVICE | awk '/\// {print $4}'`
	SPACE_PERCENT=`df -k /mnt/$DEVICE | awk '/\// {print $5}' | awk -F% '{print $1}'`
	SPACE_PERCENT=$((100 - $SPACE_PERCENT))
	if [ $SPACE -le $SPACE_SEVERE ] && [ $SPACE_PERCENT -le $SPACE_SEVERE_PC ]; then 
		REPORT_DISK $DEVICE full ERROR "Disk Space on device $DEVICE is FULL (below $SPACE_SEVERE_MB MB & $SPACE_SEVERE_PC %)"
	else 
		if [ -e $CTRL_FILE"_full.lock" ]; then rm $CTRL_FILE"_full.lock"; fi
		if [ $SPACE -le $SPACE_WARNING ] && [ $SPACE_PERCENT -le $SPACE_WARNING_PC ]; then 
			REPORT_DISK $DEVICE low WARNING "Disk Space on device $DEVICE is LOW (below $SPACE_WARNING_MB MB & $SPACE_WARNING_PC %)"
		else if [ -e $CTRL_FILE"_low.lock" ]; then rm $CTRL_FILE"_low.lock"; fi
		fi
	fi
done

# check for degraded pools
for DEG in `zpool list -H | awk '{print $1}'` 
do
	CTRL_FILE=$LOCK_DIR/extended-gui_$DEG
	zpool list -H $DEG | grep "DEGRADED"
	if [ $? -eq 0 ]; then
		REPORT_DISK $DEG degraded ERROR "ZFS pool $DEG is DEGRADED"
	else
		if [ -e $CTRL_FILE"_degraded.lock" ]; then rm $CTRL_FILE"_degraded.lock"; fi
	fi
done
