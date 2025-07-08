FROM php:8.2-cli

WORKDIR /var/www

RUN apt-get update && apt-get install -y git unzip zip && \
    docker-php-ext-install pdo pdo_mysql

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
