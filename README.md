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

Then you can issue the following commands:

## help

```
netdump [help]
```

Shows commands help

## show

### show targets

```
netdump show target[s] [| more]
```

List targets from file '/etc/netdump/targets.conf'

### show auths

```
netdump show auth[s] [| more]
```

List crendentials file '/etc/netdump/auths.conf'

### show dumps

```
netdump show dump[s] target [+/-days] [| more]
```

List dumps for 'target' (case sensitive) created 'days' 
before/after (+/-) somedays until today

### show commits

```
netdump show commit[s] target [| more] 
```

List commits made to git control version repository
in '/var/lib/netdump/git' for 'target' (case sensitive)

The output include: commit id, date, comment

```
netdump show commit[s] target | wc -l
```

Show the number of commits

```
netdump show commit[s] target | head [-n 10]
```

Show the last 10 commits (could be a greater number)

### show diffs

```
netdump show diff[s] target [commit1 commit2]
```

List all changes for all commits made to git control
version repository for 'target' (case sensitive)

The commit1 and commit2 allow to filter the changes
to those made between commit1 and commit2

**The commit1 must be older than commit2 or the output will be empty**

**Use SPACE to scroll output instead of ENTER**

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


