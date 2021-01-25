FROM php:7.4-fpm-alpine

RUN apk add zlib zlib-dev libpng libpng-dev git bash libffi libffi-dev
RUN docker-php-ext-install pdo_mysql gd
RUN apk add composer npm ruby ruby-dev
RUN apk add --update build-base libffi-dev
RUN npm install -g gulp
RUN gem install compass compass-rgbapng

CMD ["php-fpm"]

EXPOSE 9000
