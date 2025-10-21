FROM php:8.3-fpm-alpine

ENV USER=sitebase
ENV GROUPNAME=$USER
ENV UID=1000
ENV GID=1000

# Creazione utente
RUN addgroup --gid "$GID" "$GROUPNAME" \
 && adduser --disabled-password --gecos "" --home "$(pwd)" --ingroup "$GROUPNAME" --no-create-home --uid "$UID" $USER

# Dipendenze di sistema
RUN apk add --no-cache \
    bash \
    git \
    zlib zlib-dev \
    libpng libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libffi libffi-dev \
    libxml2 libxml2-dev \
    libsodium libsodium-dev \
    libzip libzip-dev \
    imagemagick-dev \
    build-base \
    autoconf \
    pkgconfig \
    npm \
    ruby ruby-dev \
    openjdk11-jre \
    graphviz \
    wget \
    curl

# Compilazione GD con JPEG e FreeType
RUN docker-php-ext-configure gd \
        --with-jpeg=/usr/include/ \
        --with-freetype=/usr/include/ \
    && docker-php-ext-install -j$(nproc) gd pdo_mysql pdo fileinfo simplexml sodium zip

# PECL extensions
RUN apk add --no-cache $PHPIZE_DEPS \
 && pecl install xdebug redis imagick \
 && docker-php-ext-enable xdebug redis imagick

# Node & Gulp
RUN npm install -g gulp

# PlantUML
RUN curl -L -o /usr/local/bin/plantuml.jar https://downloads.sourceforge.net/project/plantuml/plantuml.jar \
 && echo -e '#!/bin/sh\njava -jar /usr/local/bin/plantuml.jar "$@"' > /usr/local/bin/plantuml \
 && chmod +x /usr/local/bin/plantuml /usr/local/bin/plantuml.jar

# PHPDoc
RUN wget https://phpdoc.org/phpDocumentor.phar -O /usr/local/bin/phpdoc \
 && chmod 775 /usr/local/bin/phpdoc

# Composer
RUN curl -L -o /usr/local/bin/composer https://getcomposer.org/download/latest-stable/composer.phar \
 && chmod 775 /usr/local/bin/composer

# Composer global packages
RUN composer global require maglnet/composer-require-checker \
 && composer global require ergebnis/composer-normalize

USER sitebase
