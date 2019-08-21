This patch adds the possibility to specify a reminder repetition period. The reminder is repeated after the specified time (in minutes).

Use case example: a reminder is sent every 3 hours, if the issue status was not changed.

The following archives contain the patched files and the diff files.

-   [patch for eventum 2.2](http://medienhof.at/tmp/patch_reminder_2.2.tar.gz)
-   [patch for eventum 2.1.1](http://medienhof.at/tmp/patch_reminder_2.1.1.tar.gz)

Database Patch

```sql
    ALTER TABLE `eventum_reminder_action` ADD `rma_repetition` MEDIUMINT( 7 ) UNSIGNED DEFAULT NULL ;
    ALTER TABLE `eventum_reminder_triggered_action` ADD `rta_last_exec_date` DATETIME DEFAULT NULL ;
```

**Patches for eventum 2.2**

include/class.misc.php

```diff
    --- ../../eventum-2.2/include/class.misc.php    2009-01-12 21:14:39.000000000 +0100
    +++ ../include/class.misc.php   2009-05-07 15:30:30.000000000 +0200
    @@ -396,6 +396,28 @@
             return $input;
         }

    +    /**
    +     * Accepts a value and cleans it to only contain numeric values
    +     * If the the value is not valid (e.g. 0) the return value is NULL
    +     *
    +     * @access  public
    +     * @param   mixed $input The original input.
    +     * @return  mixed The input converted to an integer surrounded by '' or NULL
    +     */
    +    function escapeIntegerNull($input)
    +    {
    +        if (is_array($input)) {
    +            foreach ($input as $key => $value) {
    +                $input[$key] = Misc::escapeIntegerNull($value);
    +            }
    +        } else {
    +            settype($input, 'integer');
    +            if (!$input) $input = "NULL";
    +            else $input = "'".$input."'";
    +        }
    +        return $input;
    +    }
    +

         /**
          * Method used to prepare a set of fields and values for a boolean search
```

include/class.reminder_action.php

```diff
    --- ../../eventum-2.2/include/class.reminder_action.php 2009-01-12 21:14:39.000000000 +0100
    +++ ../include/class.reminder_action.php    2009-05-07 15:41:32.000000000 +0200
    @@ -201,7 +201,8 @@
                         rma_rank,
                         rma_alert_irc,
                         rma_alert_group_leader,
    -                    rma_boilerplate
    +                    rma_boilerplate,
    +                    rma_repetition
                      ) VALUES (
                         " . Misc::escapeInteger($_POST['rem_id']) . ",
                         " . Misc::escapeInteger($_POST['type']) . ",
    @@ -210,7 +211,8 @@
                         '" . Misc::escapeInteger($_POST['rank']) . "',
                         " . Misc::escapeInteger($_POST['alert_irc']) . ",
                         " . Misc::escapeInteger($_POST['alert_group_leader']) . ",
    -                    '" . Misc::escapeString($_POST['boilerplate']) . "'
    +                    '" . Misc::escapeString($_POST['boilerplate']) . "',
    +                    " . Misc::escapeIntegerNull($_POST['repetition']) . "
                      )";
             $res = $GLOBALS["db_api"]->dbh->query($stmt);
             if (PEAR::isError($res)) {
    @@ -314,7 +316,8 @@
                         rma_rmt_id=" . Misc::escapeInteger($_POST['type']) . ",
                         rma_alert_irc=" . Misc::escapeInteger($_POST['alert_irc']) . ",
                         rma_alert_group_leader=" . Misc::escapeInteger($_POST['alert_group_leader']) . ",
    -                    rma_boilerplate='" . Misc::escapeString($_POST['boilerplate']) . "'
    +                    rma_boilerplate='" . Misc::escapeString($_POST['boilerplate']) . "',
    +                    rma_repetition=" . Misc::escapeIntegerNull($_POST['repetition']) . "
                      WHERE
                         rma_id=" . Misc::escapeInteger($_POST['id']);
             $res = $GLOBALS["db_api"]->dbh->query($stmt);
    @@ -759,7 +762,7 @@
                     $mail = new Mail_API;
                     $mail->setTextBody($text_message);
                     $setup = $mail->getSMTPSettings();
    -                $mail->send($setup["from"], $address, "[#$issue_id] " . ev_gettext("Reminder") . ": " . $action['rma_title'], 0, $issue_id, 'reminder');
    +                $mail->send($setup["from"], $address, "[#$issue_id] " . ev_gettext("Reminder") . ": " . $data["iss_summary"] . " :: " . $action['rma_title'], 0, $issue_id, 'reminder');
                 }
             }
             // - eventum saves the day once again
    @@ -812,9 +815,10 @@
          * @access  public
          * @param   array $issues The list of issue IDs
          * @param   integer $rma_id The reminder action ID
    +     * @param   integer $age Ignore issues older than this value (in seconds)
          * @return  array The list of issue IDs
          */
    -    function getRepeatActions($issues, $rma_id)
    +    function getRepeatActions($issues, $rma_id, $age=0)
         {
             if (count($issues) == 0) {
                 return $issues;
    @@ -827,6 +831,12 @@
                         " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_triggered_action
                      WHERE
                         rta_iss_id IN (" . implode(', ', Misc::escapeInteger($issues)) . ")";
    +
    +        //if $age is set, ignore issues older than the $age value
    +        if ($age > 0) {
    +            $stmt .= " AND (UNIX_TIMESTAMP('" . Date_API::getCurrentDateGMT() . "') - IFNULL(UNIX_TIMESTAMP(rta_last_exec_date), 0) <= '". Misc::escapeInteger($age) ."')";
    +        }
    +
             $triggered_actions = $GLOBALS["db_api"]->dbh->getAssoc($stmt);
             if (PEAR::isError($triggered_actions)) {
                 Error_Handler::logError(array($triggered_actions->getMessage(), $triggered_actions->getDebugInfo()), __FILE__, __LINE__);
    @@ -869,7 +879,8 @@
                 $stmt = "UPDATE
                             " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_triggered_action
                          SET
    -                        rta_rma_id=$rma_id
    +                        rta_rma_id=$rma_id,
    +                        rta_last_exec_date='". Date_API::getCurrentDateGMT() ."'
                          WHERE
                             rta_iss_id=$issue_id";
             } else {
    @@ -877,10 +888,12 @@
                             " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_triggered_action
                          (
                             rta_iss_id,
    -                        rta_rma_id
    +                        rta_rma_id,
    +                        rta_last_exec_date
                          ) VALUES (
                             $issue_id,
    -                        $rma_id
    +                        $rma_id,
    +                        '". Date_API::getCurrentDateGMT() ."'
                          )";
             }
             $res = $GLOBALS["db_api"]->dbh->query($stmt);
```

misc/check_reminders.php

```diff
    --- ../../eventum-2.2/misc/check_reminders.php  2008-03-15 17:45:34.000000000 +0100
    +++ ../misc/check_reminders.php 2009-05-07 15:22:22.000000000 +0200
    @@ -88,7 +88,10 @@
             $issues = Reminder::getTriggeredIssues($reminders[$i], $conditions);
             // avoid repeating reminder actions, so get the list of issues
             // that were last triggered with this reminder action ID
    -        $repeat_issues = Reminder_Action::getRepeatActions($issues, $reminders[$i]['actions'][$y]['rma_id']);
    +        // check last execution time if user activated repetition
    +        $reminders[$i]['actions'][$y]['rma_repetition'] ? $age = $reminders[$i]['actions'][$y]['rma_repetition']*60 : $age = 0;
    +        $repeat_issues = Reminder_Action::getRepeatActions($issues, $reminders[$i]['actions'][$y]['rma_id'], $age);
    +
             if (count($repeat_issues) > 0) {
                 // add the repeated issues to the list of already triggered
                 // issues, so they get ignored for the next reminder actions

templates/manage/reminder_actions.tpl.html

    --- ../../eventum-2.2/templates/manage/reminder_actions.tpl.html    2008-09-04 23:16:21.000000000 +0200
    +++ ../templates/manage/reminder_actions.tpl.html   2009-05-07 13:05:34.000000000 +0200
    @@ -14,6 +14,9 @@
                       if (isWhitespace(f.rank.value)) {
                           errors[errors.length] = new Option('{/literal}{t escape=js}Rank{/t}{literal}', 'rank');
                       }
    +                  if (!isWhitespace(f.repetition.value) && !isNumberOnly(f.repetition.value)) {
    +                      errors[errors.length] = new Option('{/literal}{t escape=js}Please enter only integers on the repetition field.{/t}{literal}', 'repetition');
    +         }
                       // hack to make the multiple select box actually submit something
                       selectAllOptions(f, 'user_list[]');
                       return true;
    @@ -196,6 +199,16 @@
                     </td>
                   </tr>
                   <tr>
    +               <td width="120" bgcolor="{$cell_color}" class="default_white">
    +                 <b>{t}Repetition Period:{/t}</b>
    +               </td>
    +               <td bgcolor="{$light_color}">
    +                 <input type="text" size="10" class="default" name="repetition" value="{$info.rma_repetition}">
    +                 {include file="error_icon.tpl.html" field="repetition"}
    +                 <span class="small_default"><i>({t}minutes after reminder is reset and conditions are checked again{/t})</i></span>
    +               </td>
    +              </tr>
    +              <tr>
                     <td colspan="2" bgcolor="{$cell_color}" align="center">
                       {if $smarty.get.cat == 'edit'}
                       <input class="button" type="submit" value="{t}Update Action{/t}">
```
