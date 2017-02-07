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

Then you can issue the commads listed bellow.

## help

Says where to look for help:
```
netdump [help]
```

## show

### show targets

List targets from file '/etc/netdump/targets.conf':
```
netdump show target [| more]
```

### show auths

List crendentials file '/etc/netdump/auths.conf':
```
netdump show auth [| more]
```

### show dumps

List dumps for 'target' (case sensitive) created 'days' 
before/after (+/-) until today:
```
netdump show dump target [+/-days] [| more]
```

### show commits

List commits made to git control version repository
in '/var/lib/netdump/git' for 'target' (case sensitive):
```
netdump show commit target [| more] 
```

The output include: commit id, date and comment.

Show the number of commits:
```
netdump show commit target | wc -l
```

Show the last 10 commits (could be a greater number):
```
netdump show commit target | head [-n 10]
```

### show diffs

List all changes for all commits made to git control
version repository for 'target' (case sensitive):
```
netdump show diff target [commit1 commit2]
```

The commit1 and commit2 allow to filter the changes
to those made between commit1 and commit2.

**NOTE**: The commit1 must be older than commit2 or the output will be empty.

**NOTE**: Use SPACE to scroll output instead of ENTER.

# run

Remotly dump configuration for target with 'tag':
```
netdump run [tag]
```

# debug

Same as run with debugging:
```
netdump debug [tag]
```

# Version Control (Git)

* Dumps versions are saved in: */var/lib/netdump/git* an available via [GitWeb](https://git-scm.com/docs/gitweb) (/gitweb) in the same server.
* Protect unauthorized access to GitWeb using [Apache Auth Module](http://httpd.apache.org/docs/2.0/mod/mod_auth.html)
* Apache LDAP / Active Directory authentication [git/gitweb.example.conf](https://github.com/olafrv/netdump/tree/master/git)

# Backup (Global)

This are the most important directories to backup outside from netdump server:

* */etc* (/etc/apache2 & /etc/netdump)
* */opt/netdump*
* */var/lib/netdump*

# Logging

* Dumps are saved in: */var/lib/netdump/dumps*
* Logs are saved in: */var/lib/netdump/logs*


