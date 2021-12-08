ARG image_tag=latest
#ARG php_version
#FROM elifesciences/php_7.3_fpm:${php_version}
FROM elifesciences/php_7.3_fpm

ENV PROJECT_FOLDER=/srv/journal-cms

# see: `elife-base-images/utils/assert_fpm`
ENV PHP_ENTRYPOINT=web/index.php

WORKDIR ${PROJECT_FOLDER}

USER root

RUN chown -R elife:elife ./

RUN apt-get update
RUN apt-get install mysql-client git zip unzip libpng-dev redis-tools nginx -y --no-install-recommends
RUN pecl install redis igbinary uploadprogress
# 'docker-php-ext-*'?
# - https://github.com/docker-library/docs/tree/master/php#how-to-install-more-php-extensions
# needs both mysqli and pdo_mysql or site-install fails
RUN docker-php-ext-install gd mysqli pdo_mysql # cli mbstring xsl curl
RUN docker-php-ext-enable redis igbinary uploadprogress
RUN rm -rf /tmp/pear/

RUN curl https://getcomposer.org/installer > composer-setup.php && \
    php composer-setup.php --install-dir=/srv/bin --filename=composer --version=1.10.16 && \
    rm composer-setup.php

RUN echo "memory_limit = -1" >> /usr/local/etc/php/conf.d/elife-fpm.ini
RUN echo "upload_max_filesize = 32M" >> /usr/local/etc/php/conf.d/elife-fpm.ini
RUN echo "post_max_size = 32M" >> /usr/local/etc/php/conf.d/elife-fpm.ini
RUN echo "sendmail_path = /bin/true" > /usr/local/etc/php/conf.d/elife-sendmail.ini

USER elife

# files and dirs required by composer
COPY --chown=elife:elife config ./config
COPY --chown=elife:elife features ./features
COPY --chown=elife:elife src ./src
COPY --chown=elife:elife sync ./sync
COPY --chown=elife:elife scripts ./scripts
COPY --chown=elife:elife composer.json composer.lock composer-setup.json composer-setup.lock ./
# and the rest
COPY --chown=elife:elife wait-for-it.sh ./web/wait-for-it.sh
COPY --chown=elife:elife check-drush-migrate-output.sh check-drush-migrate-output.sh
COPY --chown=elife:elife smoke_tests.sh project_tests.sh ./
COPY --chown=elife:elife ./container/prod/configure.sh ./web/configure.sh

# install everything
ENV COMPOSER_DISCARD_CHANGES=true
RUN composer --no-interaction install --optimize-autoloader --no-dev



# TODO: further file/dir permissions so www-data can write/execute what it needs


# settings
RUN cp config/drupal-container.settings.php config/local.settings.php
RUN cp config/drupal-container.services.yml config/local.services.yml

WORKDIR ${PROJECT_FOLDER}/web

# `assert_fpm`, see: `elife-base-images/utils/assert_fpm`
# disabled temporarily
HEALTHCHECK --interval=5s CMD HTTP_HOST=localhost assert_fpm /ping 'pong'

# this image inherits from `elifesciences/php_7.3_fpm`, which inherits from `php:7.3.4-fpm-stretch`, which has it's own
# custom EXECUTE command that starts `php-fpm`.
# lsh@2021-12: replaced ENTRYPOINT with a custom script that inits nginx and then runs php-fpm.
# nginx will drop down to www-data and php-fpm is configured to run as www-data.
USER root
COPY docker-php-entrypoint.sh /usr/local/bin/docker-php-entrypoint.sh
RUN chmod 755 /usr/local/bin/docker-php-entrypoint.sh
ENTRYPOINT ["/usr/local/bin/docker-php-entrypoint.sh"]
