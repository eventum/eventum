#
# Dockerfile for Building Eventum Release
# https://github.com/eventum/eventum
#

# Image for building release
FROM ubuntu:bionic AS base

FROM base AS deps
RUN set -x \
    && export DEBIAN_FRONTEND=noninteractive \
    && apt update && apt install -y --no-install-recommends \
    bash \
    bzr \
    composer \
    coreutils \
    curl \
    gettext \
    git \
    gnupg \
    make \
    nodejs \
    npm \
    php-curl \
    php-dom \
    php-gd \
    php-intl \
    php-ldap \
    php-mbstring \
    php-pdo-mysql \
    php-pdo-sqlite \
    php-xml \
    php-zip \
    sudo \
    unzip \
    xz-utils \
    && curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | sudo apt-key add - \
    && echo "deb https://dl.yarnpkg.com/debian/ stable main" | sudo tee /etc/apt/sources.list.d/yarn.list \
    && apt update && apt install -y --no-install-recommends yarn \
	&& exit 0

FROM deps AS releng
WORKDIR /app
COPY Makefile .
COPY bin/releng ./bin/releng

RUN bin/releng/tools.sh
RUN bin/releng/locales.sh

# docker build . -f bin/releng/Dockerfile -t app --target=stage-release
FROM releng AS stage-release
COPY . .

# docker build . -f bin/releng/Dockerfile -t app --target=build-release
FROM stage-release AS build-release
RUN bin/releng/build-release.sh

FROM releng AS release
