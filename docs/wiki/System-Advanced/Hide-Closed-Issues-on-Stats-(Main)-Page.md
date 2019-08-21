### Hide Closed Issues on Stats (Main) Page

This mod was designed to give the users the option to show closed issues or not, just like the Issue List window. When Closed issues are hidden, the numeric listing on the left side eliminates them as well and only shows a single column of numbers.

What I did was copy the functionality almost verbatim from List Issues and applied it to Stats. I managed to keep the affected modules to 3 with 1 template affected.

The biggest change: include/class.stats.php

    diff U3B C:/www_public/web/eventum-2.0.1/include/class.stats.php C:/www_public/web/tracker-it-dev/include/class.stats.php
    --- C:/www_public/web/eventum-2.0.1/include/class.stats.php Wed Apr 11 18:50:22 2007
    +++ C:/www_public/web/tracker-it-dev/include/class.stats.php    Mon Mar 26 11:35:32 2007
    @@ -36,6 +36,9 @@
     require_once(APP_INC_PATH . "class.user.php");
     require_once(APP_INC_PATH . "class.project.php");
     require_once(APP_INC_PATH . "class.status.php");
    +require_once(APP_INC_PATH . "class.search_profile.php");
    +require_once(APP_INC_PATH . "class.session.php");
    +

     /**
      * Class to handle the business logic related to the generation of the
    @@ -89,12 +92,25 @@
          * @access  public
          * @return  array List of categories
          */
    -    function getAssocCategory()
    +    function getAssocCategory($hide_closed = 0)
         {
             $prj_id = Auth::getCurrentProject();
             $list = Category::getAssocList($prj_id);
             $stats = array();
             foreach ($list as $prc_id => $prc_title) {
    +           if ($hide_closed) {
    +               $stmt = "SELECT
    +                           COUNT(*) AS total_items
    +                        FROM
    +                           " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue,
    +                           " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status
    +                        WHERE
    +                           iss_sta_id=sta_id AND
    +                           sta_is_closed = 0 AND
    +                           iss_prj_id=$prj_id AND
    +                           iss_prc_id=" . $prc_id;
    +
    +           } else {
                 $stmt = "SELECT
                             COUNT(*) AS total_items
                          FROM
    @@ -102,6 +118,7 @@
                          WHERE
                             iss_prj_id=$prj_id AND
                             iss_prc_id=" . $prc_id;
    +           }
                 $res = (integer) $GLOBALS["db_api"]->dbh->getOne($stmt);
                 if ($res > 0) {
                     $stats[$prc_title] = $res;
    @@ -119,12 +136,24 @@
          * @access  public
          * @return  array List of releases
          */
    -    function getAssocRelease()
    +    function getAssocRelease($hide_closed = 0)
         {
             $prj_id = Auth::getCurrentProject();
             $list = Release::getAssocList($prj_id);
             $stats = array();
             foreach ($list as $pre_id => $pre_title) {
    +           if ($hide_closed) {
    +               $stmt = "SELECT
    +                           COUNT(*) AS total_items
    +                        FROM
    +                           " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue,
    +                           " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status
    +                        WHERE
    +                           iss_sta_id=sta_id AND
    +                           iss_prj_id=$prj_id AND
    +                           sta_is_closed = 0 AND
    +                           iss_pre_id=" . $pre_id;
    +           } else {
                 $stmt = "SELECT
                             COUNT(*) AS total_items
                          FROM
    @@ -132,6 +161,7 @@
                          WHERE
                             iss_prj_id=$prj_id AND
                             iss_pre_id=" . $pre_id;
    +           }
                 $res = (integer) $GLOBALS["db_api"]->dbh->getOne($stmt);
                 if ($res > 0) {
                     $stats[$pre_title] = $res;
    @@ -149,12 +179,24 @@
          * @access  public
          * @return  array List of statuses
          */
    -    function getAssocStatus()
    +    function getAssocStatus($hide_closed = 0)
         {
             $prj_id = Auth::getCurrentProject();
             $list = Status::getAssocStatusList($prj_id);
             $stats = array();
             foreach ($list as $sta_id => $sta_title) {
    +           if ($hide_closed) {
    +               $stmt = "SELECT
    +                           COUNT(*) AS total_items
    +                        FROM
    +                           " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue,
    +                           " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status
    +                        WHERE
    +                           iss_sta_id=sta_id AND
    +                           iss_prj_id=$prj_id AND
    +                           sta_is_closed = 0 AND
    +                           iss_sta_id=" . $sta_id;
    +           } else {
                 $stmt = "SELECT
                             COUNT(*) AS total_items
                          FROM
    @@ -162,6 +204,7 @@
                          WHERE
                             iss_prj_id=$prj_id AND
                             iss_sta_id=" . $sta_id;
    +           }
                 $res = (integer) $GLOBALS["db_api"]->dbh->getOne($stmt);
                 if ($res > 0) {
                     $stats[$sta_title] = $res;
    @@ -179,23 +222,31 @@
          * @access  public
          * @return  array List of statuses
          */
    -    function getStatus()
    +    function getStatus($hide_closed = 0)
         {
             $prj_id = Auth::getCurrentProject();
    -        $stmt = "SELECT
    -                    DISTINCT iss_sta_id,
    -                    sta_title,
    -                    COUNT(*) AS total_items
    -                 FROM
    -                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue,
    -                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status
    -                 WHERE
    -                    iss_sta_id=sta_id AND
    -                    iss_prj_id=$prj_id
    -                 GROUP BY
    -                    iss_sta_id
    -                 ORDER BY
    -                    total_items DESC";
    +        if ($hide_closed) {
    +           $sta_stmt= "
    +                   sta_is_closed=0 AND
    +                   ";
    +       } else {
    +           $sta_stmt = "";
    +       }
    +         $stmt = "SELECT
    +                       DISTINCT iss_sta_id,
    +                       sta_title,
    +                       COUNT(*) AS total_items
    +                    FROM
    +                       " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue,
    +                       " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status
    +                    WHERE
    +                       iss_sta_id=sta_id AND " . $sta_stmt . "
    +                       iss_prj_id=$prj_id
    +                    GROUP BY
    +                       iss_sta_id
    +                    ORDER BY
    +                       total_items DESC";
    +
             $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
             if (PEAR::isError($res)) {
                 Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
    @@ -213,9 +264,16 @@
          * @access  public
          * @return  array List of categories
          */
    -    function getCategory()
    +    function getCategory($hide_closed = 0)
         {
             $prj_id = Auth::getCurrentProject();
    +        if ($hide_closed) {
    +           $sta_stmt= "
    +                   sta_is_closed=0 AND
    +                   ";
    +       } else {
    +           $sta_stmt = "";
    +       }
             $stmt = "SELECT
                         DISTINCT iss_prc_id,
                         prc_title,
    @@ -227,7 +285,7 @@
                         " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status
                      WHERE
                         iss_prj_id=$prj_id AND
    -                    iss_prc_id=prc_id AND
    +                    iss_prc_id=prc_id AND " . $sta_stmt . "
                         iss_sta_id=sta_id
                      GROUP BY
                         iss_prc_id
    @@ -250,9 +308,16 @@
          * @access  public
          * @return  array List of releases
          */
    -    function getRelease()
    +    function getRelease($hide_closed = 0)
         {
             $prj_id = Auth::getCurrentProject();
    +        if ($hide_closed) {
    +           $sta_stmt= "
    +                   sta_is_closed=0 AND
    +                   ";
    +       } else {
    +           $sta_stmt = "";
    +       }
             $stmt = "SELECT
                         DISTINCT iss_pre_id,
                         pre_title,
    @@ -264,7 +329,7 @@
                         " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status
                      WHERE
                         iss_prj_id=$prj_id AND
    -                    iss_pre_id=pre_id AND
    +                    iss_pre_id=pre_id AND " . $sta_stmt . "
                         iss_sta_id=sta_id
                      GROUP BY
                         iss_pre_id
    @@ -287,12 +352,24 @@
          * @access  public
          * @return  array List of priorities
          */
    -    function getAssocPriority()
    +    function getAssocPriority($hide_closed = 0)
         {
             $prj_id = Auth::getCurrentProject();
             $list = Priority::getAssocList($prj_id);
             $stats = array();
             foreach ($list as $pri_id => $pri_title) {
    +           if ($hide_closed) {
    +               $stmt = "SELECT
    +                           COUNT(*) AS total_items
    +                        FROM
    +                           " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue,
    +                           " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status
    +                        WHERE
    +                           iss_sta_id=sta_id AND
    +                           sta_is_closed = 0 AND
    +                           iss_prj_id=$prj_id AND
    +                           iss_pri_id=" . $pri_id;
    +           } else {
                 $stmt = "SELECT
                             COUNT(*) AS total_items
                          FROM
    @@ -300,6 +377,7 @@
                          WHERE
                             iss_prj_id=$prj_id AND
                             iss_pri_id=" . $pri_id;
    +           }
                 $res = (integer) $GLOBALS["db_api"]->dbh->getOne($stmt);
                 if ($res > 0) {
                     $stats[$pri_title] = $res;
    @@ -317,9 +395,16 @@
          * @access  public
          * @return  array List of statuses
          */
    -    function getPriority()
    +    function getPriority($hide_closed = 0)
         {
             $prj_id = Auth::getCurrentProject();
    +        if ($hide_closed) {
    +           $sta_stmt= "
    +                   sta_is_closed=0 AND
    +                   ";
    +       } else {
    +           $sta_stmt = "";
    +       }
             $stmt = "SELECT
                         DISTINCT iss_pri_id,
                         pri_title,
    @@ -331,7 +416,7 @@
                         " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status
                      WHERE
                         iss_pri_id=pri_id AND
    -                    iss_sta_id=sta_id AND
    +                    iss_sta_id=sta_id AND " . $sta_stmt . "
                         iss_prj_id=$prj_id
                      GROUP BY
                         iss_pri_id
    @@ -354,12 +439,26 @@
          * @access  public
          * @return  array List of users
          */
    -    function getAssocUser()
    +    function getAssocUser($hide_closed)
         {
             $prj_id = Auth::getCurrentProject();
             $list = Project::getUserAssocList($prj_id, 'stats', User::getRoleID('Customer'));
             $stats = array();
             foreach ($list as $usr_id => $usr_full_name) {
    +           if ($hide_closed) {
    +               $stmt = "SELECT
    +                           COUNT(*) AS total_items
    +                        FROM
    +                           " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue,
    +                           " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status,
    +                           " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_user
    +                        WHERE
    +                           iss_sta_id=sta_id AND
    +                           sta_is_closed = 0 AND
    +                           isu_iss_id=iss_id AND
    +                           iss_prj_id=$prj_id AND
    +                           isu_usr_id=" . $usr_id;
    +           } else {
                 $stmt = "SELECT
                             COUNT(*) AS total_items
                          FROM
    @@ -369,6 +468,7 @@
                             isu_iss_id=iss_id AND
                             iss_prj_id=$prj_id AND
                             isu_usr_id=" . $usr_id;
    +           }
                 $res = (integer) $GLOBALS["db_api"]->dbh->getOne($stmt);
                 if ($res > 0) {
                     $stats[$usr_full_name] = $res;
    @@ -386,9 +486,16 @@
          * @access  public
          * @return  array List of users
          */
    -    function getUser()
    +    function getUser($hide_closed = 0)
         {
             $prj_id = Auth::getCurrentProject();
    +        if ($hide_closed) {
    +           $sta_stmt= "
    +                   sta_is_closed=0 AND
    +                   ";
    +       } else {
    +           $sta_stmt = "";
    +       }
             $stmt = "SELECT
                         DISTINCT isu_usr_id,
                         usr_full_name,
    @@ -402,7 +509,7 @@
                      WHERE
                         isu_usr_id=usr_id AND
                         isu_iss_id=iss_id AND
    -                    iss_prj_id=$prj_id AND
    +                    iss_prj_id=$prj_id AND " . $sta_stmt . "
                         iss_sta_id=sta_id
                      GROUP BY
                         isu_usr_id
    @@ -470,6 +577,45 @@
                 "associated" => $res['associated'],
                 "removed"    => $res3
             );
    +    }
    +    /**
    +     * Method used to get a specific parameter in the issue listing cookie.
    +     *
    +     * @access  public
    +     * @param   string $name The name of the parameter
    +     * @return  mixed The value of the specified parameter
    +     */
    +    function getParam($name)
    +    {
    +        $profile = Search_Profile::getProfile(Auth::getUserID(), Auth::getCurrentProject(), 'stats');
    +
    +        if (isset($_GET[$name])) {
    +            return $_GET[$name];
    +        } elseif (isset($_POST[$name])) {
    +            return $_POST[$name];
    +        } elseif (isset($profile[$name])) {
    +            return $profile[$name];
    +        } else {
    +            return "";
    +        }
    +    }
    +    /**
    +     * Method used to save the current search parameters in a cookie.
    +     *
    +     * @access  public
    +     * @return  array The search parameters
    +     */
    +    function saveSearchParams()
    +    {
    +        $hide_closed = Stats::getParam('hide_closed');
    +        if ($hide_closed === '') {
    +            $hide_closed = 0;
    +        }
    +        $cookie = array(
    +            'hide_closed'    => $hide_closed,
    +        );
    +        Search_Profile::save(Auth::getUserID(), Auth::getCurrentProject(), 'stats', $cookie);
    +        return $cookie;
         }
     }

templates/main.tpl.html:

    diff U3B C:/www_public/web/eventum-2.0.1/templates/main.tpl.html C:/www_public/web/tracker-it-dev/templates/main.tpl.html
    --- C:/www_public/web/eventum-2.0.1/templates/main.tpl.html Wed Apr 11 18:50:22 2007
    +++ C:/www_public/web/tracker-it-dev/templates/main.tpl.html    Mon Mar 26 11:36:17 2007
    @@ -1,9 +1,39 @@
     {include file="header.tpl.html" extra_title="Stats"}
     {include file="navigation.tpl.html"}
    +<script language="JavaScript">
    +<!--
    +var page_url = '{$smarty.server.PHP_SELF}';
    +var current_page = '{$list_info.current_page}';
    +var last_page = '{$list_info.last_page}';
    +{literal}
    +function hideClosed(f)
    +{
    +    if (f.hide_closed.checked) {
    +        window.location.href = page_url + "?" + replaceParam(window.location.href, 'hide_closed', '1');
    +    } else {
    +        window.location.href = page_url + "?" + replaceParam(window.location.href, 'hide_closed', '0');
    +    }
    +}
    +//-->
    +</script>
    +{/literal}

     {if $current_role == $roles.customer}
       {include file="customer/$customer_backend_name/customer_report.tpl.html"}
     {else}
    +<form name="main_form">
    +<table width="100%" border="0" cellspacing="0" cellpadding="4">
    +  <tr>
    +    <td bgcolor="{$light_color}" width="50%">
    +      <span class="default"><b>{t}Status Page{/t}</b></span>
    +    </td>
    +    <td bgcolor="{$light_color}" width="50%" align="right">
    +      <span class="default"><b>
    +        <input type="checkbox" id="hide_closed" name="hide_closed" {if $options.hide_closed}checked{/if} onClick="javascript:hideClosed(this.form);"> <label for="hide_closed">{t}Hide Closed Issues{/t}</label> 
    +      </span>
    +    </td>
    +  </tr>
    +</table>
     <table width="100%" border="0" cellpadding="0" cellspacing="0">
       <tr>
         <td valign="top">
    @@ -40,7 +70,7 @@
                         {section name="i" loop=$releases}
                         <tr>
                           <td class="default"><a class="link" href="list.php?keywords=&users=&category=&status=&priority=&release={$releases[i].iss_pre_id}">{$releases[i].pre_title|escape:"html"}</a></td>
    -                      <td align="right" class="default">{$releases[i].total_open_items} / {$releases[i].total_closed_items}</td>
    +                      <td align="right" class="default">{$releases[i].total_open_items}{if !$options.hide_closed} / {$releases[i].total_closed_items}{/if}</td>
                         </tr>
                         {sectionelse}
                         <tr>
    @@ -55,7 +85,7 @@
                         {section name="i" loop=$priorities}
                         <tr>
                           <td class="default"><a class="link" href="list.php?keywords=&users=&category=&release=&status=&priority={$priorities[i].iss_pri_id}">{$priorities[i].pri_title|escape:"html"}</a></td>
    -                      <td align="right" class="default">{$priorities[i].total_open_items} / {$priorities[i].total_closed_items}</td>
    +                      <td align="right" class="default">{$priorities[i].total_open_items}{if !$options.hide_closed} / {$priorities[i].total_closed_items}{/if}</td>
                         </tr>
                         {sectionelse}
                         <tr>
    @@ -70,7 +100,7 @@
                         {section name="i" loop=$categories}
                         <tr>
                           <td class="default"><a class="link" href="list.php?keywords=&users=&category={$categories[i].iss_prc_id}&status=&priority=&release=">{$categories[i].prc_title|escape:"html"}</a></td>
    -                      <td align="right" class="default">{$categories[i].total_open_items} / {$categories[i].total_closed_items}</td>
    +                      <td align="right" class="default">{$categories[i].total_open_items}{if !$options.hide_closed} / {$categories[i].total_closed_items}{/if}</td>
                         </tr>
                         {sectionelse}
                         <tr>
    @@ -85,7 +115,7 @@
                         {section name="i" loop=$users}
                         <tr>
                           <td class="default"><a class="link" href="list.php?keywords=&category=&release=&status=&priority=&users={$users[i].isu_usr_id}">{$users[i].usr_full_name}</a></td>
    -                      <td align="right" class="default">{$users[i].total_open_items} / {$users[i].total_closed_items}</td>
    +                      <td align="right" class="default">{$users[i].total_open_items}{if !$options.hide_closed} / {$users[i].total_closed_items}{/if}</td>
                         </tr>
                         {sectionelse}
                         <tr>
    @@ -153,7 +183,13 @@
                 <table bgcolor="#FFFFFF" width="100%" border="0" cellspacing="0" cellpadding="4">
                   <tr>
                     <td>
    -                  <span class="default"><b>{t}Graphical Stats (All Issues){/t}</b></span>
    +                  <span class="default"><b>{t}Graphical Stats{/t} 
    +                  {if $options.hide_closed}
    +                   {t}(Open Issues){/t}
    +                  {else}
    +                   {t}(All Issues){/t}
    +                  {/if}
    +                  </b></span>
                     </td>
                   </tr>
                   <tr>
    @@ -173,6 +209,7 @@
         </td>
       </tr>
     </table>
    +</form>
     {/if}

     {include file="app_info.tpl.html"}

    main.php:
    <pre>
    diff U1B C:/www_public/web/eventum-2.0.1/main.php C:/www_public/web/tracker-it-dev/main.php
    --- C:/www_public/web/eventum-2.0.1/main.php    Wed Apr 11 18:50:22 2007
    +++ C:/www_public/web/tracker-it-dev/main.php   Tue Apr 24 11:53:19 2007
    @@ -42,2 +43,3 @@
     Auth::checkAuthentication(APP_COOKIE);
    +$usr_id = Auth::getUserID();

    @@ -52,7 +54,9 @@
     } else {
    -    $tpl->assign("status", Stats::getStatus());
    -    $tpl->assign("releases", Stats::getRelease());
    -    $tpl->assign("categories", Stats::getCategory());
    -    $tpl->assign("priorities", Stats::getPriority());
    -    $tpl->assign("users", Stats::getUser());
    +    $options = Stats::saveSearchParams();
    +    $tpl->assign("options", $options);
    +    $tpl->assign("status", Stats::getStatus($options["hide_closed"]));
    +    $tpl->assign("releases", Stats::getRelease($options["hide_closed"]));
    +    $tpl->assign("categories", Stats::getCategory($options["hide_closed"]));
    +    $tpl->assign("priorities", Stats::getPriority($options["hide_closed"]));
    +    $tpl->assign("users", Stats::getUser($options["hide_closed"]));
         $tpl->assign("emails", Stats::getEmailStatus());

stats_chart.php:

    diff U1B C:/www_public/web/eventum-2.0.1/stats_chart.php C:/www_public/web/tracker-it-dev/stats_chart.php
    --- C:/www_public/web/eventum-2.0.1/stats_chart.php Wed Apr 11 18:50:22 2007
    +++ C:/www_public/web/tracker-it-dev/stats_chart.php    Mon Mar 26 11:36:10 2007
    @@ -41,2 +41,3 @@
     Auth::checkAuthentication(APP_COOKIE);
    +$hide_closed = Stats::getParam("hide_closed");

    @@ -53,3 +54,3 @@
     if ($_GET["plot"] == "status") {
    -    $data = Stats::getAssocStatus();
    +    $data = Stats::getAssocStatus($hide_closed);
         $graph_title = ev_gettext("Issues by Status");
    @@ -61,12 +62,12 @@
     } elseif ($_GET["plot"] == "release") {
    -    $data = Stats::getAssocRelease();
    +    $data = Stats::getAssocRelease($hide_closed);
         $graph_title = ev_gettext("Issues by Release");
     } elseif ($_GET["plot"] == "priority") {
    -    $data = Stats::getAssocPriority();
    +    $data = Stats::getAssocPriority($hide_closed);
         $graph_title = ev_gettext("Issues by Priority");
     } elseif ($_GET["plot"] == "user") {
    -    $data = Stats::getAssocUser();
    +    $data = Stats::getAssocUser($hide_closed);
         $graph_title = ev_gettext("Issues by Assignment");
     } elseif ($_GET["plot"] == "category") {
    -    $data = Stats::getAssocCategory();
    +    $data = Stats::getAssocCategory($hide_closed);
         $graph_title = ev_gettext("Issues by Category");
