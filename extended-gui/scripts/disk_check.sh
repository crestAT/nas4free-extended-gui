#!/bin/bash
# filename:		disk_check.sh
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
# purpose:		retrive S.M.A.R.T. infos for eGUI (disk state, temperature) and return results (HTML formated) in file: <mountpoint>.smart
# prereq.:		S.M.A.R.T. must be enabled and existing CONFIG2 file, which will be created at every eGUI startup
# usage:		disk_check.sh
# version:	date:		description:
#	0.7		2017.06.15	N: introduced Telegram as new notification service
#   0.6.10  2017.05.21  N: lifetime parameter xxx ???
#   0.6.9   2017.02.27  N: lifetime parameter 233 Media_Wearout_Indicator for Plextor SSDs
#   0.6.8   2017.01.11  N: lifetime parameter 232 Available Reserved Space for Intel SSDs
#   0.6.7   2016.10.08  F: new bash 4.4 errors -> 'break' in case & if ... statements
#   0.6.6   2016.09.24  N: create messages for index.php
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
#echo $1 $2 $3 $4 $5 $6 $7 $8 $CTRL_FILE"_"$2".lock"                                          # for debugging
	if [ ! -e $CTRL_FILE"_"$2".lock" ]; then 
        echo -e "`date +"$DT_STR"` ${3} $4 $5 $6 $7 $8" >> ${PREFIX}system_error.msg      # create system error message for index.php
        if [ $TELEGRAM_NOTIFICATIONS -eq 1 ] && ([ $EMAIL_DEGRADED_ENABLED -eq 1 ] || [ $EMAIL_SPACE_ENABLED -eq 1 ]); then     # call Telegram if enabled
            TELEGRAM "${3} $4 $5 $6 $7 $8"
        fi
        if [ $RUN_BEEP -gt 0 ]; then                                # call beep when enabled and ERROR condition set
            $SYSTEM_SCRIPT_DIR/beep ZFS_ERROR &
        fi
		NOTIFY "$3 $4 $5 $6 $7 $8"                                      # extended up to 8 for degraded pool msg
		echo "Host: $HOST" > $CTRL_FILE"_"$2.lock
		echo "\n${4}\n" >> $CTRL_FILE"_"$2.lock
		if [ "$2" == "degraded" ]; then 
            zpool status -v $1 >> $CTRL_FILE"_"$2.lock
			if [ $EMAIL_NOTIFICATIONS -eq 1 ] && [ $EMAIL_DEGRADED_ENABLED -eq 1 ]; then $SYSTEM_SCRIPT_DIR/email.sh "$EMAIL_TO" "N4F-DISK $2" $CTRL_FILE"_"$2.lock; fi
		else 
			df -h /mnt/$1 >> $CTRL_FILE"_"$2.lock
			if [ $EMAIL_NOTIFICATIONS -eq 1 ] &&[ $EMAIL_SPACE_ENABLED -eq 1 ]; then $SYSTEM_SCRIPT_DIR/email.sh "$EMAIL_TO" "N4F-DISK $2" $CTRL_FILE"_"$2.lock; fi
		fi
	fi
}

GET_DETAILS ()
{
    MSG_SSD=""; MSG_SSD_LT=""; MSG_SSD_LT_PCT="";
    while [ "${1}" != "" ]; do
#echo GET_DETAILS $1 $2 $3 "---------------"
        case $1 in
            Model|Device|Rotation)      MSG_SSD="SSD";;                          # is SSD
            177|202|232)                MSG_SSD_LT=$((100-$2)); shift 2;;        # lifetime parameter 177 Wear_Leveling_Count | 202 Percent Lifetime used | 232 Perc_Avail_Resrvd_Space = Available Reserved Space for Intel SSDs
            233)                        MSG_SSD_LT=$3; shift 2;;                 # lifetime parameter 233 Media_Wearout_Indicator for Plextor SSDs
            190|194)                    MSG_TEMP=$2; shift 2;;                   # temperature parameter 190 or 194
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
    MSG_ALL=`echo -e "${SMART_OUTPUT}" | awk '/Perc_Avail_Resrvd_Space/ || /Solid State/ || /SSD/ || /Wear/ || /Lifetime/ || /Temperature_/ {print $1,$10,$4}'`;   # check for different params
#echo -e "\n\nGET_SMART_SUB Input for checks: Param1: $1 Param2: $2 MSG_ALL: $MSG_ALL -----------------"
    GET_DETAILS $MSG_ALL
    CNAME=`echo -e ${1} | awk '{gsub("/dev/", ""); print}'`;            # remove the path from the device name
    CTRL_FILE_ORG=$CTRL_FILE;                                           # preserve CTR_FILE
    CTRL_FILE=${PREFIX}${CNAME};
    if [ "${MSG_TEMP}" == "" ]; then MSG_TEMP="n/a"
    else
        if [ ${MSG_TEMP} -ge ${TEMP_SEVERE} ]; then 
            REPORT_DISK ${CNAME} critical ERROR "Disk ${CNAME}: temperature ${MSG_TEMP} °C reached/exceeded the disk temperature critical level ${TEMP_SEVERE} °C!"
            MSG_TEMP="<font color='red'>${MSG_TEMP}&nbsp;&deg;C</font>"
        else if [ ${MSG_TEMP} -ge ${TEMP_WARNING} ]; then 
                MSG_TEMP="<font color='orange'>${MSG_TEMP}&nbsp;&deg;C</font>"
            else MSG_TEMP="<font color='blue'>${MSG_TEMP}&nbsp;&deg;C</font>"
            fi
            if [ -e $CTRL_FILE"_critical.lock" ]; then rm $CTRL_FILE"_critical.lock"; fi    # no longer necessary, we can delete the file
        fi
    fi
    CTRL_FILE=${CTRL_FILE_ORG};                                         # recover CTRL_FILE
}

GET_SMART ()
{
# Parameter 1: device_special_file 2: smart_device_type_arg 3: smart_device
#echo "INFO2 ${1} ${dcounter}_DEVICETYPEARG: ${!2} ${dcounter}_DEVICE ${!3}"    # for debugging
    MSG_TEMP="n/a"
    case $1 in                                                                          # check for special cases
        xmd[0-9]|md[0-9])   OUTPUT="${1}|<font color='black'>RAM-DRV</font>|n/a"; return;;
        ds*)        OUTPUT="${1}|<font color='black'>ZFS-DS</font>|n/a"; return;;
        vol*)       OUTPUT="${1}|<font color='black'>ZFS-VOL</font>|n/a"; return;;
        zvol/*)     OUTPUT="zvol|<font color='black'>ZFS-VOL</font>|n/a"; return;;
        cd[0-9])    OUTPUT="${1}|<font color='black'>CD/DVD</font>|n/a"; return;;
        *)      ;;
    esac

    if [ ! ${!2} ] || [ ${!2} == "UNAVAILABLE" ]; then                                  # check if SMART is available
        OUTPUT="${1}|<font color='black'>SMART&nbsp;n/a</font>|n/a";
        return
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
    if [ $FORCE_STANDBY -gt 0 ]; then                                # call beep when enabled and ERROR condition set
        if [ "$OUTPUT" == "" ]; then OUTPUT="<input name='standby' type='submit' class='formbtn' style='width:45px;' title='Force drive standby' onclick='set_standby(\"${1}\")' value='${1}'>|${MSG}|${MSG_TEMP}";
        else OUTPUT="${OUTPUT}#<input name='standby' type='submit' class='formbtn' style='width:45px;' title='Force drive standby' onclick='set_standby(\"${1}\")' value='${1}'>|${MSG}|${MSG_TEMP}"; fi
    else
        if [ "$OUTPUT" == "" ]; then OUTPUT="${1}|${MSG}|${MSG_TEMP}";
        else OUTPUT="${OUTPUT}#${1}|${MSG}|${MSG_TEMP}"; fi
    fi    
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
        ACTION2=`echo -e "$POOL_ACT" | awk '!/Enable all features/ && /done/ {print $3" "$4}'`
        MSG_SPACE_TXT="Pool ${1}: ${ACTION} in progress, ${ACTION1}, ${ACTION2}!";
        MSG_SPACE_TXT=`echo -e $MSG_SPACE_TXT | tr -d '\n'`;
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
#echo "INFO1 $POOL_STATUS"                                    # for debugging
    POOL_MSG=`echo -e "$POOL_STATUS" | awk '!/ONLINE/ {print "REPORT_DISK "$1" degraded ERROR ZFS Pool "$1" is "$9}'`
    if [ "${POOL_MSG}" == "" ]; then if [ -e ${PREFIX}*"_degraded.lock" ]; then rm ${PREFIX}*"_degraded.lock"; fi
    else 
        CTRL_FILE=${PREFIX}`echo -e ${POOL_MSG} | awk '{ print $2 }'`   # create CTRL_FILE name for pool xxx
        ${POOL_MSG};                                                    # call REPORT_DISK function
    fi
fi

# check for degraded RAID
