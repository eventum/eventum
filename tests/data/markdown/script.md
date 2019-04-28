- [x] list syntax is required (any unordered or ordered list supported)

<!--
https://github.com/xemlock/htmlpurifier-html5/blob/v0.1.10/tests/HTMLPurifier/HTMLModule/HTML5/ScriptingTest.php
-->
<script type="text/javascript">foo();</script>
<script defer src="test.js" type="text/javascript" charset="utf-8" async></script>
<script defer src="" type="text/javascript">PCDATA</script>
<script defer type="text/javascript">PCDATA</script>
<script defer src="script.js" type="text/javascript">PCDATA</script>
<script defer src="script.js" type="text/javascript"></script>
<p><script>document.write("Foo")</script></p>
<span><script>document.write("Foo")</script></span>
<script type="text/javascript" crossorigin="use-credentials">PCDATA</script>
<script type="text/javascript">PCDATA</script>
<noscript>Foo</noscript>
<noscript></noscript>
<div><noscript>Foo</noscript></div>
<span><noscript>Foo</noscript></span>
<noscript><h1>Foo</h1><div>Bar</div><p>Baz</p><span>Qux</span></noscript>
<noscript>Foo<noscript>Bar</noscript>Baz</noscript>
<noscript>Foo</noscript><noscript>Bar</noscript>Baz
<noscript>Foo<span><noscript>Bar</noscript></span></noscript>
<noscript>Foo<span>Bar</span></noscript>
<p><noscript>Foo</noscript></p>
<p></p><noscript>Foo</noscript>
<p><noscript>Foo</noscript></p>


