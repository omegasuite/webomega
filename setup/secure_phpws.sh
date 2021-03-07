#!/bin/sh

#
# Author: Jeremy Agee <jagee@tux.appstate.edu>
# Version: 0.2
#

# make sure your APACHE_USER and APACHE_GROUP is set appropriately
# nobody (apache default)
# apache (redhat and some others)
# www-data (debian and others)

APACHE_USER=apache
APACHE_GROUP=apache

case $1 in [sS][eE][tT][uU][pP])

    chown $APACHE_USER ../conf/
    chgrp $APACHE_GROUP ../conf/

    chown -R $APACHE_USER ../images/
    chgrp -R $APACHE_GROUP ../images/

    chown -R $APACHE_USER ../files/
    chgrp -R $APACHE_GROUP ../files/

    find ../ -type d | xargs chmod 2775
    find ../ -type f | xargs chmod 0664

    chmod 0555 *.sh

    echo Webserver permissions USER=\($APACHE_USER\), GROUP=\($APACHE_GROUP\)
    echo Permissions set!  Ready to run phpwebsite setup!
    echo
    echo REMEMBER YOU NEED TO RUN THIS SCRIPT WITH
    echo \"run\" MODE AFTER YOU FINISH THE WEB SETUP
    echo
    ;;

[rR][uU][nN])

    if [ $# -lt 3 ]; then
      echo 1>&2 You need a username and group
      echo 1>&2 "    "$0 run your_username your_group
      exit 127
    fi
    if [ -f ../.htaccess ]; then
      chown $APACHE_USER ../.htaccess
      chgrp $APACHE_GROUP ../.htaccess
    else
      touch ../.htaccess
      chown $APACHE_USER ../.htaccess
      chgrp $APACHE_GROUP ../.htaccess
    fi

    find ../ -type d | xargs chmod 2775
    find ../ -type f | xargs chmod 0664

    chown -R $2 ../*
    chgrp -R $3 ../*

    chown $APACHE_USER ../conf/branch/
    chgrp $APACHE_GROUP ../conf/branch/

    chown -R $APACHE_USER ../images/
    chgrp -R $APACHE_GROUP ../images/

    chown -R $APACHE_USER ../files/
    chgrp -R $APACHE_GROUP ../files/

    chmod 0555 *.sh

    echo Webserver permissions USER=\($APACHE_USER\), GROUP=\($APACHE_GROUP\)
    echo User permissions USER=\($2\), GROUP=\($3\)
    echo Permissions Set!  Ready for normal operation!
    ;;

*)
     echo Usage:
     echo $0 setup
     echo or
     echo $0 run your_username your_groupname
     echo ""
     echo Use \'setup\' before running the phpWebSite setup utility.
     echo Use \'run\' for normal phpWebSite operation.
     echo ""
     exit 127
     ;;

esac

exit 0