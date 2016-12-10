#!/bin/bash
# filename:		automount_usb.sh
# author:		Andreas Schmidhuber
# purpose:		auto-mount/umount partitions
# usage:		automount.sh [umount | rmount | amount] mount / unmount USB (ATA) partitions daxxx automatically
# comment:		supported devices/file systems:
#               hard disks: UFS, NTFS, MSDOS, FAT32, EXT2, ...  - partitions/slices
#               USB sticks: UFS, MSDOS, FAT32, EXT2, ...  - partitions/slices
#               CD/DVDs: cd9660
# *** IMPORTANT *** for NTFS drives it's necessary to:
#                   - add in /boot/loader.conf  -> fuse_load="YES"
#                   - add in /etc/rc.conf       -> fusefs_enable="YES"
#                   and restart the server to use NTFS drives
#
# version:	date:		description:
#   6.6     2016.09.24  N: verbose message on failed umount
#   6.5     2016.09.23  N: create _DEVICE for SMART support
#	5.9		2016.09.18	N: create _DEVICETYPEARG for SMART support
#	5.8		2016.03.13	N: create index.refresh ctrl file -> force refresh of index.php to display newly mounted devices
#	5.7		2015.11.23	C: set FIRST_DEVICE regarding to the used system (full or embedded)
#	5.6		2015.11.21	C: ext2fs handling
#                       N: take care of CD/DVDs
#                       C: allow disks without 'YourMountpointName.mounted' file, but stays though optional
#                       C: chmod 770 -> 777 for the mount point
#	5.5		2015.10.12	N: sysid 255 - exFAT
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
FIRST_DEVICE=${FIRST_USB}                   # 0 for full, 1 for embedded systems
if [ -e nextMP ]; then
. nextMP                                    # holds the variable NEXT_MP for consecutive mountpoint numbers
fi
if [ -e nextUSBMP ]; then
. nextUSBMP                                 # holds the variable NEXT_MP to avoid to be overwriten by config.xml changes, replaces 'netxMP'
fi
EXITCODE=0
#-----------------------------------------------

# create display entry, parameter: $1 DISK_NAME $2 DEVICE
# e.g. gpart status | awk -v mp=\^da1p1\$ '$1 ~ mp {print $3}'
CREATE_DISPLAY ()
{
    $SYSTEM_SCRIPT_DIR/beep ceg
    /usr/bin/touch /mnt/$DEVICE.automount
    DEVICE_NAME=`gpart status | awk -v mp=\^${2}\$ '$1 ~ mp {print $3}'`    # get device from slice/partition
    if [ "${DEVICE_NAME}" == "" ]; then DEVICE_NAME=$2; fi
    echo "MOUNT${NEXT_MP}=${1}" >> USBMP
    echo "MOUNT${NEXT_MP}DISK0_DEVICE=${DEVICE_NAME}" >> USBMP
    echo "MOUNT${NEXT_MP}DISK0_DEVICETYPEARG=AUTOMOUNT_USB" >> USBMP
    echo "MOUNT${NEXT_MP}DISK0=${DEVICE_NAME}" >> USBMP
    NEXT_MP=$((NEXT_MP+1))
    echo "NEXT_MP=${NEXT_MP}" > nextUSBMP
    /usr/bin/touch ${PREFIX}index.refresh
}

FAILED ()
{
	NOTIFY ERROR $1
	echo $1 > /mnt/$DEVICE.automount.failed
	$SYSTEM_SCRIPT_DIR/beep aaaaa
}

A_MOUNT ()
{ 
	x=$FIRST_DEVICE							# first possible USB-device - take care of embedded systems
    if [ "$1" == "cd" ]; then x=0; fi;      # CD/DVD driver always starts with cd0
	while [ $x -le 9 ]
	do
#echo stage 1
		PART_NAME=/dev/$1$x$2
		DEVICE=$1$x$2
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
				if [ ! -e /mnt/$DEVICE.automount ] && [ ! -e /mnt/$DEVICE.automount.failed ]
				then
#echo stage 4.1 no automount AND no automount.failed
# mount as $DEVICE - works ONLY for disks with ONE partition
					if [ ! -e /mnt/$DEVICE ]; then mkdir -m 777 /mnt/$DEVICE; fi
					chmod 777 /mnt/$DEVICE
					if [ "$1" != "cd" ]; then TEST=`fdisk /dev/$1$x | grep --max-count=1 sysid | awk '{print $2}'`;
                    else TEST="cdrom"; fi
                    if [ "$TEST" == "238" ]; then                                                   # UFS or NTFS
                        TEST1=`fdisk /dev/$DEVICE | grep --max-count=1 sysid | awk '{print $2}'`
                        if [ "$TEST1" == "165" ]; then TEST=$TEST1; fi                              # UFS
                    fi
                    MOUNT_CMD="mount_"; 
					case $TEST in
                        cdrom)  FS_TYPE="cd9660";;          # CD/DVD
						1)      FS_TYPE="msdosfs";;         # 01 DOS
						6)      FS_TYPE="msdosfs";;         # 06 Primary 'big' DOS (>= 32MB)
						7)      FS_TYPE="ntfs";;			# 07 HPFS/NTFS/exFAT
						11)		FS_TYPE="msdosfs";			# 0B W95 FAT32
                                MOUNT_CMD="mount -o large -t ";;
						12)		FS_TYPE="msdosfs";;			# 0C W95 FAT32 (LBA)
						15)     FS_TYPE="ntfs";;			# 15 NTFS
						116)    FS_TYPE="ntfs-3g";;			# 74 NTFS
						131)	FS_TYPE="ext2fs";			# 83 Linux
                                MOUNT_CMD="mount -t ";;
						165)	FS_TYPE="ufs";				# A5 FreeBSD
                                MOUNT_CMD="mount -t ";;
						238)	FS_TYPE="ntfs";;			# EE GPT
						255)	FS_TYPE="exfat";;			# FF exFAT
						*)		FS_TYPE="unknown";;
					esac
                    MOUNT_CMD="${MOUNT_CMD}${FS_TYPE}";
#echo "stage 5 FS_TYPE $FS_TYPE for DEVICE /dev/$1$x MP = /mnt/$DEVICE"
#fdisk /dev/$1$x
					if [ "$FS_TYPE" == "unknown" ]; then 
#echo "stage 5.1 FS_TYPE $FS_TYPE for DEVICE /dev/$1$x MP = /mnt/$DEVICE - FAILED"
                        FAILED "File system type with sysid $TEST UNKNOWN - automount for $PART_NAME not possible"
                        rmdir /mnt/$DEVICE
					else
                        if [ "$FS_TYPE" == "exfat" ]; then 
                            /sbin/kldload /boot/kernel/fuse.ko;
#                            /usr/local/bin/mount.exfat $PART_NAME /mnt/$DEVICE  >> $LOG_MSG_NOTIFY 2>&1;
                            /usr/local/bin/mount.exfat $PART_NAME /mnt/$DEVICE  ;
#                        else mount -o sync -t $FS_TYPE $PART_NAME /mnt/$DEVICE  >> $LOG_MSG_NOTIFY 2>&1 ; fi
                        else 
#echo "stage 6 $MOUNT_CMD $PART_NAME /mnt/$DEVICE"
                            $MOUNT_CMD $PART_NAME /mnt/$DEVICE  >> $LOG_MSG_NOTIFY 2>&1 ; 
                        fi
						MOUNT_ERROR=$?
#echo "stage 6.1 $MOUNT_CMD $PART_NAME /mnt/$DEVICE - result = $MOUNT_ERROR"
						if [ $MOUNT_ERROR -ne 0 ]; then 
                            FAILED "Partition $PART_NAME mount error $MOUNT_ERROR - mount for $DEVICE failed"
#echo "stage 7.1 FAILED Partition $PART_NAME mount return code $MOUNT_ERROR - mount for $DEVICE failed"
                            rmdir /mnt/$DEVICE
                            EXITCODE=27
						else
#echo "stage 7.2 SUCCESS Partition $PART_NAME mounted"
							if [ ! -e /mnt/$DEVICE/*.mounted ]; then 
                                NOTIFY INFO "Partition $PART_NAME mounted as $DEVICE with file system type $TEST $FS_TYPE"
								if [ "$TEST" != "cdrom" ]; then 
                                    chmod 777 /mnt/$DEVICE
                                    NOTIFY INFO "No diskname as alias available for device $DEVICE - you could create a file with the command \"touch /mnt/${DEVICE}/YourMountpointName.mounted\" (e.g. USBxxxxGB.mounted) in the root directory on this drive (current mountpoint $DEVICE). After that re-mount all USB-drives to see this device with alias in SYSTEM | STATUS."
                                fi
                                CREATE_DISPLAY ${DEVICE} ${DEVICE}
							else
# fetch diskname 
								DISK_NAME=`ls /mnt/$DEVICE/*.mounted | cut -d/ -f4 | cut -d. -f1`
#echo "stage 8 DISK_NAME $DISK_NAME"
								chmod 000 /mnt/$DEVICE/$DISK_NAME.mounted				# to make sure that the file survives
								umount /mnt/$DEVICE
								if [ $? -eq 0 ]; then rmdir /mnt/$DEVICE; fi
# mount disk with name from file USBxxxxGB.mounted
								if [ "$DISK_NAME" == "" ]; then 
                                    FAILED "Cannot retrieve proper disk name from /mnt/$DEVICE/*.mounted - partition $PART_NAME mount failed"
                                    EXITCODE=28
								else 
									if [ ! -e /mnt/$DISK_NAME ]; then mkdir -m 777 /mnt/$DISK_NAME; fi
									if [ $? -ne 0 ]; then FAILED "Problems occured during mountpoint creation for $DISK_NAME - partition $PART_NAME mount failed"
									else 
                                        $MOUNT_CMD $PART_NAME /mnt/$DISK_NAME
										if [ $? -ne 0 ]; then 
                                            FAILED "Problems occured mounting partition $PART_NAME on mountpoint /mnt/$DISK_NAME - mount failed"
                                            rmdir /mnt/$DISK_NAME
                                            EXITCODE=29
										else 
											if [ ! -e /mnt/$DISK_NAME/$DISK_NAME.mounted ]; then 
                                                FAILED "Partition $PART_NAME mount failed as $DISK_NAME"
                                                EXITCODE=30
											else 
# send success message and create control file for disk and device
												chmod 777 /mnt/$DISK_NAME
												NOTIFY INFO "Partition $PART_NAME mounted as $DISK_NAME with file system type $TEST $FS_TYPE"
                                                CREATE_DISPLAY ${DISK_NAME} ${DEVICE}
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

# e.g. fstat | grep /mnt/d ... to check for open devices/files
U_MOUNT ()
{
#echo "stage 11 unmount"
	MOUNTED=`mount | awk '/\/dev\/da/ && /\/mnt\// || /\/dev\/cd/ && /\/mnt\//  || /\/dev\/fuse/ && /\/mnt\// {print $3}'`
	for NAME in $MOUNTED; do 
#echo "stage 12 unmount $NAME from $MOUNTED"
		sync $NAME; 
		umount $NAME >> $LOG_MSG_NOTIFY 2>&1; 
		if [ $? -ne 0 ]; then 
            NOTIFY ERROR "Cannot un-mount $NAME, device is busy";
            NOTIFY ERROR "fstat output: "`fstat ${NAME} | grep "${NAME}"`;
            $SYSTEM_SCRIPT_DIR/beep bgbgbgbg;
            exit 12;
		else 
			rmdir $NAME
            CNAME=`echo -e ${NAME} | awk '{gsub("/mnt/", ""); print}'`;     # remove the path from the mount name
            rm ${PREFIX}${CNAME}*.*
			NOTIFY INFO "$NAME unmounted"; $SYSTEM_SCRIPT_DIR/beep gec
		fi
	done
    if [ -e USBMP ]; then rm USBMP; fi
    if [ -e nextUSBMP ]; then rm nextUSBMP; fi
}

R_MOUNT ()
{ NOTIFY INFO "Re-mount USB drives"; U_MOUNT; rm /mnt/*.automount*; $0; }

ATA_U_MOUNT ()
{
# not used at the moment
#	MOUNTED=`mount | awk '/mnt\/NAS/ {print $3}'`
#	for NAME in $MOUNTED; do sync $NAME; NOTIFY INFO "$NAME synced"; umount $NAME; NOTIFY INFO "$NAME unmounted"; done
	NOTIFY INFO "Un-mount ALL disks (except system disk)"; umount -Av >> $LOG_MSG_NOTIFY
}

case $1 in
	amount)	ATA_U_MOUNT;;
	umount)	U_MOUNT;;
	rmount)	R_MOUNT;;
	*)		A_MOUNT da s1; A_MOUNT da s2; A_MOUNT da s3; A_MOUNT da s4;		# slices (MBR)
			A_MOUNT da p1; A_MOUNT da p2; A_MOUNT da p3; A_MOUNT da p4;     # partitions (GPT)
            A_MOUNT cd;;                                                    # CD/DVDs
esac

exit $EXITCODE
