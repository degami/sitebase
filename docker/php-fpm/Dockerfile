FROM php:8.0-fpm-alpine

RUN apk add zlib zlib-dev libpng libpng-dev git bash libffi libffi-dev libxml2 libxml2-dev libsodium libsodium-dev
RUN docker-php-ext-install pdo_mysql gd pdo fileinfo simplexml sodium
RUN apk add npm ruby ruby-dev
RUN apk add --update build-base libffi-dev
RUN apk add --no-cache $PHPIZE_DEPS && pecl install xdebug && docker-php-ext-enable xdebug
RUN docker-php-ext-enable xdebug pdo_mysql gd fileinfo sodium
RUN npm install -g gulp
RUN gem install compass compass-rgbapng
RUN wget https://phpdoc.org/phpDocumentor.phar -O /usr/local/bin/phpdoc
RUN chmod 775 /usr/local/bin/phpdoc
RUN wget https://getcomposer.org/download/latest-stable/composer.phar -O  /usr/local/bin/composer
RUN chmod 775 /usr/local/bin/composer

CMD ["php-fpm"]

EXPOSE 9003
