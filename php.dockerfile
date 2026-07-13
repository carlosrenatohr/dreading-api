FROM php:8.2-fpm

# The container runs as the host UID/GID (see docker-compose `user:`) so the
# bind-mounted ./src stays host-writable. Drop the pool user/group directives,
# which php-fpm only honors as root and otherwise warns about.
RUN sed -i 's/^user = www-data/;&/; s/^group = www-data/;&/' /usr/local/etc/php-fpm.d/www.conf

RUN apt-get -y update && apt-get install -y \
    libssl-dev pkg-config libzip-dev unzip git

# RUN pecl config-set php_ini  /usr/local/etc/php-fpm/php.ini
# zlib is a bundled PHP extension, not a PECL package — only zip + mongodb come from PECL.
# Pin ext-mongodb to the last 1.x: jenssegers/mongodb 3.9 needs the 1.x lib, which
# requires ext-mongodb ^1.15 (the 2.x extension is incompatible).
RUN pecl install zip mongodb-1.21.0 \
    && docker-php-ext-enable zip \
    && docker-php-ext-enable mongodb

RUN docker-php-ext-install pdo pdo_mysql mysqli

# Composer, running on this same PHP 8.2 image so platform/extension checks match runtime.
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
    # docker-php-ext-install zip \
    # && docker-php-ext-install mongodb apcu \ 
    # && docker-php-ext-install opcache \

# RUN docker-php-ext-enable mongodb apcu opcache

# Clean up
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

CMD ["php-fpm", "-y", "/usr/local/etc/php-fpm.conf", "-R"]

EXPOSE 9000