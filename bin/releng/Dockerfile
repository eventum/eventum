# syntax = docker/dockerfile:1.3-labs
#
# Dockerfile for Building Eventum Release
# https://github.com/eventum/eventum
#

ARG BUILD_IMAGE=ghcr.io/eventum/eventum:release-image-v2

# Image for building release
FROM ubuntu:focal AS base

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
    jq \
    make \
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
    && apt update && apt install -y --no-install-recommends nodejs yarn \
	&& exit 0

FROM deps AS releng
WORKDIR /app
RUN \
    --mount=type=bind,source=./bin/releng/locales.sh,target=./bin/releng/locales.sh \
    bin/releng/locales.sh

RUN \
    --mount=type=bind,source=./Makefile,target=./Makefile \
    --mount=type=bind,source=./bin/releng/tools.sh,target=./bin/releng/tools.sh \
    bin/releng/tools.sh /usr/bin

# docker build . -f bin/releng/Dockerfile -t app --target=stage-release
FROM releng AS stage-release
COPY . .

# docker build . -f bin/releng/Dockerfile -t app --target=build-release
FROM stage-release AS build-release
RUN bin/releng/build-release.sh

# docker build . -f bin/releng/Dockerfile -t app --target=build-src
FROM $BUILD_IMAGE AS build-src
WORKDIR /src
RUN \
    --mount=type=bind,target=/src \
    git archive HEAD | tar -x -C /app && \
    bin/releng/update_timestamps.sh / /app

FROM releng AS release
