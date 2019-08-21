Link filters are used to replace text with an automatic link to some external resource. It uses regular expressions to replace the text.

Specify the search pattern in the pattern field without delimiters. Specify the entire string you would like to use as a replacement to the matched text.

## Useful link filters

### linking to eventum attachment

pattern:

`attachment(?:_id)? #?(\d+)`

replacement:

<a class="link" href="/download.php?cat=attachment&id=$1">`$0`</a>

description:

`eventum attachment`

note id you can grab by copying link url.

### linking to eventum note

pattern:

`note[_ ]id[= ]#?(\d+)`

replacement:

<a title="view note details" href="javascript:void(null);" onClick="javascript:viewNote($1, 0);" class="link">`$0`</a>

description:

`eventum note`

note id is little complicated, you have to copy the id from link of the popup window.
