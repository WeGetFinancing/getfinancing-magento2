#!/bin/bash

# Check database connection or wait until it's up
while [ "$(mysql --connect-timeout=1 -h $MYSQL_HOST -u root -p$MYSQL_ROOT_PASSWORD -e "show databases")" == "" ]; do 
    sleep 1; # Wait to be sure the mysql connection is possible
    echo "can't access to dabase, please run the db container"
done

echo "-------- check if database and tables exist -------- "
DB_SHOW=$(/usr/bin/mysql -h $MYSQL_HOST -u root -p$MYSQL_ROOT_PASSWORD -e "use $MYSQL_DATABASE;show tables" | grep $MYSQL_DATABASE); 
if [ "$DB_SHOW" == '' ]; then # DATABASE exists, DROP IT
    echo "-------- Creating database -------- "
    mysql -h $MYSQL_HOST -u $MYSQL_USER -p$MYSQL_PASSWORD < /var/www/mage2_dump.sql
    echo "-------- Database magento and magento 2 tables created -------- "
fi
