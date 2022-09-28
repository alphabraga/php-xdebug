FROM php:7.4-apache

RUN apt-get update \
&& apt-get install -y apt-utils \
&& pecl install xdebug

ADD docker-php-ext-xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini