Extended GUI
------------

THE WORK ON THIS EXTENSION IS STILL IN PROGRESS - ANY FEEDBACK / COMMENTS / IDEAS ARE APPRICIATED!!!

Extension for NAS4Free with several improvements for the WebGUI and additional functions as
described below (NAS4Free WebGUI MENU | SUBMENU and according pages).

The extension
- works on all plattforms (full, embedded, i386, x64)
- does not need jail or pkg_add.
- replace pages of the NAS4Free WebGUI
- let you switch between STANDARD (original) and EXTENDED WebGUI
- let you easily configure/enable/disable views and additional functions
- is easy to install/uninstall

CONTENT
-------
ACCESS | USERS & GROUPS | USERS (access_users.php)
- CHANGED: column "GROUP" now shows all groups a user belongs to (originally shows only the primary 
			group of a user). This patch is committed to r957 of NAS4Free.

DISKS | ZFS | DATASETS | DATASET & VOLUMES | VOLUME (disks_zfs_dataset.php and disks_zfs_volume.php)			
- CHANGED: errors are not trapped when one apply changes to ZFS datasets and ZFS volumes where settings 
			are not accepted by ZFS. This patch is committed to r958 of NAS4Free.

DIAGNOSTICS | LOG (diag_log.php)
- NEW: search (case insensitive) in all logs
- NEW: entry NOTIFICATIONS for Extended GUI related messages
- CHANGED: column "USER" now shows user / processes (originally shows always the host name)
	
STATUS | DISKS (status_disks.php)
- NEW: column "ACTIVITY" shows disk status "spinning / standby" (S.M.A.R.T. must be enabled)

STATUS | GRAPH | SYSTEM LOAD & CPU LOAD (status_graph.php and status_graph_cpu.php)
- NEW: graph show time and refresh time configurable

STATUS | SYSTEM (index.php)
- NEW: compact display with LIVE view for all information - "dashboard" like - without refreshing needed.
		(currently ZFS ist NOT live on index.php)
- NEW: display of CPU load and Network traffic graphs
- NEW: hide/show (multicore) CPU usage bar(s)
- NEW: show system drive - NOT YET IMPLEMENTED
- NEW: support/display for temporarly (not via WebGUI) mounted disks (USB disks, Flash drives, ...)
		see also -> http://forums.nas4free.org/viewtopic.php?f=56&t=3797
- NEW: compact disks view (S.M.A.R.T. must be enabled to get temperature and activity) with drive 
		status/temperature and disk/pool capacity warning indicator (optional information via email)
- NEW: UPS is only displayed if enabled
- NEW: automount for USB disks, Flash drives, ...  - NOT YET IMPLEMENTED
- NEW: display of logged in users (SSH, FTP, SMB/CIFS -> user/ip@/port) (optional information via email)
- NEW: display of clients in network (for Autoshutdown detection based on IP@)  - NOT YET IMPLEMENTED
- NEW: display of services / service status (ON/OFF) / quick links  - NOT YET IMPLEMENTED
		- Autoshutdown, SubSonic, miniDLNA, pyLoad, BitTorrent, udpxy, Webserver, BitTorrent Sync, ...
- NEW: implementation of additional functions:
		- Autoshutdown ON/OFF: disable/enable temorarely autoshutdown - NOT YET IMPLEMENTED
		- Purge: purge of files in CIFS/SMB recycle bin - NOT YET IMPLEMENTED
		- unmount / remount USB disks - NOT YET IMPLEMENTED
		- unmount ATA (adaX) disks  - NOT YET IMPLEMENTED
		- FSCK All: run fsck script online - NOT YET IMPLEMENTED
				
INSTALLATION
------------
1. Prior to the installation make a backup of the N4F configuration via SYSTEM | BACKUP/RESTORE | Download configuration
2. Use the system console shell or connect via ssh to your N4F server - for the installation you must be logged in as root
3. Change to a persistant place / data directory which should hold the extensions, in my case /mnt/DATA/extensions
4. Download the extension with the fetch command, extract files, remove archive, run the installation script and choose option 1 to install

	cd /mnt/DATA/extensions
	fetch <address-from> 
	tar xzvf extended-gui.tar.gz
	rm extended-gui.tar.gz
	./extended-gui-install.php

After the installation one can manage the extension via the WebGUI SYSTEM | EXTENSIONS | Extended GUI (don't forget to refresh 
the WebGUI to see the new extension). Just click Enable, choose Extended type, change the settings for your needs and Save. 
After that you see the modified index page of the N4F WebGUI with additional information and functions.

HISTORY
-------
Version Date		Description
4.3.1	2014.04.17	F: temp display wrapped in Chrome
					N: UPS is only displayed if enabled
4.3		2014.04.16	first public release
