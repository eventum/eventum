### Add a timeout for outgoing smtp connections 171

A problem apparently quite common with eventum users is the outgoing email queue getting locked up.

One of the reasons of this happening is that there is no timeout set by default on the smpt connection (socket) that the php script in charge of sending email does, and if the connection with the mailserver is flaky, the said php script can hang forever (php.ini setting about max script execution time do not work in this case). If the stalled php script is killed by hand (eg. kill -9), it will not properly release a lock file it has acquired at startup, so that subsequent runs of the same script, eg. via a scheduled cron job will do nothing, and the outgoing mail queue will pile up messages until the admin resolves the problem by hand.

A cleaner solution involves properly using a timeout for smtp connections. This way, if the mail sending script times out during communication with the mail server and some messages are left unsent, the lock is properly released, and on the next run of the cron job everything will adjust by itself.

To enable this all that needs to be done is change the template of the general settings page:

          <table width="100%" bgcolor="{$cell_color}" border="0" cellspacing="0" cellpadding="1" align="center">
            <tr>
              <td>
                <table bgcolor="#FFFFFF" width="100%" cellspacing="1" cellpadding="2" border="0">
                  {literal}
                  <script language="JavaScript">
                  <!--
                  function validateForm(f)
                  {
                      var field = getFormElement(f, 'smtp[from]');
                      if (isWhitespace(field.value)) {
                          alert('Please enter the sender address that will be used for all outgoing notification emails.');
                          selectField(f, 'smtp[from]');
                          return false;
                      }
                      field = getFormElement(f, 'smtp[host]');
                      if (isWhitespace(field.value)) {
                          alert('Please enter the SMTP server hostname.');
                          selectField(f, 'smtp[host]');
                          return false;
                      }
                      field = getFormElement(f, 'smtp[port]');
                      if ((isWhitespace(field.value)) || (!isNumberOnly(field.value))) {
                          alert('Please enter the SMTP server port number.');
                          selectField(f, 'smtp[port]');
                          return false;
                      }
                      field = getFormElement(f, 'smtp[timeout]');
                      if ((!isWhitespace(field.value)) && (!isNumberOnly(field.value))) {
                          alert('{/literal}Please enter the SMTP server timeout (or BLANK for no timeout).{literal}');
                          selectField(f, 'smtp[timeout]');
                          return false;
                      }
                      var field1 = getFormElement(f, 'smtp[auth]', 0);
                      var field2 = getFormElement(f, 'smtp[auth]', 1);
                      if ((!field1.checked) && (!field2.checked)) {
                          alert('Please indicate whether the SMTP server requires authentication or not.');
                          return false;
                      }
                      if (field1.checked) {
                          field = getFormElement(f, 'smtp[username]');
                          if (isWhitespace(field.value)) {
                              alert('Please enter the SMTP server username.');
                              selectField(f, 'smtp[username]');
                              return false;
                          }
                          field = getFormElement(f, 'smtp[password]');
                          if (isWhitespace(field.value)) {
                              alert('Please enter the SMTP server password.');
                              selectField(f, 'smtp[password]');
                              return false;
                          }
                      }
                      var field1 = getFormElement(f, 'smtp[save_outgoing_email]', 0);
                      var field2 = getFormElement(f, 'smtp[save_address]');
                      if ((field1.checked) && (!isEmail(field2.value))) {
                          alert('Please enter the email address of where copies of outgoing emails should be sent to.');
                          selectField(f, 'smtp[save_address]');
                          return false;
                      }
                      if ((!f.open_signup[0].checked) && (!f.open_signup[1].checked))  {
                          alert('Please choose whether the system should allow visitors to signup for new accounts or not.');
                          return false;
                      }
                      if (f.open_signup[0].checked) {
                          field = getFormElement(f, 'accounts_projects[]');
                          if (!hasOneSelected(f, 'accounts_projects[]')) {
                              alert('Please select the assigned projects for users that create their own accounts.');
                              selectField(f, 'accounts_projects[]');
                              return false;
                          }
                      }
                      field1 = getFormElement(f, 'email_routing[status]', 0);
                      if (field1.checked) {
                          field1 = getFormElement(f, 'email_routing[address_prefix]');
                          if (isWhitespace(field1.value)) {
                              alert('Please enter the email address prefix for the email routing interface.');
                              selectField(f, 'email_routing[address_prefix]');
                              return false;
                          }
                          field1 = getFormElement(f, 'email_routing[address_host]');
                          if (isWhitespace(field1.value)) {
                              alert('Please enter the email address hostname for the email routing interface.');
                              selectField(f, 'email_routing[address_host]');
                              return false;
                          }
                      }
                      if ((!f.scm_integration[0].checked) && (!f.scm_integration[1].checked))  {
                          alert('Please choose whether the SCM integration feature should be enabled or not.');
                          return false;
                      }
                      if (f.scm_integration[0].checked) {
                          field = getFormElement(f, 'checkout_url');
                          if (isWhitespace(field.value)) {
                              alert('Please enter the checkout page URL for your SCM integration tool.');
                              selectField(f, 'checkout_url');
                              return false;
                          }
                          field = getFormElement(f, 'diff_url');
                          if (isWhitespace(field.value)) {
                              alert('Please enter the diff page URL for your SCM integration tool.');
                              selectField(f, 'diff_url');
                              return false;
                          }
                      }
                      if ((!f.support_email[0].checked) && (!f.support_email[1].checked))  {
                          alert('Please choose whether the email integration feature should be enabled or not.');
                          return false;
                      }
                      if ((!f.daily_tips[0].checked) && (!f.daily_tips[1].checked))  {
                          alert('Please choose whether the daily tips feature should be enabled or not.');
                          return false;
                      }
                      return true;
                  }
                  function disableAuthFields(f, bool)
                  {
                      if (bool) {
                          var bgcolor = '#CCCCCC';
                      } else {
                          var bgcolor = '#FFFFFF';
                      }
                      var field = getFormElement(f, 'smtp[username]');
                      field.disabled = bool;
                      field.style.backgroundColor = bgcolor;
                      field = getFormElement(f, 'smtp[password]');
                      field.disabled = bool;
                      field.style.backgroundColor = bgcolor;
                  }
                  function checkDebugField(f)
                  {
                      var field = getFormElement(f, 'smtp[save_outgoing_email]');
                      if (field.checked) {
                          var bool = false;
                      } else {
                          var bool = true;
                      }
                      if (bool) {
                          var bgcolor = '#CCCCCC';
                      } else {
                          var bgcolor = '#FFFFFF';
                      }
                      field = getFormElement(f, 'smtp[save_address]');
                      field.disabled = bool;
                      field.style.backgroundColor = bgcolor;
                  }
                  function disableSCMFields(f, bool)
                  {
                      if (bool) {
                          var bgcolor = '#CCCCCC';
                      } else {
                          var bgcolor = '#FFFFFF';
                      }
                      var field = getFormElement(f, 'checkout_url');
                      field.disabled = bool;
                      field.style.backgroundColor = bgcolor;
                      field = getFormElement(f, 'diff_url');
                      field.disabled = bool;
                      field.style.backgroundColor = bgcolor;
                  }
                  function disableSignupFields(f, bool)
                  {
                      if (bool) {
                          var bgcolor = '#CCCCCC';
                      } else {
                          var bgcolor = '#FFFFFF';
                      }
                      var field = getFormElement(f, 'accounts_projects[]');
                      field.disabled = bool;
                      field.style.backgroundColor = bgcolor;
                      field = getFormElement(f, 'accounts_role');
                      field.disabled = bool;
                      field.style.backgroundColor = bgcolor;
                  }
                  function disableEmailRoutingFields(f, bool)
                  {
                      if (bool) {
                          var bgcolor = '#CCCCCC';
                      } else {
                          var bgcolor = '#FFFFFF';
                      }

                      var field = getFormElement(f, 'email_routing[address_prefix]');
                      field.disabled = bool;
                      field.style.backgroundColor = bgcolor;
                      field = getFormElement(f, 'email_routing[address_host]');
                      field.disabled = bool;
                      field.style.backgroundColor = bgcolor;
                      field = getFormElement(f, 'email_routing[host_alias]');
                      field.disabled = bool;
                      field.style.backgroundColor = bgcolor;
                      field = getFormElement(f, 'email_routing[warning][status]', 0);
                      field.disabled = bool;
                      field = getFormElement(f, 'email_routing[warning][status]', 1);
                      field.disabled = bool;
                  }
                  function disableNoteRoutingFields(f, bool)
                  {
                      if (bool) {
                          var bgcolor = '#CCCCCC';
                      } else {
                          var bgcolor = '#FFFFFF';
                      }
                      var field = getFormElement(f, 'note_routing[address_prefix]');
                      field.disabled = bool;
                      field.style.backgroundColor = bgcolor;
                      field = getFormElement(f, 'note_routing[address_host]');
                      field.disabled = bool;
                      field.style.backgroundColor = bgcolor;
                  }
                  function disableDraftRoutingFields(f, bool)
                  {
                      if (bool) {
                          var bgcolor = '#CCCCCC';
                      } else {
                          var bgcolor = '#FFFFFF';
                      }
                      var field = getFormElement(f, 'draft_routing[address_prefix]');
                      field.disabled = bool;
                      field.style.backgroundColor = bgcolor;
                      field = getFormElement(f, 'draft_routing[address_host]');
                      field.disabled = bool;
                      field.style.backgroundColor = bgcolor;
                  }
                  function disableErrorEmailFields(f, bool)
                  {
                      if (bool) {
                          var bgcolor = '#CCCCCC';
                      } else {
                          var bgcolor = '#FFFFFF';
                      }
                      var field = getFormElement(f, 'email_error[addresses]');
                      field.disabled = bool;
                      field.style.backgroundColor = bgcolor;
                  }
                  function disableReminderEmailFields(f, bool)
                  {
                      if (bool) {
                          var bgcolor = '#CCCCCC';
                      } else {
                          var bgcolor = '#FFFFFF';
                      }
                      var field = getFormElement(f, 'email_reminder[addresses]');
                      field.disabled = bool;
                      field.style.backgroundColor = bgcolor;
                  }
                  function toggleSubjectBasedRouting(f, enabled)
                  {

                      var email_routing_enabled = getFormElement(f, 'email_routing[status]', 0);
                      email_routing_enabled.disabled = enabled;
                      if ((enabled != true) && (email_routing_enabled.checked != true)) {
                          disableEmailRoutingFields(f, true);
                      } else {
                          disableEmailRoutingFields(f, enabled);
                      }
                      getFormElement(f, 'email_routing[status]', 1).disabled = enabled;

                      var note_routing_enabled = getFormElement(f, 'note_routing[status]', 0);
                      note_routing_enabled.disabled = enabled;
                      if ((enabled != true) && (note_routing_enabled.checked != true)) {
                          disableNoteRoutingFields(f, true);
                      } else {
                          disableNoteRoutingFields(f, enabled);
                      }
                      getFormElement(f, 'note_routing[status]', 1).disabled = enabled;
                  }
                  //-->
                  </script>
                  {/literal}
                  <form name="general_setup_form" onSubmit="javascript:return validateForm(this);" method="post" action="{$smarty.server.PHP_SELF}">
                  <input type="hidden" name="cat" value="update">
                  <tr>
                    <td colspan="2" class="default">
                      <b>General Setup</b>
                    </td>
                  </tr>
                  {if $result != ""}
                  <tr>
                    <td colspan="2" bgcolor="{$cell_color}" align="center" class="error">
                      {if $result == -1}
                        ERROR: The system doesn't have the appropriate permissions to
                        create the configuration file in the setup directory
                        ({$app_setup_path}). Please contact your local system
                        administrator and ask for write privileges on the provided path.
                      {elseif $result == -2}
                        ERROR: The system doesn't have the appropriate permissions to
                        update the configuration file in the setup directory
                        ({$app_setup_file}). Please contact your local system
                        administrator and ask for write privileges on the provided filename.
                      {elseif $result == 1}
                        Thank you, the setup information was saved successfully.
                      {/if}
                    </td>
                  </tr>
                  {/if}
                  <tr>
                    <td width="120" bgcolor="{$cell_color}" class="default_white">
                      <b>Tool Caption:</b>
                    </td>
                    <td bgcolor="{$light_color}">
                      <input type="text" class="default" name="tool_caption" size="50" value="{$setup.tool_caption|escape:"html"}">
                    </td>
                  </tr>
                  <tr>
                    <td width="120" bgcolor="{$cell_color}" class="default_white">
                      <b>SMTP (Outgoing Email) Settings:</b>
                    </td>
                    <td bgcolor="{$light_color}" class="default">
                      <table>
                        <tr>
                          <td width="100" class="default" align="right">
                            Sender: 
                          </td>
                          <td width="80%">
                            <input type="text" class="default" name="smtp[from]" size="30" value="{$setup.smtp.from|escape:"html"}">
                            {include file="error_icon.tpl.html" field="smtp[from]"}
                          </td>
                        </tr>
                        <tr>
                          <td width="100" class="default" align="right">
                            Hostname: 
                          </td>
                          <td width="80%">
                            <input type="text" class="default" name="smtp[host]" size="30" value="{$setup.smtp.host|escape:"html"}">
                            {include file="error_icon.tpl.html" field="smtp[host]"}
                          </td>
                        </tr>
                        <tr>
                          <td width="100" class="default" align="right">
                            Port: 
                          </td>
                          <td width="80%">
                            <input type="text" class="default" name="smtp[port]" size="5" value="{$setup.smtp.port}">
                            {include file="error_icon.tpl.html" field="smtp[port]"}
                          </td>
                        </tr>
                        <tr>
                          <td width="100" class="default" align="right">
                            Timeout (secs): 
                          </td>
                          <td width="80%">
                            <input type="text" class="default" name="smtp[timeout]" size="5" value="{$setup.smtp.timeout}">
                            {include file="error_icon.tpl.html" field="smtp[timeout]"}
                          </td>
                        </tr>
                        <tr>
                          <td width="100" class="default" align="right">
                            Requires Authentication? 
                          </td>
                          <td width="80%" class="default">
                            <input type="radio" name="smtp[auth]" value="1" {if $setup.smtp.auth}checked{/if} onClick="javascript:disableAuthFields(this.form, false);">
                            <a id="link" class="link" href="javascript:void(null);" onClick="javascript:checkRadio('general_setup_form', 'smtp[auth]', 0);disableAuthFields(getForm('general_setup_form'), false);">Yes</a>  
                            <input type="radio" name="smtp[auth]" value="0" {if not $setup.smtp.auth}checked{/if} onClick="javascript:disableAuthFields(this.form, true);">
                            <a id="link" class="link" href="javascript:void(null);" onClick="javascript:checkRadio('general_setup_form', 'smtp[auth]', 1);disableAuthFields(getForm('general_setup_form'), true);">No</a>
                          </td>
                        </tr>
                        <tr>
                          <td width="100" class="default" align="right">
                            Username: 
                          </td>
                          <td width="80%">
                            <input type="text" class="default" name="smtp[username]" size="20" value="{$setup.smtp.username|escape:"html"}">
                            {include file="error_icon.tpl.html" field="smtp[username]"}
                          </td>
                        </tr>
                        <tr>
                          <td width="100" class="default" align="right">
                            Password: 
                          </td>
                          <td width="80%">
                            <input type="password" class="default" name="smtp[password]" size="20" value="{$setup.smtp.password|escape:"html"}">
                            {include file="error_icon.tpl.html" field="smtp[password]"}
                          </td>
                        </tr>
                        <tr>
                          <td colspan="2" class="default">
                                   
                            <input type="checkbox" name="smtp[save_outgoing_email]" value="yes" {if $setup.smtp.save_outgoing_email == 'yes'}checked{/if} onClick="javascript:checkDebugField(this.form);">
                            <a id="link" class="link" href="javascript:void(null);" onClick="javascript:toggleCheckbox('general_setup_form', 'smtp[save_outgoing_email]');checkDebugField(getForm('general_setup_form'));">Save a Copy of Every Outgoing Issue Notification Email</a>
                          </td>
                        </tr>
                        <tr>
                          <td width="100" class="default" align="right">
                            Email Address to Send Saved Messages: 
                          </td>
                          <td width="80%">
                            <input type="text" name="smtp[save_address]" class="default" size="30" value="{$setup.smtp.save_address}">
                            {include file="error_icon.tpl.html" field="smtp[save_address]"}
                          </td>
                        </tr>
                      </table>
                    </td>
                  </tr>
                  <tr>
                    <td width="120" bgcolor="{$cell_color}" class="default_white">
                      <b>Open Account Signup:</b>
                    </td>
                    <td bgcolor="{$light_color}" class="default">
                      <table>
                        <tr>
                          <td colspan="2" class="default_white">
                            <input type="radio" name="open_signup" value="enabled" {if $setup.open_signup == 'enabled'}checked{/if} onClick="javascript:disableSignupFields(this.form, false);">
                            <a id="link" class="link" href="javascript:void(null);" onClick="javascript:checkRadio('general_setup_form', 'open_signup', 0);disableSignupFields(getForm('general_setup_form'), false);">Enabled</a>  
                            <input type="radio" name="open_signup" value="disabled" {if not $setup.open_signup == 'enabled'}checked{/if} onClick="javascript:disableSignupFields(this.form, true);">
                            <a id="link" class="link" href="javascript:void(null);" onClick="javascript:checkRadio('general_setup_form', 'open_signup', 1);disableSignupFields(getForm('general_setup_form'), true);">Disabled</a>
                          </td>
                        </tr>
                        <tr>
                          <td width="100" class="default" align="right">
                            Assigned Projects: 
                          </td>
                          <td width="80%">
                            <select name="accounts_projects[]" multiple size="3" class="default">
                            {html_options options=$project_list selected=$setup.accounts_projects}
                            </select>
                            {include file="error_icon.tpl.html" field="accounts_projects[]"}
                          </td>
                        </tr>
                        <tr>
                          <td width="100" class="default" align="right">
                            Assigned Role: 
                          </td>
                          <td width="80%">
                            <select name="accounts_role" class="default">
                            {html_options options=$user_roles selected=$setup.accounts_role}
                            </select>
                            {include file="error_icon.tpl.html" field="accounts_role"}
                          </td>
                        </tr>
                      </table>
                    </td>
                  </tr>
                  <tr>
                    <td width="120" bgcolor="{$cell_color}" class="default_white">
                      <b>Subject Based Routing:</b>
                    </td>
                    <td bgcolor="{$light_color}" class="default">
                      <table>
                        <tr>
                          <td>
                            <input id="subject_based_routing_enabled" type="radio" name="subject_based_routing[status]" value="enabled" {if $setup.subject_based_routing.status == 'enabled'}checked{/if} onChange="javascript:toggleSubjectBasedRouting(this.form, true);">
                            <label for="subject_based_routing_enabled" class="default">Enabled</label>  
                            <input id="subject_based_routing_disabled" type="radio" name="subject_based_routing[status]" value="disabled" {if $setup.subject_based_routing.status != 'enabled'}checked{/if} onClick="javascript:toggleSubjectBasedRouting(this.form, false);">
                            <label for="subject_based_routing_disabled" class="default">Disabled</label><br />
                            <span class="small_default">If enabled, Eventum will look in the subject line of incoming notes/emails to determine which issue they should be associated with.</span><br />
                          </td>
                        </tr>
                      </td>
                    </table>
                  </tr>
                  <tr>
                    <td width="120" bgcolor="{$cell_color}" class="default_white">
                      <b>Email Recipient Type Flag:</b>
                    </td>
                    <td bgcolor="{$light_color}" class="default">
                      <table>
                        <tr>
                          <td width="100" class="default" align="right">
                            Recipient Type Flag: 
                          </td>
                          <td>
                            <input class="default" type="text" name="email_routing[recipient_type_flag]" value="{$setup.email_routing.recipient_type_flag|escape:"html"}">
                            <span class="small_default">(This will be included in the From address of all emails sent by Eventum)</span><br />
                            <span class="default">
                            <input type="radio" name="email_routing[flag_location]" value="before" {if $setup.email_routing.flag_location == 'before'}checked{/if}>
                            <a id="link" class="link" href="javascript:void(null);" onClick="javascript:checkRadio('general_setup_form', 'email_routing[flag_location]', 0);">Before Sender Name</a>  
                            <input type="radio" name="email_routing[flag_location]" value="after" {if $setup.email_routing.flag_location != 'before'}checked{/if}>
                            <a id="link" class="link" href="javascript:void(null);" onClick="javascript:checkRadio('general_setup_form', 'email_routing[flag_location]', 1);">After Sender Name</a>
                            </span>
                          </td>
                        </tr>
                      </td>
                    </table>
                  </tr>
                  <tr>
                    <td width="120" bgcolor="{$cell_color}" class="default_white">
                      <b>Email Routing Interface:</b>
                    </td>
                    <td bgcolor="{$light_color}" class="default">
                      <table>
                        <tr>
                          <td colspan="2" class="default_white">
                            <input type="radio" name="email_routing[status]" value="enabled" {if $setup.email_routing.status == 'enabled'}checked{/if} onClick="javascript:disableEmailRoutingFields(this.form, false);">
                            <a id="link" class="link" href="javascript:void(null);" onClick="javascript:checkRadio('general_setup_form', 'email_routing[status]', 0);disableEmailRoutingFields(getForm('general_setup_form'), false);">Enabled</a>  
                            <input type="radio" name="email_routing[status]" value="disabled" {if $setup.email_routing.status != 'enabled'}checked{/if} onClick="javascript:disableEmailRoutingFields(this.form, true);">
                            <a id="link" class="link" href="javascript:void(null);" onClick="javascript:checkRadio('general_setup_form', 'email_routing[status]', 1);disableEmailRoutingFields(getForm('general_setup_form'), true);">Disabled</a>
                          </td>
                        </tr>
                        <tr>
                          <td width="100" class="default" align="right">
                            Email Address Prefix: 
                          </td>
                          <td width="80%">
                            <input type="text" name="email_routing[address_prefix]" value="{if $setup.email_routing.address_prefix}{$setup.email_routing.address_prefix}{else}issue_{/if}" class="default">
                            {include file="error_icon.tpl.html" field="email_routing[address_prefix]"}
                            <span class="small_default">(i.e. <b>issue_</b>51@example.com)</span>
                          </td>
                        </tr>
                        <tr>
                          <td width="100" class="default" align="right">
                            Address Hostname: 
                          </td>
                          <td width="80%">
                            <input type="text" name="email_routing[address_host]" class="default" value="{$setup.email_routing.address_host}">
                            {include file="error_icon.tpl.html" field="email_routing[address_host]"}
                            <span class="small_default">(i.e. issue_51@<b>example.com</b>)</span>
                          </td>
                        </tr>
                        <tr>
                          <td width="100" class="default" align="right">
                            Host Alias: 
                          </td>
                          <td width="80%">
                            <input type="text" name="email_routing[host_alias]" class="default" value="{$setup.email_routing.host_alias}">
                            {include file="error_icon.tpl.html" field="email_routing[host_alias]"}
                            <span class="small_default">(Alternate domains that point to 'Address Hostname')</span>
                          </td
                        </tr>
                        <tr>
                          <td width="100" class="default" align="right">
                            Warn Users Whether They Can Send Emails to Issue: 
                          </td>
                          <td width="80%" class="default">
                            <input type="radio" name="email_routing[warning][status]" value="enabled" {if $setup.email_routing.warning.status == 'enabled'}checked{/if} onClick="javascript:disableWarningFields(this.form, false);">
                            <a id="link" class="link" href="javascript:void(null);" onClick="javascript:checkRadio('general_setup_form', 'email_routing[warning][status]', 0);">Yes</a>  
                            <input type="radio" name="email_routing[warning][status]" value="disabled" {if $setup.email_routing.warning.status != 'enabled'}checked{/if} onClick="javascript:disableWarningFields(this.form, true);">
                            <a id="link" class="link" href="javascript:void(null);" onClick="javascript:checkRadio('general_setup_form', 'email_routing[warning][status]', 1);">No</a>
                          </td>
                        </tr>
                      </table>
                    </td>
                  </tr>
                  <tr>
                    <td width="120" bgcolor="{$cell_color}" class="default_white">
                      <b>Note Recipient Type Flag:</b>
                    </td>
                    <td bgcolor="{$light_color}" class="default">
                      <table>
                        <tr>
                          <td width="100" class="default" align="right">
                            Recipient Type Flag: 
                          </td>
                          <td>
                            <input class="default" type="text" name="note_routing[recipient_type_flag]" value="{$setup.note_routing.recipient_type_flag|escape:"html"}">
                            <span class="small_default">(This will be included in the From address of all notes sent by Eventum)</span><br />
                            <span class="default">
                            <input type="radio" name="note_routing[flag_location]" value="before" {if $setup.note_routing.flag_location == 'before'}checked{/if}>
                            <a id="link" class="link" href="javascript:void(null);" onClick="javascript:checkRadio('general_setup_form', 'note_routing[flag_location]', 0);">Before Sender Name</a>  
                            <input type="radio" name="note_routing[flag_location]" value="after" {if $setup.note_routing.flag_location != 'before'}checked{/if}>
                            <a id="link" class="link" href="javascript:void(null);" onClick="javascript:checkRadio('general_setup_form', 'note_routing[flag_location]', 1);">After Sender Name</a>
                            </span>
                          </td>
                        </tr>
                      </table>
                    </td>
                  </tr>
                  <tr>
                    <td width="120" bgcolor="{$cell_color}" class="default_white">
                      <b>Internal Note Routing Interface:</b>
                    </td>
                    <td bgcolor="{$light_color}" class="default">
                      <table>
                        <tr>
                          <td colspan="2" class="default_white">
                            <input type="radio" name="note_routing[status]" value="enabled" {if $setup.note_routing.status == 'enabled'}checked{/if} onClick="javascript:disableNoteRoutingFields(this.form, false);">
                            <a id="link" class="link" href="javascript:void(null);" onClick="javascript:checkRadio('general_setup_form', 'note_routing[status]', 0);disableNoteRoutingFields(getForm('general_setup_form'), false);">Enabled</a>  
                            <input type="radio" name="note_routing[status]" value="disabled" {if $setup.note_routing.status != 'enabled'}checked{/if} onClick="javascript:disableNoteRoutingFields(this.form, true);">
                            <a id="link" class="link" href="javascript:void(null);" onClick="javascript:checkRadio('general_setup_form', 'note_routing[status]', 1);disableNoteRoutingFields(getForm('general_setup_form'), true);">Disabled</a>
                          </td>
                        </tr>
                        <tr>
                          <td width="100" class="default" align="right">
                            Note Address Prefix: 
                          </td>
                          <td width="80%">
                            <input type="text" name="note_routing[address_prefix]" value="{if $setup.note_routing.address_prefix}{$setup.note_routing.address_prefix}{else}note_{/if}" class="default">
                            {include file="error_icon.tpl.html" field="note_routing[address_prefix]"}
                            <span class="small_default">(i.e. <b>note_</b>51@example.com)</span>
                          </td>
                        </tr>
                        <tr>
                          <td width="100" class="default" align="right">
                            Address Hostname: 
                          </td>
                          <td width="80%">
                            <input type="text" name="note_routing[address_host]" class="default" value="{$setup.note_routing.address_host}">
                            {include file="error_icon.tpl.html" field="note_routing[address_host]"}
                            <span class="small_default">(i.e. note_51@<b>example.com</b>)</span>
                          </td>
                        </tr>
                      </table>
                    </td>
                  </tr>
                  <tr>
                    <td width="120" bgcolor="{$cell_color}" class="default_white">
                      <b>Email Draft Interface:</b>
                    </td>
                    <td bgcolor="{$light_color}" class="default">
                      <table>
                        <tr>
                          <td colspan="2" class="default_white">
                            <input type="radio" name="draft_routing[status]" value="enabled" {if $setup.draft_routing.status == 'enabled'}checked{/if} onClick="javascript:disableDraftRoutingFields(this.form, false);">
                            <a id="link" class="link" href="javascript:void(null);" onClick="javascript:checkRadio('general_setup_form', 'draft_routing[status]', 0);disableDraftRoutingFields(getForm('general_setup_form'), false);">Enabled</a>  
                            <input type="radio" name="draft_routing[status]" value="disabled" {if $setup.draft_routing.status != 'enabled'}checked{/if} onClick="javascript:disableDraftRoutingFields(this.form, true);">
                            <a id="link" class="link" href="javascript:void(null);" onClick="javascript:checkRadio('general_setup_form', 'draft_routing[status]', 1);disableDraftRoutingFields(getForm('general_setup_form'), true);">Disabled</a>
                          </td>
                        </tr>
                        <tr>
                          <td width="100" class="default" align="right">
                            Draft Address Prefix: 
                          </td>
                          <td width="80%">
                            <input type="text" name="draft_routing[address_prefix]" value="{if $setup.draft_routing.address_prefix}{$setup.draft_routing.address_prefix}{else}draft_{/if}" class="default">
                            {include file="error_icon.tpl.html" field="draft_routing[address_prefix]"}
                            <span class="small_default">(i.e. <b>draft_</b>51@example.com)</span>
                          </td>
                        </tr>
                        <tr>
                          <td width="100" class="default" align="right">
                            Address Hostname: 
                          </td>
                          <td width="80%">
                            <input type="text" name="draft_routing[address_host]" class="default" value="{$setup.draft_routing.address_host}">
                            {include file="error_icon.tpl.html" field="draft_routing[address_host]"}
                            <span class="small_default">(i.e. draft_51@<b>example.com</b>)</span>
                          </td>
                        </tr>
                      </table>
                    </td>
                  </tr>
                  <tr>
                    <td width="120" bgcolor="{$cell_color}" class="default_white">
                      <b>SCM <br />Integration:</b> {include file="help_link.tpl.html" topic="scm_integration"}
                    </td>
                    <td bgcolor="{$light_color}" class="default">
                      <table>
                        <tr>
                          <td colspan="2" class="default_white">
                            <input type="radio" name="scm_integration" value="enabled" {if $setup.scm_integration == 'enabled'}checked{/if} onClick="javascript:disableSCMFields(this.form, false);">
                            <a id="link" class="link" href="javascript:void(null);" onClick="javascript:checkRadio('general_setup_form', 'scm_integration', 0);disableSCMFields(getForm('general_setup_form'), false);">Enabled</a>  
                            <input type="radio" name="scm_integration" value="disabled" {if not $setup.scm_integration == 'enabled'}checked{/if} onClick="javascript:disableSCMFields(this.form, true);">
                            <a id="link" class="link" href="javascript:void(null);" onClick="javascript:checkRadio('general_setup_form', 'scm_integration', 1);disableSCMFields(getForm('general_setup_form'), true);">Disabled</a>
                          </td>
                        </tr>
                        <tr>
                          <td width="100" class="default" align="right">
                            Checkout Page: 
                          </td>
                          <td width="80%">
                            <input type="text" class="default" name="checkout_url" size="50" value="{$setup.checkout_url|escape:"html"}">
                            {include file="error_icon.tpl.html" field="checkout_url"}
                          </td>
                        </tr>
                        <tr>
                          <td width="100" class="default" align="right">
                            Diff Page: 
                          </td>
                          <td width="80%">
                            <input type="text" class="default" name="diff_url" size="50" value="{$setup.diff_url|escape:"html"}">
                            {include file="error_icon.tpl.html" field="diff_url"}
                          </td>
                        </tr>
                      </table>
                    </td>
                  </tr>
                  <tr>
                    <td width="120" bgcolor="{$cell_color}" class="default_white">
                      <b>Email Integration Feature:</b>
                    </td>
                    <td bgcolor="{$light_color}" class="default">
                      <input type="radio" name="support_email" value="enabled" {if $setup.support_email == 'enabled'}checked{/if}>
                      <a id="link" class="link" href="javascript:void(null);" onClick="javascript:checkRadio('general_setup_form', 'support_email', 0);">Enabled</a>  
                      <input type="radio" name="support_email" value="disabled" {if $setup.support_email != 'enabled'}checked{/if}>
                      <a id="link" class="link" href="javascript:void(null);" onClick="javascript:checkRadio('general_setup_form', 'support_email', 1);">Disabled</a>
                    </td>
                  </tr>
                  <tr>
                    <td width="120" bgcolor="{$cell_color}" class="default_white">
                      <b>Daily Tips:</b>
                    </td>
                    <td bgcolor="{$light_color}" class="default">
                      <input type="radio" name="daily_tips" value="enabled" {if $setup.daily_tips == 'enabled'}checked{/if}>
                      <a id="link" class="link" href="javascript:void(null);" onClick="javascript:checkRadio('general_setup_form', 'daily_tips', 0);">Enabled</a>  
                      <input type="radio" name="daily_tips" value="disabled" {if $setup.daily_tips != 'enabled'}checked{/if}>
                      <a id="link" class="link" href="javascript:void(null);" onClick="javascript:checkRadio('general_setup_form', 'daily_tips', 1);">Disabled</a>
                    </td>
                  </tr>
                  <tr>
                    <td width="120" bgcolor="{$cell_color}" class="default_white">
                      <b>Email Spell Checker:</b>
                    </td>
                    <td bgcolor="{$light_color}">
                      <span class="default">
                      <input type="radio" name="spell_checker" value="enabled" {if $setup.spell_checker == 'enabled'}checked{/if}>
                      <a id="link" class="link" href="javascript:void(null);" onClick="javascript:checkRadio('general_setup_form', 'spell_checker', 0);">Enabled</a>  
                      <input type="radio" name="spell_checker" value="disabled" {if $setup.spell_checker != 'enabled'}checked{/if}>
                      <a id="link" class="link" href="javascript:void(null);" onClick="javascript:checkRadio('general_setup_form', 'spell_checker', 1);">Disabled</a></span>
                        <span class="small_default">(requires <a target="_aspell" class="link" href="http://aspell.sourceforge.net/">aspell</a> installed in your server)</span>
                    </td>
                  </tr>
                  <tr>
                    <td width="120" bgcolor="{$cell_color}" class="default_white">
                      <b>IRC Notifications:</b>
                    </td>
                    <td bgcolor="{$light_color}" class="default">
                      <input type="radio" name="irc_notification" value="enabled" {if $setup.irc_notification == 'enabled'}checked{/if}>
                      <a id="link" class="link" href="javascript:void(null);" onClick="javascript:checkRadio('general_setup_form', 'irc_notification', 0);">Enabled</a>  
                      <input type="radio" name="irc_notification" value="disabled" {if $setup.irc_notification != 'enabled'}checked{/if}>
                      <a id="link" class="link" href="javascript:void(null);" onClick="javascript:checkRadio('general_setup_form', 'irc_notification', 1);">Disabled</a>
                    </td>
                  </tr>
                  <tr>
                    <td width="120" bgcolor="{$cell_color}" class="default_white">
                      <b>Allow Un-Assigned Issues?</b>
                    </td>
                    <td bgcolor="{$light_color}" class="default">
                      <input type="radio" name="allow_unassigned_issues" value="yes" {if $setup.allow_unassigned_issues == 'yes'}checked{/if}>
                      <a id="link" class="link" href="javascript:void(null);" onClick="javascript:checkRadio('general_setup_form', 'allow_unassigned_issues', 0);">Yes</a>  
                      <input type="radio" name="allow_unassigned_issues" value="no" {if $setup.allow_unassigned_issues != 'yes'}checked{/if}>
                      <a id="link" class="link" href="javascript:void(null);" onClick="javascript:checkRadio('general_setup_form', 'allow_unassigned_issues', 1);">No</a>
                    </td>
                  </tr>
                  <tr>
                    <td width="120" bgcolor="{$cell_color}" class="default_white">
                      <b>Default Options for Notifications:</b>
                    </td>
                    <td bgcolor="{$light_color}" class="default">
                      <input type="checkbox" name="update" {if $setup.update}checked{/if} value="1"> <a id="link" class="link" href="javascript:void(null);" onClick="javascript:toggleCheckbox('general_setup_form', 'update');">Issues are Updated</a><br />
                      <input type="checkbox" name="closed" {if $setup.closed}checked{/if} value="1"> <a id="link" class="link" href="javascript:void(null);" onClick="javascript:toggleCheckbox('general_setup_form', 'closed');">Issues are Closed</a><br />
                      <input type="checkbox" name="emails" {if $setup.emails}checked{/if} value="1"> <a id="link" class="link" href="javascript:void(null);" onClick="javascript:toggleCheckbox('general_setup_form', 'emails');">Emails are Associated</a><br />
                      <input type="checkbox" name="files" {if $setup.files}checked{/if} value="1"> <a id="link" class="link" href="javascript:void(null);" onClick="javascript:toggleCheckbox('general_setup_form', 'files');">Files are Attached</a>
                    </td>
                  </tr>
                  <tr>
                    <td width="120" bgcolor="{$cell_color}" class="default_white">
                      <b>Email Reminder System Status Information:</b>
                    </td>
                    <td bgcolor="{$light_color}" class="default">
                      <table>
                        <tr>
                          <td colspan="2" class="default_white">
                            <input type="radio" name="email_reminder[status]" value="enabled" {if $setup.email_reminder.status == 'enabled'}checked{/if} onClick="javascript:disableReminderEmailFields(getForm('general_setup_form'), false);">
                            <a id="link" class="link" href="javascript:void(null);" onClick="javascript:checkRadio('general_setup_form', 'email_reminder[status]', 0);disableReminderEmailFields(getForm('general_setup_form'), false);">Enabled</a>  
                            <input type="radio" name="email_reminder[status]" value="disabled" {if $setup.email_reminder.status != 'enabled'}checked{/if} onClick="javascript:disableReminderEmailFields(getForm('general_setup_form'), true);">
                            <a id="link" class="link" href="javascript:void(null);" onClick="javascript:checkRadio('general_setup_form', 'email_reminder[status]', 1);disableReminderEmailFields(getForm('general_setup_form'), true);">Disabled</a>
                          </td>
                        </tr>
                        <tr>
                          <td width="100" class="default" align="right">
                            Email Addresses To Send Information To: 
                          </td>
                          <td width="80%">
                            <input class="default" type="text" name="email_reminder[addresses]" value="{$setup.email_reminder.addresses|escape:"html"}" size="50">
                            <span class="small_default">(separate multiple addresses with commas)</span>
                          </td>
                        </tr>
                      </table>
                    </td>
                  </tr>
                  <tr>
                    <td width="120" bgcolor="{$cell_color}" class="default_white">
                      <b>Email Error Logging System:</b>
                    </td>
                    <td bgcolor="{$light_color}" class="default">
                      <table>
                        <tr>
                          <td colspan="2" class="default_white">
                            <input type="radio" name="email_error[status]" value="enabled" {if $setup.email_error.status == 'enabled'}checked{/if} onClick="javascript:disableErrorEmailFields(getForm('general_setup_form'), false);">
                            <a id="link" class="link" href="javascript:void(null);" onClick="javascript:checkRadio('general_setup_form', 'email_error[status]', 0);disableErrorEmailFields(getForm('general_setup_form'), false);">Enabled</a>  
                            <input type="radio" name="email_error[status]" value="disabled" {if $setup.email_error.status != 'enabled'}checked{/if} onClick="javascript:disableErrorEmailFields(getForm('general_setup_form'), true);">
                            <a id="link" class="link" href="javascript:void(null);" onClick="javascript:checkRadio('general_setup_form', 'email_error[status]', 1);disableErrorEmailFields(getForm('general_setup_form'), true);">Disabled</a>
                          </td>
                        </tr>
                        <tr>
                          <td width="100" class="default" align="right">
                            Email Addresses To Send Errors To: 
                          </td>
                          <td width="80%">
                            <input class="default" type="text" name="email_error[addresses]" value="{$setup.email_error.addresses|escape:"html"}" size="50">
                            <span class="small_default">(separate multiple addresses with commas)</span>
                          </td>
                        </tr>
                      </table>
                    </td>
                  </tr>
                  <tr>
                    <td colspan="2" bgcolor="{$cell_color}" align="center">
                      <input class="button" type="submit" value="Update Setup">
                      <input class="button" type="reset" value="Reset">
                    </td>
                  </tr>
                  </form>
                </table>
              </td>
            </tr>
          </table>
          {literal}
          <script language="JavaScript">
          <!--
          window.onload = setDisabledFields;
          function setDisabledFields()
          {
              var f = getForm('general_setup_form');
              var field1 = getFormElement(f, 'smtp[auth]', 0);
              if (field1.checked) {
                  disableAuthFields(f, false);
              } else {
                  disableAuthFields(f, true);
              }
              checkDebugField(f);
              if (f.scm_integration[0].checked) {
                  disableSCMFields(f, false);
              } else {
                  f.scm_integration[1].checked = true;
                  disableSCMFields(f, true);
              }
              if (f.open_signup[0].checked) {
                  disableSignupFields(f, false);
              } else {
                  f.open_signup[1].checked = true;
                  disableSignupFields(f, true);
              }
              field1 = getFormElement(f, 'email_routing[status]', 0);
              var field2 = getFormElement(f, 'email_routing[status]', 1);
              if (field1.checked) {
                  disableEmailRoutingFields(f, false);
              } else {
                  field2.checked = true;
                  disableEmailRoutingFields(f, true);
              }
              field1 = getFormElement(f, 'note_routing[status]', 0);
              field2 = getFormElement(f, 'note_routing[status]', 1);
              if (field1.checked) {
                  disableNoteRoutingFields(f, false);
              } else {
                  field2.checked = true;
                  disableNoteRoutingFields(f, true);
              }
              field1 = getFormElement(f, 'draft_routing[status]', 0);
              field2 = getFormElement(f, 'draft_routing[status]', 1);
              if (field1.checked) {
                  disableDraftRoutingFields(f, false);
              } else {
                  field2.checked = true;
                  disableDraftRoutingFields(f, true);
              }
              field1 = getFormElement(f, 'email_reminder[status]', 0);
              field2 = getFormElement(f, 'email_reminder[status]', 1);
              if (field1.checked) {
                  disableReminderEmailFields(f, false);
              } else {
                  field2.checked = true;
                  disableReminderEmailFields(f, true);
              }
              field1 = getFormElement(f, 'email_error[status]', 0);
              field2 = getFormElement(f, 'email_error[status]', 1);
              if (field1.checked) {
                  disableErrorEmailFields(f, false);
              } else {
                  field2.checked = true;
                  disableErrorEmailFields(f, true);
              }
              toggleSubjectBasedRouting(f, getFormElement(f, 'subject_based_routing[status]', 0).checked);
          }
          //-->
          </script>
          {/literal}
