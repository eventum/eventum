- [x] list syntax is required (any unordered or ordered list supported)

<!--
https://github.com/xemlock/htmlpurifier-html5/blob/v0.1.10/tests/HTMLPurifier/HTMLModule/HTML5/ScriptingTest.php
-->

1: <script type="text/javascript">foo();</script>
2: <script defer src="test.js" type="text/javascript" charset="utf-8" async></script>
3: <script defer src="" type="text/javascript">PCDATA</script>
4: <script defer type="text/javascript">PCDATA</script>
5: <script defer src="script.js" type="text/javascript">PCDATA</script>
6: <script defer src="script.js" type="text/javascript"></script>
7: <p><script>document.write("Foo")</script></p>
8: <span><script>document.write("Foo")</script></span>
9: <script type="text/javascript" crossorigin="use-credentials">PCDATA</script>
10: <script type="text/javascript">PCDATA</script>

----

11: <noscript>noscriptFoo</noscript>
12: <noscript></noscript>
13: <div><noscript>div-noscript-Foo</noscript></div>
14: <span><noscript>span-noscript-Foo</noscript></span>
15: <noscript><h1>Foo</h1><div>Bar</div><p>Baz</p><span>Qux</span></noscript>
16: <noscript>nested-Foo<noscript>Bar</noscript>Baz</noscript>
17: <noscript>subsequent-Foo</noscript><noscript>Bar</noscript>Baz
18: <noscript>nested-span-Foo<span><noscript>Bar</noscript></span></noscript>
19: <noscript>noscript-span-Foo<span>Bar</span></noscript>
20: <p><noscript>p-Foo</noscript></p>
21: <p></p><noscript>after-p-Foo</noscript>

- a task
- list here

