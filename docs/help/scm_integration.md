# SCM Integration

This feature allows your software development teams to integrate your Source
Control Management system with your Issue Tracking System.

The integration is implemented in such a way that it will be forward compatible
with pretty much any SCM system, such as CVS. When entering the required
information for the checkout page and diff page input fields, use the following
placeholders:

| Keyword         |                                 |
| --------------- | ------------------------------- |
| `{MODULE}`      | The CVS module name             |
| `{FILE}`        | The filename that was committed |
| `{OLD_VERSION}` | The old revision of the file    |
| `{NEW_VERSION}` | The new revision of the file    |

As an example, using the [Chora CVS viewer](https://www.horde.org/apps/chora/)
(_highly recommended_) from the Horde project you would usually have the
following URL as the diff page:

- **`http://example.com/chora/diff.php/module/filename.ext?r1=1.3&r2=1.4&ty=h`**

With that information in mind, the appropriate value to be entered in the
`Checkout page` input field is:

- **`http://example.com/chora/diff.php/{MODULE}/{FILE}?r1={OLD_VERSION}&r2={NEW_VERSION}&ty=h`**

# Available Related Topics

- [Usage Examples](scm_integration_usage.md)
- [Installation Instructions](scm_integration_installation.md)
