# Netdump

Remote collect configuration (dumps) from networked switches, routers, firewalls and servers using expect php library

# Installation

Installation script is tested in Ubuntu 16.04 LTS. See *install.sh* for details. 

Default installation directory is */opt/netdump/netdump*.

Configuration files stays in */etc/netdump*.


# Commands (CLI)

**php netdump.php [help]**

Shows this commands help

**php netdump.php show target[s]**

List targets from file '/etc/netdump/targets.conf'

**php netdump.php show auth[s]**

List crendentials file '/etc/netdump/auths.conf'

**php netdump.php show dump[s] target [+/-days]**

List dumps for 'target' (case sensitive) created 'days' 
before/after (+/-) somedays until today, using system 
comands like: find, sort, etc.

**php netdump.php show commit[s] target**

List commits made to git control version repository
in '/var/lib/netdump/git' for 'target' (case sensitive)

**php netdump.php show diff[s] target**

List changed (lines) between commits made to git control
version repository for 'target' (case sensitive)

**php netdump.php run [tag]**

Remotly dump configuration for target with 'tag'

**php netdump.php debug [tag]**

Same as run with debugging

# Version Control (Git)

* Dumps versions are saved in: */var/lib/netdump/git*

# Logging

* Dumps are saved in: */var/lib/netdump/dumps*
* Logs are saved in: */var/lib/netdump/logs*


