### Open Source Project Mod

by Bryan Petty ([Tierra](User:Tierra "wikilink") 22:38, 30 Aug 2007 (CEST))

These are some bare minimum instructions for using this Open Source mod to Eventum for allowing anonymous (non-registered) user access to tracker issues. If you have any questions, direct them to the Eventum mailing list. Even if I don't see your post myself, others there may be able to help.

Currently, only version 2.0.1 of Eventum is supported by this patch, but it may still work with newer (and maybe older) versions.

If you haven't installed Eventum yet, do so now following the official instructions, then come back here.

## Setup Instructions

Please backup your Eventum installation before continuing.

### Step \#1: Apply the Patch

The first step is to apply the source patch needed to make this work. This can be done in one of two ways, only one of these is required.

#### Using Patch Utilities with the Patch File

If you are working on a Windows-based server, or you are not familiar with using patch utilities, you may want to skip down to the other method.

-   Download [eventum-2.0.1-osmod.patch](http://ibaku.net/eventum/eventum-2.0.1-osmod.patch) to your root Eventum directory.
-   Run the following command from the root installation directory:
    \$ patch -p0 < eventum-2.0.1-osmod.patch

-   If there were any errors, try the second method, otherwise you're done and can move down to step \#2.

#### Using Pre-Patched Files

-   Windows Users
    -   Download [eventum-2.0.1-osmod.tar.gz](http://ibaku.net/eventum/eventum-2.0.1-osmod.tar.gz).
    -   Unpack the contained files, and upload them to your root Eventum installation directory, overwriting the existing files.
-   Linux / Unix / Mac Users
    -   Download [eventum-2.0.1-osmod.tar.gz](http://ibaku.net/eventum/eventum-2.0.1-osmod.tar.gz) to your root Eventum directory.
    -   Run the following command from the root installation directory:
        \$ tar xzvf eventum-2.0.1-osmod.tar.gz

### Step \#2: Add an Anonymous User

-   From the Administration panel, add a new user keeping this in mind:
    -   This will require specifying an email address, which doesn't have to be a real email address, but I would recommend setting up a real email account anyway (they're usually free anyway).
    -   The password can be anything. Anonymous users will not be able to retrieve the password, nor will they be able to change it. It won't be required to login either, so it doesn't really matter what you use.
    -   The full name isn't important, but it will be what users see as their "logged in name" on every page. I'd recommend using "Anonymous" to indicate that they haven't registered or logged in.
    -   Assigned Role: Here's where the magic of this approach to supporting anonymous users comes in. You can configure any level of access for anonymous users, as well as per-project access levels just like you would for any other registered user. You should be able to find a configuration that best suits your needs. Personally, for my open source projects, I prefer to keep this access at 'Viewer' levels, and still require users to register if they want to actually submit or comment on issues.

### Step \#3: Add the Account to Your Configuration File

-   Open the config/config.php file for editing. If you followed the Eventum installation instructions to the letter, this file should be set as read-only, so you will need to change it back to writable first.
-   Add the following line anywhere in the file:

<!-- -->

    define('APP_ANON_USER', 'anonymous@example.org');

-   Make sure the email used in the line above is the same as the anonymous user you just setup. This will make that account the default anon user.

That's it! If you log out of Eventum, you will be forwarded to the login page, however, if you simply direct your browser to "list.php", you'll find that you are automatically logged in as the anonymous user (though it won't look like you're logged in since it will have "Login" and "Register" links shown still).

If you direct your browser at either the Login page, or the Sign Up / Register page, you are automatically logged back out of the anonymous account so you are able to login or register.

You will also notice that any links to "Preferences" have disappeared. Anonymous users are not able to change the account preferences (don't worry, they've been locked out of the page even if they tried the direct URL approach). In addition, saved searches are disabled, and you won't be able to search for issues that are assigned to the anonymous user (as this would be fairly useless).

If you find any other aspects of Eventum that show up to Anonymous users, but probably shouldn't, please let me know on the Eventum mailing list.

## Full Source of the Patch

This is saved here for posterity (and in case my server isn't available). Without needing to copy and & paste this all to a file, you can download the patch here: <http://ibaku.net/eventum/eventum-2.0.1-osmod.patch>

    Index: adv_search.php
    ===================================================================
    --- adv_search.php  (revision 3372)
    +++ adv_search.php  (working copy)
    @@ -61,7 +61,9 @@
         "-1"    =>  "un-assigned",
         "-2"    =>  "myself and un-assigned"
     );
    -if (User::getGroupID(Auth::getUserID()) != '') {
    +if (Auth::isAnonUser())
    +    unset($assign_options["-2"]);
    +else if (User::getGroupID(Auth::getUserID()) != '') {
         $assign_options['-3'] = 'myself and my group';
         $assign_options['-4'] = 'myself, un-assigned and my group';
     }
    Index: include/class.auth.php
    ===================================================================
    --- include/class.auth.php  (revision 3372)
    +++ include/class.auth.php  (working copy)
    @@ -99,7 +99,10 @@
             }
             $failed_url .= "&url=" . Auth::getRequestedURL();
             if (!isset($_COOKIE[$cookie_name])) {
    -            Auth::redirect($failed_url, $is_popup);
    +            // Auth::redirect($failed_url, $is_popup);
    +            Auth::createFakeCookie(User::getUserIDByEmail(APP_ANON_USER));
    +            @Auth::createLoginCookie(APP_COOKIE, APP_ANON_USER);
    +            Session::init(User::getUserIDByEmail(APP_ANON_USER));
             }
             $cookie = $_COOKIE[$cookie_name];
             $cookie = unserialize(base64_decode($cookie));
    @@ -156,6 +159,23 @@


         /**
    +     * Method for logging out the currently logged in user.
    +     *
    +     * @access  public
    +     * @returns void
    +     */
    +    function logout()
    +    {
    +        Auth::removeCookie(APP_COOKIE);
    +        // if 'remember projects' is true don't remove project cookie
    +        $project_cookie = Auth::getCookieInfo(APP_PROJECT_COOKIE);
    +        if (empty($project_cookie['remember'])) {
    +            Auth::removeCookie(APP_PROJECT_COOKIE);
    +        }
    +    }
    +
    +
    +    /**
          * Method to check whether an user is pending its confirmation
          * or not.
          *
    @@ -230,6 +250,18 @@


         /**
    +     * Method to check if the current user is an anonymous user.
    +     *
    +     * @access  public
    +     * @return  boolean
    +     */
    +    function isAnonUser()
    +    {
    +        return Auth::getUserID() == User::getUserIDByEmail(APP_ANON_USER);
    +    }
    +
    +
    +    /**
          * Method used to get the unserialized contents of the specified cookie
          * name.
          *
    @@ -436,7 +468,7 @@
                 return isset($cookie['prj_id']) ? (int )$cookie['prj_id'] : null;
             }
             if (!in_array($cookie["prj_id"], array_keys($projects))) {
    -            Auth::redirect(APP_RELATIVE_URL . "select_project.php?err=1");
    +            Auth::redirect(APP_RELATIVE_URL . "select_project.php");
             }
             return $cookie["prj_id"];
         }
    Index: include/class.template.php
    ===================================================================
    --- include/class.template.php  (revision 3372)
    +++ include/class.template.php  (working copy)
    @@ -185,6 +185,7 @@
                 $this->assign("current_email", $info["usr_email"]);
                 $this->assign("current_user_id", $usr_id);
                 $this->assign("is_current_user_clocked_in", User::isClockedIn($usr_id));
    +            $this->assign("is_anon_user", Auth::isAnonUser());
                 $this->assign("roles", User::getAssocRoleIDs());
             }
             $this->assign("app_setup", Setup::load());
    Index: index.php
    ===================================================================
    --- index.php   (revision 3372)
    +++ index.php   (working copy)
    @@ -42,7 +42,12 @@
     $tpl = new Template_API();
     $tpl->setTemplate("index.tpl.html");

    -if (Auth::hasValidCookie(APP_COOKIE)) {
    +// log anonymous users out so they can use the login form
    +if (Auth::hasValidCookie(APP_COOKIE) && Auth::isAnonUser()) {
    +    Auth::logout();
    +}
    +
    +if (Auth::hasValidCookie(APP_COOKIE) && !Auth::isAnonUser()) {
         $cookie = Auth::getCookieInfo(APP_COOKIE);
         if (!empty($_REQUEST["url"])) {
             $extra = '?url=' . $_REQUEST["url"];
    Index: list.php
    ===================================================================
    --- list.php    (revision 3372)
    +++ list.php    (working copy)
    @@ -79,7 +79,9 @@
         "-1"    =>  ev_gettext("un-assigned"),
         "-2"    =>  ev_gettext("myself and un-assigned")
     );
    -if (User::getGroupID($usr_id) != '') {
    +if (Auth::isAnonUser())
    +    unset($assign_options["-2"]);
    +else if (User::getGroupID($usr_id) != '') {
         $assign_options['-3'] = ev_gettext('myself and my group');
         $assign_options['-4'] = ev_gettext('myself, un-assigned and my group');
     }
    Index: logout.php
    ===================================================================
    --- logout.php  (revision 3372)
    +++ logout.php  (working copy)
    @@ -30,11 +30,6 @@
     require_once(dirname(__FILE__) . "/init.php");
     require_once(APP_INC_PATH . "class.auth.php");

    -Auth::removeCookie(APP_COOKIE);
    +Auth::logout();

    -// if 'remember projects' is true don't remove project cookie
    -$project_cookie = Auth::getCookieInfo(APP_PROJECT_COOKIE);
    -if (empty($project_cookie['remember'])) {
    -    Auth::removeCookie(APP_PROJECT_COOKIE);
    -}
     Auth::redirect(APP_RELATIVE_URL . "index.php?err=6");
    Index: preferences.php
    ===================================================================
    --- preferences.php (revision 3372)
    +++ preferences.php (working copy)
    @@ -50,6 +50,10 @@

     Auth::checkAuthentication(APP_COOKIE);

    +if (Auth::isAnonUser()) {
    +    Auth::redirect("index.php");
    +}
    +
     $usr_id = Auth::getUserID();

     if (@$_POST["cat"] == "update_account") {
    Index: signup.php
    ===================================================================
    --- signup.php  (revision 3372)
    +++ signup.php  (working copy)
    @@ -35,6 +35,11 @@
     $tpl = new Template_API();
     $tpl->setTemplate("signup.tpl.html");

    +// log anonymous users out so they can use the signup form
    +if (Auth::hasValidCookie(APP_COOKIE) && Auth::isAnonUser()) {
    +    Auth::logout();
    +}
    +
     if (@$_POST['cat'] == 'signup') {
         $setup = Setup::load();
         $res = User::createVisitorAccount($setup['accounts_role'], $setup['accounts_projects']);
    Index: templates/adv_search.tpl.html
    ===================================================================
    --- templates/adv_search.tpl.html   (revision 3372)
    +++ templates/adv_search.tpl.html   (working copy)
    @@ -302,6 +302,7 @@
                 </select>
               </td>
             </tr>
    +   {if !$is_anon_user}
             <tr>
               <td colspan="5">
                 <table width="100%" cellspacing="0" border="0" cellpadding="0">
    @@ -319,6 +320,7 @@
                 </table>
               </td>
             </tr>
    +   {/if}
             <tr>
               <td colspan="5">
               <hr>
    @@ -599,6 +601,7 @@
                 <input class="button" type="reset" value="{t}Reset{/t}">
               </td>
             </tr>
    +   {if !$is_anon_user}
             <tr>
               <td colspan="5" align="center" bgcolor="{$light_color}">
                 <span class="default">{t}Search Title{/t}:</span>
    @@ -615,6 +618,7 @@
                 <input class="button" type="button" value="Save Search" onClick="javascript:saveCustomFilter(this.form);">
               </td>
             </tr>
    +   {/if}
             </form>
           </table>
         </td>
    @@ -623,6 +627,7 @@
         </td>
       </tr>
     </table>
    +{if !$is_anon_user}
     <br />
     <table width="450" bgcolor="{$cell_color}" border="0" cellspacing="0" cellpadding="1" align="center">
       <tr>
    @@ -682,6 +687,7 @@
         </td>
       </tr>
     </table>
    +{/if}

     <script language="JavaScript">
     <!--
    Index: templates/navigation.tpl.html
    ===================================================================
    --- templates/navigation.tpl.html   (revision 3372)
    +++ templates/navigation.tpl.html   (working copy)
    @@ -5,7 +5,7 @@
           <table width="100%" border="0" cellspacing="0" cellpadding="4" bgcolor="{$cell_color}">
             <tr>
               <td class="default_white">
    -            <b>{$app_setup.tool_caption|default:$application_title}</b> (<a title="{t}logout from{/t} {$app_setup.tool_caption|default:$application_title}" target="_top" href="{$rel_url}logout.php" class="white_link">{t}Logout{/t}</a>)
    +            <b>{$app_setup.tool_caption|default:$application_title}</b> ({if !$is_anon_user}<a title="{t}logout from{/t} {$app_setup.tool_caption|default:$application_title}" target="_top" href="{$rel_url}logout.php" class="white_link">{t}Logout{/t}</a>{else}<a target="_top" href="{$rel_url}index.php" class="white_link">{t}Login{/t}</a>{/if})
               </td>
               <td align="right" class="default_white">
                 {if $current_role > $roles.developer}
    @@ -87,8 +87,8 @@
               {/if}
               <td width="50%" nowrap bgcolor="{$light_color}" class="default">
                 <b>{$current_role_name}: {$current_full_name}{if $current_role > $roles.standard_user} [{t}CLOCKED{/t} {if $is_current_user_clocked_in}{t}IN{/t}{else}{t}OUT{/t}{/if}]{/if}</b>
    -            (<a target="_top" title="{t}modify your account details and preferences{/t}" href="{$rel_url}preferences.php" class="link">{t}Preferences{/t}</a>{if $current_role > $roles.standard_user}
    -            <a target="_top" title="{t}change your account clocked-in status{/t}" href="javascript:void(null);" onClick="javascript:changeClockStatus();" class="link">{t}Clock{/t} {if $is_current_user_clocked_in}{t}Out{/t}{else}{t}In{/t}{/if}</a>{/if})
    +            ({if $is_anon_user}<a target="_top" href="{$rel_url}signup.php" class="link">{t}Register{/t}</a>{else}<a target="_top" title="{t}modify your account details and preferences{/t}" href="{$rel_url}preferences.php" class="link">{t}Preferences{/t}</a>{if $current_role > $roles.standard_user}
    +            <a target="_top" title="{t}change your account clocked-in status{/t}" href="javascript:void(null);" onClick="javascript:changeClockStatus();" class="link">{t}Clock{/t} {if $is_current_user_clocked_in}{t}Out{/t}{else}{t}In{/t}{/if}</a>{/if}{/if})
               </td>
               <form target="_top" method="get" action="{$rel_url}list.php">
               <td width="5%" nowrap bgcolor="{$light_color}">
