FROM php:8.0-fpm-alpine

RUN apk add zlib zlib-dev libpng libpng-dev git bash libffi libffi-dev
RUN docker-php-ext-install pdo_mysql gd
RUN apk add composer npm ruby ruby-dev
RUN apk add --update build-base libffi-dev
RUN apk add --no-cache $PHPIZE_DEPS && pecl install xdebug && docker-php-ext-enable xdebug
RUN npm install -g gulp
RUN gem install compass compass-rgbapng
RUN wget https://phpdoc.org/phpDocumentor.phar -O /usr/local/bin/phpdoc
RUN chmod 775 /usr/local/bin/phpdoc

CMD ["php-fpm"]

EXPOSE 9003
