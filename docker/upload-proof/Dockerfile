# Upload proof — clean PHP app + turso-api-client-php via path repo.
# Build context must be the repo root.
#
#   docker/upload-proof/proof.sh

ARG PHP_VERSION=8.4
FROM php:${PHP_VERSION}-cli-bookworm

ENV DEBIAN_FRONTEND=noninteractive \
    COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_NO_INTERACTION=1

RUN apt-get update && apt-get install -q -y --no-install-recommends \
        git \
        curl \
        unzip \
        ca-certificates \
        libsqlite3-dev \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /proof

COPY docker/upload-proof/php-project ./

COPY . /driver-src

ARG DRIVER_VERSION=*
RUN composer config minimum-stability dev && \
    composer config prefer-stable true && \
    composer config repositories.turso-path '{"type":"path","url":"/driver-src","options":{"symlink":false}}' && \
    composer require "darkterminal/turso-api-client-php:${DRIVER_VERSION}" --no-progress --with-all-dependencies

CMD ["php", "proof.php"]
