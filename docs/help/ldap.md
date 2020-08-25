# LDAP Authentication

Eventum can be used with an LDAP directory service for centralized
authentication.

The following parameters need to be configured to enable LDAP integration:

<dl>
  <dt><b>Host</b></dt>
  <dd>The host name or IP address of the LDAP server</dd>
  <dt><b>Port</b></dt>
  <dd>The TCP port of the LDAP directory service. The default port is 389 for LDAP with or without StartTLS, or 636 for LDAPS.</dd>
  <dt><b>Encryption</b></dt>
  <dd>The security protocol to use. Options are: None (no encryption), LDAPS (LDAP over SSL) and StartTLS (LDAP with TLS).</dd>
  <dt><b>Bind DN</b></dt>
  <dd>The DN of the LDAP principal used for directory lookups</dd>
  <dt><b>Bind PW</b></dt>
  <dd>The password for the principal specified as Bind DN</dd>
  <dt><b>Base DN</b></dt>
  <dd>The path of the LDAP container to be used when looking up user accounts</dd>
  <dt><b>User ID attribute</b></dt>
  <dd>The name of an LDAP attribute containing the DN (or its user-specific part) of an LDAP account. This is used as bind DN when checking the entered password. If unsure leave blank to use the default value "uid".</dd>
  <dt><b>User DN</b></dt>
  <dd>The DN used for verifying the entered user password. The placeholder <code>%UID</code> will be replaced with the value of the User ID attribute. Multiple DNs to be tried may be separated with | characters.</dd>
  <dt><b>User Filter (optional)</b></dt>
  <dd>LDAP filter to be used when searching for an account matching the Eventum login name. The placeholder <code>{username}</code> will be replaced with the entered login name. If unsure leave blank.</dd>
  <dt><b>Customer ID attribute (optional)</b></dt>
  <dd>The name of an LDAP attribute containing the customer ID for a user</dd>
  <dt><b>Contact ID attribute (optional)</b></dt>
  <dd>The name of an LDAP attribute containing the contatct ID for a user</dd>
  <dt><b>DN for active users (optional)</b></dt>
  <dd>The path of an LDAP container containing active accounts. This is used by the <code>ldapsync</code> utility to update or create Eventum users. Leave blank to skip that step.</dd>
  <dt><b>DN for inactive users (optional)</b></dt>
  <dd>The path of an LDAP container containing inactive accounts. This is used by the <code>ldapsync</code> utility to disable accounts. Leave blank to skip that step. </dd>
  <dt><b>Create Users</b></dt>
  <dd>Automatically create Eventum users for successfully authenticated LDAP accounts</dd>
  <dt><b>Default Roles</b></dt>
  <dd>The default set of roles for automatically created Eventum users</dd>
</dl>

# Integration with Microsoft Active Directory

Active Directory uses a few non-standard attribute names, so it requires some
special configuration:

<table>
  <tr> <td><b>Port</b></td> <td>389 (local domain) or 3268 (global catalog)</td> </tr>
  <tr> <td><b>User ID attribute</b></td> <td><code>userPrincipalName</code></td> </tr>
  <tr> <td><b>User DN</b></td> <td><code>%UID</code></td> </tr>
  <tr> <td><b>User Filter</b></td> <td><code>(|(mail={username})(userPrincipalName={username}))</code></td> </tr>
</table>
