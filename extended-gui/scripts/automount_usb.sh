#!/bin/sh
# filename:		automount_usb.sh
# author:		Andreas Schmidhuber
# purpose:		auto-mount/umount partitions
# usage:		automount.sh [umount | rmount | amount] mount / unmount USB (ATA) partitions daxxx automatically
# comment:		for access to the USB drive the N4F user MUST be member of group wheel !!!
# version:	date:		description:
#	5.4		2015.01.13	N: sysid 6
#	5.3		2014.07.15	F: changed chmod position -> after mount command to gain write access for user group
#	5.2		2014.07.11	F: drives with more then one partition are supported but all partitions/slices must have 
#							the same file system type => grep --max-count=1 !!!
#	5.1		2014.06.14	N: sysid 12
#	5.0		2014.05.29	C: initial version for Extended GUI
#------------- initialize variables ------------
cd `dirname $0`
. CONFIG
DEVICE=""
FIRST_DEVICE=0
#-----------------------------------------------

FAILED ()
{
	NOTIFY ERROR $1
	echo $1 > /mnt/$DEVICE.automount.failed
	$SYSTEM_SCRIPT_DIR/beep gecceggecceg
}

A_MOUNT ()
{ 
	x=$FIRST_DEVICE							# first possible USB-device - take care of embedded systems
	while [ $x -le 9 ]
	do
#echo stage 1
		PART_NAME=/dev/da$x$1
		DEVICE=da$x$1
# check if device exists, if YES then continue
#echo stage 2 $PART_NAME $DEVICE
		if [ -r $PART_NAME ]
		then 
# check if device is already mounted, if NO then continue
#echo stage 3
			mount | grep $PART_NAME
			if [ $? -ne 0 ]
			then
# check if disk was already auto mounted OR disk mount failed (!), if YES do nothing
#echo stage 4
				if [ ! -e /mnt/$DEVICE.automount* ]
				then
# mount as $DEVICE -> test fs-type: sysid 165=ufs, 238=ufs, 131=ext2fs, 7=ntfs - works ONLY for disks with ONE partition
					if [ ! -e /mnt/$DEVICE ]; then mkdir -m 770 /mnt/$DEVICE; fi
					chmod 770 /mnt/$DEVICE
					TEST=`fdisk /dev/da$x | grep --max-count=1 sysid | awk '{print $2}'`
					case $TEST in
						131)	FS_TYPE="ext2fs";;			# 83 Linux
						165)	FS_TYPE="ufs";;				# A5 FreeBSD
						238)	FS_TYPE="ufs";;				# EE GPT
						11)		FS_TYPE="msdosfs";;			# 0B W95 FAT32
						12)		FS_TYPE="msdosfs";;			# 0C W95 FAT32 (LBA)
						7)		FS_TYPE="ntfs";;			# 07 HPFS/NTFS/exFAT
						6)      FS_TYPE="msdosfs";;         # 06 Primary 'big' DOS (>= 32MB)
						*)		FS_TYPE="unknown";;
					esac
#echo stage 5 $FS_TYPE
#fdisk /dev/da$x
					if [ "$FS_TYPE" == "unknown" ]; then FAILED "File system type with sysid $TEST UNKNOWN - automount for $PART_NAME not possible";
					else
						mount -o sync -t $FS_TYPE $PART_NAME /mnt/$DEVICE  >> $LOG_MSG_NOTIFY 2>&1
						MOUNT_ERROR=$?
						if [ $MOUNT_ERROR -ne 0 ]; then FAILED "Partition $PART_NAME mount error $MOUNT_ERROR - mount for $DEVICE failed"
						else
							if [ ! -e /mnt/$DEVICE/*.mounted ]; then FAILED "No diskname available for your USB device $DEVICE - create a file with the command \"touch /mnt/${DEVICE}/YourMountpointName.mounted\" (e.g. USBxxxxGB.mounted) in the root directory on this drive (current mountpoint $DEVICE). After that re-mount all USB-drives."
							else
# fetch diskname 
								DISK_NAME=`ls /mnt/$DEVICE/*.mounted | cut -d/ -f4 | cut -d. -f1`
								chmod 000 /mnt/$DEVICE/$DISK_NAME.mounted				# to make sure that the file survives
								umount /mnt/$DEVICE
								if [ $? -eq 0 ]; then rmdir /mnt/$DEVICE; fi
# mount disk with name from file USBxxxxGB.mounted
								if [ "$DISK_NAME" == "" ]; then FAILED "Cannot retrieve proper disk name from /mnt/$DEVICE/*.mounted - partition $PART_NAME mount failed"
								else 
									if [ ! -e /mnt/$DISK_NAME ]; then mkdir -m 770 /mnt/$DISK_NAME; fi
									if [ $? -ne 0 ]; then FAILED "Problems occured during mountpoint creation for $DISK_NAME - partition $PART_NAME mount failed"
									else 
										mount -o sync -t $FS_TYPE $PART_NAME /mnt/$DISK_NAME
										if [ $? -ne 0 ]; then FAILED "Problems occured mounting partition $PART_NAME - mount failed"
										else 
											if [ ! -e /mnt/$DISK_NAME/$DISK_NAME.mounted ]; then FAILED "Partition $PART_NAME mount failed as $DISK_NAME"
											else 
# send success message and create control file for disk and device
												chmod 770 /mnt/$DISK_NAME
												NOTIFY INFO "Partition $PART_NAME mounted as $DISK_NAME with file system type $FS_TYPE"
												$SYSTEM_SCRIPT_DIR/beep ceg
												/usr/bin/touch /mnt/$DEVICE.automount
											fi
										fi
									fi
								fi
							fi
						fi
					fi
				fi
			fi
		else 
# if a partition is no longer available, remove the appropriate lock file
			if [ -e /mnt/$DEVICE.automount* ]; then rm /mnt/$DEVICE.automount*; fi
		fi
		x=$((x+1))
	done
}

U_MOUNT ()
{
	MOUNTED=`mount | awk '/dev\/da/ && /mnt/ {print $3}'`
	for NAME in $MOUNTED; do 
		sync $NAME; 
#		NOTIFY INFO "$NAME synced"; 
		umount $NAME >> $LOG_MSG_NOTIFY 2>&1; 
		if [ $? -ne 0 ]; then NOTIFY ERROR "Cannot un-mount $NAME"; $SYSTEM_SCRIPT_DIR/beep ceggecceggec
		else 
			rmdir $NAME
			NOTIFY INFO "$NAME unmounted"; $SYSTEM_SCRIPT_DIR/beep gec
		fi
	done
}

R_MOUNT ()
{ NOTIFY INFO "Re-mount USB drives"; U_MOUNT; rm /mnt/da*.automount*; $0; }

ATA_U_MOUNT ()
{
#	MOUNTED=`mount | awk '/mnt\/NAS/ {print $3}'`
#	for NAME in $MOUNTED; do sync $NAME; NOTIFY INFO "$NAME synced"; umount $NAME; NOTIFY INFO "$NAME unmounted"; done
	NOTIFY INFO "Un-mount ALL disks (except system disk)"; umount -Av >> $LOG_MSG_NOTIFY
}

case $1 in
	amount)	ATA_U_MOUNT;;
	umount)	U_MOUNT;;
	rmount)	R_MOUNT;;
	*)		A_MOUNT s1; A_MOUNT s2; A_MOUNT s3; A_MOUNT s4;		# slices ext2fs, ntfs, 
			A_MOUNT p1; A_MOUNT p2; A_MOUNT p3; A_MOUNT p4;		# partitions ufs 
			A_MOUNT a;; 										# USB-Sticks
esac
