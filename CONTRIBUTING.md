Eventum Code Contributions
==========================

The Eventum project welcomes all contributions under a GPL license.

Submission of a patch implies that the submmitter acknowledges that they are the author of the code (or have permission from the author to release the code) and agree that the code can be released under the GPL. The copyright for the changes will then revert to the Eventum Development Team - this is required so that any copyright infringements can be investigated quickly without contacting a huge list of copyright holders. Credit will always be given for any patches through a [AUTHORS](AUTHORS) file in the distribution.

## Reporting issues

Bugs should be reported to [issue tracker](https://github.com/eventum/eventum/issues).

Before opening [new issue](https://github.com/eventum/eventum/issues/new), first check that the bug is not already fixed by testing with master branch, then check that your problem is not already reported, by looking at [open issues](https://github.com/eventum/eventum/issues?state=open) in github and [open issues](https://bugs.launchpad.net/eventum/+bugs?orderby=-id&field.status:list=NEW&field.status:list=CONFIRMED&field.status:list=TRIAGED&field.status:list=INPROGRESS&assignee_option=any&field.tags_combinator=ANY&field.omit_dupes=on) in [legacy issue tracker](https://bugs.launchpad.net/eventum).

## Pull requests

- Fork it.
- Create your feature branch (`git checkout -b fixing-blah`), please avoid working directly on the `master` branch.
- Check for unnecessary whitespace with `git diff --check` before committing.
- Commit your changes (`git commit -am 'Fixed blah'`).
- Push to the branch (`git push -u origin fixing-blah`).
- Create a new pull request.

Do not update changelog or attempt to change version, the changes may not be merged on codebase you created patch for and it will just create annoying merge conflicts later.

## Mailing Lists

 - [eventum-users@lists.mysql.com][1] - A general mailing list for users of the Eventum issue tracking tool
 - [eventum-devel@lists.mysql.com][2] - A mailing list for developers of the Eventum issue tracking tool

  [1]: http://news.gmane.org/gmane.comp.bug-tracking.eventum.user
  [2]: http://news.gmane.org/gmane.comp.bug-tracking.eventum.devel
