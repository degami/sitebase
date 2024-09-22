FROM php:8.0-cli-alpine

USER root

COPY ./group /etc/group
COPY ./passwd /etc/passwd

RUN apk add zlib zlib-dev libpng libpng-dev git bash libffi libffi-dev libxml2 libxml2-dev libsodium libsodium-dev
RUN docker-php-ext-install pdo_mysql gd pdo fileinfo simplexml sodium
RUN apk add --update build-base libffi-dev
RUN apk add --no-cache $PHPIZE_DEPS && pecl install xdebug && docker-php-ext-enable xdebug
RUN apk add --no-cache $PHPIZE_DEPS && pecl install redis && docker-php-ext-enable redis
RUN docker-php-ext-enable xdebug pdo_mysql gd fileinfo sodium

RUN apk add supervisor

RUN chmod 1777 /tmp
RUN chmod 755 /var/run

RuN touch /var/log/supervisord.log
RUN chown sitebase:sitebase /var/log/supervisord.log

RuN touch /tmp/supervisor.sock
RUN chown sitebase:sitebase /tmp/supervisor.sock

RUN touch /var/run/supervisord.pid
RUN chown sitebase:sitebase /var/run/supervisord.pid

RUN touch /var/log/sitebase_queue.err.log /var/log/sitebase_queue.out.log
RUN chown sitebase:sitebase /var/log/sitebase_queue.err.log /var/log/sitebase_queue.out.log

USER sitebase

CMD ["supervisord", "--nodaemon", "--configuration", "/etc/supervisord.conf"]
