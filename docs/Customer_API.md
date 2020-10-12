Customer API
============

Author: *Bryan Alsdorf*  
Created: *2004-09-07*

The Customer API allows Eventum to interact with with your customer database.
This document will cover what is needed to implement the class needed to
interact with your customer database system.

Getting Started
---------------

To write your own Customer class create a file named `class.my_name.php` in
`config/customer/`. In that file, create a class named
`my_name_Customer_Backend` that extends `Abstract_Customer_Backend`. Now you
can add methods to that class to handle specific events. You can copy the
method signatures from `lib/eventum/customer/class.abstract_customer_backend.php` to get started.  An
example customer implementation is in
`docs/examples/customer/class.example_customer.php`. If you want to use this
example implementation, follow the steps below to enable customer integration
and run `doc/examples/customer/create_customers.php`.

Once you have your class created you must set your project to use it.

 1. Login to eventum with your administrative account.
 2. Go to `Administration` and click the the name of the project you want to use the customer class.
 3. Select `my_name` from the dropdown list next to `Customer Integration Backend`
 4. Click `Update Project`

Your project should now be using your customer class.

Individual methods
------------------

Please see `lib/eventum/customer/class.abstract_customer_backend.php` for the
individual methods you can override.

Database Structure
------------------

While most customer information is stored outside Eventum, certain key
information is kept in the Eventum database. Tables are listed without a
prefix.

 - `user`
	 - `usr_customer_id` - The ID of the customer (company) in your customer database
	 - `usr_customer_contract_id` - The ID of the contact (person) in your customer database. Multiple contacts can belong to the same company.
 - `issue`
	 - `iss_customer_id` - The ID of the customer that this issue is for
	 - `iss_customer_contract_id` - The ID of the contact who this issue is for.
 - `customer_account_manager` - This table is used to store who the account manager for a customer is.
 - `customer_note` - This table stores customer specific notes.

Per-Incident Support
--------------------

Per-Incident support allows for control of how many issues a customer opens.
When a customer opens an issue, it is not counted as an incident because it
could be a duplicate, a mistake or not meet whatever requirement you set for an
issue to count. Once you decide to count an issue, you can "redeem" the
incident by clicking the `Mark as Redeemed Incident` button. When this happens,
the method `Customer::flagIncident()` is called.

Per-Incident support is not included in the example API.

File Structure
--------------

Any templates that are customer related should be located in
`config/templates/en/customer/my_name/`. Any customer files that are not
templates should be located in `config/templates/customer/my_name/.` The
following is a list of files you need to implement to create a customer
backend.

 - Templates
	 - `customer_report.tpl.html` - Displays customer information to customer on main page when they login.
	 - `report_form_fields.tpl.html` - Display on the create issue form to customers.
	 - `customer_info.tpl.html` - Displays customer information on the issue page.

Future Direction
----------------

As Eventum is developed more methods will be added to the customer class and
some methods will be changed. We will try to minimize any changes. If you have
any feedback on this API please email the [Eventum development
list](mailto:eventum-devel@lists.mysql.com).
