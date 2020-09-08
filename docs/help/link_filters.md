# Link Filters

Link filters are used to replace text such as `Bug #42` with an automatic link
to some external resource. It uses regular expressions to replace the text.
Specify the search pattern in the pattern field without delimiters. Specify the
entire string you would like to use as a replacement with `$N` to insert the
matched text.

For example:

- Pattern: `bug #(\d+)`
- Replacement: `<a href="https://example.com/bug.php?id=$1">Bug #$1</a>`
