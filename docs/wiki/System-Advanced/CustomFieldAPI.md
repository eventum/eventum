### CustomFieldAPI

## Overview

The custom field API allows special functionality to be added to custom fields. Custom Field backends should be placed in include/custom_field/ and named "class._name_.php". Inside the file the class should be named "_name_\_Custom_Field_Backend". Once the backend file is in place you need to specify the backend on the manage custom fields page.

This documentation page is a work in progress.

## Examples

### Default Value

```php
    /**
     * Custom field backend showing example default value
     *
     * @author Bryan Alsdorf <bryan@mysql.com>
     */
    class Default_Value_Custom_Field_Backend
    {
        function getDefaultValue($fld_id)
        {
            // your logic here
            return 'eventum is the best';
        }
    }
```
