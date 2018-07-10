TARGET="magento_2"
MAGENTO_ID=$(shell docker ps -a | grep $(TARGET) | cut -d ' ' -f 1)
ADMIN_USR="admin"
ADMIN_PASSWORD="admin123"
ADMIN_FIRSTNAME="admin"
ADMIN_LASTNAME="admin"
ADMIN_EMAIL="admin@example.com"
MG_BASE_URL="http://magento.local:8280/"
MG_SECURE_URL="http://magento.local:8280/"
MG_MODE="developer"


show_img_id: ##@ View Magento Image ID
	@echo $(MAGENTO_ID)

build:  ##@ Build the image
	docker build -t $(TARGET)

build-nc: ##@ Build the image not using cache
	docker build --no-cache -t $(TARGET)

start: ##@ Start the image
	docker up -d $(TARGET)

stop: ##@ Stop image
	docker stop $(MAGENTO_ID)

delete: ##@ Delete image
	docker rm $(MAGENTO_ID)

logs: ##@ show logs (tail)
	docker logs -t $(TARGET)

logsf: ##@ show logs in real time
	docker logs -f -t $(TARGET)

ps: ##@ Show docker processes
	docker ps

install_gf_plugin: ##@ Install Magento base, Magento sample data and GetFinancing plugni
	@echo install Magento in $(MAGENTO_ID)
	docker exec $(MAGENTO_ID) install-getfinancing
	@echo install Magento Sample data in $(MAGENTO_ID)
	docker exec $(MAGENTO_ID) install-sampledata
	docker exec $(MAGENTO_ID) install-magento
	@echo install GF_Plugin in $(MAGENTO_ID)

mg_change_admin_password: ##@ Example of use: make change_admin_password ADMIN_PASSWORD="admin123"
	docker exec -it $(MAGENTO_ID) ./bin/magento  admin:use:create --admin-user=$(ADMIN_USR) --admin-password=$(ADMIN_PASSWORD)  --admin-firstname=$(ADMIN_FIRSTNAME) --admin-lastname=$(ADMIN_LASTNAME) --admin-email=$(ADMIN_EMAIL)

login_bash:
	docker exec -it  $(MAGENTO_ID) bash

mg_exec_cmd: ##@ Example of use: make exec_cmd USR_CMD="ls /tmp"
	docker exec -it $(MAGENTO_ID) $(USR_CMD)

mg_show_admin_uri: ##@ Show Admin URL
	docker exec -it $(MAGENTO_ID) ./bin/magento info:adminuri

mg_show_config: ##@ Show all Magento confing
	docker exec -it $(MAGENTO_ID) ./bin/magento  config:show

mg_set_baseurl: ##@ Change BASE URL
	docker exec -it $(MAGENTO_ID) ./bin/magento  setup:store-config:set --base-url="$(MG_BASE_URL)"

mg_set_secure_baseurl: ##@ change SECURE URL
	docker exec -it $(MAGENTO_ID) ./bin/magento  setup:store-config:set --base-url-secure="$(MG_SECURE_URL)"

mg_flush_cache: ##@ Delete cache
	docker exec -it $(MAGENTO_ID) ./bin/magento  cache:flush

mg_set_mode: ##@ change modes between developer and production
	docker exec -it $(MAGENTO_ID) ./bin/magento deploy:mode:set $(MG_MODE)

.DEFAULT_GOAL := help

help:
	@grep -E '(^[a-zA-Z_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) \
		| awk 'BEGIN {FS = ":.*?##@ "}; {printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' \
		| sed -e 's/\[32m##/[33m/'
