### Defaulting Assigned Emails to Yes

Diffs for fixing the assigned email feature

    Index: preferences.php

    RCS file: /cvsroot/eventum/preferences.php,v
    retrieving revision 1.1.1.2
    diff -r1.1.1.2 preferences.php
    60a61
    > $assigned_prj_ids=Project::getAssocList($usr_id, false, true);
    65a67,72
    > foreach($assigned_prj_ids as $prj_key => $value) {
    >
    >   if(!(array_key_exists($prj_key,$prefs['receive_assigned_emails']))){
    >     $prefs['receive_assigned_emails'][$prj_key] = APP_DEFAULT_ASSIGNED_EMAILS;
    >   }
    > }
    67c74
    < $tpl->assign("assigned_projects", Project::getAssocList($usr_id, false, true));
    ---
    > $tpl->assign("assigned_projects", $assigned_prj_ids);


    Index: class.notification.php


    RCS file: /cvsroot/eventum/include/class.notification.php,v
    retrieving revision 1.1.1.2
    diff -b -r1.1.1.2 class.notification.php
    1446a1447,1449
    >       if(!(array_key_exists($prj_id,$prefs['receive_assigned_emails']))){
    >         $prefs['receive_assigned_emails'][$prj_id] = APP_DEFAULT_ASSIGNED_EMAILS;
    >       }
