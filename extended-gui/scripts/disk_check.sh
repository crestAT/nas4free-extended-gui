#!/bin/bash
# filename:		disk_check.sh
# author:		Andreas Schmidhuber
# purpose:		retrive S.M.A.R.T. infos for eGUI (disk state, temperature) and return results (HTML formated) in file: <mountpoint>.smart
# prereq.:		S.M.A.R.T. must be enabled and existing CONFIG2 file, which will be created at every eGUI startup
# usage:		disk_check.sh
# version:	date:		description:
#   0.3     2015.10.03  F: GET_SPACE - return if no disk exists for a mountpoint
#   0.2     2015.07.29  C: take care of systems without ZFS pools
#   0.1     2015.04.08  initial version for Extended GUI 
#------------- initialize variables ------------
cd `dirname $0`
. CONFIG
PREFIX="${LOCK_DIR}/extended-gui_"
SPACE_WARNING=$(($SPACE_WARNING_MB * 1000))
SPACE_SEVERE=$(($SPACE_SEVERE_MB * 1000))
#-----------------------------------------------

REPORT_DISK ()
{
	if [ ! -e $CTRL_FILE"_"$2".lock" ]; then 
		NOTIFY "$3 $4"
		echo "Host: $HOST" > $CTRL_FILE"_"$2.lock
		echo "\n${4}\n" >> $CTRL_FILE"_"$2.lock
		if [ "$2" == "degraded" ]; then 
			zpool status -v $1 >> $CTRL_FILE"_"$2.lock
			if [ $EMAIL_DEGRADED_ENABLED -gt 0 ]; then $SYSTEM_SCRIPT_DIR/email.sh "$EMAIL_TO" "N4F-DISK $2" $CTRL_FILE"_"$2.lock; fi
		else 
			df -h /mnt/$1 >> $CTRL_FILE"_"$2.lock
			if [ $EMAIL_SPACE_ENABLED -gt 0 ]; then $SYSTEM_SCRIPT_DIR/email.sh "$EMAIL_TO" "N4F-DISK $2" $CTRL_FILE"_"$2.lock; fi
		fi
	fi
}

GET_SMART_SUB ()
{
    SMART_OUTPUT=`smartctl -A "${1}"`;
    MSG_TEMP=`echo -e "${SMART_OUTPUT}" | awk '/Temperature_/ {print $10; exit}'`;                    # check for SMART ID 190 or 194
    if [ "${MSG_TEMP}" == "" ]; then 
        MSG_TEMP=`echo -e "${SMART_OUTPUT}" | awk '/Current Drive Temperature/ {print $4; exit}'`;    # or alternative output
    fi
    if [ "${MSG_TEMP}" == "" ]; then MSG_TEMP="n/a"
    else
        if [ ${MSG_TEMP} -ge ${TEMP_SEVERE} ]; then MSG_TEMP="<font color='red'>${MSG_TEMP}&nbsp;&deg;C</font>"
        else if [ ${MSG_TEMP} -ge ${TEMP_WARNING} ]; then MSG_TEMP="<font color='orange'>${MSG_TEMP}&nbsp;&deg;C</font>"
            else MSG_TEMP="<font color='blue'>${MSG_TEMP}&nbsp;&deg;C</font>"
            fi
        fi
    fi
}

GET_SMART ()
{
    MSG_TEMP="n/a"
	if [ $TEMP_ALWAYS -eq 0 ]; then SMART_STANDBY="-n standby"; else SMART_STANDBY=""; fi
    smartctl -n standby -q silent -A "/dev/${1}"
    case $? in 
        4) MSG="<font color='black'>SMART&nbsp;n/a</font>";;
        2) MSG="<font color='green'>Standby</font>"; if [ "${TEMP_ALWAYS}" == "1" ]; then GET_SMART_SUB "/dev/${1}"; fi;;
        1) MSG="<font color='orange'>Unknown</font>";;      
        0) MSG="<font color='red'>Spinning</font>"; GET_SMART_SUB "/dev/${1}";;
        *) MSG="<font color='red'>exit: ${?}</font>";;       
    esac;
    if [ "$OUTPUT" == "" ]; then OUTPUT="${1}|${MSG}|${MSG_TEMP}";
    else OUTPUT="${OUTPUT}#${1}|${MSG}|${MSG_TEMP}"; fi
}

GET_SPACE ()
{
	CTRL_FILE=${PREFIX}${1}
	SPACE=`echo -e "$MOUNTPOINTS" | awk -v mp=\^\/mnt\/${1}\$ '$6 ~ mp {print $4}'`
    if [ "$SPACE" == "" ]; then return; fi                              #F: v0.3
	SPACE_PERCENT=`echo -e "$MOUNTPOINTS" | awk -v mp=\^\/mnt\/${1}\$ '$6 ~ mp {gsub("%",""); print $5}'`
	SPACE_PERCENT=$((100 - $SPACE_PERCENT))
    if [ $SPACE -gt $SPACE_WARNING ] || [ $SPACE_PERCENT -gt $SPACE_WARNING_PC ]; then 
        MSG_SPACE="<img src='ext/extended-gui/state_ok.png' alt='Space OK' title='Free space on device is ok.'/>";
        if [ -e $CTRL_FILE"_low.lock" ]; then rm $CTRL_FILE"_low.lock"; fi
        if [ -e $CTRL_FILE"_full.lock" ]; then rm $CTRL_FILE"_full.lock"; fi
    elif [ $SPACE -gt $SPACE_SEVERE ] || [ $SPACE_PERCENT -gt $SPACE_SEVERE_PC ]; then
        MSG_SPACE_TXT="Disk Space on device $1 is LOW (below $SPACE_WARNING_MB MB & $SPACE_WARNING_PC %)!";
        MSG_SPACE="<img src='ext/extended-gui/state_warning.png' alt='Space LOW' title='"$MSG_SPACE_TXT"'/>";
        REPORT_DISK $1 low WARNING "$MSG_SPACE_TXT"
        if [ -e $CTRL_FILE"_full.lock" ]; then rm $CTRL_FILE"_full.lock"; fi
	else 
        MSG_SPACE_TXT="Disk Space on device $1 is (almost) FULL (below $SPACE_SEVERE_MB MB & $SPACE_SEVERE_PC %)!";
        MSG_SPACE="<img src='ext/extended-gui/state_error.png' alt='Device FULL' title='"$MSG_SPACE_TXT"'/>";
		REPORT_DISK $1 full ERROR "$MSG_SPACE_TXT"
        if [ -e $CTRL_FILE"_low.lock" ]; then rm $CTRL_FILE"_low.lock"; fi
    fi
    OUTPUT="${OUTPUT}##${MSG_SPACE}"
}

MOUNTPOINTS=`df -k | awk '/\/mnt\// {print}'`                           # get usage for all mountpoints
POOL_STATUS=`zpool list -H`                                             # get usage and status for all pools
i=0; counter=MOUNT${i};                                                 # set first mountpoint i
while [ "${!counter}" != "" ]; do                                       # run through all mountpoints
    j=0; dcounter=MOUNT${i}DISK${j};                                    # set first disk j for mountpoint i
#echo "INFO ${counter}: ${!counter}"                                    # for debugging
    OUTPUT=""
    while [ "${!dcounter}" != "" ]; do                                  # run through all disks of a mountpoint (RAID, ZFS POOL)
        GET_SMART ${!dcounter}                                          # retrive SMART values
        j=$((j+1)); dcounter=MOUNT${i}DISK${j};                         # increase disk counter j
    done
    GET_SPACE ${!counter}
    echo "${OUTPUT}" > "${PREFIX}${!counter}.smart"                     # output SMART values to file named as the mountpoint
    i=$((i+1)); counter=MOUNT${i};                                      # increase mountpoint counter i
done

# check for degraded pools
if [ "$POOL_STATUS" != "" ]; then
    POOL_MSG=`echo -e "$POOL_STATUS" | awk '!/ONLINE/ {print "REPORT_DISK "$1" degraded ERROR \"ZFS pool "$1" is DEGRADED\" "}'`
    if [ "${POOL_MSG}" == "" ]; then if [ -e ${PREFIX}*"_degraded.lock" ]; then rm ${PREFIX}*"_degraded.lock"; fi
    else ${POOL_MSG}; fi
fi

# check for degraded RAID
