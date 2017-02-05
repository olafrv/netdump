# NETDUMP

Remote collect configuration (dumps) from networked switches, routers, firewalls and servers using expect php library

# INSTALLATION

Installation script is tested in Ubuntu 16.04 LTS. See *install.sh* for details. 

Default installation directory is */opt/netdump/netdump*.

Configuration files stays in */etc/netdump*.


# COMMANDS

php netdump.php [help]
  Shows this help

php netdump.php show target[s]
  List targets from file '/etc/netdump/targets.conf'

php netdump.php show auth[s]
  List crendentials file '/etc/netdump/auths.conf'

php netdump.php show dump[s] target [+/-days]
  List dumps for 'target' (case sensitive) created 'days' 
  before/after (+/-) somedays until today, using system 
  comands like: find, sort, etc.

php netdump.php run [tag]
	Remotly dump configuration for target with 'tag	

php netdump.php debug [tag]
	Same as run with debugging

# GIT REPOSITORY

* Dumps versions are saved in: */var/lib/netdump/git*

# LOGGING

* Dumps are saved in: */var/lib/netdump/dumps*
* Logs are saved in: */var/lib/netdump/logs*


