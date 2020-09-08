# Usage Examples

An integration script will need to be installed in your CVS root repository in
order to send a message to Eventum whenever changes are committed to the
repository. This message will then be processed by Eventum and the changes to
the appropriate files will be associated with existing issue mentioned in your
commit message.

So to examplify its use, whenever the users are ready to commit the changes to
the CVS repository, they will add a special string to specify which issue this
is related to. The following would be a good example of its use:

```
[prompt]$ cvs -q commit -m "Adding form validation as requested (issue: 13)" form.php
```

You may also use `bug` to specify the issue ID - whichever you are more
comfortable with.

This command will be parsed by the CVS integration script (provided to you and
available in `%eventum_path%/scm/eventum-cvs-hook`) and it will notify Eventum
that these changes are to be associated with issue #13.
