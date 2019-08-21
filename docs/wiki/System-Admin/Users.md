## Adding Users

Only Administrators are allowed to add users. The initial Administrator is created when Eventum is installed.

To add a new user:

-   Click the "Administration" link located on the top menu
-   Select "Manage Users" in the left menu under "Areas"
-   Add the required user information
-   Click the "Create User" button

**NOTE:** The Email Address is also the user's login, and must be valid.

## Editing Users

Only Administrators are allowed to edit user information:

-   Click the "Administration" link located on the top menu
-   Select "Manage Users" in the left menu under "Areas"
-   Click on the user's link under "Full Name"
-   Edit the user's information
-   Click the "Update User" button

## Assigning Projects and Roles

When creating or editing a user, use "Assigned Projects and Roles" to set the desired level of access for each project (or "No Access" to exclude the user from a project).

### Types of Roles

#### Viewer

A Viewer can only log in and view issues. No input options are allowed other than updating personal preferences.

#### Reporter

A Reporter is considered an "external" user, who can report issues, but is excluded from viewing any internal discussion/notes on the issue until they are formally replied to.

#### Customer

The Customer role has the most restrictions on it, such as only being able to see issues from the same customer. Some of the fields are also hidden from this user. This role requires the use of the Customer API.

#### Standard User

The Standard User can perform most actions in Eventum, can view internal discussions, but cannot change any configuration options.

#### Developer

Similar to the Standard User role, but this role has access to the clocking in function, can set reminder triggers and set an issue as Private - a Standard User can't.

#### Manager

A Manager has access to many configuration options (including everything listed under "Areas") on the Administration page.

#### Administrator

The Administrator is an Eventum super user. All features are available to Administrators. Administrators will see an extra "Configuration" section on the "Administration" page, including general setup, email configuration, custom field creation, and more.
