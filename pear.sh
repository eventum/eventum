#!/bin/sh
# vim: set noexpandtab tabstop=4 shiftwidth=4 encoding=utf-8:
# +----------------------------------------------------------------------+
# | Eventum - Issue Tracking System                                      |
# +----------------------------------------------------------------------+
# | Copyright 2011, Elan Ruusamäe <glen@delfi.ee>                        |
# +----------------------------------------------------------------------+
# |                                                                      |
# | This program is free software; you can redistribute it and/or modify |
# | it under the terms of the GNU General Public License as published by |
# | the Free Software Foundation; either version 2 of the License, or    |
# | (at your option) any later version.                                  |
# |                                                                      |
# | This program is distributed in the hope that it will be useful,      |
# | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
# | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        |
# | GNU General Public License for more details.                         |
# |                                                                      |
# | You should have received a copy of the GNU General Public License    |
# | along with this program; if not, write to:                           |
# |                                                                      |
# | Free Software Foundation, Inc.                                       |
# | 59 Temple Place - Suite 330                                          |
# | Boston, MA 02111-1307, USA.                                          |
# +----------------------------------------------------------------------+

set -e
# download and update PEAR packages
pear_pkgs="
DB-stable
Auth_SASL-stable
Date-stable
File_Util
Mail-stable
Mail_Mime-stable
Mail_mimeDecode-stable
Math_Stats
Net_POP3-stable
Net_SMTP-stable
Net_SmartIRC-stable
Net_Socket-stable
Net_URL-stable
Net_UserAgent_Detect-stable
PEAR-stable
Text_Diff-stable
XML_RPC-stable
"

t=pear-root
if  [ ! -f pear.download ]; then
	for p in $pear_pkgs; do
		pear download $p
	done
	touch pear.download
fi

install -d $t

if [ ! -f pear.install ]; then
	for p in $pear_pkgs; do
		p=${p%-*}
		pear install -O -n -l -f -P $t $p-*.tgz
	done
	touch pear.install
fi

if [ ! -f pear.clean ]; then
	rm -rf $t/usr/bin $t/usr/share/doc $t/usr/share/pear/tests $t/usr/share/pear/.??*

	# individual package cleanup
	cd $t/usr/share/pear

	# Mail_Mime
	rm -rf data/Mail_Mime

	# Mail
	rm Mail/mock.php
	rm Mail/smtpmx.php

	# Math_Stats
	rm -r Math/examples

	# XML_RPC
	rm XML/RPC/Dump.php

	# PEAR
	rm -rf data/PEAR
	rm -f pearcmd.php peclcmd.php
	rm -rf OS PEAR
	rm -f System.php

	# DB
	for a in DB/*.php; do
		case "${a##*/}" in
		common.php | mysql*.php)
			;;
		*)
			rm -f $a
		esac
	done

	test -d data && rmdir data
	cd -
fi
