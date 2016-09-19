#!/bin/bash
# filename:		disk_check.sh
# author:		Andreas Schmidhuber
# purpose:		retrive S.M.A.R.T. infos for eGUI (disk state, temperature) and return results (HTML formated) in file: <mountpoint>.smart
# prereq.:		S.M.A.R.T. must be enabled and existing CONFIG2 file, which will be created at every eGUI startup
# usage:		disk_check.sh
# version:	date:		description:
#   0.6.5   2016.09.19  N: _DEVICE for nice SMART output
#   0.6.4   2016.09.18  C: check _DEVICETYPEARG for SMART support
#   0.6.3   2016.03.13  C: SSD lifetime -> bold
#   0.6.2   2015.12.11  N: SSD support
#   0.6.1   2015.12.10  N: ZFS datasets & volumes
#   0.6     2015.11.24  N: beep on ERROR
#   0.5.2   2015.11.19  N: take care of CD/DVDs
#   0.5.1   2015.11.16  C: more elaborate pool busy states in STATUS | SYSTEM (tooltip)
#   0.5     2015.11.16  N: include USB drives
#   0.4.3   2015.11.10  F: degraded pool reporting
#   0.4.2   2015.11.09  N: pool busy states (scrub, resilver)
#   0.4.1   2015.11.05  N: add A_USR treatment and optimize code with "case" statement
#   0.4     2015.10.26  N: add A_OS and A_VAR treatment
#   0.3     2015.10.03  F: GET_SPACE - return if no disk exists for a mountpoint
#   0.2     2015.07.29  C: take care of systems without ZFS pools
#   0.1     2015.04.08  initial version for Extended GUI 
#------------- initialize variables ------------
cd `dirname $0`
. CONFIG
SPACE_WARNING=$(($SPACE_WARNING_MB * 1000))
SPACE_SEVERE=$(($SPACE_SEVERE_MB * 1000))
if [ -e USBMP ]; then
. USBMP
fi
#-----------------------------------------------

REPORT_DISK ()
{
	if [ ! -e $CTRL_FILE"_"$2".lock" ]; then 
		NOTIFY "$3 $4 $5 $6 $7 $8"                                          # extended up to 8 for degraded pool msg
		echo "Host: $HOST" > $CTRL_FILE"_"$2.lock
		echo "\n${4}\n" >> $CTRL_FILE"_"$2.lock
		if [ "$2" == "degraded" ]; then 
			zpool status -v $1 >> $CTRL_FILE"_"$2.lock
			if [ $EMAIL_DEGRADED_ENABLED -gt 0 ]; then $SYSTEM_SCRIPT_DIR/email.sh "$EMAIL_TO" "N4F-DISK $2" $CTRL_FILE"_"$2.lock; fi
            if [ $RUN_BEEP -gt 0 ]; then                                    # call beep when enabled and ERROR condition set
                $SYSTEM_SCRIPT_DIR/beep ZFS_ERROR &
            fi
		else 
			df -h /mnt/$1 >> $CTRL_FILE"_"$2.lock
			if [ $EMAIL_SPACE_ENABLED -gt 0 ]; then $SYSTEM_SCRIPT_DIR/email.sh "$EMAIL_TO" "N4F-DISK $2" $CTRL_FILE"_"$2.lock; fi
		fi
	fi
}

GET_DETAILS ()
{
    MSG_SSD=""; MSG_SSD_LT=""; MSG_SSD_LT_PCT="";
    while [ "${1}" != "" ]; do
        case $1 in
            Model|Device|Rotation)      MSG_SSD="SSD";;             # is SSD
            177|202)                    MSG_SSD_LT=$((100-$2));;    # lifetime parameter 177 Wear_Leveling_Count | 202 Percent Lifetime used
            190|194)                    MSG_TEMP=$2;;               # temperature parameter 190 or 194
        esac
        shift
    done
    if [ "${MSG_SSD}" != "" ]; then 
        if [ "${MSG_SSD_LT}" != "" ]; then MSG_SSD_LT_PCT="<font color='blue'><b>${MSG_SSD_LT}%</b></font>"; fi
        MSG="${MSG_SSD} ${MSG_SSD_LT_PCT}"; 
    fi
}

GET_SMART_SUB ()
{
#echo "INFO3 $1 $2"                                    # for debugging
    SMART_OUTPUT=`smartctl -a $1 $2`;
    MSG_TEMP=`echo -e "${SMART_OUTPUT}" | awk '/Current Drive Temperature/ {print $4; exit}'`;                                      # alternative temperature
    MSG_ALL=`echo -e "${SMART_OUTPUT}" | awk '/Solid State/ || /SSD/ || /Wear_/ || /Lifetime/ || /Temperature_/ {print $1,$10}'`;   # check for different params
    GET_DETAILS $MSG_ALL
    
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
# Parameter 1: device_special_file 2: smart_device_type_arg 3: smart_device
#echo "INFO2 ${1} ${dcounter}_DEVICETYPEARG: ${!2} ${dcounter}_DEVICE ${!3}"    # for debugging
    MSG_TEMP="n/a"
    case $1 in                                                                          # check for special cases
        xmd[0-9])   OUTPUT="${1}|<font color='black'>RAM-DRV</font>|n/a";   break;;
        ds*)        OUTPUT="${1}|<font color='black'>ZFS-DS</font>|n/a";    break;;
        zvol/*)     OUTPUT="zvol|<font color='black'>ZFS-VOL</font>|n/a";   break;;
        cd[0-9])    OUTPUT="${1}|<font color='black'>CD/DVD</font>|n/a";    break;;
        *)      ;;
    esac

    if [ ! ${!2} ] || [ ${!2} == "UNAVAILABLE" ]; then                                  # check if SMART is available
        OUTPUT="${1}|<font color='black'>SMART&nbsp;n/a</font>|n/a";
        break
    fi

    if [ "${!2}" == "AUTOMOUNT_USB" ]; then DEVICETYPEARG="";                           # we don't know the USB device type
    else DEVICETYPEARG="-d ${!2}";
    fi
	
    if [ $TEMP_ALWAYS -eq 0 ]; then SMART_STANDBY="-n standby"; else SMART_STANDBY=""; fi
    smartctl $SMART_STANDBY -q silent -A /dev/${!3} $DEVICETYPEARG
EXIT_VAL=`echo $?`;
#echo INFO2a exit_value: $EXIT_VAL;                                                  # for debugging
    case $EXIT_VAL in 
        4) MSG="<font color='black'>SMART&nbsp;n/a</font>";;
        2) MSG="<font color='green'>Standby</font>"; if [ "${TEMP_ALWAYS}" == "1" ]; then GET_SMART_SUB "/dev/${!3}" "${DEVICETYPEARG}" ; fi;;
        1) MSG="<font color='orange'>Unknown</font>";;
        0) MSG="<font color='red'>Spinning</font>"; GET_SMART_SUB "/dev/${!3}" "${DEVICETYPEARG}";;
        *) MSG="<font color='red'>exit: ${?}</font>";;       
    esac;
    if [ "$OUTPUT" == "" ]; then OUTPUT="${1}|${MSG}|${MSG_TEMP}";
    else OUTPUT="${OUTPUT}#${1}|${MSG}|${MSG_TEMP}"; fi

}

GET_SPACE ()
{
	CTRL_FILE=${PREFIX}${1}
    case $1 in                                                          # set awk compare string for mountpoints ...
        A_OS)	MP_TYPE="/";;                                           # ... for column 6 of df -k output
        A_USR)	MP_TYPE="/usr/local";;
        A_VAR)	MP_TYPE="/var";;
        cd[0-9])    MP_TYPE="cdrom";
                    OUTPUT="${OUTPUT}##<img src='ext/extended-gui/state_ok.png' alt='Space OK' title='Free space on device is ok.'/>${MSG_ACTION}";;
        *)      MP_TYPE="/mnt/${1}";;
    esac
	SPACE=`echo -e "$MOUNTPOINTS" | awk -v mp=\^${MP_TYPE}\$ '$6 ~ mp {print $4}'`
    if [ "$SPACE" == "" ]; then return; fi                              #F: v0.3
	SPACE_PERCENT=`echo -e "$MOUNTPOINTS" | awk -v mp=\^${MP_TYPE}\$ '$6 ~ mp {gsub("%",""); print $5}'`
    if [ "$SPACE_PERCENT" == "" ]; then return; fi                      #F: v0.3
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
    POOL_ACT=`echo -e "$POOL_BUSY" | awk -v p=\^${1}\$ 'BEGIN { RS = "" }; $2 ~ p {print $0}; '`
    ACTION=`echo -e "$POOL_ACT" | awk '/in progress/ {print $2}'`
    if [ "$ACTION" != "" ]; then
        ACTION1=`echo -e "$POOL_ACT" | awk '/to go/ {print $8, $9, $10}'`
        ACTION2=`echo -e "$POOL_ACT" | awk '/done/ {print $3" "$4}'`
        MSG_SPACE_TXT="Pool ${1}: ${ACTION} in progress, ${ACTION1}, ${ACTION2}!";
        MSG_ACTION="&nbsp;&nbsp;<img src='ext/extended-gui/${ACTION}.gif' alt='${ACTION} in progress' title='"$MSG_SPACE_TXT"'/>";
    fi
    OUTPUT="${OUTPUT}##${MSG_SPACE}${MSG_ACTION}"
    MSG_ACTION=""
}

MOUNTPOINTS=`df -k`                                                     # get usage for all mountpoints
POOL_STATUS=`zpool list -H`                                             # get usage and status for all pools
POOL_BUSY=`zpool status`                                                # get pool busy states (scrub, resilver)
i=0; counter=MOUNT${i};                                                 # set first mountpoint i
while [ "${!counter}" != "" ]; do                                       # run through all mountpoints
    j=0; dcounter=MOUNT${i}DISK${j};                                    # set first disk j for mountpoint i
#echo "INFO ${counter}: ${!counter}"                                    # for debugging
    OUTPUT=""
    while [ "${!dcounter}" != "" ]; do                                  # run through all disks of a mountpoint (RAID, ZFS POOL)
    if [ ! -d `dirname ${PREFIX}${!counter}.smart` ]; then mkdir -p `dirname ${PREFIX}${!counter}.smart`; fi    # create zfs ds/vol directory
#echo "INFO ${!dcounter} ${dcounter}_DEVICETYPEARG ${dcounter}_DEVICE"  # for debugging
        GET_SMART ${!dcounter} ${dcounter}_DEVICETYPEARG ${dcounter}_DEVICE     # retrive SMART values
        j=$((j+1)); dcounter=MOUNT${i}DISK${j};                         # increase disk counter j
    done
    GET_SPACE ${!counter}
    echo "${OUTPUT}" > "${PREFIX}${!counter}.smart"                     # output SMART values to file named as the mountpoint
    i=$((i+1)); counter=MOUNT${i};                                      # increase mountpoint counter i
done

# check for degraded pools
if [ "$POOL_STATUS" != "" ]; then
    POOL_MSG=`echo -e "$POOL_STATUS" | awk '!/ONLINE/ {print "REPORT_DISK "$1" degraded ERROR ZFS pool "$1" is DEGRADED"}'`
    if [ "${POOL_MSG}" == "" ]; then if [ -e ${PREFIX}*"_degraded.lock" ]; then rm ${PREFIX}*"_degraded.lock"; fi
    else 
        CTRL_FILE=${PREFIX}`echo -e ${POOL_MSG} | awk '{ print $2 }'`   # create CTRL_FILE name for pool xxx
        ${POOL_MSG};                                                    # call REPORT_DISK function
    fi
fi

# check for degraded RAID
