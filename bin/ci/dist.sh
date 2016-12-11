#!/bin/bash
set -xe

# need to fetch tags first for release process
git fetch --tags --unshallow

# prepare release tarball
make dist
