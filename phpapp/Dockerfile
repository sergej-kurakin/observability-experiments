FROM php:8.4-fpm

RUN apt-get update \
    && apt-get install -qq -y --no-install-recommends \
    locales libicu-dev libzip-dev libpng-dev libxml2-dev libonig-dev libxslt-dev \
    gcc make autoconf

RUN curl -sSk https://getcomposer.org/installer | php -- --disable-tls && \
   mv composer.phar /usr/local/bin/composer

RUN docker-php-ext-configure intl
RUN docker-php-ext-install pdo pdo_mysql mysqli gd opcache intl zip calendar dom mbstring zip gd xsl
RUN pecl install apcu && docker-php-ext-enable apcu
RUN pecl install opentelemetry && docker-php-ext-enable opentelemetry
RUN pecl install grpc && docker-php-ext-enable grpc
RUN pecl install protobuf && docker-php-ext-enable protobuf

RUN mkdir -p /var/www/html

WORKDIR /var/www/html
