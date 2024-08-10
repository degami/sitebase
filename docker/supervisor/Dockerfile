FROM php:8.0-fpm-alpine

USER root

RUN apk add zlib zlib-dev libpng libpng-dev git bash libffi libffi-dev libxml2 libxml2-dev libsodium libsodium-dev
RUN docker-php-ext-install pdo_mysql gd pdo fileinfo simplexml sodium
RUN apk add --update build-base libffi-dev
RUN apk add --no-cache $PHPIZE_DEPS && pecl install xdebug && docker-php-ext-enable xdebug
RUN apk add --no-cache $PHPIZE_DEPS && pecl install redis && docker-php-ext-enable redis
RUN docker-php-ext-enable xdebug pdo_mysql gd fileinfo sodium

RUN apk add supervisor

#CMD ["php-fpm"]
#ENTRYPOINT ["supervisord", "--nodaemon", "--configuration", "/etc/supervisord.conf"]
CMD ["supervisord", "--nodaemon", "--configuration", "/etc/supervisord.conf"]

