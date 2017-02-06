#!/bin/bash

# Test on Ubuntu 16.04 LTS

if [ $(id -u) -ne 0 ]
then
	echo "Must be run as root"
	exit 2
fi

# Netdump default user
adduser --system --disabled-password --home /opt/netdump netdump

# We need find, sort, colordiff, more
apt-get -y install coreutils findutils colordiff util-linux

# We need php < 7.0!
# http://askubuntu.com/questions/761713/how-can-i-downgrade-from-php-7-to-php-5-6-on-ubuntu-16-04
add-apt-repository -y ppa:ondrej/php
apt-get -y update
apt-get -y install php5.6 php5.6-dev php5.6-mysql php5.6-mbstring libapache2-mod-php5.6 php5.6-xml
apt-get -y install php-pear

# Switch from php5.6 to php7.0 :
#  Apache:
#   a2dismod php5.6 ; a2enmod php7.0 ; service apache2 restart
#  CLI:
#   update-alternatives --set php /usr/bin/php7.0
#
# Switch from php7.0 to php5.6 :
#  Apache:
a2dismod php7.0 ; a2enmod php5.6 ; service apache2 restart
#  CLI:
update-alternatives --set php /usr/bin/php5.6

# tk, tcl and tcl expect binaries and sources
apt-get -y install tcl tcl-dev tk tk-dev tcl-expect-dev
apt-get -y install tcl8.4 tcl8.4-dev tk8.4 tk8.4-dev
apt-get -y install tcl8.5 tcl8.5-dev tk8.5 tk8.5-dev
apt-get -y install tcl8.6 tcl8.5-dev tk8.6 tk8.6-dev
apt-get -y install expect expect-dev

# PHP Expect Library Install (For PHP < 7.0)
pecl install channel://pecl.php.net/expect-0.3.3
echo "extension = expect.so" | tee /etc/php/5.6/cli/conf.d/expect.ini

# Library to print tables in terminal
pear install Console_Table

# Default directories
[ -d /etc/netdump ] || mkdir /etc/netdump
[ -d /var/lib/netdump ] || mkdir /var/lib/netdump

# Default config files
[ -f /etc/netdump/targets.conf ] || cp conf/targets.conf.example /etc/netdump/targets.conf
[ -f /etc/netdump/auths.conf ] || cp conf/auths.conf.example /etc/netdump/auths.conf
chmod 600 /etc/netdump/auths.conf

# Default permissions
chown -R netdump:netdump /etc/netdump
chown -R netdump:netdump /var/lib/netdump
chown -R netdump:netdump /opt/netdump

# Apache modules for GitWeb
a2enmod cgi

# Apache modules for GitWeb (LDAP Auth)
a2enmod ldap
a2enmod authnz_ldap 

# GitWeb install (Browse dump versions)
apt-get -y install git gitweb
sed -i 's/\/var\/lib\/git/\/var\/lib\/netdump\/git/g' /etc/gitweb.conf

# Apply config in Apache Web Server
service apache2 restart


