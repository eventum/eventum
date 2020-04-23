#!/bin/bash
set -xe

# install all locales used by eventum
sudo apt-get update
sudo apt-get --reinstall install -qq \
	language-pack-{br,ca,cs,da,de,eo,es,et,fi,fr,he,hu,id,it,ja,ko,lt,lv,nl,oc,pl,pt,ru,si,sv,ta,th,tr,uk,vi,zh-hans}

# display some info from system
dpkg --list | grep language-pack
locale -a
