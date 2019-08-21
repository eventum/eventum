### WorkflowDocumentation

## Workflow API

The workflow API is used to provide custom functionality to Eventum. Many common events (creating/assigning/updating/closing issues, receiving/associating/blocking email, new notes, etc.) are mapped to corresponding methods in the Abstract_Workflow_Backend class, which can be extended to provide new functionality for those events. Each project can have its own customization of the class, or can share a single customization.

### Getting Started

First, review the example workflow implementation:

`docs/examples/workflow/class.example.php`

This example extends the Abstract_Workflow_Backend class defined in:

`lib/eventum/workflow/class.abstract_workflow_backend.php`

All methods of the Abstract_Workflow_Backend class that are available for customization are defined in this file. For example, if you want to customize Eventum's behaviour when closing an issue, you will be adding your own code to the handleIssueClosed() function when you extend the class. You will do this in a new file, so do not edit class.abstract_workflow_backend.php.

Note that while a copy of class.example.php can be used as a basis for your own customizations, it does contain functions that affect the behaviour of Eventum, so you should remove any functions you are not using, to prevent unexpected results.

#### Creating a Workflow Class

First, you must create a file in lib/eventum/workflow/ that will be used to extend the Abstract_Workflow_Backend class. The name of this file is important, as it will indicate a collection of customizations that can be applied to one or more projects. The format of the filename is:

`class.`-name-`.php`

So, assuming you are customizing eventum for "Acme, Inc.", you could name your file:

`class.acme.php`

The file must be valid PHP and must include the Abstract_Workflow_Backend class in order to extend it. Therefore, the contents will always be in the following format:

    <?php
    require_once(APP_INC_PATH . "workflow/class.abstract_workflow_backend.php");
    class <name>_Workflow_Backend extends Abstract_Workflow_Backend
    {
        /** Put the methods you want to customize here */
    }

Note that you must provide a name for your new class. For our example, we could use:

    <?php
    require_once(APP_INC_PATH . "workflow/class.abstract_workflow_backend.php");
    class ACME_Workflow_Backend extends Abstract_Workflow_Backend
    {
        /** Put the methods you want to customize here */
    }

Now you can override methods in that class that correspond to specific events.

To get started, you can copy class.example.php and rename it (and the class within) appropriately. Be sure to delete any functions you do not intend to customize.

For a list of all available methods, refer to:

`lib/eventum/workflow/class.abstract_workflow_backend.php`

#### Assigning a Workflow Class to a Project

Once you have created your class, you must set your project(s) to use it. Only one workflow class may be assigned to a project at a time. However, a class can be shared among many projects, if similar behaviour is desired for all of them.

-   Login to Eventum as an Administrator
-   Go to "Administration" and click on the desired project
-   Select the new class by name from the dropdown list next to "Workflow Backend" [Note that the values are displayed in Title Case, based on the filename class.<name_of_workflow>`.php, replacing underscores with spaces.]`

*   Click "Update Project"

Your project should now be using your workflow class.

### Individual Methods

Please see /eventum/include/workflow/class.abstract_workflow_backend.php for the individual methods you can override.

### Future Direction

As Eventum is developed more methods will be added to the workflow class. If you need a new workflow method, or you need more arguments passed to an existing method please email the Eventum development list.

### Examples

Please see [the examples page](WorkflowExamples.md) for example workflow methods.
