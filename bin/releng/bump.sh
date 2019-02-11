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
+Upgrading to 3.6.x versions requires that you upgrade to latest 3.5.x version first.
+
+[$new]: https://github.com/eventum/eventum/compare/v$old...master
+
--- a/src/AppInfo.php
+++ b/src/AppInfo.php
@@ -16,7 +16,7 @@ namespace Eventum;
 class AppInfo
 {
     const URL = 'https://github.com/eventum/eventum';
-    const VERSION = '$old-dev';
+    const VERSION = '$new-dev';
 
     /** @var string */
     private \$version;
EOF

git commit -am "$new-dev"
