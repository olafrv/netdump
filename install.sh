#!/bin/bash

id -u

if [ $(id -u) -ne 0 ]
then
	echo "Must be run as root"
	exit 2
fi

adduser --system --disabled-password --home /opt/netdump netdump

# http://askubuntu.com/questions/761713/how-can-i-downgrade-from-php-7-to-php-5-6-on-ubuntu-16-04

add-apt-repository -y ppa:ondrej/php
apt-get -y update
apt-get -y install php5.6 php5.6-dev php5.6-mysql php5.6-mbstring libapache2-mod-php5.6 php5.6-xml

# From php5.6 to php7.0 :
#  Apache:
#   a2dismod php5.6 ; a2enmod php7.0 ; service apache2 restart
#  CLI:
#   update-alternatives --set php /usr/bin/php7.0
#
#  From php7.0 to php5.6 :
#   Apache:
a2dismod php7.0 ; a2enmod php5.6 ; service apache2 restart
#   CLI:
update-alternatives --set php /usr/bin/php5.6

apt-get -y install php-pear
apt-get -y install tcl tcl-dev tk tk-dev tcl-expect-dev
#apt-get -y install tcl tcl-dev tk tk-dev
# 8.4, 8.5, 8.6
apt-get -y install expect expect-dev
pear install Console_Table
pecl install channel://pecl.php.net/expect-0.3.3
echo "extension = expect.so" | tee /etc/php/5.6/cli/conf.d/expect.ini

[ -d /etc/netdump ] || mkdir /etc/netdump
[ -d /var/lib/netdump ] || mkdir /var/lib/netdump

[ -f /etc/netdump/targets.conf ] || cp conf/targets.conf.example /etc/netdump/targets.conf
[ -f /etc/netdump/auths.conf ] || cp conf/auths.conf.example /etc/netdump/auths.conf
chmod 600 /etc/netdump/auths.conf

chown -R netdump:netdump /etc/netdump
chown -R netdump:netdump /var/lib/netdump
chown -R netdump:netdump /opt/netdump

chmod +x /opt/netdump/git.sh

apt-get -y install git gitweb
a2enmod cgi
a2enmod ldap
a2enmod authnz_ldap 
service apache2 restart

# Modify /etc/gitweb.conf and change path $projectroot
#  to git repositories to /var/lib/netdump/git
#cat - > /etc/apache2/conf.d/gitweb << END

#Alias /gitweb /usr/share/gitweb

#<Directory /usr/share/gitweb>
#        Options +ExecCGI +FollowSymLinks +SymLinksIfOwnerMatch
#        AllowOverride All
#        order allow,deny
#        Allow from all
#        AddHandler cgi-script cgi
#        DirectoryIndex gitweb.cgi
#</Directory>
#END

#cd /etc/apache2/conf-available
#ln -s ../conf.d/gitweb gitweb.conf
#a2enconf gitweb


