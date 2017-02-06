# Netdump

Remote collect configuration (dumps) from networked switches, routers, firewalls and servers using expect php library

# Installation

Installation script is tested in Ubuntu 16.04 LTS, run installer with:

```bash
curl https://raw.githubusercontent.com/olafrv/netdump/master/install.sh | bash -
```

Default installation directory is */opt/netdump/netdump*.

Configuration files stays in */etc/netdump*.


# Commands (CLI)

**WARNING: Netdump PHP script can't be run as superuser (root)**.

First, from **root** change to the user *netdump*:

```
sudo su - netdump
```
**WARNING**: netdump command is an alias of php netdump.php**

Then you can issue the following commands:

## help

```
netdump [help]
```

Shows this commands help

## show

### show targets

```
netdump show target[s]
```

List targets from file '/etc/netdump/targets.conf'

### show auths

```
netdump show auth[s]
```

List crendentials file '/etc/netdump/auths.conf'

### show dumps

```
netdump show dump[s] target [+/-days]
```

List dumps for 'target' (case sensitive) created 'days' 
before/after (+/-) somedays until today

### show commits

```
netdump show commit[s] target
```

List commits made to git control version repository
in '/var/lib/netdump/git' for 'target' (case sensitive)

### show diffs

```
netdump show diff[s] target
```

List changed (lines) between commits made to git control
version repository for 'target' (case sensitive)

# run

netdump run [tag]

Remotly dump configuration for target with 'tag'

# debug

```
netdump debug [tag]
```

Same as run with debugging

# Version Control (Git)

* Dumps versions are saved in: */var/lib/netdump/git* an available via [GitWeb](https://git-scm.com/docs/gitweb) (/gitweb) in the same server.

* Protect access to GitWeb using [Apache Auth](http://httpd.apache.org/docs/2.0/mod/mod_auth.html)

* Apache LDAP / Active Directory authentication [git/gitweb.example.conf](https://github.com/olafrv/netdump/tree/master/git)

# Backup (Global)

This are the most important directories to backup outside from netdump server:

* */etc* (/etc/apache2 & /etc/netdump)
* */opt/netdump*
* */var/lib/netdump*

# Logging

* Dumps are saved in: */var/lib/netdump/dumps*
* Logs are saved in: */var/lib/netdump/logs*


