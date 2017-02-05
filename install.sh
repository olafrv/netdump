#!/bin/bash

[ $(id -u) -eq 0 ] || echo "Must be run as root" && exit 0;

# http://askubuntu.com/questions/761713/how-can-i-downgrade-from-php-7-to-php-5-6-on-ubuntu-16-04

add-apt-repository ppa:ondrej/php
apt-get update
apt-get install php5.6 php5.6-dev php5.6-mysql php5.6-mbstring libapache2-mod-php5.6 php5.6-xml

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
mkdir /etc/netdump
[ -f conf/targets.conf.example ] || cp conf/targets.conf.example /etc/netdump/targets.conf
[ -f conf/auth.conf.example ] || cp conf/auths.conf.example /etc/netdump/auths.conf
chmod 600 /etc/netdump/auths.conf
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


