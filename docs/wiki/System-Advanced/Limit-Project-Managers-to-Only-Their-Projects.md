This mod will limit what a project manager can do in the Administration section.

-   Managers can add / edit /update users only on the projects they manage.
-   Managers can modify project parameters only on the projects they manage.

Administrators can still do anything on any project.

The down side of this is that Eventum restricts access to three project-level pages to Administrators:

-   Manage Email Accounts
-   Manage Custom Fields
-   Customize Issue Listing Screen

I'll take a shot at these later

The patches for Eventum 2.1:

**include/class.user.php**

    diff U3wBi /eventum-2.1/include/class.user.php /tracker-it/include/class.user.php
    --- /eventum-2.1/include/class.user.php Mon Apr 16 20:32:48 2007
    +++ /tracker-it/include/class.user.php  Fri Dec 21 16:19:11 2007
    @@ -1006,21 +1006,19 @@
             if (PEAR::isError($res)) {
                 Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
                 return -1;
    -        } else {
    +        }
                 // update the project associations now
    -            $stmt = "DELETE FROM
    -                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_user
    -                     WHERE
    -                        pru_usr_id=" . Misc::escapeInteger($_POST["id"]);
    +        foreach ($_POST["role"] as $prj_id => $role) {
    +               $stmt  = "DELETE FROM ";
    +               $stmt .= APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_user ";
    +               $stmt .= " WHERE pru_usr_id=" . Misc::escapeInteger($_POST["id"]) . " ";
    +               $stmt .= " AND  pru_prj_id=" . $prj_id;
                 $res = $GLOBALS["db_api"]->dbh->query($stmt);
                 if (PEAR::isError($res)) {
                     Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
                     return -1;
    -            } else {
    -                foreach ($_POST["role"] as $prj_id => $role) {
    -                    if ($role < 1) {
    -                        continue;
                         }
    +           if ($role > 0) {
                         $stmt = "INSERT INTO
                                     " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_user
                                  (
    @@ -1045,7 +1043,6 @@
                     Notification::notifyUserAccount($_POST["id"]);
                 }
                 return 1;
    -        }
         }

**manage/projects.php**

    diff U3wBi /eventum-2.1/manage/projects.php /tracker-it/manage/projects.php
    --- H:/Software/Devel/Project Mgmt-Issue Track/Issue Tracking/Eventum/eventum-2.1/manage/projects.php   Wed Jan 24 15:24:35 2007
    +++ C:/_Devel/InternalWeb/tracker-it/manage/projects.php    Fri Dec 21 14:25:16 2007
    @@ -46,12 +46,28 @@

     Auth::checkAuthentication(APP_COOKIE);

    +   $admin = User::getRoleID('administrator');
    +   $manager = User::getRoleID('manager');
    +   $current_user = Auth::getUserID();
    +
     $tpl->assign("type", "projects");

     $role_id = Auth::getCurrentRole();
    -if (($role_id == User::getRoleID('administrator')) || ($role_id == User::getRoleID('manager'))) {
    -    if ($role_id == User::getRoleID('administrator')) {
    +   if (($role_id == $admin) || ($role_id == $manager)) {
    +       $project_list = array();
    +       if ($role_id == $admin) {
             $tpl->assign("show_setup_links", true);
    +           $project_list = Project::getList();
    +       } else {
    +           $project_temp = Project::getList();
    +           foreach ($project_temp as $prj_id => $project) {
    +               $proj_admins = Project::getUserAssocList($project['prj_id'], null, (User::getRoleID('developer')));
    +               foreach ($proj_admins as $user_id => $user_full_name){
    +                   if (intval($user_id) == intval($current_user)){
    +                       array_push($project_list, $project);
    +                   }
    +               }
    +           }
         }

         if (@$_POST["cat"] == "new") {
    @@ -67,11 +83,12 @@
             $tpl->assign("info", Project::getDetails($_GET["id"]));
         }

    -    $tpl->assign("list", Project::getList());
    +       $tpl->assign("list", $project_list);
         $tpl->assign("user_options", User::getActiveAssocList());
         $tpl->assign("status_options", Status::getAssocList());
         $tpl->assign("customer_backends", Customer::getBackendList());
         $tpl->assign("workflow_backends", Workflow::getBackendList());
    +       $tpl->assign("current_user",User::getFullName(Auth::getUserID()));
     } else {
         $tpl->assign("show_not_allowed_msg", true);
     }

**manage/users.php**

    diff U3wBi /eventum-2.1/manage/users.php /tracker-it/manage/users.php
    --- /eventum-2.1/manage/users.php   Wed Jan 24 15:24:35 2007
    +++ /tracker-it/manage/users.php    Fri Dec 21 15:34:54 2007
    @@ -41,9 +41,13 @@

     $tpl->assign("type", "users");

    +$admin = User::getRoleID('administrator');
    +$manager = User::getRoleID('manager');
    +$current_user = Auth::getUserID();
    +
     $role_id = Auth::getCurrentRole();
    -if (($role_id == User::getRoleID('administrator')) || ($role_id == User::getRoleID('manager'))) {
    -    if ($role_id == User::getRoleID('administrator')) {
    +if (($role_id == $admin) || ($role_id == $manager)) {
    +    if ($role_id == $admin) {
             $tpl->assign("show_setup_links", true);
             $excluded_roles = array('customer');
         } else {
    @@ -59,7 +63,19 @@
         }

         $project_roles = array();
    +    $project_admin = array();
         $project_list = Project::getAll();
    +       if ($role_id == $manager){
    +           foreach ($project_list as $prj_id => $prj_title){
    +           $proj_admins = Project::getUserAssocList($prj_id, null, (User::getRoleID('developer')));
    +           $is_manager = 0;
    +           foreach ($proj_admins as $user_id => $user_full_name){
    +               if (intval($user_id) == intval($current_user)){
    +                   $project_admin[$prj_id] = 1;
    +               }
    +           }
    +           }
    +       }
         if (@$_GET["cat"] == "edit") {
             $info = User::getDetails($_GET["id"]);
             $tpl->assign("info", $info);
    @@ -72,10 +88,12 @@
                     $excluded_roles = array('administrator');
                 }
             }
    -        if (@$info['roles'][$prj_id]['pru_role'] == User::getRoleID("administrator")) {
    -            $excluded_roles = false;
    +        if (@$info['roles'][$prj_id]['pru_role'] == $admin) {
    +            $excluded = false;
    +        } else {
    +           $excluded = $excluded_roles;
             }
    -        $project_roles[$prj_id] = $user_roles = array(0 => "No Access") + User::getRoles($excluded_roles);
    +        $project_roles[$prj_id] = $user_roles = array(0 => "No Access") + User::getRoles($excluded);
         }
         if (@$_GET['show_customers'] == 1) {
             $show_customer = true;
    @@ -85,6 +103,7 @@
         $tpl->assign("list", User::getList($show_customer));
         $tpl->assign("project_list", $project_list);
         $tpl->assign("project_roles", $project_roles);
    +    $tpl->assign("project_admin", $project_admin);
     } else {
         $tpl->assign("show_not_allowed_msg", true);
     }

**templates/manage/users.tpl.html**

    diff U3wBi /eventum-2.1/templates/manage/users.tpl.html /tracker-it/templates/manage/users.tpl.html
    --- /eventum-2.1/templates/manage/users.tpl.html    Wed Mar 07 18:26:48 2007
    +++ /tracker-it/templates/manage/users.tpl.html Fri Dec 21 15:38:24 2007
    @@ -135,7 +135,7 @@
                             <span class="default">{t}Customer{/t}</span>
                             <input type="hidden" name="role[{$prj_id}]" value="{$roles.customer}">
                             {else}
    -                        <select name="role[{$prj_id}]" class="default"  {if $current_role < $info.roles.$prj_id.pru_role}disabled{/if}>
    +                        <select name="role[{$prj_id}]" class="default"  {if (($current_role != 7) and ($project_admin.$prj_id != 1)) or ($current_role < $info.roles.$prj_id.pru_role)}disabled{/if}>
                             {html_options options=$project_roles[$prj_id] selected=$info.roles.$prj_id.pru_role}
                             </select>
                             {if $current_role < $info.roles.$prj_id.pru_role}<input type="hidden" name="role[{$prj_id}]" value="{$info.roles.$prj_id.pru_role}">{/if}
