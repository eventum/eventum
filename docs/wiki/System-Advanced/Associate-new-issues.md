### Associate new issues

Associate incoming emails only with new issues

On the associate emails page, the selection box to select the issue to associate an email with soon gets too many entries, most of them closed issues. This patch limits the selection to only open issues. (issue closed date is null). A limitation of this is that an email can't be associated with a closed issue, the issue has to be reopened first.

    --- eventum-1.7.0/emails.php    2005-12-30 08:27:24.000000000  1300
        eventum/emails.php  2006-02-22 12:21:59.000000000  1300
    @@ -61,9  61,9 @@
     $list = Support::getEmailListing($options, $pagerRow, $rows);
     $tpl->assign("list", $list["list"]);
     $tpl->assign("list_info", $list["info"]);
    -$tpl->assign("issues", Issue::getColList());
     $tpl->assign("issues", Issue::getColList("iss_closed_date IS NULL"));
     $tpl->assign("accounts", Email_Account::getAssocList(Auth::getCurrentProject()));
    -$tpl->assign("assoc_issues", Issue::getAssocList());
     $tpl->assign("assoc_issues", Issue::getAssocList("iss_closed_date IS NULL"));

     $prefs = Prefs::get(Auth::getUserID());
     $tpl->assign("refresh_rate", $prefs['emails_refresh_rate'] * 60);
