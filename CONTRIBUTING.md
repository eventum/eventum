# Eventum Code Contributions

The Eventum project welcomes all contributions under a GPL license.

Submission of a patch implies that the submmitter acknowledges that they are
the author of the code (or have permission from the author to release the code)
and agree that the code can be released under the GPL. The copyright for the
changes will then revert to the Eventum Development Team - this is required so
that any copyright infringements can be investigated quickly without contacting
a huge list of copyright holders. Credit will always be given for any patches
through a [AUTHORS](AUTHORS) file in the distribution.

## Reporting issues

Bugs should be reported to [issue tracker].

Before opening [new issue], first check that the bug is not already fixed by
testing with `master` branch, then check that your problem is not already
reported, by looking at [open issues].

[issue tracker]: https://github.com/eventum/eventum/issues
[new issue]: https://github.com/eventum/eventum/issues/new

## Pull requests

- [Fork it](https://github.com/eventum/eventum/fork).
- Create your feature branch (`git checkout -b fixing-blah`), please avoid working directly on the `master` branch.
- Check for unnecessary whitespace with `git diff --check` before committing.
- Commit your changes (`git commit -am 'Fixed blah'`).
- Push to the branch (`git push -u origin fixing-blah`).
- Create a new pull request.

Commits follow good practices for message and content
  - Write [good commit messages]
  - Strive for [atomic commits] whenever you can

[good commit messages]: http://chris.beams.io/posts/git-commit/
[atomic commits]: http://www.freshconsulting.com/atomic-commits/

## Development

For asset compilation [laravel-mix] is used.

You will need [node] and [yarn] installed.

```
// Run all Mix tasks and watch for file changes
yarn run watch
```

The [webpack.mix.js] file is the entry point for all asset compilation.

[webpack.mix.js]: webpack.mix.js
[laravel-mix]: https://laravel.com/docs/5.8/mix
[node]: https://nodejs.org/
[yarn]: https://yarnpkg.com/
