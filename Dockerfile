FROM php:7.4-apache-bullseye AS drupal-9

# install the PHP extensions we need
RUN set -eux; \
	\
	if command -v a2enmod; then \
		a2enmod rewrite; \
	fi; \
	\
	apt-get update; \
	apt-get install -y --no-install-recommends git; \
	\
	savedAptMark="$(apt-mark showmanual)"; \
	\
	apt-get install -y --no-install-recommends \
		libfreetype6-dev \
		libjpeg-dev \
		libpng-dev \
		libpq-dev \
		libwebp-dev \
		libzip-dev \
	; \
	\
	docker-php-ext-configure gd \
		--with-freetype \
		--with-jpeg=/usr \
		--with-webp \
	; \
	\
	docker-php-ext-install -j "$(nproc)" \
		gd \
		opcache \
		pdo_mysql \
		pdo_pgsql \
		zip \
	; \
	\
# reset apt-mark's "manual" list so that "purge --auto-remove" will remove all build dependencies
	apt-mark auto '.*' > /dev/null; \
	apt-mark manual $savedAptMark; \
	ldd "$(php -r 'echo ini_get("extension_dir");')"/*.so \
		| awk '/=>/ { so = $(NF-1); if (index(so, "/usr/local/") == 1) { next }; gsub("^/(usr/)?", "", so); print so }' \
		| sort -u \
		| xargs -r dpkg-query -S \
		| cut -d: -f1 \
		| sort -u \
		| xargs -rt apt-mark manual; \
	\
	apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false; \
	rm -rf /var/lib/apt/lists/*

# set recommended PHP.ini settings
# see https://secure.php.net/manual/en/opcache.installation.php
RUN { \
		echo 'opcache.memory_consumption=128'; \
		echo 'opcache.interned_strings_buffer=8'; \
		echo 'opcache.max_accelerated_files=4000'; \
		echo 'opcache.revalidate_freq=60'; \
	} > /usr/local/etc/php/conf.d/opcache-recommended.ini

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/

# https://www.drupal.org/node/3060/release
ENV DRUPAL_VERSION 9.5.11

WORKDIR /opt/drupal
RUN set -eux; \
	export COMPOSER_HOME="$(mktemp -d)"; \
	composer create-project --no-interaction "drupal/recommended-project:$DRUPAL_VERSION" ./; \
	chown -R www-data:www-data web/sites web/modules web/themes; \
	rmdir /var/www/html; \
	ln -sf /opt/drupal/web /var/www/html; \
	# delete composer cache
	rm -rf "$COMPOSER_HOME"

ENV PATH=${PATH}:/opt/drupal/vendor/bin

FROM drupal-9 AS journal-cms

RUN apt-get update && apt-get install -y unzip git \
	&& rm -rf /var/lib/apt/lists/*

RUN pecl install redis igbinary uploadprogress \
	&& docker-php-ext-enable redis igbinary uploadprogress

# Downgrade composer for eLife Journal CMS
COPY --from=composer:1.10 /usr/bin/composer /usr/local/bin/

# Copy custom scripts
COPY ./scripts scripts

# Copy patches
COPY ./src/patches src/patches

# Copy over custom modules and themes
COPY ./src/modules/ web/modules/
COPY ./src/themes/ web/themes/

# Copy sync config
COPY ./sync sync

# Copy docker configs
COPY ./config/docker/settings.php web/sites/default/settings.php
COPY ./config/docker/services.yml web/sites/default/services.yml
RUN chmod 644 web/sites/default/settings.php
RUN chmod 644 web/sites/default/services.yml

# Copy our deps and composer install (which runs install scripts)
COPY ./composer.json composer.json
COPY ./composer.lock composer.lock

# drupal/core-project-message apparently won't work on first install.
# So we install without script, then with scripts
RUN composer install --no-interaction --no-scripts
# Our JCMSDrupalProject\composer\ScriptHandler presupposes a lot about development and deployment
# but it's easier to just inject a settings.php into sites/default in docker/kubernetes with env vars.
RUN mkdir config && touch config/drupal-vm.settings.php && touch config/drupal-vm.services.yml \
  && composer install --no-interaction \
  && rm -r config
