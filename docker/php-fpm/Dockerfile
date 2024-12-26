FROM php:8.3-fpm-alpine

ENV USER=sitebase
ENV GROUPNAME=$USER
ENV UID=1000
ENV GID=1000

RUN addgroup \
    --gid "$GID" \
    "$GROUPNAME" \
&&  adduser \
    --disabled-password \
    --gecos "" \
    --home "$(pwd)" \
    --ingroup "$GROUPNAME" \
    --no-create-home \
    --uid "$UID" \
    $USER

RUN apk add zlib zlib-dev libpng libpng-dev git bash libffi libffi-dev libxml2 libxml2-dev libsodium libsodium-dev libzip libzip-dev
RUN docker-php-ext-install pdo_mysql gd pdo fileinfo simplexml sodium zip
RUN apk add npm ruby ruby-dev
RUN apk add --update build-base libffi-dev
RUN apk add --no-cache $PHPIZE_DEPS && pecl install xdebug && docker-php-ext-enable xdebug
RUN apk add --no-cache $PHPIZE_DEPS && pecl install redis && docker-php-ext-enable redis
RUN docker-php-ext-enable xdebug pdo_mysql gd fileinfo sodium
RUN npm install -g gulp
#RUN gem install compass compass-rgbapng
RUN apk add graphviz plantuml
RUN wget https://phpdoc.org/phpDocumentor.phar -O /usr/local/bin/phpdoc
RUN chmod 775 /usr/local/bin/phpdoc
RUN wget https://getcomposer.org/download/latest-stable/composer.phar -O  /usr/local/bin/composer
RUN chmod 775 /usr/local/bin/composer

RUN composer global require maglnet/composer-require-checker
RUN composer global require ergebnis/composer-normalize

USER sitebase