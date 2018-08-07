TARGET="magento_2"
#MAGENTO_ID=$(shell docker ps -a | grep docker_$(TARGET) | grep -v maria | cut -d ' ' -f 1)
MAGENTO_ID=gf_mage2_http
ADMIN_USR="admin"
ADMIN_PASSWORD="admin123"
ADMIN_FIRSTNAME="admin"
ADMIN_LASTNAME="admin"
ADMIN_EMAIL="admin@example.com"
MG_BASE_URL="http://magento.local:8280/"
MG_SECURE_URL="https://magento.local:8280/"
MG_MODE="developer"


show_img_id: ##@ View Magento Image ID
	@echo $(MAGENTO_ID)

env_info: ##@ See info of current variables
	@echo TARGET=$(TARGET) MAGENTO_ID=$(MAGENTO_ID) ADMIN_USR=$(ADMIN_USR) ADMIN_PASSWORD=$(ADMIN_PASSWORD) 
	@echo ADMIN_FIRSTNAME=$(ADMIN_FIRSTNAME) ADMIN_LASTNAME=$(ADMIN_LASTNAME) ADMIN_EMAIL=$(ADMIN_EMAIL) MG_BASE_URL=$(MG_BASE_URL) MG_SECURE_URL=$(MG_SECURE_URL)
	@echo MG_MODE=$(MG_MODE)

build:  ##@ Build the image
	docker-compose build $(TARGET)

build-nc: ##@ Build the image not using cache MG_SECURE_URL= MG_SECURE_URL= MG_MODE= 
	docker build --no-cache $(TARGET)

start: ##@ Start the image
	docker-compose up -d $(TARGET)

stop: ##@ Stop image
	docker stop $(MAGENTO_ID)

delete: ##@ Delete image
	docker rm $(MAGENTO_ID)

logs: ##@ show logs (tail)
	docker logs -t $(MAGENTO_ID)

logsf: ##@ show logs in real time
	docker logs -f -t $(MAGENTO_ID)

ps: ##@ Show docker processes
	docker ps

override_with_dev: ##@ Override installed files with modified development files (from ../ to app/vendor/getfinancing/getfinancing)
	@bash -c "./magento_2/sync_in_app.sh"

install_gf_plugin: ##@ Install Magento base, Magento sample data and GetFinancing plugni
	@echo install Magento in $(MAGENTO_ID)
	docker exec $(MAGENTO_ID) install-magento
	@echo install Magento Sample data in $(MAGENTO_ID)
	docker exec $(MAGENTO_ID) install-sampledata
	@echo install GF_Plugin in $(MAGENTO_ID)
	docker exec $(MAGENTO_ID) install-getfinancing

mg_change_admin_password: ##@ Example of use: make change_admin_password ADMIN_PASSWORD="admin123"
	docker exec -it $(MAGENTO_ID) php ./bin/magento  admin:use:create --admin-user=$(ADMIN_USR) --admin-password=$(ADMIN_PASSWORD)  --admin-firstname=$(ADMIN_FIRSTNAME) --admin-lastname=$(ADMIN_LASTNAME) --admin-email=$(ADMIN_EMAIL)

login_bash: ##@ Log into the machine
	docker exec -it  $(MAGENTO_ID) bash

mg_set_file_permissions: ##@ Fix file permissions in Magento folder
	docker exec -it $(MAGENTO_ID) chown www-data:www-data /var/www/html/ -R
	docker exec -it $(MAGENTO_ID) find /var/www/html/ -type d -exec chmod 775 {} \; 
	docker exec -it $(MAGENTO_ID) find /var/www/html/ -type f -exec chmod 664 {} \; 

mg_gf_plugin_upgrade: ##@ Upgrade GetFinancing plugin installation
	docker exec -it $(MAGENTO_ID) php ./bin/magento setup:upgrade

mg_exec_cmd: ##@ Example of use: make exec_cmd USR_CMD="ls /tmp"
	docker exec $(MAGENTO_ID) $(USR_CMD)

mg_show_admin_uri: ##@ Show Admin URL
	docker exec -it $(MAGENTO_ID) php ./bin/magento info:adminuri

mg_show_config: ##@ Show all Magento confing
	docker exec -it $(MAGENTO_ID) php /var/www/html/bin/magento  config:show

mg_set_baseurl: ##@ Change BASE URL, use MG_BASE_URL="http://magento.local:8280/"
	docker exec -it $(MAGENTO_ID) php ./bin/magento  setup:store-config:set --base-url="$(MG_BASE_URL)"

mg_set_secure_baseurl: ##@ change SECURE URL, use MG_SECURE_URL="https://magento.local:8280/"
	docker exec -it $(MAGENTO_ID) php ./bin/magento  setup:store-config:set --base-url-secure="$(MG_SECURE_URL)"

mg_flush_cache: ##@ Delete cache
	docker exec -it $(MAGENTO_ID) php /var/www/html/bin/magento  cache:clean
	docker exec -it $(MAGENTO_ID) php /var/www/html/bin/magento  cache:flush

mg_set_mode: ##@ change modes between developer and production
	docker exec -it $(MAGENTO_ID) php ./bin/magento deploy:mode:set $(MG_MODE)

mg_run_test_coverage:
	@echo "run GF plugin tests coverage into /tmp/report"
	docker exec -it $(MAGENTO_ID) ./vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist ./vendor/getfinancing/ --coverage-html /tmp/report vendor/vendorname/m2-module-name/

mg_run_tests:
	@echo "run GF plugin tests"
	docker exec -it $(MAGENTO_ID) ./vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist ./vendor/getfinancing/ vendor/vendorname/m2-module-name/


.DEFAULT_GOAL := help

help:
	@grep -E '(^[a-zA-Z_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) \
		| awk 'BEGIN {FS = ":.*?##@ "}; {printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' \
		| sed -e 's/\[32m##/[33m/'
