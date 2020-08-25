# User Permission Levels

The following is a brief overview of the available user permission levels in
Eventum:

- **Viewer** - Allowed to view all issues on the projects associated to this
  user; cannot create new issues or edit existing issues.
- **Reporter** - Allowed to view all issues on the projects associated to this
  user; Allowed to create new issues and to send emails on existing issues.
- **Customer** - This is a special permission level reserved for the Customer
  Integration API, which allows you to integrate Eventum with your CRM database.
  When this feature is enabled, this type of user can only access issues
  associated with their own customer. Allowed to create new issues, update and
  send emails to existing issues.
- **Standard User** - Allowed to view all issues on the projects associated to
  this user; Allowed to create new issues, update existing issues, and to send
  emails and notes to existing issues.
- **Developer** - Similar in every way to the above permission level, but this
  extra level allows you to segregate users who will deal with issues, and
  overall normal staff users who do not handle issues themselves.
- **Manager** - Allowed to view all issues on the projects associated to this
  user; Allowed to create new issues, update existing issues, and to send emails
  and notes to existing issues. Also, this type of user is also allowed on the
  special administration section of Eventum to tweak most project-level features
  and options.
- **Administrator** - This type of user has full access to Eventum, including
  the low level configuration parameters available through the administration
  interface.
