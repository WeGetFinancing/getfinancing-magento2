READ THIS FIRST 
=

All this is able to excecute using Makefile

For **local access** use the **MAGENTO_URL** declared in the **env file**: (http://localhost:8280/)

In order to **navigate Magento**, magento_url in the env file has to be declared in **/etc/hosts** file too like this\
127.0.0.1 http://localhost:8280/

**auth.json** has your **Magento credentials**, you need to create a **Magento developer account**, create an **api key** with permissions for download Magento and put it in the auth.json file in **magento_2 folder**
 
If running Docker container in local, need access from internet to the Docker container, it can be done with an external server using a port redirection:\
ssh [-l user] [-p 22] -R 8280:localhost:8280 externalserverhostorip (**without this postbacks will not arrive to the Magento installation**)\
Free tunneling services are available too

Work with the Make file in the parent folder
=

**CREATE THE IMAGE**:\
make build TARGET="magento_2"


**START THE image/machine**:\
make start TARGET="magento_2"

**VIEW LOGS IN REAL TIME**:\
make logsf TARGET="magento_2"

**Change admin password** (or create new admin users) in order to change admin password user and email must be the same declared in env file:\
docker exec -it gf_mage2_http ./bin/magento admin:use:create --admin-user=admin --admin-password=admin123  --admin-firstname=admin --admin-lastname=admin --admin-email=admin@example.com

**Log into** Magento machine **command line**:\
docker exec -it gf_mage2_http bash

**Running commands** in the docker machine:\
docker exec -it gf_mage2_http command

I.E. (Get the docker **admin url**):\
docker exec -it gf_mage2_http ./bin/magento info:adminuri

Get **config info** (first param is base_url):\
docker exec -it gf_mage2_http ./bin/magento  config:show

**change base_url**:\
docker exec -it gf_mage2_http ./bin/magento  setup:store-config:set --base-url="http://localhost:8280/" \
docker exec -it gf_mage2_http ./bin/magento  setup:store-config:set --base-url-secure="http://localhost:8280/"

**DELETE CACHE**:\
docker exec -it gf_mage2_http ./bin/magento  cache:flush

**SET DEVELOPER MODE**:\
docker exec -it gf_mage2_http ./bin/magento deploy:mode:set developer

DESTROY AND RECREATE:
=

If we already have the image and want to **delete**, **recreate it and start** it again (**this will not destroy the Mysql database**):
docker stop gf_mage2_http; docker rm gf_mage2_http; make build TARGET="magento_2"; make start TARGET="magento_2"

Run the containers 
=

`docker network create gf_mage2_net` \
`docker run -d -p 3306 --net=gf_mage2_net --name gf_mage2_db getfinancingdockerhub/gf_mage2_db` \
`docker run -d -p 8280:80 --net=gf_mage2_net --name gf_mage2_http getfinancingdockerhub/gf_mage2_http` \
`docker exec -it gf_mage2_http php ./bin/magento deploy:mode:set developer`

## In Development:
Use `make override_with_dev` to override app\vendor\getfinancing with getfinancing files from git (in this way you can work with git files and override installed files easily)\
For development can be useful make a `watch make override_with_dev` to automatically override the plugin installation files.


