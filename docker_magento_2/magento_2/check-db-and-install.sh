#!/bin/bash

COUNT=0
while [ "$(mysql --connect-timeout=1 -h $MYSQL_HOST -u root -p$MYSQL_ROOT_PASSWORD -e "show databases")" == "" ]; do
    sleep 2; # Wait to be sure the mysql connection is possible
    COUNT=$((COUNT + 1))
    if [ $COUNT -gt 8 ]; then # If after several tries we still can't connect to the database just stop the container
        echo " ----------- CANT CONNECT TO DB AFTER $COUNT TRIES JUST STOP TRYING --------------- "
        exit 2
    fi
done


DB_EMPTY=$(mysql -u $MYSQL_USER -p$MYSQL_PASSWORD -h $MYSQL_HOST -e "use magento; show tables;" )

if [ "$DB_EMPTY" = "" ] || [ -f "bin/magento" ]; then 
    /usr/local/bin/install-magento
    /usr/local/bin/install-sampledata
    /usr/local/bin/install-getfinancing
fi

echo " ---------- SET FILE PERMISSIONS --------- "
chown www-data:www-data /var/www/html/ -R
find /var/www/html/ -type d -exec chmod 775 {} \;
find /var/www/html/ -type f -exec chmod 664 {} \;
echo " ---------- FINISHED SETTINGS FILE PERMISSIONS --------- "

MG_VERSION=$(php bin/magento -V)

if [ "${MG_VERSION:0:7}" != "Magento" ]; then 
    echo "Install Magento"; 
else 
    echo "Magento 2 installed version: $MG_VERSION"
fi
