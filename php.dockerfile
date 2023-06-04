FROM php:8-fpm

# Copy the .env file to the working directory
# ENV PHPUSER laravel
# ENV PHPGROUP laravel

# # Create laravel user and group
# RUN addgroup --system ${PHPGROUP} && adduser --system --no-create-home --ingroup ${PHPGROUP} ${PHPUSER}
# # RUN addgroup -S ${PHPGROUP} && adduser --system ${PHPUSER} -G ${PHPGROUP}
# # Update PHP-FPM configuration
# RUN sed -i "s/user = www-data/user = ${PHPUSER}/g" /usr/local/etc/php-fpm.d/www.conf
# RUN sed -i "s/group = www-data/group = ${PHPGROUP}/g" /usr/local/etc/php-fpm.d/www.conf

# RUN mkdir -p /var/www/html/public

RUN apt-get -y update && apt-get install -y \
    libssl-dev pkg-config libzip-dev unzip git

# RUN pecl config-set php_ini  /usr/local/etc/php-fpm/php.ini
# RUN pecl install zlib zip mongodb apcu
RUN pecl install zlib zip mongodb \
    && docker-php-ext-enable zip \
    && docker-php-ext-enable mongodb

RUN docker-php-ext-install pdo pdo_mysql mysqli 
    # docker-php-ext-install zip \
    # && docker-php-ext-install mongodb apcu \ 
    # && docker-php-ext-install opcache \

# RUN docker-php-ext-enable mongodb apcu opcache

# Clean up
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# Set proper permissions for the working directory
# RUN chown -R ${PHPUSER}:${PHPGROUP} /var/www/html

# Switch to the laravel user
# USER ${PHPUSER}

CMD ["php-fpm", "-y", "/usr/local/etc/php-fpm.conf", "-R"]

EXPOSE 9000