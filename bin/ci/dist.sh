#!/bin/bash
set -xe

bin/ci/tools.sh
bin/ci/locales.sh

# need to fetch tags first for release process
git fetch --tags --unshallow

# prepare release tarball
make dist
