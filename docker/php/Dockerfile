ARG COMPOSER_VERSION=2
ARG PHP_VERSION=8.2

FROM composer:$COMPOSER_VERSION AS composer
FROM php:${PHP_VERSION}-fpm

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libpq-dev \
    libicu-dev \
    openssl \
    curl \
    libffi-dev \
    iputils-ping \
    && apt-get clean

# UID/GID passati da docker-compose
ARG UID=1000
ARG GID=1000

# Crea gruppo e utente con stessi UID/GID dell'utente host
RUN groupadd -g $GID llphant \
    && useradd -m -u $UID -g $GID llphant

# Install Composer
COPY --from=composer /usr/bin/composer /usr/bin/composer

# Install xdebug-3.2.2 This fixed version solves problems developing on Apple Silicon. Check for future versions
RUN pecl install xdebug-3.5.0 mongodb \
&& docker-php-ext-enable xdebug

RUN docker-php-ext-install pdo pdo_pgsql pdo_mysql zip intl bcmath ffi

RUN docker-php-ext-enable opcache

COPY php-custom.ini /usr/local/etc/php/conf.d/docker-php-custom.ini
COPY xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

ARG COMPOSE_PROJECT_NAME
WORKDIR /var/${COMPOSE_PROJECT_NAME}

USER llphant
