#!/bin/bash
# filename:		purge.sh
# author:		Andreas Schmidhuber
# purpose:		purge deleted files from .recycle/USER directory
# usage:		purge.sh [nn|show] ( nn ... number of days for delete | show ... just show existing recycle bins)
# version:	date:		description:
#	3.3		2015.11.07	C: amendments to purge v03
#	3.2		2015.04.16	C: get extension variables from CONFIG2 instead of reading from config.xml
#	3.1		2015.04.01	F: file find from mtime (modification time) to atime (access time)
#                       C: check for all samba/cifs config file versions (smb.conf, smb4.conf, ...)
#                       C: add trailing '/' at the end of path for smb4 path format 
#	3.0		2014.05.13	C: initial version for Extended GUI
#	2.0		2013.04.29	N: rewrite whole script - use smb.conf (samba shares) to search for waste bins
#	1.2		2010.04.20	initial release
#------------- initialize variables -------------
cd `dirname $0`
. CONFIG
DIRS=""
FILE_LOG=/var/log/samba_purge.log
#------------------------------------------------

if [ "$DAYSOLD" == "" ]; then DAYSOLD=30; fi
if [ "$1" != "" ] && [ "$1" != "show" ]; then DAYSOLD=$1; fi

# get the samba/cifs shares, check for all samba/cifs config file versions (smb.conf, smb4.conf, ...)
cat /var/etc/smb*.conf | awk -F" = " '/path = / {gsub("[/]$", ""); print $2"/.recycle"}' | { while read WASTE_BIN
do
	if [ -d "$WASTE_BIN" ]; then
		if [ "$1" == "show" ] 2>/dev/null; then 			# just show a list of existing recycle bins
			echo "$WASTE_BIN <br />"
			continue; 
		fi
		find "$WASTE_BIN" -type f -mindepth 1 -depth -xdev -atime +$DAYSOLD -print -delete > "$FILE_LOG"
		find "$WASTE_BIN" -type d -mindepth 1 -depth -xdev -empty -print -delete >> "$FILE_LOG"
		if [ "$DIRS" == "" ]; then DIRS="$WASTE_BIN"; else DIRS="$DIRS, $WASTE_BIN"; fi
	fi
done
if ! [ "$1" == "show" ] 2>/dev/null; then logger "purge: deleted files older than $DAYSOLD days (in $DIRS)"; fi
}
