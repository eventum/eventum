### Customer API

This is a work in progress...

The most useful information for now is located [here](../System-Admin/Extending-and-Integrating-Eventum.md).

<hr>
Out-of-the-box, Eventum will meet many of your business needs.

## Overview

I found the example customer API is a bit intimidating. Hopefully this will help clear up how customer integration can be set up to meet your need.

-   The data source class..
-   The parser class..
-   The Templates

## Database Connection

Eventum uses the Pear Database Abstraction Layer [Pear::DB] for its database connections. I will use Pear::DB in my examples to keep the code consistent with the rest of the code base.

### Pear::DB

Pear::DB Package [Documentation](http://pear.php.net/package/DB/docs)

```php
 class CRM_Customer_Backend extends Abstract_Customer_Backend
 {

  // The database handler can only be accessed from inside this class.
  private $dbh;

  function connect()
  {

    // Database connection information
    $dsn = array(
      'phptype'  => "mysql",
      'hostspec' => "localhost",
      'database' => "mydb",
      'username' => "myuser",
      'password' => "mypass"
    );

    // Connect to the database
    $this->dbh = DB::connect($dsn);

    // Confirm connection to the database -or- Error
    if(PEAR::isError($this->dbh)) {
     die("Database Connection Error in Customer API.");
    }

    // Set mySQL character set to UTF-8
    $this->dbh->query("SET NAMES utf8");
  }
 }
```

## Running a Query

```php
 function getAssocList()
 {

  // Define the array
  $assoc = array();

  // Run the database query
  $res = $this->dbh->query("SELECT id, name FROM accounts");

  // Insert the query results into the array
  while($res->fetchInto($account, DB_FETCHMODE_ASSOC)) {
   $assoc[$account['id']] = $account['name'];
  }

  // Release the database query
  $res->free();

  // Return the array
  return $assoc;
 }
```

# Customer Data

Eventum provides a mechanism to use customer records from a different source. This is accomplished by _creating_ a customized 'Customer Integration Backend' to use.

Oddly, the example provided uses a statically defined array. This leaves it up to you to design a database and connection method.

## The Backend

### Getting Started

<span style="background:aqua; color:black"> Paragraph based on workflow page, needs cleanup </span>

First, review the example customer implementation:

`include/customer/class.example.php`

This example extends the Abstract_Customer_Backend class defined in:

`include/customer/class.abstract_customer_backend.php`

All methods of the Abstract_Customer_Backend class available for customization are defined in this file. For example,

_if you want to customize Eventum's behaviour when closing an issue, you will be adding your own code to the handleIssueClosed() function when you extend the class._ You will do this in a new file, so do not edit class.abstract_customer_backend.php.

_Note that while a copy of class.example.php can be used as a basis for your own customizations, it does contain functions that affect the behaviour of Eventum, so you should remove any functions you are not using, to prevent unexpected results._

### class.example.php

    path_to_eventum/include/customer/class.example.php

### class.abstract_customer_backend.php

    path_to_eventum/include/customer/class.abstract_customer_backend.php

Use ../include/customer/class.example.php as a template for creating your own.

# Paths

Files that are used reside in the following directories:

-   path_to_eventum/customer
-   path_to_eventum/include/customer
-   path_to_eventum/templates/customer

# Files

path_to_eventum/templates/customer/example/report_form_fields.tpl.html

-   Called when creating a 'New Issue'
-   Provides fields to associate a customer with an issue.

/templates/customer/example/customer_info.tpl.html

-   Called when viewing an issue
-   Displays associated customer information related to the issue.

# Notes

With **any** type of software, customization will be needed to meet the **specific** needs of your business.

Out-of-the-box, Eventum will meet many of your business needs. But with any type business software, customization to will be needed to address the specifics of your business processes. The 'Customer API' if one of the tools available to modify the system to your needs.

Thanks: [Martin Svangren](http://lists.mysql.org/eventum-devel/766)
