/*
 * Page specific JS for eventum
 */


/*
 * List Issues Page
 */
function list_issues()
{
}

list_issues.ready = function(page_id)
{
    $('#toggle_quick_filter').click(function() { Eventum.toggle_section_visibility('quick_filter'); });
    $('#toggle_current_filters').click(function() { Eventum.toggle_section_visibility('current_filters'); });
    $('#toggle_bulk_update').click(function() { Eventum.toggle_section_visibility('bulk_update'); });
}


/*
 * View Issue Page
 */
function issue_view()
{
}

issue_view.get_issue_id = function()
{
    return $('#issue_overview').attr('data-issue-id');
}

issue_view.get_ema_id = function()
{
    return $('#issue_overview').attr('data-ema-id');
}

issue_view.ready = function(page_id)
{
    $('#toggle_time_tracking').click(function() { issue_view.toggle_issue_section('time_tracking'); });
    $('#toggle_internal_notes').click(function() { issue_view.toggle_issue_section('internal_notes'); });
    $('#toggle_phone_calls').click(function() { issue_view.toggle_issue_section('phone_calls'); });
    $('#toggle_drafts').click(function() { issue_view.toggle_issue_section('drafts'); });
    $('#toggle_support_emails').click(function() { issue_view.toggle_issue_section('support_emails'); });
    $('#issue_description_link').click(issue_view.toggle_issue_description).
        ready(issue_view.display_description_collapse_message);

    /* Main Issue actions */
    $('.open_history').click(issue_view.openHistory);
    $('.open_nl').click(issue_view.openNotificationList);
    $('.open_ar').click(issue_view.openAuthorizedReplier);
    $('.self_assign').click(issue_view.selfAssign);
    $('.unassign').click(issue_view.unassign);
    $('.self_notification').click(issue_view.selfNotification);
    $('.self_authorized_replier').click(issue_view.signupAsAuthorizedReplier);
    $('.change_status').click(issue_view.changeIssueStatus);

    $('.remove_quarantine').click(issue_view.removeQuarantine);
    $('.clear_duplicate').click(issue_view.clearDuplicateStatus);
    $('.reply_issue').click(issue_view.replyIssue);
    $('.edit_incident_redemption').click(issue_view.editIncidentRedemption);

    $('.mark_duplicate').click(function() { window.location.href='duplicate.php?id=' + issue_view.get_issue_id(); });
    $('.close_issue').click(function() { window.location.href='close.php?id=' + issue_view.get_issue_id(); });
    $('.display_fixed_width').click(function() { Eventum.displayFixedWidth($('#issue_description')); });



    /* Attachments Section */
    $('#toggle_attachments').click(function() { issue_view.toggle_issue_section('attachments'); });
    $('#upload_file').click(issue_view.upload_file);
    $('#attachments .delete_attachment').click(issue_view.delete_attachment);
    $('#attachments .delete_file').click(issue_view.delete_file);
}

issue_view.toggle_issue_description = function()
{
    console.debug('foo');
    Eventum.toggle_section_visibility('issue_description');

    issue_view.display_description_collapse_message();
}

issue_view.display_description_collapse_message = function()
{
    var hidden = $('#description_hidden');
    if ($('#issue_description:visible').length > 0) {
        hidden.hide();
    } else {
        hidden.show();
    }
}

issue_view.toggle_issue_section = function(id)
{
    var element = $('#' + id + ' div.content');
    var display = '';
    var link_title = ''
    if (element.is(':visible')) {
        display = 'none';
        element.hide();
        link_title = 'show';
    } else {
        display = 'block';
        element.show();
        link_title = 'hide';
    }

    $('#toggle_' + id).text(link_title);

    $.cookie('visibility_' + id, display, {expires: Eventum.expires});
}

issue_view.openHistory = function()
{
    var features = 'width=520,height=400,top=30,left=30,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
    var popupWin = window.open('history.php?iss_id=' + issue_view.get_issue_id(), '_history', features);
    popupWin.focus();
    return false;
}

issue_view.openNotificationList = function()
{
    var features = 'width=440,height=400,top=30,left=30,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
    var popupWin = window.open('notification.php?iss_id=' + issue_view.get_issue_id(), '_notification', features);
    popupWin.focus();
    return false;
}

issue_view.openAuthorizedReplier = function()
{
    var features = 'width=440,height=400,top=30,left=30,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
    var popupWin = window.open('authorized_replier.php?iss_id=' + issue_view.get_issue_id(), '_replier', features);
    popupWin.focus();
    return false;
}


issue_view.selfAssign = function()
{
    var features = 'width=420,height=170,top=30,left=30,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
    var popupWin = window.open('self_assign.php?iss_id=' + issue_view.get_issue_id(), '_selfAssign', features);
    popupWin.focus();
}

issue_view.unassign = function()
{
    var features = 'width=420,height=400,top=30,left=30,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
    var popupWin = window.open('popup.php?cat=unassign&iss_id=' + issue_view.get_issue_id(), '_unassign', features);
    popupWin.focus();
}

issue_view.selfNotification = function()
{
    var features = 'width=440,height=400,top=30,left=30,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
    var popupWin = window.open('notification.php?iss_id=' + issue_view.get_issue_id() + '&cat=selfnotify', '_notification', features);
    popupWin.focus();
}

issue_view.signupAsAuthorizedReplier = function()
{
    var features = 'width=420,height=400,top=30,left=30,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
    var popupWin = window.open('popup.php?cat=authorize_reply&iss_id=' + issue_view.get_issue_id(), '_authorizeReply', features);
    popupWin.focus();
}


issue_view.changeIssueStatus = function(e)
{
    var current_status_id = $(e.target).attr('data-status-id');
    var new_status = $('#new_status').val();
    if (new_status == current_status_id) {
        Validation.selectField($('#new_status'));
        alert('Please select the new status for this issue.');
    } else {
        var features = 'width=420,height=400,top=30,left=30,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
        var popupWin = window.open('popup.php?cat=new_status&iss_id=' + issue_view.get_issue_id() + '&new_sta_id=' + new_status, '_newStatus', features);
        popupWin.focus();
    }
}

issue_view.upload_file = function(e)
{
    var issue_id = $(e.target).attr('data-issue-id');
    var features = 'width=600,height=350,top=30,left=30,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
    var popupWin = window.open('file_upload.php?iss_id=' + issue_id, 'file_upload_' + issue_id, features);
    popupWin.focus();
}

issue_view.delete_attachment = function(e)
{
    var iat_id = $(e.target).attr('data-iat-id');
    if (!confirm('This action will permanently delete the selected attachment.')) {
        return false;
    } else {
        var features = 'width=420,height=200,top=30,left=30,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
        var popupWin = window.open('popup.php?cat=delete_attachment&id=' + iat_id, '_popup', features);
        popupWin.focus();
    }
}

issue_view.delete_file = function(e)
{
    iaf_id = $(e.target).attr('data-iaf-id');
    if (!confirm('This action will permanently delete the selected file.')) {
        return false;
    } else {
        var features = 'width=420,height=200,top=30,left=30,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
        var popupWin = window.open('popup.php?cat=delete_file&id=' + iaf_id, '_popup', features);
        popupWin.focus();
    }
}

issue_view.removeQuarantine = function()
{
    var features = 'width=420,height=400,top=30,left=30,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
    var popupWin = window.open('popup.php?cat=remove_quarantine&iss_id=' + issue_view.get_issue_id(), '_removeQuarantine', features);
    popupWin.focus();
}

issue_view.replyIssue = function()
{
    var features = 'width=740,height=580,top=30,left=30,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
    var popupWin = window.open('send.php?cat=reply&ema_id=' + ema_id + '&issue_id=' + issue_view.get_issue_id(), '_replyIssue' + issue_id, features);
    popupWin.focus();
}

issue_view.clearDuplicateStatus = function()
{
    var features = 'width=420,height=400,top=30,left=30,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
    var popupWin = window.open('popup.php?cat=clear_duplicate&iss_id=' + issue_view.get_issue_id(), '_clearDuplicate', features);
    popupWin.focus();
}

issue_view.editIncidentRedemption = function()
{
    var features = 'width=300,height=300,top=30,left=30,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
    var popupWin = window.open('redeem_incident.php?iss_id=' + issue_view.get_issue_id(), '_flagIncident', features);
    popupWin.focus();
}



/*
 * Close Issue Page
 */
function close_issue()
{
}

close_issue.ready = function()
{
    $('input[name=notification_list]').change(close_issue.toggleNotificationList);

    close_issue.toggleNotificationList();

    $('input[name=add_email_signature]').change(close_issue.toggleEmailSignature);

    $('form[name=close_form]').submit(function() {
        return Validation.checkFormSubmission($('form[name=close_form]'), close_issue.validateForm);
    });
}

close_issue.toggleNotificationList = function()
{
    var cell = $('#reason_cell');

    if ($('input#notification_internal:checked').length > 0) {
        cell.addClass("internal");
    } else {
        cell.removeClass("internal");
    }
}

close_issue.toggleEmailSignature = function()
{
    var reason = $('textarea[name=reason]');
    var sig = $('#signature').text();
    if ($('input[name=add_email_signature]:checked').length > 0) {
        reason.val(reason.val() + "\n" + sig);
    } else {
        reason.val(reason.val().replace("\n" + sig, ''));
    }
}

close_issue.validateForm = function()
{
    var form = $('form[name=close_form]');
    if ($('form [name=status]').val() == -1) {
        Validation.errors[Validation.errors.length] = new Option('Status', 'status');
    }
    if (Validation.isWhitespace($('form [name=reason]').val())) {
        Validation.errors[Validation.errors.length] = new Option('Reason to close', 'reason');
    }

    var time_spent = $('form [name=time_spent]').val();
    if (!Validation.isWhitespace(time_spent)) {
        if (!Validation.isNumberOnly(time_spent)) {
            Validation.errors[Validation.errors.length] = new Option('Please enter integers (or floating point numbers) on the time spent field.', 'time_spent');
        }
        if ($('form [name=category]').val() == '') {
            Validation.errors[Validation.errors.length] = new Option('Time tracking category', 'category');
        }
    }

    Validation.checkCustomFields(form);

    // TODO: this needs to be double checked with a customer backend
    var has_per_incident_contract = (('tr.per_incident').length > 0)
    if ((Validation.errors.length < 1) && (has_per_incident_contract)) {
        if ($('input[type=checkbox][name^=redeem]:checked').length > 0) {
            return confirm('This customer has a per incident contract. You have chosen not to redeem any incidents. Press \'OK\' to confirm or \'Cancel\' to revise.');
        }
    }
    return true;
}