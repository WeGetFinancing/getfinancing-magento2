# ---------------------------------------------- READ THIS FIRST ----------------------------------------------
# All this is able to excecute using Makefile
# For local access use the MAGENTO_URL declared in the env file: (http://localhost:8280/)
# In order to navigate Magento, magento_url in the env file has to be declared in /etc/hosts file too like this
# i.e. if in env file we have MAGENTO_URL=http://magento.local:8280/ in /etc/hosts add 127.0.0.1 magento.local
# If access from internet is needed, can be done with an external server using a port redirection:
ssh [-l user] [-p 22] -R 8280:localhost:8280 externalserverhostorip (used by GF: 8081:vpn.getfinancing.us:8280)
# Work with the Make file in the parent folder
# CREATE THE IMAGE:
make build TARGET="magento_2"
# START THE image/machine:
make start TARGET="magento_2"
# VIEW LOGS IN REAL TIME:
make logsf TARGET="magento_2"
# INSTALL GF magento plugin, set permissions, install shop and sample data:
magentoid=$(docker ps -a | grep gf_magento_2 | cut -d ' ' -f 1); docker exec $magentoid install-getfinancing; docker exec $magentoid install-magento; docker exec $magentoid install-sampledata
# Change admin password (or create new admin users) in order to change admin password user and email must be the same declared in env file:
magentoid=$(docker ps -a | grep gf_magento_2 | cut -d ' ' -f 1); docker exec -it $magentoid ./bin/magento  admin:use:create --admin-user=admin --admin-password=admin123  --admin-firstname=admin --admin-lastname=admin --admin-email=admin@example.com
# login into Magento machine command line
magentoid=$(docker ps -a | grep gf_magento_2 | cut -d ' ' -f 1); docker exec -it  $magentoid bash
# BROWSE the magengo in this URL: 
http://localhost:8280/
# Running commands in the docker machine:
magentoid=$(docker ps -a | grep gf_magento_2 | cut -d ' ' -f 1); docker exec -it $magentoid command
# I.E. (Get the docker admin url)
magentoid=$(docker ps -a | grep gf_magento_2 | cut -d ' ' -f 1); docker exec -it $magentoid ./bin/magento info:adminuri
# Get config info (first param is base_url):
magentoid=$(docker ps -a | grep gf_magento_2 | cut -d ' ' -f 1); docker exec -it $magentoid ./bin/magento  config:show
# change base_url:
magentoid=$(docker ps -a | grep gf_magento_2 | cut -d ' ' -f 1); docker exec -it $magentoid ./bin/magento  setup:store-config:set --base-url="http://localhost:8280/"
magentoid=$(docker ps -a | grep gf_magento_2 | cut -d ' ' -f 1); docker exec -it $magentoid ./bin/magento  setup:store-config:set --base-url-secure="http://localhost:8280/"
# DELETE CACHE:
magentoid=$(docker ps -a | grep gf_magento_2 | cut -d ' ' -f 1); docker exec -it $magentoid ./bin/magento  cache:flush
# SET DEVELOPER MODE
magentoid=$(docker ps -a | grep gf_magento_2 | cut -d ' ' -f 1); docker exec -it $magentoid ./bin/magento deploy:mode:set developer
# DESTROY AND RECREATE: 
# If we already have the image and want to delete, recreate it and start it again (this will not destroy the Mysql database):
magentoid=$(docker ps -a | grep gf_magento_2 | cut -d ' ' -f 1); echo $magentoid; docker stop $magentoid; docker rm $magentoid; make build TARGET="magento_2"; make start TARGET="magento_2"
