### Add a timeout for outgoing smtp connections

A problem apparently quite common with eventum users is the outgoing email queue getting locked up.

One of the reasons of this happening is that there is no timeout set by default on the smpt connection (socket) that the php script in charge of sending email does, and if the connection with the mailserver is flaky, the said php script can hang forever (php.ini setting about max script execution time do not work in this case). If the stalled php script is killed by hand (eg. kill -9), it will not properly release a lock file it has acquired at startup, so that subsequent runs of the same script, eg. via a scheduled cron job will do nothing, and the outgoing mail queue will pile up messages until the admin resolves the problem by hand.

A cleaner solution involves properly using a timeout for smtp connections. This way, if the mail sending script times out during communication with the mail server and some messages are left unsent, the lock is properly released, and on the next run of the cron job everything will adjust by itself.

To enable this all that needs to be done is change the template of the general settings page:

    diff -u htdocs/eventum-2.0.1/templates/manage/general.tpl.html buffert.tpl.html
    --- htdocs/eventum-2.0.1/templates/manage/general.tpl.html      2007-04-23 08:10:06.000000000 +0200
    +++ buffert2.tpl.html   2007-05-11 23:58:06.000000000 +0200
    @@ -1,4 +1,3 @@
    -
           <table width="100%" bgcolor="{$cell_color}" border="0" cellspacing="0" cellpadding="1" align="center">
             <tr>
               <td>
    @@ -26,6 +25,12 @@
                           selectField(f, 'smtp[port]');
                           return false;
                       }
    +                  field = getFormElement(f, 'smtp[timeout]');
    +                  if ((!isWhitespace(field.value)) && (!isNumberOnly(field.value))) {
    +                      alert('{/literal}{t escape=js}Please enter the SMTP server timeout (or BLANK for no timeout).{/t}                         {literal}');
    +                      selectField(f, 'smtp[timeout]');
    +                      return false;
    +                  }
                       var field1 = getFormElement(f, 'smtp[auth]', 0);
                       var field2 = getFormElement(f, 'smtp[auth]', 1);
                       if ((!field1.checked) && (!field2.checked)) {
    @@ -333,6 +338,15 @@
                         </tr>
                         <tr>
                           <td width="100" class="default" align="right">
    +                        {t}Timeout (secs):{/t}
    +                      </td>
    +                      <td width="80%">
    +                        <input type="text" class="default" name="smtp[timeout]" size="5" value="{$setup.smtp.timeout}">
    +                        {include file="error_icon.tpl.html" field="smtp[timeout]"}
    +                      </td>
    +                    </tr>
    +                    <tr>
    +                      <td width="100" class="default" align="right">
                             {t}Requires Authentication?{/t} 
                           </td>
                           <td width="80%" class="default">
