#!/bin/bash
# filename:		cpu_check.sh
# author:		Andreas Schmidhuber
# purpose:		retrive CPU temperature infos for eGUI in file: cpu_check.log
# usage:		cpu_check.sh
# version:	date:		description:
#   0.2     2015.11.24  N: beep on ERROR
#   0.1     2015.11.23  initial version for Extended GUI 
#------------- initialize variables ------------
cd `dirname $0`
. CONFIG
REPORT_FILE="${PREFIX}cpu_check.log"
CTRL_FILE="${PREFIX}cpu_check"
HYSTERESIS=${CPU_TEMP_HYSTERESIS}            # avoid repetitive alarms if temperature changes for some degrees
#-----------------------------------------------

# compares arg1 and arg2, returns: "0" on arg1 less, "1" on arg1 equal, "2" on arg1 greater than arg2
COMPARE ()
{ local RESULT=`echo | awk -v n1=$1 -v n2=$2 '{if (n1<n2) print("0"); else if (n1==n2) print("1"); else print("2");}'`; return $RESULT; }

# subtract floating point numbers $1 minus $2, return result directly
SUB ()
{ echo $1 $2 | awk '{print ($1) - ($2)}'; }

# $1 = severity level (WARNING|ERROR), $2 = whole output message
REPORT ()
{
	if [ ! -e ${CTRL_FILE}_${1}.lock ]; then 
        NOTIFY $@
		echo "Host: $HOST" > ${CTRL_FILE}_${1}.lock
		echo "\n$2" >> ${CTRL_FILE}_${1}.lock
        if [ $EMAIL_CPU_TEMP_ENABLED -gt 0 ] && [ -e ${CTRL_FILE}_ERROR.lock ]; then 
            $SYSTEM_SCRIPT_DIR/email.sh "$EMAIL_TO" "N4F-CPU" ${CTRL_FILE}_ERROR.lock; 
            if [ $RUN_BEEP -gt 0 ]; then                                        # call beep when enabled and ERROR condition set
                $SYSTEM_SCRIPT_DIR/beep CPU_ERROR &
            fi
        fi
	fi
}

GET_TEMPERATURE ()
{
    x=0;
    OUTPUT="";
	while [ $x -lt $CPU_NUMBER ]
	do
        TEMPERATURE=`sysctl -q -n dev.cpu.${x}.temperature | awk '{gsub("C", ""); print}'`
#echo 1 "CPU${x} actual temp ${TEMPERATURE}, warning temp ${CPU_TEMP_WARNING} minus $HYSTERESIS = `SUB ${CPU_TEMP_WARNING} $HYSTERESIS`"
        COMPARE ${TEMPERATURE} ${CPU_TEMP_SEVERE}                               # test if temperature is >= CPU_TEMP_SEVERE
        if [ $? -ge 1 ]; then 
            MSG_TEMP="<font color='red'>${TEMPERATURE}&nbsp;&deg;C</font>"
            REPORT ERROR "CPU ${x} reached critical temperature threshold ${CPU_TEMP_SEVERE} degree C, temperature is ${TEMPERATURE} degree C."
#echo 2 "$TEMPERATURE ${TEMPERATURE}"
        else 
            COMPARE ${TEMPERATURE} ${CPU_TEMP_WARNING}                          # test if temperature is >= CPU_TEMP_WARNING
            if [ $? -ge 1 ]; then 
                MSG_TEMP="<font color='orange'>${TEMPERATURE}&nbsp;&deg;C</font>"
                REPORT WARNING "CPU ${x} reached warning temperature threshold ${CPU_TEMP_WARNING} degree C, temperature is ${TEMPERATURE} degree C."
#echo 3 "$TEMPERATURE ${TEMPERATURE}"
            else 
                COMPARE ${TEMPERATURE} `SUB ${CPU_TEMP_WARNING} $HYSTERESIS`    # test if temperature is < CPU_TEMP_WARNING - $HYSTERESIS °C !
                if [ $? -eq 0 ]; then 
                    if [ -e "${CTRL_FILE}_ERROR.lock" ]; then rm "${CTRL_FILE}_ERROR.lock"; fi
                    if [ -e "${CTRL_FILE}_WARNING.lock" ]; then rm "${CTRL_FILE}_WARNING.lock"; fi
#echo 9 "actual temp ${TEMPERATURE}, warning temp ${CPU_TEMP_WARNING} minus $HYSTERESIS = `SUB ${CPU_TEMP_WARNING} $HYSTERESIS`, files will be deleted"                
                fi
                MSG_TEMP="<font color='blue'>${TEMPERATURE}&nbsp;&deg;C</font>"
            fi
        fi
        if [ "$OUTPUT" == "" ]; then OUTPUT="${MSG_TEMP}";
        else OUTPUT="${OUTPUT}&nbsp;&nbsp;${MSG_TEMP}"; fi
		x=$((x+1));
	done
}

GET_TEMPERATURE
echo "${OUTPUT}" > "${REPORT_FILE}"                                     # output temperature(s) to file for index.php
