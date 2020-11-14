#!/bin/sh
set -xeu

topdir=$(git rev-parse --show-toplevel)
tag=$(git describe --tags --abbrev=0)
old=${tag#v}
new=${1:-$($topdir/contrib/shell-semver/increment_version.sh -p $old)}

cat <<EOF | patch -p1
--- a/CHANGELOG.md
+++ b/CHANGELOG.md
@@ -1,2 +1,8 @@
 # Eventum Issue Tracking System
 
+## [$new]
+
+See [Upgrading] for details on how to upgrade.
+
+[$new]: https://github.com/eventum/eventum/compare/v$old...master
+
EOF

git commit -am "$new-dev"
