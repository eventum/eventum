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

# docker build . -f bin/releng/Dockerfile -t app --target=build-po
FROM $BUILD_IMAGE AS build-po
RUN bzr branch lp:~glen666/eventum/po /po
WORKDIR /app/localization
RUN \
    --mount=type=bind,source=./localization,target=/src/localization,rw \
<<eot bash
    cp -af /po/localization/*.po .
    make -r -f /src/localization/Makefile touch-po
eot
RUN \
    --mount=type=bind,source=./localization/Makefile,target=./Makefile \
    make install clean

# docker build . -f bin/releng/Dockerfile -t app --target=build-vendor
FROM $BUILD_IMAGE AS build-vendor
WORKDIR /app

# Install hirak/prestissimo for parallel downloads
ENV COMPOSER_CACHE_DIR=/root/.cache/composer
RUN \
    --mount=type=cache,id=composer,target=/root/.cache/composer \
    --mount=type=cache,id=composer-bin,target=/root/.composer \
   composer global require hirak/prestissimo --ansi

# Install runtime dependencies
RUN \
    --mount=type=cache,id=composer,target=/root/.cache/composer \
    --mount=type=cache,id=composer-bin,target=/root/.composer \
    --mount=type=bind,source=./composer.json,target=./composer.json \
    --mount=type=bind,source=./composer.lock,target=./composer.lock \
    --mount=type=cache,target=./lib/eventum,ro \
    composer install --prefer-dist --no-dev --no-suggest --no-scripts --no-autoloader --ansi

# Remove packages defined in "extra.replace"
RUN \
    --mount=type=cache,id=composer,target=/root/.cache/composer \
    --mount=type=cache,id=composer-bin,target=/root/.composer \
    --mount=type=bind,source=./composer.json,target=/src/composer.json \
    --mount=type=bind,source=./composer.lock,target=/src/composer.lock \
    --mount=type=bind,source=./bin/releng/composer-replace.sh,target=./bin/releng/composer-replace.sh \
    cp /src/* . && \
    bin/releng/composer-replace.sh

# Cleanup vendor of unwanted files
RUN \
    --mount=type=bind,source=./build.xml,target=/src/build.xml \
    phing -f /src/build.xml clean-vendor

# Clean empty dirs
RUN find vendor -type d -print0 | sort -zr | xargs -0 rmdir --ignore-fail-on-non-empty

# Dump autoloader, including package versions
ARG APP_VERSION=0.0.0
RUN \
    --mount=type=cache,id=composer,target=/root/.cache/composer \
    --mount=type=bind,source=./composer.json,target=./composer.json \
    --mount=type=bind,source=./composer.lock,target=./composer.lock \
    --mount=type=bind,from=build-src,source=/app/lib/eventum,target=./lib/eventum,ro \
<<eot
    if [ "${APP_VERSION%-*-*}" != "$APP_VERSION" ]; then
        APP_VERSION=$(IFS=-; set -- $APP_VERSION; echo dev-${1#v}-$2@$3)
    fi
    COMPOSER_ROOT_VERSION=$APP_VERSION \
    composer dump-autoload --no-dev --ansi
eot

# docker build . -f bin/releng/Dockerfile -t app --target=build-assets
FROM $BUILD_IMAGE AS build-assets
WORKDIR /app
RUN \
    --mount=type=cache,id=yarn,target=/usr/local/share/.cache/yarn \
    --mount=type=bind,source=./package.json,target=./package.json \
    --mount=type=bind,source=./yarn.lock,target=./yarn.lock \
    yarn
RUN \
    --mount=type=bind,source=./package.json,target=./package.json \
    --mount=type=bind,source=./webpack.mix.js,target=./webpack.mix.js \
    --mount=type=bind,source=./res/assets,target=./res/assets \
    yarn assets:production

# docker build . -f bin/releng/Dockerfile -t app --target=build-phpcompatinfo
FROM $BUILD_IMAGE AS build-phpcompatinfo
RUN \
    --mount=type=bind,source=./phpcompatinfo.json,target=./phpcompatinfo.json \
    --mount=type=bind,source=./bin,target=./bin \
    --mount=type=bind,source=./db,target=./db \
    --mount=type=bind,source=./htdocs,target=./htdocs \
    --mount=type=bind,source=./lib,target=./lib \
    --mount=type=bind,source=./res,target=./res \
    --mount=type=bind,source=./src,target=./src \
    --mount=type=bind,source=./autoload.php,target=./autoload.php \
    --mount=type=bind,source=./init.php,target=./init.php \
    --mount=type=bind,source=./phinx.php,target=./phinx.php \
	phpcompatinfo analyser:run --alias current --output PhpCompatInfo.txt

# Avoid empty result
RUN if grep -qF 'None data source matching' PhpCompatInfo.txt; then exit 2; fi

# docker build . -f bin/releng/Dockerfile -t app --target=build-tar
FROM $BUILD_IMAGE AS build-tar
WORKDIR /out
ARG APP_VERSION=unknown
WORKDIR /eventum-$APP_VERSION
RUN \
    --mount=type=bind,from=build-src,source=/app,target=.,rw \
    --mount=type=bind,from=build-po,source=/app/localization,target=./localization,rw \
    --mount=type=bind,from=build-src,source=/app/localization/LINGUAS.php,target=./localization/LINGUAS.php,rw \
    --mount=type=bind,from=build-src,source=/app/localization/Makefile,target=./localization/Makefile,rw \
    --mount=type=bind,from=build-src,source=/app/localization/eventum.pot,target=./localization/eventum.pot,rw \
    --mount=type=bind,from=build-assets,source=/app/htdocs/css,target=./htdocs/css,rw \
    --mount=type=bind,from=build-assets,source=/app/htdocs/js,target=./htdocs/js,rw \
    --mount=type=bind,from=build-assets,source=/app/htdocs/fonts,target=./htdocs/fonts,rw \
    --mount=type=bind,from=build-assets,source=/app/htdocs/images,target=./htdocs/images,rw \
    --mount=type=bind,from=build-assets,source=/app/htdocs/mix-manifest.json,target=./htdocs/mix-manifest.json,rw \
    --mount=type=bind,from=build-src,source=/app/htdocs/images/eventum.gif,target=./htdocs/images/eventum.gif,rw \
    --mount=type=bind,from=build-src,source=/app/htdocs/images/no_data.gif,target=./htdocs/images/no_data.gif,rw \
    --mount=type=bind,from=build-phpcompatinfo,source=/app/PhpCompatInfo.txt,target=./docs/PhpCompatInfo.txt,rw \
    --mount=type=bind,from=build-vendor,source=/app/vendor,target=./vendor,rw \
<<eot bash -xe
    # install dirs and fix permissions
    install -d var/{log,cache,lock}
    install -d config/{workflow,custom_field,templates,crm,partner,include}
    touch var/log/{eventum.log,auth.log,cli.log,errors.log,login_attempts.log}
    touch config/{private_key.php,secret_key.php,setup.php}
    chmod -R a+rX,g-w .
    chmod -R a+rwX config var

    tar -cf /out/eventum-$APP_VERSION.tar \
        --exclude=*.phar \
        --exclude=.dockerignore \
        --exclude=bin/releng \
        --exclude=composer.json \
        --exclude=composer.lock \
        --exclude=contrib/git \
        --exclude=contrib/shell-semver \
        --exclude=htdocs/debugbar \
        --exclude=package.json \
        --exclude=res/packages/test \
        --exclude=src/Mail/MailStorage.php \
        --exclude=symfony.lock \
        --exclude=webpack.mix.js \
        --exclude=yarn.lock \
        -C / eventum-$APP_VERSION/
eot

# APP_VERSION=$(git describe --tags --match=v*)
# docker build . -f bin/releng/Dockerfile --build-arg=APP_VERSION=${APP_VERSION#v} --target=out -o out
FROM scratch AS out
COPY --from=build-tar /out/* /

FROM releng AS release
