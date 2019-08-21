### Enhanced FAQ part 1

ALTER TABLE faq add faq_published tinyint(1) NOT NULL DEFAULT 0;

ALTER TABLE faq add faq_deleted tinyint(1) NOT NULL DEFAULT 0;

---

    Index: faq.php
    ===================================================================
    --- faq.php (revision 108)
    +++ faq.php (arbetskopia)
    @@ -59,7 +59,7 @@
             }
         }
     }
    -$tpl->assign("faqs", FAQ::getListBySupportLevel($support_level_id));
    +$tpl->assign("faqs", FAQ::getListBySupportLevel($support_level_id, true));

     if (!empty($HTTP_GET_VARS["id"])) {
         $t = FAQ::getDetails($HTTP_GET_VARS['id']);

    Index: include/class.faq.php
    ===================================================================
    --- include/class.faq.php   (revision 108)
    +++ include/class.faq.php   (arbetskopia)
    @@ -36,13 +36,20 @@
          *
          * @access  public
          * @param   integer $support_level_id The support level ID
    +     * @param   bool  $published Get published(true) or unpublished(false) items
    +     * @param   bool  $deleted Get deleted
          * @return  array The list of FAQ entries
          */
    -    function getListBySupportLevel($support_level_id)
    +    function getListBySupportLevel($support_level_id, $published=1, $deleted=0)
         {
             $support_level_id = Misc::escapeInteger($support_level_id);
             $prj_id = Auth::getCurrentProject();

    +   //x
    +   $published = Misc::escapeInteger($published);
    +   $deleted   = Misc::escapeInteger($deleted);
    +   //x
    +
             if ($support_level_id == -1) {
                 $stmt = "SELECT
                             *
    @@ -50,6 +57,8 @@
                             " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "faq
                          WHERE
                             faq_prj_id = $prj_id
    +                     AND faq_published=$published
    +                     AND faq_deleted=$deleted
                          ORDER BY
                             faq_rank ASC";
             } else {
    @@ -61,7 +70,9 @@
                          WHERE
                             faq_id=fsl_faq_id AND
                             fsl_support_level_id=$support_level_id AND
    -                        faq_prj_id = $prj_id
    +                        faq_prj_id = $prj_id
    +                     AND faq_published=$published
    +                     AND faq_deleted=$deleted
                          ORDER BY
                             faq_rank ASC";
             }
    @@ -92,10 +103,10 @@
             global $HTTP_POST_VARS;

             $items = @implode(", ", Misc::escapeInteger($HTTP_POST_VARS["items"]));
    -        $stmt = "DELETE FROM
    +        $stmt = "UPDATE
                         " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "faq
    -                 WHERE
    -                    faq_id IN ($items)";
    +                 SET faq_deleted=1
    +                 WHERE faq_id IN ($items)";
             $res = $GLOBALS["db_api"]->dbh->query($stmt);
             if (PEAR::isError($res)) {
                 Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
    @@ -161,7 +172,9 @@
                         faq_updated_date='" . Date_API::getCurrentDateGMT() . "',
                         faq_title='" . Misc::escapeString($HTTP_POST_VARS["title"]) . "',
                         faq_message='" . Misc::escapeString($HTTP_POST_VARS["message"]) . "',
    -                    faq_rank=" . $HTTP_POST_VARS['rank'] . "
    +                    faq_rank=" . Misc::escapeInteger($HTTP_POST_VARS['rank']) . ",
    +                    faq_published=" . Misc::escapeInteger($HTTP_POST_VARS['published']) . ",
    +                    faq_deleted=". Misc::escapeInteger($HTTP_POST_VARS['deleted']) ."
                      WHERE
                         faq_id=" . $HTTP_POST_VARS["id"];
             $res = $GLOBALS["db_api"]->dbh->query($stmt);
    @@ -205,14 +218,16 @@
                         faq_created_date,
                         faq_title,
                         faq_message,
    -                    faq_rank
    +                    faq_rank,
    +                    faq_published
                      ) VALUES (
                         " . $HTTP_POST_VARS['project'] . ",
                         " . Auth::getUserID() . ",
                         '" . Date_API::getCurrentDateGMT() . "',
                         '" . Misc::escapeString($HTTP_POST_VARS["title"]) . "',
                         '" . Misc::escapeString($HTTP_POST_VARS["message"]) . "',
    -                    " . $HTTP_POST_VARS['rank'] . "
    +                    " . $HTTP_POST_VARS['rank'] . ",
    +                    " . Misc::escapeInteger($HTTP_POST_VARS["published"]) ."
                      )";
             $res = $GLOBALS["db_api"]->dbh->query($stmt);
             if (PEAR::isError($res)) {
    @@ -292,9 +307,10 @@
          * Method used to get the list of FAQ entries available in the system.
          *
          * @access  public
    +     * @param   bool Get published items (1) or unpublished(0)
          * @return  array The list of news entries
          */
    -    function getList()
    +    function getList($published=1)
         {
             $stmt = "SELECT
                         faq_id,
    @@ -303,6 +319,8 @@
                         faq_rank
                      FROM
                         " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "faq
    +                 WHERE faq_deleted=0
    +                 AND faq_published=" . Misc::escapeInteger($published) ."
                      ORDER BY
                         faq_rank ASC";
             $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
    Index: manage/faq.php
    ===================================================================
    --- manage/faq.php  (revision 108)
    +++ manage/faq.php  (arbetskopia)
    @@ -34,6 +34,7 @@
     include_once(APP_INC_PATH . "class.project.php");
     include_once(APP_INC_PATH . "class.faq.php");
     include_once(APP_INC_PATH . "class.customer.php");
    +include_once(APP_INC_PATH . "class.misc.php");

     $tpl = new Template_API();
     $tpl->setTemplate("manage/index.tpl.html");
    @@ -78,11 +79,14 @@
             FAQ::changeRank($HTTP_GET_VARS['id'], $HTTP_GET_VARS['rank']);
         }

    -    $tpl->assign("list", FAQ::getList());
    +    //x
    +    $tpl->assign("list", FAQ::getList(true)); //Get published FAQ-items
    +    $tpl->assign("waitlist", FAQ::getList(false)); //Get unpublished FAQ-items
    +    //x
         $tpl->assign("project_list", Project::getAll());
     } else {
         $tpl->assign("show_not_allowed_msg", true);
     }

     $tpl->displayTemplate();

    Index: templates/manage/faq.tpl.html
    ===================================================================
    --- templates/manage/faq.tpl.html   (revision 108)
    +++ templates/manage/faq.tpl.html   (arbetskopia)
    @@ -56,12 +56,13 @@
                   {if $smarty.get.cat == 'edit'}
                   <input type="hidden" name="cat" value="update">
                   <input type="hidden" name="id" value="{$smarty.get.id}">
    +              <input type="hidden" name="deleted" value="{$info.faq_deleted}">
                   {else}
                   <input type="hidden" name="cat" value="new">
                   {/if}
                   <tr>
                     <td colspan="2" class="default">
    -                  <b>{t}Manage Internal FAQ{/t}</b>
    +                  <b>{t}Manage FAQ{/t}</b>
                     </td>
                   </tr>
                   {if $result != ""}
    @@ -134,8 +135,21 @@
                       {include file="error_icon.tpl.html" field="title"}
                     </td>
                   </tr>
    +         {* x *}
                   <tr>
                     <td width="140" bgcolor="{$cell_color}" class="default_white">
    +                  <b>{t}Published:{/t}</b>
    +                </td>
    +                <td bgcolor="{$light_color}" class="default">
    +
    +            <input type="radio" name="published" {if $info.faq_published == "1"}checked{/if} value="1"> <a id="link" class="link" href="javascript:void(null);" onClick="javascript:checkRadio('news_form', 'published', 0);">{t}Yes{/t}</a>  
    +            <input type="radio" name="published" {if $info.faq_published != "1"}checked{/if} value="0"> <a id="link" class="link" href="javascript:void(null);" onClick="javascript:checkRadio('news_form', 'published', 1);">{t}No{/t}</a>
    +                  {include file="error_icon.tpl.html" field="published"}
    +                </td>
    +              </tr>
    +         {* x *}
    +              <tr>
    +                <td width="140" bgcolor="{$cell_color}" class="default_white">
                       <b>{t}Message:{/t}</b>
                     </td>
                     <td bgcolor="{$light_color}">
    @@ -154,9 +168,12 @@
                     </td>
                   </tr>
                   </form>
    +
    +
    +{* x *}
                   <tr>
                     <td colspan="2" class="default">
    -                  <b>{t}Existing Internal FAQ Entries:{/t}</b>
    +                  <br /><b>{t}Waiting FAQ Entries:{/t}</b>
                     </td>
                   </tr>
                   <tr>
    @@ -183,6 +200,82 @@
                         <form onSubmit="javascript:return checkDelete(this);" method="post" action="{$smarty.server.PHP_SELF}">
                         <input type="hidden" name="cat" value="delete">
                         <tr>
    +                      <td width="4" bgcolor="{$internal_color}" nowrap><input type="button" value="{t}All{/t}" class="shortcut" onClick="javascript:toggleSelectAll(this.form, 'items[]');"></td>
    +                      <td bgcolor="{$internal_color}" class="default_white" align="center"> <b>{t}Rank{/t}</b> </td>
    +                      <td width="{if $backend_uses_support_levels}50%{else}100%{/if}" bgcolor="{$internal_color}" class="default_white"> <b>{t}Title{/t}</b></td>
    +                      {if $backend_uses_support_levels}
    +                      <td width="50%" bgcolor="{$cell_color}" class="default_white"> <b>{t}Support Levels{/t}</b></td>
    +                      {/if}
    +                    </tr>
    +                    {section name="i" loop=$waitlist}
    +                    {cycle values=$cycle assign="row_color"}
    +                    <tr>
    +                      <td width="4" nowrap bgcolor="{$row_color}" align="center"><input type="checkbox" name="items[]" value="{$waitlist[i].faq_id}"></td>
    +                      <td bgcolor="{$row_color}" class="default" align="center" nowrap>
    +                        <a href="{$smarty.server.PHP_SELF}?cat=change_rank&id={$waitlist[i].faq_id}&rank=desc"><img src="{$rel_url}images/desc.gif" border="0"></a> {$waitlist[i].faq_rank}
    +                        <a href="{$smarty.server.PHP_SELF}?cat=change_rank&id={$waitlist[i].faq_id}&rank=asc"><img src="{$rel_url}images/asc.gif" border="0"></a>
    +                      </td>
    +                      <td width="50%" bgcolor="{$row_color}" class="default">
    +                         <a class="link" href="{$smarty.server.PHP_SELF}?cat=edit&id={$waitlist[i].faq_id}" title="{t}update this entry{/t}">{$waitlist[i].faq_title|escape:"html"}</a>
    +                      </td>
    +                      {if $backend_uses_support_levels}
    +                      <td width="50%" bgcolor="{$row_color}" class="default">
    +                         {$waitlist[i].support_levels|escape:"html"}
    +                      </td>
    +                      {/if}
    +                    </tr>
    +                    {sectionelse}
    +                    <tr>
    +                      <td colspan="{if $backend_uses_support_levels}4{else}3{/if}" bgcolor="{$light_color}" align="center" class="default">
    +                        <i>{t}No waiting FAQ entries could be found.{/t}</i>
    +                      </td>
    +                    </tr>
    +                    {/section}
    +                    <tr>
    +                      <td width="4" align="center" bgcolor="{$internal_color}">
    +                        <input type="button" value="{t}All{/t}" class="shortcut" onClick="javascript:toggleSelectAll(this.form, 'items[]');">
    +                      </td>
    +                      <td colspan="{if $backend_uses_support_levels}3{else}2{/if}" bgcolor="{$internal_color}" align="center">
    +                        <input type="submit" value="{t}Delete{/t}" class="button">
    +                      </td>
    +                    </tr>
    +                    </form>
    +                  </table>
    +         <br /><br />
    +                </td>
    +              </tr>
    +{* x *}
    +
    +
    +              <tr>
    +                <td colspan="2" class="default">
    +                  <b>{t}Existing FAQ Entries:{/t}</b>
    +                </td>
    +              </tr>
    +              <tr>
    +                <td colspan="2">
    +                  {literal}
    +                  <script language="JavaScript">
    +                  <!--
    +                  function checkDelete(f)
    +                  {
    +                      if (!hasOneChecked(f, 'items[]')) {
    +                          alert('{/literal}{t}Please select at least one of the FAQ entries.{/t}{literal}');
    +                          return false;
    +                      }
    +                      if (!confirm('{/literal}{t}This action will permanently remove the selected FAQ entries.{/t}{literal}')) {
    +                          return false;
    +                      } else {
    +                          return true;
    +                      }
    +                  }
    +                  //-->
    +                  </script>
    +                  {/literal}
    +                  <table border="0" width="100%" cellpadding="1" cellspacing="1">
    +                    <form onSubmit="javascript:return checkDelete(this);" method="post" action="{$smarty.server.PHP_SELF}">
    +                    <input type="hidden" name="cat" value="delete">
    +                    <tr>
                           <td width="4" bgcolor="{$cell_color}" nowrap><input type="button" value="{t}All{/t}" class="shortcut" onClick="javascript:toggleSelectAll(this.form, 'items[]');"></td>
                           <td bgcolor="{$cell_color}" class="default_white" align="center"> <b>{t}Rank{/t}</b> </td>
                           <td width="{if $backend_uses_support_levels}50%{else}100%{/if}" bgcolor="{$cell_color}" class="default_white"> <b>{t}Title{/t}</b></td>
