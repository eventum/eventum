#!/bin/sh
set -e

XREFDIR=/usr/share/phpxref
CONFIG=./config/phpxref.cfg

. "$CONFIG"
[ -d "$OUTPUT" ] || mkdir -p "$OUTPUT"

phpxref -c $CONFIG

[ -f "$OUTPUT/$STYLEFILE" ] || cp -a "$XREFDIR/$STYLEFILE" "$OUTPUT"
[ -f "$OUTPUT/$PRINTSTYLEFILE" ] || cp -a "$XREFDIR/$PRINTSTYLEFILE" "$OUTPUT"
[ -f "$OUTPUT/_icons/folder.gif" ] || cp -a "$XREFDIR/folder.gif" "$OUTPUT/_icons"
[ -f "$OUTPUT/_icons/text.gif" ] || cp -a "$XREFDIR/text.gif" "$OUTPUT/_icons"
[ -f "$OUTPUT/phpxref.js" ] || cp -a "$XREFDIR/phpxref.js" "$OUTPUT"
[ -f "$OUTPUT/_jstree/tree.js" ] || cp -a "$XREFDIR/jstree/tree.js" "$OUTPUT/_jstree"
[ -f "$OUTPUT/_jstree/tree_tpl.js" ] || cp -a "$XREFDIR/jstree/tree_tpl.js" "$OUTPUT/_jstree"
for i in base.gif empty.gif folder.gif folderopen.gif join.gif joinbottom.gif line.gif minus.gif minusbottom.gif page.gif plus.gif plusbottom.gif; do
	[ -f "$OUTPUT/_jstree/icons/$i" ] || cp -a "$XREFDIR/jstree/icons/$i" "$OUTPUT/_jstree/icons"
done
