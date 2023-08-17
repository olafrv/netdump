# Netdump

> :warning::warning::warning: This repository is **NOT** under **active maintenance** since August 2023, see https://www.rconfig.com/ for a replacement.

## What it is?

A tool to remotly backup the configuration of networked switches, routers, firewalls and servers using expect php library, git version control and many other Linux utilities.

## What it is not?

It is not the [netdump kernel module, client or server utility](https://linux.die.net/man/8/netdump) for Linux.

## Features

* Tested on Ubuntu Linux Server Edition 16.04 LTS (64 bits).
* Editable templates to backup the following devices:
  * Cisco UCS (SSH trigger FTP/SFTP/TFTP copy).
  * Cisco IOS (SSH/Telnet).
  * Cisco Nexus OS (SSH/Telnet).
  * Fortigate FortiOS (SSH trigger FTP/TFTP copy).
  * Foundry ServerIron (Telnet).
  * Netgear Switches (Telnet).
* Asisted version control with Git repositories per device.
* Friendly Web browsing of backups via secured GitWeb interface.
* SSH client param included to support old devices (Weak protocols).
* Notification support via PHPMailer (Installed in /usr/share/php).

# Installation

**WARNING: Requires PHP 5.6 because PHP Expect library is not yet compatible with PHP 7.0**

**WARNING: Installation REQUIRE MANUAL STEPS described in other section of this manual**

Installation script is tested in Ubuntu 16.04 LTS, run installer on a 'root' session:
```bash
curl https://raw.githubusercontent.com/olafrv/netdump/master/install.sh | bash -
```

Default installation directory is */opt/netdump/netdump*.

Configuration files stays in:

* */etc/netdump/target.conf* (Switches, Router, Firewall & Servers)
* */etc/netdump/auth.conf* (Authentication credendials for Targets)
* */etc/netdump/mail.php* (Mail reporting configuration)

Output (Dump) from devices are saved in: 

* */var/lib/netdump/dumps*

Dumps versions are saved in individual git repositories per device in: 

* */var/lib/netdump/git* 

Git repositories can be manipulated with:

* Using **netdump commands**: show and clone.
* [GitWeb](https://git-scm.com/docs/gitweb) (/gitweb) in the same server.
* Using raw **git commands** using **netdump** local user.

Finally, some manual configuration are REQUIRED:

* Look at the **Security**, **Logging**, **Scheduled Tasks** and **Backup** sections bellow.

# Security

* Please configure iptables to protect FTP, SFTP, SCP, SSH and TFTP access to netdump server.
* Protect unauthorized access to GitWeb using [Apache Auth Module](http://httpd.apache.org/docs/2.0/mod/mod_auth.html)

**WARNING**: By default, it is installed LDAP / Active Directory authentication 
[conf/gitweb.conf](https://github.com/olafrv/netdump/tree/master/conf) you can
comment the lines if you dont need them or prefer another security measure.

# Scheduled Tasks (Cron Jobs)

An example of crontab is here [conf/crontab](https://github.com/olafrv/netdump/tree/master/conf)

Cron jobs should run with **netdump** user and edited as follows:
```
sudo su - netdump
crontab -e
```

An adding and the following content:
```
MAILTO=netdump@localhost
SHELL=/bin/bash
PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/snap/bin
0 21 * * * source ~/.bash_profile && netdump runmail
0 9 * * * source ~/.bash_profile && ~/netdump/purge.sh > /dev/null
```

* Cronjobs are:
  * Created on netdump users session (crontab -e)
  * Output are delivered locally by **exim4** MTA (/var/spool/mail/netdump) 
  * Cronjobs mails can be read with *mail* client command from netdump user session

# Logging

* Unfiltered expect output are saved in: */var/lib/netdump/logs*
* Netdump command output are saved in: */var/log/syslog*

**WARNING: Increase retention of syslog and apache logs** 

**/etc/logrotate.d/rsyslog**
```
/var/log/syslog
{
  rotate 52
  weekly
```
**/etc/logrotate.d/apache2**
```
/var/log/apache2/*.log {
        weekly
        missingok
        rotate 52
```

Then restart the service:
```
service restart rsyslog
```

# Backup

This are the most important directories to backup outside from netdump server:

* /etc 
  * */etc/apache2*
  * */etc/netdump*
* /opt/netdump
* /var/lib/netdump

# Purge 

To erase all backups just delete the following directories:

* /var/lib/netdump/{dumps,logs}/* using a purge script (See cronjobs section).
* /var/lib/netdump/git/* using linux rm command carefully. 


# Templates

![Netdump Workflow](https://raw.githubusercontent.com/olafrv/netdump/master/doc/netdump.png "Netdump Workflow")

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

### show target

List targets from file '/etc/netdump/targets.conf':
```
netdump show target
```

**Fields:** Template, Target Tag, IP Address / Hostname, Authentication Tag

### show targetbytpl

List targets from file '/etc/netdump/targets.conf' filtered by template:
```
netdump show targetbytpl <template>
```

### show auth

List crendentials file '/etc/netdump/auths.conf':
```
netdump show auth
```

### show dump

List dumps for 'target' (case sensitive) created 'days' 
before/after (+/-) until today:
```
netdump show dump <target> [+/-days]
```

### show commit

List commits made to git control version repository
in '/var/lib/netdump/git' for 'target' (case sensitive):
```
netdump show commit <target> 
```

The output include: commit id, date and comment.

Show the number of commits:
```
netdump show commit <target>
```

Show the last 10 commits (could be a greater number):
```
netdump show commit <target> 
```

### show diff

List all changes for all commits made to git control
version repository for 'target' (case sensitive):
```
netdump show diff <target> [commit1 commit2]
```

The commit1 and commit2 allow to filter the changes
to those made between commit1 and commit2.

**NOTE**: The commit1 must be older than commit2 or the output will be empty.

**NOTE**: Use SPACE to scroll output instead of ENTER.

# run

Remotly dump configuration for target with 'tag':
```
netdump [run|runmail] [tag]
```

**runmail** is the same as **run** but send an email with a execution report

# debug

Same as run with debugging:
```
netdump [debug|debugmail] [tag]
```

**runmail** is the same as **run** but send an email with a execution report

# clone

Make a copy of the last or a specific dump commited version of a target:
```
netdump clone <target> <destination> [commit]
```

**destination:** is directory (e.g. /tmp).
**commit:** if specified should be taked from the output of the **commit command**.

# Troubleshooting

For basic trouble shooting use **debug command**, which shows more **useful output**.

**SSH** in template needs to be run in **QUITE MODE** issue a **'-q'** parameter.

**Run manually the command** (cmd) shown in the **debug screen output** without '-q'.

Some **SSH errors** like: key mismatch, weak key algorithm, can be detected easily.

Most SSH errors with **legacy devices** could be resolved using **telnet**.

The installation process (install.sh) create a client configuration  **~/.ssh/config** to allow connection with old cisco devices using **weak protocols**.  An example is shown here:

```
# https://www.openssh.com/legacy.html
Host *
        KexAlgorithms +diffie-hellman-group1-sha1
        HostKeyAlgorithms +ssh-dss
```

