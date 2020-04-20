Extended GUI
------------

- compact display with LIVE view for all information - "dashboard" like - without refreshing needed.
- compact disks view (S.M.A.R.T. must be enabled to get temperature and activity) with drive status/temperature and disk/pool capacity warning indicator (optional information via email)
[color=#FF0000]- System warning / error notifications (audible / visual /email / Telegram ) and alarm history for mount point/disk errors/space/temperature, ZFS, CPU and UPS[/color]
[color=#FF0000]- disk drive spindown buttons[/color]
[color=#FF0000]- buttons for selective unmount of single USB devices[/color]
[color=#FF0000]- user defined buttons / functions[/color]
[color=#FF0000]- user defined log files[/color]
- full support for ZFS pools (displays all drives in a pool)
- display of ZFS Datasets
- display SSDs with lifetime values (percents)
- full support for Software RAID (displays all drives in a raid)
- support/display for temporarly (not via WebGUI) mounted disks (USB disks, Flash drives, ...)
- compact UPS display
- USB Automount: for USB disks, Flash drives and CD/DVDs
- Purge: clean recycle bins of CIFS/SMB shares (.recycle directories) from deleted files
- Function buttons for CIFS/SMB Purge and unmount/remount USB disks
- hide/show CPU usage bar(s)
- hide/show multicore CPU usage bar(s)
- hide/show of CPU load and Network traffic ([color=#FF0000]interface selectable[/color]) graphs
- display Operating System (root filesystem as A_OS)
- display /usr/local filesystem (as A_USR)
- display /var filesystem (as A_VAR)
- ZFS busy state indicators for scrubing and resilvering
- display of logged in users (SSH, FTP, SMB/CIFS -> user/ip@/port) (optional login/logout information via email)
- Network hosts monitor (editable definition of network and host part), display IP@s and host names (as defined in /etc/hosts)
- display of services/service status
- hide/show function buttons row
- audible alarms for Extended GUI (optional - for users login/out, USB drives mount/unmount/errors, CPU temperature, ZFS errors, HDD/mount point errors)
- editable CPU temperature warning levels
- editable disk temperature warning levels
- editable disk free space warning levels
- editable graph show and refresh time
- Telegram support
- disk free space threshold warning notifications (via email/Telegram) (optional)
- CPU temperature threshold warning notifications (via email/Telegram) (optional)
- ZFS pool degraded warning notifications (via email/Telegram) (optional)
- user login/logout warning notifications (via email/Telegram) (optional)
- TOOLS section in EXTENSIONS | EXTENDED GUI includes: 
[list]
[*]User defined action buttons for STATUS | SYSTEM and additional log file support for DIAGNOSTICS | LOG
[*]CIFS/Samba recycle bin Purge
[*]USB Automount
[/list]
