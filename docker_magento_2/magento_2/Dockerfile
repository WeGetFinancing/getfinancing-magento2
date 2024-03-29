# for info check the README file
FROM debian

LABEL maintainer="oferraro@getfinancing.com"
LABEL version="2.2.4"
LABEL description="Magento 2.2.4"

ENV MAGENTO_VERSION 2.2.4
ENV WORKDIR /var/www/html/
WORKDIR $WORKDIR

RUN apt-get update && apt-get install -y apache2 php \
	php-mbstring php-xml php-zip php-mysql php-bcmath php-gd \
    openssl ca-certificates php-mcrypt php-curl php-intl \
    php-soap git curl mysql-client

# Define composer home, install composer and add auth.json to be able to download Magento 
ENV COMPOSER_HOME /var/www/.composer/
RUN curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/composer
COPY ./auth.json /var/www/.composer

# Copy Magento installer, data installer and plugin installer scripts. Set exec permissions
COPY ./install-magento ./install-sampledata ./install-getfinancing \ 
    ./load_db.sh httpd-foreground /usr/local/bin/ 
RUN chmod u+x /usr/local/bin/install-magento /usr/local/bin/install-sampledata \
    /usr/local/bin/install-getfinancing /usr/local/bin/httpd-foreground \
    /usr/local/bin/load_db.sh \
	&& rm /etc/apache2/sites-enabled/000-default.conf 
COPY 000-default.conf /etc/apache2/sites-enabled/000-default.conf 
COPY get-env-vars env full_dump.sql mage2_dump.sql /var/www/

# Clean unnecesary files, create composer cache folder, set permissions
RUN apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* 
RUN chsh -s /bin/bash www-data && \
    mkdir -p /var/www/.composer/cache/repo/ && \
    rm -rf $WORKDIR/* && \
    chown -R www-data:www-data /var/www/ 
RUN find /var/www/ -type d -exec chmod 775 {} \; && \
    find /var/www/ -type d -exec chmod g+s {} \; && \
    find /var/www/ -type f -exec chmod 664 {} \; 

CMD ["/usr/local/bin/httpd-foreground"]
