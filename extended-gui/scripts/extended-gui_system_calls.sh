#!/bin/bash
# filename:		extended-gui_system_calls.sh
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
# purpose:		executes several scripts every n seconds
# usage:		extended-gui_system_calls.sh (... w/o parameters)
# version:	date:		description:
#	4.2		2017.06.27	N: run services check
#	4.1		2017.03.13	N: check for firmware upgrade
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
    if [ "$LOOP_DELAY" == "" ]; then LOOP_DELAY=60; fi
    if [ -e "$FIRMWARELOCK_PATH" ]; then logger "extended-gui: firmware upgrade in progress, no further checks will be performed"
    else
        lastchange_config=`/usr/local/bin/xml sel -t -v "//lastchange" ${XML_CONFIG_FILE}`
        if [ "$change_config" != "$lastchange_config" ]; then
#NOTIFY "INFO2 system_calls start ALT: $change_config NEU: $lastchange_config"
            su root -c "/usr/local/www/ext/extended-gui/extended-gui_create_config2.php"
        fi

        $SYSTEM_SCRIPT_DIR/cpu_check.sh
        $SYSTEM_SCRIPT_DIR/disk_check.sh
        if [ $RUN_SERVICES -gt 0 ]; then 
            if [ -e ${PREFIX}services_firstrun.lock ]; then php $SYSTEM_SCRIPT_DIR/extended-gui_create_services_list.inc;
            else touch ${PREFIX}services_firstrun.lock; fi
        fi
        if [ $RUN_USER -gt 0 ]; then $SYSTEM_SCRIPT_DIR/user_check.sh; fi
        if [ $RUN_HOSTS -gt 0 ]; then 
            $SYSTEM_SCRIPT_DIR/hosts_check.sh &
        fi
        if [ $RUN_AUTOMOUNT -gt 0 ]; then $SYSTEM_SCRIPT_DIR/automount_usb.sh; fi
#NOTIFY "INFO system_calls end"
    fi
	sleep $LOOP_DELAY
done

UNLOCK_SCRIPT
