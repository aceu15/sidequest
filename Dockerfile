FROM php:8.4-apache

RUN a2dismod mpm_event mpm_worker mpm_prefork || true
RUN a2enmod mpm_prefork rewrite

RUN docker-php-ext-install pdo_mysql

COPY . /var/www/html/

EXPOSE 80
