#!/bin/bash

APP_DIR=./app/vendor/getfinancing/getfinancing/

for i in $(ls .. | grep -v docker_magento_2); do 
    for f in $(find ../$i); do
        if [ ! -d $APP_DIR$f ]; then
            FNAME=$(echo $f | cut -c 4-)
            if [ ! -d $f ]; then
                IS_DIFF=$(diff $f $APP_DIR$FNAME)
                if [ "$IS_DIFF" != "" ]; then
                    echo "diff $f and $APP_DIR$FNAME"
                    cp $f $APP_DIR$FNAME
                fi
            fi
        fi
    done
done
