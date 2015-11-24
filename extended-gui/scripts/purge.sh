#!/bin/bash
# filename:		purge.sh
# author:		Andreas Schmidhuber
# purpose:		purge deleted files from .recycle/USER directory
# usage:		purge.sh [nn|show] ( nn ... number of days for delete | show ... just show existing recycle bins)
# version:	date:		description:
#	3.0		2014.05.13	C: initial version for Extended GUI
#	2.0		2013.04.29	N: rewrite whole script - use smb.conf (samba shares) to search for waste bins
#	1.2		2010.04.20	initial release
#------------- initialize variables -------------
cd `dirname $0`
. CONFIG
DAYSOLD=`/usr/local/bin/xml sel -t -v "//extended-gui/purge/days" ${XML_CONFIG_FILE}`
DIRS=""
FILE_LOG=extended-gui_purge_files_deleted.txt
DIR_LOG=extended-gui_purge_dirs_deleted.txt
#------------------------------------------------

if [ $# -gt 0 ]; then DAYSOLD=$1; fi
if [ "$DAYSOLD" == "" ]; then DAYSOLD=90; fi

# get the samba/cifs shares
cat /var/etc/smb.conf | awk -F" = " '/path = / {print $2".recycle"}' | { while read WASTE_BIN
do
	if [ -d "$WASTE_BIN" ]; then
		if [ "$1" == "show" ] 2>/dev/null; then 			# just show a list of existing recycle bins
			echo "$WASTE_BIN <br />"
			continue; 
		fi
		echo "*** purge: delete files older than $DAYSOLD day(s) in $WASTE_BIN"
		echo `date` ------------------- >> "$WASTE_BIN/$FILE_LOG"
		echo `date` ------------------- >> "$WASTE_BIN/$DIR_LOG"
		find "$WASTE_BIN" -type f -depth -xdev -mtime +$DAYSOLD -print -delete >> "$WASTE_BIN/$FILE_LOG"
		find "$WASTE_BIN" -type d -depth -xdev -empty -print -delete >> "$WASTE_BIN/$DIR_LOG"
		if [ "$DIRS" == "" ]; then DIRS="$WASTE_BIN"; else DIRS="$DIRS, $WASTE_BIN"; fi
	fi
done
if ! [ "$1" == "show" ] 2>/dev/null; then NOTIFY INFO "Deleted files older than $DAYSOLD days (in $DIRS)"; fi
}
