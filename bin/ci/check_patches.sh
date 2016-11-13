#!/bin/bash
# Check that the patch id does not overlap

set -e

patch_dir=upgrade/patches

find_duplicate_patch_id() {
	git ls-files $patch_dir | sed -e "s#$patch_dir/##" | cut -d_ -f1 | uniq -c | grep -v ' 1 '
}

rc=0
while read count patch; do
	test -n "$count" || continue
	echo >&2 "ERROR: Found $count patches with same id"
	git ls-files $patch_dir/$patch*
	rc=1
done <<< $(find_duplicate_patch_id)

exit $rc
