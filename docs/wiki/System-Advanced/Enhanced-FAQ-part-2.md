You must first choose a project before editing FAQ-entries

(Is it perhaps better to move FAQ-editing into the page "Manage Projects"?)

    Index: include/class.faq.php
    ===================================================================
    --- include/class.faq.php   (revision 124)
    +++ include/class.faq.php   (arbetskopia)
    @@ -310,7 +310,7 @@
          * @param   bool Get published items (1) or unpublished(0)
          * @return  array The list of news entries
          */
    -    function getList($published=1)
    +    function getList($published=1, $prj_id=-1)
         {
             $stmt = "SELECT
                         faq_id,
    @@ -320,7 +320,12 @@
                      FROM
                         " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "faq
                      WHERE faq_deleted=0
    -                 AND faq_published=" . Misc::escapeInteger($published) ."
    +                 AND faq_published=" . Misc::escapeInteger($published);
    +
    +   if ($prj_id!=-1)
    +       $stmt .= " AND faq_prj_id=" . Misc::escapeInteger($prj_id);
    +
    +   $stmt .= "
                      ORDER BY
                         faq_rank ASC";
             $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
    Index: manage/faq.php
    ===================================================================
    --- manage/faq.php  (revision 124)
    +++ manage/faq.php  (arbetskopia)
    @@ -41,6 +41,15 @@

     Auth::checkAuthentication(APP_COOKIE);

    +//x
    +if(isset($HTTP_POST_VARS['project'])){    $prj_id = $HTTP_POST_VARS['project']; }
    +else if(isset($HTTP_GET_VARS['project'])){ $prj_id = $HTTP_GET_VARS['project'];  }
    +else if(isset($HTTP_GET_VARS['prj_id'])){  $prj_id = $HTTP_GET_VARS['prj_id'];   }
    +
    +$prj_id = isset($prj_id)?$prj_id:-1;
    +$tpl->assign('prj_id', $prj_id);
    +//x
    +
     $tpl->assign("type", "faq");

     $role_id = Auth::getCurrentRole();
    @@ -80,8 +89,8 @@
         }

         //x
    -    $tpl->assign("list", FAQ::getList(true)); //Get published FAQ-items
    -    $tpl->assign("waitlist", FAQ::getList(false)); //Get unpublished FAQ-items
    +    $tpl->assign("list", FAQ::getList(true, $prj_id)); //Get published FAQ-items
    +    $tpl->assign("waitlist", FAQ::getList(false, $prj_id)); //Get unpublished FAQ-items
         //x
         $tpl->assign("project_list", Project::getAll());
     } else {
    Index: templates/manage/faq.tpl.html
    ===================================================================
    --- templates/manage/faq.tpl.html   (revision 124)
    +++ templates/manage/faq.tpl.html   (arbetskopia)
    @@ -94,16 +94,23 @@
                   {/if}
                   <tr>
                     <td width="150" bgcolor="{$cell_color}" class="default_white">
    -                  <b>{t}Project:{/t}</b>
    +                  <b>{t}Project:*{/t}</b>
                     </td>
                     <td width="80%" bgcolor="{$light_color}">
                       <select name="project" class="default" onChange="javascript:populateComboBox(this.form);">
                         <option value="-1">{t}Please choose an option{/t}</option>
    -                    {html_options options=$project_list selected=$info.faq_prj_id}
    +           {if isset($prj_id) && !isset($info.faq_prj_id)}
    +           {assign var=prj_id value=$prj_id}
    +           {else if(isset($info.faq_prj_id))}
    +           {assign var=prj_id value=$info.faq_prj_id}
    +           {/if}
    +                    {html_options options=$project_list selected=$prj_id}
                       </select>
    +         {if $prj_id == -1} <span class="default">{t}You must have a selected project{/t}</span>{/if}
                       {include file="error_icon.tpl.html" field="project"}
                     </td>
                   </tr>
    +         {if $prj_id != -1}
                   {if $backend_uses_support_levels}
                   <tr>
                     <td width="140" bgcolor="{$cell_color}" class="default_white">
    @@ -167,10 +174,12 @@
                       <input class="button" type="reset" value="{t}Reset{/t}">
                     </td>
                   </tr>
    +         {/if}{* if we have a selected project *}
                   </form>


     {* x *}
    +         {if $prj_id != -1}
                   <tr>
                     <td colspan="2" class="default">
                       <br /><b>{t}Waiting FAQ Entries:{/t}</b>
    @@ -199,6 +208,7 @@
                       <table border="0" width="100%" cellpadding="1" cellspacing="1">
                         <form onSubmit="javascript:return checkDelete(this);" method="post" action="{$smarty.server.PHP_SELF}">
                         <input type="hidden" name="cat" value="delete">
    +                    <input type="hidden" name="project" value="{$prj_id}">
                         <tr>
                           <td width="4" bgcolor="{$internal_color}" nowrap><input type="button" value="{t}All{/t}" class="shortcut" onClick="javascript:toggleSelectAll(this.form, 'items[]');"></td>
                           <td bgcolor="{$internal_color}" class="default_white" align="center"> <b>{t}Rank{/t}</b> </td>
    @@ -212,11 +222,11 @@
                         <tr>
                           <td width="4" nowrap bgcolor="{$row_color}" align="center"><input type="checkbox" name="items[]" value="{$waitlist[i].faq_id}"></td>
                           <td bgcolor="{$row_color}" class="default" align="center" nowrap>
    -                        <a href="{$smarty.server.PHP_SELF}?cat=change_rank&id={$waitlist[i].faq_id}&rank=desc"><img src="{$rel_url}images/desc.gif" border="0"></a> {$waitlist[i].faq_rank}
    -                        <a href="{$smarty.server.PHP_SELF}?cat=change_rank&id={$waitlist[i].faq_id}&rank=asc"><img src="{$rel_url}images/asc.gif" border="0"></a>
    +                        <a href="{$smarty.server.PHP_SELF}?cat=change_rank&id={$waitlist[i].faq_id}&rank=desc&prj_id={$prj_id}"><img src="{$rel_url}images/desc.gif" border="0"></a> {$waitlist[i].faq_rank}
    +                        <a href="{$smarty.server.PHP_SELF}?cat=change_rank&id={$waitlist[i].faq_id}&rank=asc&prj_id={$prj_id}"><img src="{$rel_url}images/asc.gif" border="0"></a>
                           </td>
                           <td width="50%" bgcolor="{$row_color}" class="default">
    -                         <a class="link" href="{$smarty.server.PHP_SELF}?cat=edit&id={$waitlist[i].faq_id}" title="{t}update this entry{/t}">{$waitlist[i].faq_title|escape:"html"}</a>
    +                         <a class="link" href="{$smarty.server.PHP_SELF}?cat=edit&id={$waitlist[i].faq_id}&prj_id={$prj_id}" title="{t}update this entry{/t}">{$waitlist[i].faq_title|escape:"html"}</a>
                           </td>
                           {if $backend_uses_support_levels}
                           <td width="50%" bgcolor="{$row_color}" class="default">
    @@ -244,9 +254,10 @@
              <br /><br />
                     </td>
                   </tr>
    +         {/if}
     {* x *}

    -
    +{if $prj_id != -1}
                   <tr>
                     <td colspan="2" class="default">
                       <b>{t}Existing FAQ Entries:{/t}</b>
    @@ -275,6 +286,7 @@
                       <table border="0" width="100%" cellpadding="1" cellspacing="1">
                         <form onSubmit="javascript:return checkDelete(this);" method="post" action="{$smarty.server.PHP_SELF}">
                         <input type="hidden" name="cat" value="delete">
    +                    <input type="hidden" name="project" value="{$prj_id}">{* x *}
                         <tr>
                           <td width="4" bgcolor="{$cell_color}" nowrap><input type="button" value="{t}All{/t}" class="shortcut" onClick="javascript:toggleSelectAll(this.form, 'items[]');"></td>
                           <td bgcolor="{$cell_color}" class="default_white" align="center"> <b>{t}Rank{/t}</b> </td>
    @@ -288,11 +300,11 @@
                         <tr>
                           <td width="4" nowrap bgcolor="{$row_color}" align="center"><input type="checkbox" name="items[]" value="{$list[i].faq_id}"></td>
                           <td bgcolor="{$row_color}" class="default" align="center" nowrap>
    -                        <a href="{$smarty.server.PHP_SELF}?cat=change_rank&id={$list[i].faq_id}&rank=desc"><img src="{$rel_url}images/desc.gif" border="0"></a> {$list[i].faq_rank}
    -                        <a href="{$smarty.server.PHP_SELF}?cat=change_rank&id={$list[i].faq_id}&rank=asc"><img src="{$rel_url}images/asc.gif" border="0"></a>
    +                        <a href="{$smarty.server.PHP_SELF}?cat=change_rank&id={$list[i].faq_id}&rank=desc&prj_id={$prj_id}"><img src="{$rel_url}images/desc.gif" border="0"></a> {$list[i].faq_rank}
    +                        <a href="{$smarty.server.PHP_SELF}?cat=change_rank&id={$list[i].faq_id}&rank=asc&prj_id={$prj_id}"><img src="{$rel_url}images/asc.gif" border="0"></a>
                           </td>
                           <td width="50%" bgcolor="{$row_color}" class="default">
    -                         <a class="link" href="{$smarty.server.PHP_SELF}?cat=edit&id={$list[i].faq_id}" title="{t}update this entry{/t}">{$list[i].faq_title|escape:"html"}</a>
    +                         <a class="link" href="{$smarty.server.PHP_SELF}?cat=edit&id={$list[i].faq_id}&prj_id={$prj_id}" title="{t}update this entry{/t}">{$list[i].faq_title|escape:"html"}</a>
                           </td>
                           {if $backend_uses_support_levels}
                           <td width="50%" bgcolor="{$row_color}" class="default">
    @@ -319,6 +331,7 @@
                       </table>
                     </td>
                   </tr>
    +{/if}
                 </table>
               </td>
             </tr>
