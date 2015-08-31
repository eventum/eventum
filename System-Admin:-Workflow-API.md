### Workflow API

The workflow API is used to provide custom functionality to Eventum. The basic idea is when an event happens (new issue, new note, blocked email, etc.) Eventum calls the workflow class specified for that project.

<h2>
Getting Started

</h2>
To write your own Workflow class create a file named "class.my_name.php" in <i>/eventum/include/workflow/</i>. In that file, create a class named "my_name_Workflow_Backend" that extends "Abstract_Workflow_Backend". Now you can add methods to that class to handle specific events. You can copy the method signatures from <i>/eventum/include/workflow/class.abstract_workflow_backend.php</i> to get started.
An example workflow implementation is in <i>/eventum/include/workflow/class.example_workflow.php</i>.

Once you have your class created you must set your project to use it.

1.  Login to eventum with your administrative account.
2.  Go to "Administration" and click the the name of the project you want to
    `       use the workflow class.`

3.  Select "my_name" from the dropdown list next to "Workflow Backend"
4.  Click "Update Project"

Your project should now be using your workflow class.

<h2>
Individual methods

</h2>
Please see <i>/eventum/include/workflow/class.abstract_workflow_backend.php</i> for the individual methods you can override.

<h2>
Future Direction

</h2>
As Eventum is developed more methods will be added to the workflow class. If you need a new workflow method, or you need more arguments passed to an existing method please email the [Eventum development](mailto:eventum-devel@lists.mysql.com) list.