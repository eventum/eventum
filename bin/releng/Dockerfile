#
# Dockerfile for Building Eventum Release
# https://github.com/eventum/eventum
#

# Image for building release
FROM ubuntu:bionic AS base

FROM base AS deps
RUN apt update && DEBIAN_FRONTEND=noninteractive apt install -y \
    bash \
    bzr \
    composer \
    coreutils \
    curl \
    gettext \
    git \
    make \
    nodejs \
    php-curl \
    php-dom \
    php-gd \
    php-intl \
    php-ldap \
    php-mbstring \
    php-pdo-mysql \
    php-xml \
    php-zip \
    sudo \
    unzip \
    xz-utils \
    yarn \
	&& exit 0

FROM deps AS releng
WORKDIR /app
COPY Makefile .
COPY bin/releng ./bin/releng

RUN bin/releng/tools.sh
RUN bin/releng/locales.sh
