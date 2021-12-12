#!/bin/sh
set -eu

# update timestamps from last commit
# see http://stackoverflow.com/a/5531813
update_timestamps() {
	echo >&2 "Updating timestamps from last commit of each file in ${dir#$topdir/}, please wait..."

	git ls-files | while read file; do
		# skip files which were not exported
		test -f "$dir/$file" || continue
		rev=$(git rev-list -n 1 HEAD "$file")
		file_time=$(git show --pretty=format:%ai --abbrev-commit $rev | head -n 1)
		touch -d "$file_time" "$dir/$file"
	done
}

topdir="$1"
dir="$2"
update_timestamps "$@"
