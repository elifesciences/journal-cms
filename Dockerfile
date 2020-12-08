ARG image_tag=latest
#ARG php_version
#FROM elifesciences/php_7.3_fpm:${php_version}
FROM elifesciences/php_7.3_fpm

ENV PROJECT_FOLDER=/srv/journal-cms

# see: `elife-base-images/utils/assert_fpm`
ENV PHP_ENTRYPOINT=web/index.php

WORKDIR ${PROJECT_FOLDER}

USER root

RUN chown -R www-data:www-data ./

RUN apt-get update
RUN apt-get install mysql-client git zip unzip libpng-dev -y --no-install-recommends

# sqlite3? looks like it's a built in now
RUN pecl install redis igbinary uploadprogress
RUN pecl install # todo: rm
# needs both mysqli and pdo_mysql or site-install fails
RUN docker-php-ext-install gd mysqli pdo_mysql # cli mbstring xsl curl
RUN docker-php-ext-enable redis igbinary uploadprogress
RUN rm -rf /tmp/pear/

# TODO: sendmail.ini

RUN curl https://getcomposer.org/installer > composer-setup.php && \
    php composer-setup.php --install-dir=/srv/bin --filename=composer --version=1.10.16 && \
    rm composer-setup.php

USER www-data

# required by composer
COPY --chown=www-data:www-data config ./config
COPY --chown=www-data:www-data features ./features
COPY --chown=www-data:www-data src ./src
COPY --chown=www-data:www-data sync ./sync
COPY --chown=www-data:www-data scripts ./scripts
COPY --chown=www-data:www-data composer.json composer.lock ./

ENV COMPOSER_DISCARD_CHANGES=true
RUN composer --no-interaction install --optimize-autoloader

# TODO: further file/dir permissions

# default settings
# these are overridden when instance is launched by mounting custom per-environment versions
# see docker-compose.yml
RUN cp config/drupal-container.settings.php config/local.settings.php
RUN cp config/drupal-container.services.yml config/local.services.yml

WORKDIR ${PROJECT_FOLDER}/web

COPY --chown=www-data:www-data wait-for-it.sh wait-for-it.sh

# todo: shift up
USER root
RUN echo "memory_limit = -1" > /usr/local/etc/php/conf.d/elife-fpm.ini

USER www-data

# requires other services. see docker-composer.yml from here on out
#RUN ../vendor/bin/drush site-install config_installer -y

# `assert_fpm`, see: `elife-base-images/utils/assert_fpm`
# disabled temporarily
#HEALTHCHECK --interval=5s CMD HTTP_HOST=localhost assert_fpm /ping 'pong'

# this image inherits from `elifesciences/php_7.3_fpm`, which inherits from `php:7.3.4-fpm-stretch`, which has it's own
# custom EXECUTE command that starts `php-fpm`.
