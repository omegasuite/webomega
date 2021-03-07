#!/bin/sh

#
# Author: Jeremy Agee <jagee@tux.appstate.edu>
# Version: 0.6
#
# $Id: NONROOT_secure_phpws.sh,v 1.22 2003/10/16 13:30:02 matt Exp $
#
# SEE: docs/install.txt for manual setup details
#

echo ""
echo --------------------------------------------------------------------------
echo $0
echo ""
echo Note: This script is for those without root access to their web servers.
echo If you have root access, please use the other script as it is more secure.
echo --------------------------------------------------------------------------
echo ""

case $1 in [sS][eE][tT][uU][pP])

     touch ../conf/config.php

     find ../ -type d | xargs chmod 2775
     find ../ -type f | xargs chmod 0664

     find ../conf -type d | xargs chmod 2777
     find ../conf -type f | xargs chmod 0666

     find ../images -type d | xargs chmod 2777
     find ../images -type f | xargs chmod 0666

     find ../files -type d | xargs chmod 2777
     find ../files -type f | xargs chmod 0666

     chmod 0775 *.sh

     touch ../.htaccess
     chmod 0666 ../.htaccess

     echo Permissions set!  Ready to run the phpWebSite setup utility!
     echo
     echo REMEMBER YOU NEED TO RUN THIS SCRIPT WITH
     echo \"run\" MODE AFTER YOU FINISH THE WEB SETUP
     echo
     ;;

[rR][uU][nN])

     find ../ -type d | xargs chmod 775
     find ../ -type f | xargs chmod 664

     find ../conf -type d | xargs chmod 2775
     find ../conf -type f | xargs chmod 0666

     find ../conf/branch -type d | xargs chmod 2777

     if [ -f ../conf/branch/ ]; then
       find ../conf/branch -type f | xargs chmod 0666
     fi

     find ../images -type d | xargs chmod 2777
     find ../images -type f | xargs chmod 0666

     find ../files -type d | xargs chmod 2777
     find ../files -type f | xargs chmod 0666

     chmod 0555 *.sh

     touch ../.htaccess
     chmod 0666 ../.htaccess

     echo Permissions Set!  Ready for normal operation!
     ;;

*)
     echo Usage:
     echo $0 setup
     echo or
     echo $0 run
     echo ""
     echo Use \'setup\' before running the phpWebSite setup utility.
     echo Use \'run\' for normal phpWebSite operation.
     echo ""
     exit 127
     ;;

esac

exit 0