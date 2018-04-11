/*
 * This file is part of the Eventum (Issue Tracking System) package.
 *
 * @copyright (c) Eventum Team
 * @license GNU General Public License, version 2 or later (GPL-2+)
 *
 * For the full copyright and license information,
 * please see the COPYING and AUTHORS files
 * that were distributed with this source code.
 */

/*
 * Page specific JS for eventum
 */

function select_project()
{
}

select_project.ready = function()
{
    $('#project_select_form input[name=project]').on('change', function() {
	        $(this).closest("form").submit();
    });
    $('#project_select_form .project_label').on('click', function() {
	        $(this).closest("form").submit();
    });
}


/*
 * List Issues Page
 */
function list_issues()
{
}
list_issues.current_page = 0;
list_issues.last_page = 0;
list_issues.page_url = '';

list_issues.ready = function(page_id)
{
    var list_form = $('#list_form');
    list_issues.current_page = parseInt(list_form.attr('data-current-page'));
    list_issues.last_page = parseInt(list_form.attr('data-last-page'));
    list_issues.page_url = Eventum.rel_url + 'list.php';


    $('#toggle_quick_filter').click(function() { Eventum.toggle_section_visibility('quick_filter'); });
    $('#toggle_current_filters').click(function() { Eventum.toggle_section_visibility('current_filters'); });
    $('#toggle_bulk_update').click(function() { Eventum.toggle_section_visibility('bulk_update'); });

    $('#reset_bulk_update').click(list_issues.reset_bulk_update);
    $('#bulk_update_button').click(list_issues.bulk_update);
    $('#clear_filters').click(list_issues.clearFilters);
    $('#hide_closed').click(list_issues.hideClosed);
    $('#page_size').change(list_issues.resizePager);
    $('#resize_page').click(list_issues.resizePager);
    $('#custom_filter').change(list_issues.runCustomFilter);

    $('.select_all').click(function() { Eventum.toggleCheckAll('item[]'); });

    Eventum.getField('first').click(function () {list_issues.setPage(0);});
    Eventum.getField('previous').click(function() { list_issues.setPage(list_issues.current_page-1); });
    Eventum.getField('next').click(function() { list_issues.setPage(list_issues.current_page+1); });
    Eventum.getField('last').click(function() { list_issues.setPage(list_issues.last_page); });
    Eventum.getField('go').click(list_issues.goPage);
    Eventum.getField('page').keydown(function(e) {
        if (e.which === 13) {
            list_issues.goPage();
        }
    });

    $('#export_csv').click(list_issues.downloadCSV);
    $('.custom_field').click(list_issues.updateCustomFields);

    list_issues.disableFields();

    setTimeout(function() {
        location.reload();
    }, parseInt(list_form.attr('data-refresh-rate')) * 1000);
};

list_issues.reset_bulk_update = function(e)
{
    Eventum.clearSelectedOptions('users[]');
    Eventum.clearSelectedOptions('status');
    Eventum.clearSelectedOptions('release');
    Eventum.clearSelectedOptions('priority');
    Eventum.clearSelectedOptions('category');
    Eventum.clearSelectedOptions('closed_status');
};

list_issues.bulk_update = function(e)
{
    var form = $('#list_form');

    if (!Validation.hasOneChecked('item[]')) {
        alert('Please choose which issues to update.');
        return false;
    }

    // figure out what is changing
    var changed = [];
    if (Validation.hasOneSelected('users[]')) {
        changed[changed.length] = 'Assignment';
    }
    if (Eventum.getField('status', form).val() !== '') {
        changed[changed.length] = 'Status';
    }
    if (Eventum.getField('release', form).val() !== '') {
        changed[changed.length] = 'Release';
    }
    if (Eventum.getField('priority', form).val() !== '') {
        changed[changed.length] = 'Priority';
    }
    if (Eventum.getField('category', form).val() !== '') {
        changed[changed.length] = 'Category';
    }
    if (Eventum.getField('closed_status', form).val() !== '') {
        changed[changed.length] = 'Closed Status';
    }
    if (changed.length < 1) {
        alert('Please choose new values for the selected issues');
        return false;
    }
    var msg = 'Warning: If you continue, you will change the ';
    for (var i = 0; i < changed.length; i++) {
        msg += changed[i];
        if ((changed.length > 1) && (i === (changed.length-2))) {
            msg += ' and ';
        } else {
            if (i !== (changed.length-1)) {
                msg += ', ';
            }
        }
    }
    msg += ' for all selected issues. Are you sure you want to continue?';
    if (!confirm(msg)) {
        return false;
    }
    var features = 'width=420,height=200,top=30,left=30,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
    var popupWin = window.open('', '_popup', features);
    popupWin.focus();
    form.attr('action','popup.php');
    form.attr('target', '_popup');
    form.submit();
};

list_issues.clearFilters = function()
{
    var form = $('#quick_filter_form');
    form.find('input,select').val('');
    form.submit();
};

list_issues.runCustomFilter = function()
{
    var $customFilter = $('#custom_filter');
    var cst_url = $customFilter.val();
    if (Validation.isWhitespace(cst_url)) {
        alert('Please select the custom filter to search against.');
        $customFilter.focus();
        return false;
    }
    location.href = 'list.php?cat=search&' + cst_url;
    return false;
};

list_issues.hideClosed = function()
{
    if ($('#hide_closed').is(':checked')) {
        window.location.href = list_issues.page_url + "?" + Eventum.replaceParam(window.location.href, 'hide_closed', '1');
    } else {
        window.location.href = list_issues.page_url + "?" + Eventum.replaceParam(window.location.href, 'hide_closed', '0');
    }
};

list_issues.resizePager = function()
{
    window.location.href = list_issues.page_url + "?" + Eventum.replaceParam(window.location.href, 'rows', $('#page_size').val());
};


list_issues.setPage =  function(new_page)
{
    if ((new_page > list_issues.last_page) || (new_page < 0) ||
            (new_page === list_issues.current_page)) {
        return false;
    }
    window.location.href = list_issues.page_url + "?" + Eventum.replaceParam(window.location.href, 'pagerRow', new_page);
};

list_issues.goPage = function()
{
    var new_page = Eventum.getField('page').val();
    if ((new_page > list_issues.last_page+1) || (new_page <= 0) ||
            (new_page === list_issues.current_page+1) || (!Validation.isNumberOnly(new_page))) {
        Eventum.getField('page').val(list_issues.current_page+1);
        return false;
    }
    list_issues.setPage(new_page-1);
};

list_issues.disableFields = function ()
{
    if (list_issues.current_page === 0) {
        Eventum.getField('first').attr('disabled', 'disabled');
        Eventum.getField('previous').attr('disabled', 'disabled');
    }
    if ((list_issues.current_page === list_issues.last_page) || (list_issues.last_page <= 0)) {
        Eventum.getField('next').attr('disabled', 'disabled');
        Eventum.getField('last').attr('disabled', 'disabled');
    }
    if ((list_issues.current_page === 0) && (list_issues.last_page <= 0)) {
        Eventum.getField('page').attr('disabled', 'disabled');
        Eventum.getField('go').attr('disabled', 'disabled');
    }
};

list_issues.downloadCSV = function()
{
    $('#csv_form').submit();
    return false;
};

list_issues.updateCustomFields = function(e)
{
    var target = $(e.target);
    var issue_id = target.parents('tr').attr('data-issue-id');
    var features = 'width=560,height=460,top=30,left=30,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
    var customWin = window.open('custom_fields.php?issue_id=' + issue_id, '_custom_fields', features);
    customWin.focus();
    return false;
};


/*
 * View Issue Page
 */
function issue_view()
{
}

issue_view.get_issue_id = function()
{
    return $('#issue_overview').attr('data-issue-id');
};

issue_view.get_ema_id = function()
{
    return $('#issue_overview').attr('data-ema-id');
};

issue_view.ready = function(page_id)
{
    $('#toggle_time_tracking').click(function() { issue_view.toggle_issue_section('time_tracking'); });
    $('#toggle_checkins').click(function() { issue_view.toggle_issue_section('checkins'); });
    $('#toggle_custom_fields').click(function() { issue_view.toggle_issue_section('custom_fields'); });
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
    $('.change_access').click(issue_view.changeAccess);

    $('.remove_quarantine').click(issue_view.removeQuarantine);
    $('.clear_duplicate').click(issue_view.clearDuplicateStatus);
    $('.reply_issue').click(issue_view.replyIssue);
    $('.reply_issue_note').click(issue_view.replyIssueNote);
    $('.reply_email').click(issue_view.reply);
    $('.reply_email_note').click(issue_view.replyAsNote);
    $('.edit_incident_redemption').click(issue_view.editIncidentRedemption);
    $('a.edit_time_entry').click(issue_view.editTimeEntry);
    $('a.delete_time_entry').click(issue_view.deleteTimeEntry);
    $('.add_time_entry').click(issue_view.addTimeEntry);

    $('.mark_duplicate').click(function() { window.location.href='duplicate.php?id=' + issue_view.get_issue_id(); });
    $('.close_issue').click(function() { window.location.href='close.php?id=' + issue_view.get_issue_id(); });
    $('.display_fixed_width').click(function() { issue_view.toggle_plain_view(); });



    /* Attachments Section */
    $('#toggle_attachments').click(function() { issue_view.toggle_issue_section('attachments'); });
    $('#upload_file').click(issue_view.upload_file);
    $('#attachments .delete_attachment').click(issue_view.delete_attachment);
    $('#attachments .delete_file').click(issue_view.delete_file);
};

issue_view.toggle_issue_description = function()
{
    Eventum.toggle_section_visibility('issue_description');

    issue_view.display_description_collapse_message();
};

issue_view.display_description_collapse_message = function()
{
    var hidden = $('#description_hidden');
    if ($('#issue_description:visible').length > 0) {
        hidden.hide();
    } else {
        hidden.show();
    }
};

issue_view.toggle_plain_view = function()
{
    var formatted = $('#description_formatted');
    var plain = $('#description_plain');
    var link = $('#fixed_width_link');

    if (!plain.is(':visible')) {
        formatted.hide();
        plain.show();
        link.text(link.attr('data-normal'));
    } else {
        plain.hide();
        formatted.show();
        link.text(link.attr('data-fixed'));
    }
}

issue_view.toggle_issue_section = function(id)
{
    var element = $('#' + id + ' div.content');
    var display = '';
    var link_title = '';
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
};

issue_view.openHistory = function()
{
    var features = 'width=890,height=400,top=30,left=30,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
    var popupWin = window.open('history.php?iss_id=' + issue_view.get_issue_id(), '_history', features);
    popupWin.focus();
    return false;
};

issue_view.openNotificationList = function()
{
    var features = 'width=540,height=400,top=30,left=30,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
    var popupWin = window.open('notification.php?iss_id=' + issue_view.get_issue_id(), '_notification', features);
    popupWin.focus();
    return false;
};

issue_view.openAuthorizedReplier = function()
{
    var features = 'width=440,height=400,top=30,left=30,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
    var popupWin = window.open('authorized_replier.php?iss_id=' + issue_view.get_issue_id(), '_replier', features);
    popupWin.focus();
    return false;
};

issue_view.changeAccess = function()
{
    var features = 'width=440,height=400,top=30,left=30,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
    var popupWin = window.open('access.php?iss_id=' + issue_view.get_issue_id(), '_access', features);
    popupWin.focus();
    return false;
};


issue_view.selfAssign = function()
{
    var features = 'width=420,height=170,top=30,left=30,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
    var popupWin = window.open('self_assign.php?iss_id=' + issue_view.get_issue_id(), '_selfAssign', features);
    popupWin.focus();
};

issue_view.unassign = function()
{
    var features = 'width=420,height=400,top=30,left=30,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
    var popupWin = window.open('popup.php?cat=unassign&iss_id=' + issue_view.get_issue_id(), '_unassign', features);
    popupWin.focus();
};

issue_view.selfNotification = function()
{
    var features = 'width=440,height=400,top=30,left=30,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
    var popupWin = window.open('popup.php?iss_id=' + issue_view.get_issue_id() + '&cat=selfnotify', '_notification', features);
    popupWin.focus();
};

issue_view.signupAsAuthorizedReplier = function()
{
    var features = 'width=420,height=400,top=30,left=30,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
    var popupWin = window.open('popup.php?cat=authorize_reply&iss_id=' + issue_view.get_issue_id(), '_authorizeReply', features);
    popupWin.focus();
};


issue_view.changeIssueStatus = function(e)
{
    var current_status_id = $(e.target).attr('data-status-id');
    var $newStatus = $('#new_status');
    var new_status = $newStatus.val();
    if (new_status === current_status_id) {
        Validation.selectField($newStatus);
        alert('Please select the new status for this issue.');
    } else {
        var features = 'width=420,height=400,top=30,left=30,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
        var popupWin = window.open('popup.php?cat=new_status&iss_id=' + issue_view.get_issue_id() + '&new_sta_id=' + new_status, '_newStatus', features);
        popupWin.focus();
    }
};


issue_view.updateCustomFields = function(e)
{
    var issue_id = $(e.target).attr('data-issue-id');
    var features = 'width=560,height=460,top=30,left=30,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
    var customWin = window.open('custom_fields.php?issue_id=' + issue_id, '_custom_fields', features);
    customWin.focus();
};

issue_view.upload_file = function(e)
{
    var issue_id = $(e.target).attr('data-issue-id');
    var features = 'width=600,height=350,top=30,left=30,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
    var popupWin = window.open('file_upload.php?iss_id=' + issue_id, 'file_upload_' + issue_id, features);
    popupWin.focus();
};

issue_view.delete_attachment = function(e)
{
    var iat_id = $(e.target).attr('data-iat-id');
    if (!confirm('This action will permanently delete the selected attachment.')) {
        return false;
    }

    var features = 'width=420,height=200,top=30,left=30,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
    var popupWin = window.open('popup.php?cat=delete_attachment&id=' + iat_id, '_popup', features);
    popupWin.focus();
};

issue_view.delete_file = function(e)
{
    var iaf_id = $(e.target).attr('data-iaf-id');
    if (!confirm('This action will permanently delete the selected file.')) {
        return false;
    }

    var features = 'width=420,height=200,top=30,left=30,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
    var popupWin = window.open('popup.php?cat=delete_file&id=' + iaf_id, '_popup', features);
    popupWin.focus();
};

issue_view.removeQuarantine = function()
{
    var features = 'width=420,height=400,top=30,left=30,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
    var popupWin = window.open('popup.php?cat=remove_quarantine&iss_id=' + issue_view.get_issue_id(), '_removeQuarantine', features);
    popupWin.focus();
};

issue_view.replyIssue = function()
{
    var features = 'width=740,height=580,top=30,left=30,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
    var popupWin = window.open('send.php?cat=reply&ema_id=' + issue_view.get_ema_id() + '&issue_id=' + issue_view.get_issue_id(), '_replyIssue' + issue_view.get_issue_id(), features);
    popupWin.focus();
};

issue_view.replyIssueNote = function()
{
    var features = 'width=740,height=580,top=30,left=30,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
    var popupWin = window.open('post_note.php?cat=issue_reply' + '&issue_id=' + issue_view.get_issue_id(), '_replyIssueNote' + issue_view.get_issue_id(), features);
    popupWin.focus();
};

issue_view.replyAsNote = function ()
{
    var $this = $(this);
    var email_id = $this.data('sup_id');
    var iss_id = issue_view.get_issue_id();
    var account_id = issue_view.get_ema_id();

    var features = 'width=740,height=740,top=30,left=30,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
    var emailWin = window.open('post_note.php?cat=email_reply&issue_id=' + iss_id + '&ema_id=' + account_id + '&id=' + email_id, '_noteReply' + email_id, features);
    emailWin.focus();
};

issue_view.reply = function()
{
    var $this = $(this);
    var email_id = $this.data('sup_id');
    var iss_id = issue_view.get_issue_id();
    var account_id = issue_view.get_ema_id();

    var features = 'width=740,height=580,top=30,left=30,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
    var emailWin = window.open('send.php?issue_id=' + iss_id + '&ema_id=' + account_id + '&id=' + email_id, '_emailReply' + email_id, features);
    emailWin.focus();
};

issue_view.clearDuplicateStatus = function()
{
    var features = 'width=420,height=400,top=30,left=30,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
    var popupWin = window.open('popup.php?cat=clear_duplicate&iss_id=' + issue_view.get_issue_id(), '_clearDuplicate', features);
    popupWin.focus();
};

issue_view.editIncidentRedemption = function()
{
    var features = 'width=300,height=300,top=30,left=30,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
    var popupWin = window.open('redeem_incident.php?iss_id=' + issue_view.get_issue_id(), '_flagIncident', features);
    popupWin.focus();
};

issue_view.openReporter = function(issue_id)
{
    var features = 'width=440,height=400,top=30,left=30,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
    var popupWin = window.open('edit_reporter.php?iss_id=' + issue_id, '_reporter', features);
    popupWin.focus();
};

issue_view.deleteTimeEntry = function(e)
{
    var target = $(e.target);
    var ttr_id = target.data('ttr-id');
    var warning_msg = target.closest('form').data('delete-warning');
    if (!confirm(warning_msg)) {
        return false;
    }

    var features = 'width=420,height=200,top=30,left=30,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
    var popupWin = window.open('popup.php?cat=delete_time&id=' + ttr_id, '_popup', features);
    popupWin.focus();

    return false;
};

issue_view.addTimeEntry = function()
{
    var features = 'width=550,height=250,top=30,left=30,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
    var popupWin = window.open('time_tracking.php?iss_id=' + issue_view.get_issue_id(), 'time_tracking_' + issue_view.get_issue_id(), features);

    popupWin.focus();
};

issue_view.editTimeEntry = function(e)
{
    var target = $(e.target);
    var features = 'width=550,height=250,top=30,left=30,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';

    var ttr_id = target.data('ttr-id');
    var popupWin = window.open('time_tracking.php?ttr_id=' + ttr_id, 'time_tracking_edit_' + ttr_id, features);

    popupWin.focus();
    return false;
};

/*
 * Update Issue Page
 */
function issue_update()
{
}

issue_update.ready = function(page_id)
{
    var $updateForm = $('#update_form');

    var validateAction = function () {
        return Validation.checkFormSubmission($updateForm, issue_update.validateForm);
    };
    $updateForm.submit(validateAction);

    // remove validation if hitting cancel
    $updateForm.find('[name=cancel]').click(function() {
        $updateForm.unbind('submit', validateAction);
    });

    $('#clear_selected').click(function() {
        Eventum.clearSelectedOptions('assignments[]');
    });

    $('.close_issue').unbind('click', issue_view.closeIssue).click(issue_update.closeIssue);

    Eventum.setupShowSelections($('#assignments'));

    $('.open_history').click(issue_view.openHistory);
    $('.open_nl').click(issue_view.openNotificationList);
    $('.open_ar').click(issue_view.openAuthorizedReplier);

    $('#severity').bind('change', issue_update.display_severity_description).change();
};

issue_update.validateForm = function()
{
    var f = $('#update_form');
    if (Validation.isFieldWhitespace('summary')) {
        Validation.errors[Validation.errors.length] = new Option('Summary', 'summary');
    }
    if (Validation.isFieldWhitespace('description')) {
        Validation.errors[Validation.errors.length] = new Option('Description', 'description');
    }
    var percent_complete = Eventum.getField('percent_complete').val();
    if ((percent_complete != '') && ((percent_complete < 0) || (percent_complete > 100))) {
        Validation.errors[Validation.errors.length] = new Option('Percentage complete should be between 0 and 100', 'percent_complete');
        return false;
    }

    Validation.checkCustomFields(f);

    return true;
};

issue_update.closeIssue = function(e)
{
    if (confirm('Warning: All changes to this issue will be lost if you continue and close this issue.')) {
        window.location.href='close.php?id=' + issue_view.get_issue_id();
    }
    e.preventDefault();
};

issue_update.display_severity_description = function()
{
    var description = $('#severity :selected').attr('data-desc');
    if (description == undefined || description == '') {
        $('#severity_desc').hide();
    } else {
        $('#severity_desc').text(description).show();
    }
};


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

    var status_options = Eventum.getField('status').children('option');
    if (status_options.length == 2) {
        status_options[1].selected = true;
    }
};

close_issue.toggleNotificationList = function()
{
    var cell = $('#reason_cell');

    if ($('input#notification_internal:checked').length > 0) {
        cell.addClass("internal");
    } else {
        cell.removeClass("internal");
    }
};

close_issue.toggleEmailSignature = function()
{
    var reason = $('textarea[name=reason]');
    var sig = $('#signature').text();
    if ($('input[name=add_email_signature]:checked').length > 0) {
        reason.val(reason.val() + "\n" + sig);
    } else {
        reason.val(reason.val().replace("\n" + sig, ''));
    }
};

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
    var has_per_incident_contract = ($('tr.per_incident').length > 0)
    if ((Validation.errors.length < 1) && (has_per_incident_contract)) {
        if ($('input[type=checkbox][name^=redeem]:checked').length > 0) {
            return confirm('This customer has a per incident contract. You have chosen not to redeem any incidents. Press \'OK\' to confirm or \'Cancel\' to revise.');
        }
    }
    return true;
};

/*
 * Adv search page
 */
function adv_search() {}

adv_search.ready = function()
{
    var $showDateFieldsCheckbox = $('#show_date_fields_checkbox');
    $showDateFieldsCheckbox.click(function() {
        adv_search.toggle_date_row();
    });

    $('.date_filter_type').change(function(e) {
        var target = $(e.target);
        adv_search.checkDateFilterType(target.attr('name').replace("[filter_type]", ""));
    });

    var $showCustomFieldsCheckbox = $('#show_custom_fields_checkbox');
    $showCustomFieldsCheckbox.click(function() {
        adv_search.toggle_custom_fields()
    });

    $('.date_filter_checkbox').click(function(e) {
        var target = $(e.target);
        var field_name = target.attr('name').replace('filter[', '').replace(']', '')
        adv_search.toggle_date_field(field_name);
    });

    $('#save_search').click(function(e) {
        adv_search.saveCustomFilter();
    });
    $('#remove_filter').submit(function(e) {
        return adv_search.validateRemove();
    });

    $('.select_all').click(function() { Eventum.toggleCheckAll('item[]'); });


    var elements_to_hide = ['created_date', 'updated_date', 'first_response_date', 'last_response_date', 'closed_date'];
    for (var i = 0; i < elements_to_hide.length; i++) {
        adv_search.checkDateFilterType(elements_to_hide[i]);
        adv_search.toggle_date_field(elements_to_hide[i]);
    }

    $('form[name=custom_filter_form]').submit(function() {
        return Validation.checkFormSubmission($('form[name=custom_filter_form]'), adv_search.validateForm);
    });

    if ($showDateFieldsCheckbox.is(':checked')) {
        adv_search.toggle_date_row(true);
    }

    if ($showCustomFieldsCheckbox.is(':checked')) {
        adv_search.toggle_custom_fields(true);
    }
};

adv_search.checkDateFilterType = function(field_name)
{
    var filter_type = Eventum.getField(field_name + '[filter_type]').val()

    if (filter_type == 'between') {
        Eventum.changeVisibility(field_name + '1', true);
        Eventum.changeVisibility(field_name + '2', true);
        Eventum.changeVisibility(field_name + '_last', false);
    } else if (filter_type == 'in_past') {
        Eventum.changeVisibility(field_name + '1', false);
        Eventum.changeVisibility(field_name + '2', false);
        Eventum.changeVisibility(field_name + '_last', true);
    } else {
        Eventum.changeVisibility(field_name + '1', true);
        Eventum.changeVisibility(field_name + '2', false);
        Eventum.changeVisibility(field_name + '_last', false);
    }
};

adv_search.toggle_custom_fields = function(show)
{
    if (show == undefined) {
        if ($('#show_custom_fields_checkbox').is(':checked')) {
            show = true;
        } else {
            show = false;
        }
    }
    $('tr#custom_fields_row').toggle(show);

    $('#custom_fields_row select').add('#custom_fields_row input').each(function(index) {
        this.disabled = !show;
    });

    // enable/disable hidden field
    $('#custom_field_hidden').attr('disabled', show);
};

adv_search.toggle_date_row = function(show)
{
    if (show == undefined) {
        if ($('#show_date_fields_checkbox').is(':checked')) {
            show = true;
        } else {
            show = false;
        }
    }
    $('tr#date_fields').toggle(show);

    if (show == false) {
        $('#date_fields select').add('#date_fields input').not('.date_filter_checkbox').each(function(index) {
            this.disabled = !show;
        });
        $('.date_filter_checkbox').attr('checked', false);
    }
};

adv_search.validateForm = function()
{
    if (!Eventum.getField('hide_closed').is(':checked')) {
        Eventum.getField('hidden1').attr('name', 'hide_closed').val(0);
    }
    if (!Eventum.getField('show_authorized_issues').is(':checked')) {
        Eventum.getField('hidden2').attr('name', 'show_authorized_issues').val('');
    }
    if (!Eventum.getField('show_notification_list_issues').is(':checked')) {
        Eventum.getField('hidden3').attr('name', 'show_notification_list_issues').val('');
    }
    return true;
};

adv_search.toggle_date_field = function(field_name)
{
    var checkbox = Eventum.getField('filter[' + field_name + ']');
    var filter_type = Eventum.getField(field_name + '[filter_type]');
    var month_field = Eventum.getField(field_name + '[Month]');
    var day_field = Eventum.getField(field_name + '[Day]');
    var year_field = Eventum.getField(field_name + '[Year]');
    var month_end_field = Eventum.getField(field_name + '_end[Month]');
    var day_end_field = Eventum.getField(field_name + '_end[Day]');
    var year_end_field = Eventum.getField(field_name + '_end[Year]');
    var time_period_field = Eventum.getField(field_name + '[time_period]');
    var disabled = !checkbox.is(':checked');
    filter_type.attr('disabled', disabled);
    month_field.attr('disabled', disabled);
    day_field.attr('disabled', disabled);
    year_field.attr('disabled', disabled);
    month_end_field.attr('disabled', disabled);
    day_end_field.attr('disabled', disabled);
    year_end_field.attr('disabled', disabled);
    time_period_field.attr('disabled', disabled);

    Eventum.getField(field_name + '_hidden').disabled = !disabled;
};

adv_search.saveCustomFilter = function()
{
    var form = $('form[name=custom_filter_form]');
    if (Validation.isFieldWhitespace('title')) {
        Validation.selectField('title');
        alert('Please enter the title for this saved search.');
        return false;
    }
    var features = 'width=420,height=200,top=30,left=30,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
    var popupWin = window.open('', '_customFilter', features);
    popupWin.focus();

    Eventum.getField('cat').val('save_filter');
    form.attr('target', '_customFilter').attr('method', 'post').attr('action', 'popup.php').submit();
};

adv_search.validateRemove = function()
{
    if (!Validation.hasOneChecked('item[]')) {
        alert('Please choose which entries need to be removed.');
        return false;
    }
    if (!confirm('This action will permanently delete the selected entries.')) {
        return false;
    }

    var features = 'width=420,height=200,top=30,left=30,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
    var popupWin = window.open('', '_removeFilter', features);
    popupWin.focus();
    return true;
};

/*
 * New Issue
 */
function new_issue() {}

new_issue.ready = function()
{
    var report_form = $('form#report_form');
    report_form.find('input,select').filter(':visible').first().focus();

    report_form.submit(function() { return Validation.checkFormSubmission(report_form, new_issue.validateForm) });

    $('#severity').bind('change', new_issue.display_severity_description).change();
    product.ready();
};

new_issue.validateForm = function()
{
    var form = $('form#report_form');

    var category_field = Eventum.getField('category')
    if (category_field.attr('type') != 'hidden' && category_field.val() == -1 && category_field.data('required')) {
        Validation.errors[Validation.errors.length] = new Option('Category', 'category');
    }
    var priority_field = Eventum.getField('priority')
    if (priority_field.attr('type') != 'hidden' && priority_field.val() == -1 && priority_field.data('required')) {
        Validation.errors[Validation.errors.length] = new Option('Priority', 'priority');
    }
    var severity_field = Eventum.getField('severity')
    if (severity_field.attr('type') != 'hidden' && severity_field.val() == -1 && severity_field.data('required')) {
        Validation.errors[Validation.errors.length] = new Option('Severity', 'severity');
    }
    var release_field = Eventum.getField('release')
    if (release_field.attr('type') != 'hidden' && release_field.val() == 0 && release_field.data('required')) {
        Validation.errors[Validation.errors.length] = new Option('Scheduled Release', 'release');
    }
    var expected_res_date_field = Eventum.getField('expected_resolution_date')
    if (expected_res_date_field.attr('type') != 'hidden' && expected_res_date_field.val() == '' &&
        expected_res_date_field.data('required')) {
        Validation.errors[Validation.errors.length] = new Option('Expected Resolution Date', 'expected_resolution_date');
    }
    var associated_issues_field = Eventum.getField('associated_issues')
    if (associated_issues_field.attr('type') != 'hidden' && associated_issues_field.val() == '' &&
        associated_issues_field.data('required')) {
        Validation.errors[Validation.errors.length] = new Option('Associated Issues', 'associated_issues_field');
    }
    var group_field = Eventum.getField('group')
    if (group_field.attr('type') != 'hidden' && group_field.val() == '' && group_field.data('required')) {
        Validation.errors[Validation.errors.length] = new Option('Group', 'group');
    }
    var product_field = Eventum.getField('product')
    if (product_field.attr('type') != 'hidden' && product_field.val() == -1 && product_field.data('required')) {
        Validation.errors[Validation.errors.length] = new Option('Product', 'product');
    }
    var user_field = Eventum.getField('users[]');
    if (user_field.length > 0  && user_field.data('required') && user_field.attr('type') != 'hidden' &&
        !Validation.hasOneSelected(user_field)) {
            Validation.errors[Validation.errors.length] = new Option('Assignment', 'users');
    }
    if (Validation.isFieldWhitespace('summary')) {
        Validation.errors[Validation.errors.length] = new Option('Summary', 'summary');
    }

    // replace special characters in description
    var description_field = Eventum.getField('description');
    description_field.val(Eventum.replaceSpecialCharacters(description_field.val()));

    if (Validation.isFieldWhitespace('description')) {
        Validation.errors[Validation.errors.length] = new Option('Description', 'description');
    }

    var estimated_dev_field = Eventum.getField('estimated_dev_time')
    if (estimated_dev_field.attr('type') != 'hidden' && !Validation.isFieldWhitespace(estimated_dev_field) &&
        !Validation.isFloat(estimated_dev_field.val()) && estimated_dev_field.data('required')) {
        Validation.errors[Validation.errors.length] = new Option('Estimated Dev. Time (only numbers)', 'estimated_dev_time');
    }
    Validation.checkCustomFields(form);

    // check customer fields (if function exists
    if (window.validateCustomer) {
        validateCustomer();
    }
};

new_issue.display_severity_description = function()
{
    var description = $('#severity :selected').attr('data-desc');
    if (description == undefined || description == '') {
        $('#severity_desc').hide();
    } else {
        $('#severity_desc').text(description).show();
    }
};

function anon_post() {}

anon_post.ready = function()
{
    var project_form = $('form#project_form');
    project_form.find('input,select').filter(':visible').first().focus();

    project_form.submit(function() { return Validation.checkFormSubmission(project_form, anon_post.validateProjectForm) });

    var report_form = $('form#report_form');
    report_form.find('input,select').filter(':visible').first().focus();

    report_form.submit(function() { return Validation.checkFormSubmission(report_form, anon_post.validateForm) });
};

anon_post.validateProjectForm = function(form)
{
    var project_field = Eventum.getField('project');
    if (project_field.val() == '-1') {
        Validation.errors[Validation.errors.length] = new Option('Project', 'project');
    }
};

anon_post.validateForm = function(form)
{
    if (Validation.isFieldWhitespace('summary')) {
        Validation.errors[Validation.errors.length] = new Option('Summary', 'summary');
    }

    // replace special characters in description
    var description_field = Eventum.getField('description');
    description_field.val(Eventum.replaceSpecialCharacters(description_field.val()));

    if (Validation.isFieldWhitespace('description')) {
        Validation.errors[Validation.errors.length] = new Option('Description', 'description');
    }

    Validation.checkCustomFields(form);

};

/*
 * Stats page
 */
function stats() {}

stats.ready = function()
{
    $('#hide_closed').change(function hideClosed(e) {
        var target = $(e.target);
        if (target.is(':checked')) {
            window.location.href = "?" + Eventum.replaceParam(window.location.href, 'hide_closed', '1');
        } else {
            window.location.href = "?" + Eventum.replaceParam(window.location.href, 'hide_closed', '0');
        }
    });
};


/*
 * Product chooser functions used in multiplepages
 */
function product() {}

product.ready = function()
{
    $('#product').bind('change', product.display_product_version_howto).change();
};

product.display_product_version_howto = function()
{
    var howto = $('#product :selected').attr('data-desc');
    if (howto == undefined || howto == '') {
        $('#product_version_howto').hide();
    } else {
        $('#product_version_howto').text(howto).show();
    }
};

/*
 * Preferences page
 */
function preferences() {}

preferences.ready = function()
{
    $('#show_revoked').click(function() {
        $('body#preferences .api_token .revoked').show();
    });

    $('form#api_token_form').submit(preferences.confirmRegenerateToken);

    $('form.update_name_form').submit(preferences.validateName);
    $('form.update_email_form').submit(preferences.validateEmail);
    $('form.update_password_form').submit(preferences.validatePassword);
    $('input.api_token').focus(function() {
        var $this = $(this);
        $this.select();
    });
};

preferences.validateName = function()
{
    if (Validation.isFieldWhitespace('full_name')) {
        alert('Please enter your full name.');
        Validation.selectField('full_name');
        return false;
    }
    return true;
};

preferences.validateEmail = function()
{
    if (!Validation.isEmail(Eventum.getField('email').val())) {
        alert('Please enter a valid email address.');
        Validation.selectField('email');
        return false;
    }
    return true;
};

preferences.validatePassword = function()
{
    if (Validation.isWhitespace(Eventum.getField('password').val())) {
        alert('Please input current password.');
        Validation.selectField('password');
        return false;
    }

    var new_password = Eventum.getField('new_password').val();
    if (Validation.isWhitespace(new_password) || new_password.length < 6) {
        alert('Please enter your new password with at least 6 characters.');
        Validation.selectField('new_password');
        return false;
    }
    if (new_password != Eventum.getField('confirm_password').val()) {
        alert('The two passwords do not match. Please review your information and try again.');
        Validation.selectField('confirm_password');
        return false;
    }
    return true;
};

preferences.confirmRegenerateToken = function()
{
    if (confirm("Regenerating your API Key will revoke all previous keys. Do you want to procede?")) {
        return true;
    }
    return false;
};


function custom_field_options()
{
}

custom_field_options.ready = function()
{
    $('#sortable').sortable();

    custom_field_options.bind_actions();

    $('#add_option').click(custom_field_options.add_option);
    custom_field_options.add_option();
};

custom_field_options.bind_actions = function()
{
    $('.ui-sortable-handle .delete').off('click.delete').on('click.delete', function(e) {
        $(e.target).parent().fadeOut(function() {
            $(this).remove();
        });
    });

    $('#custom_field_options input').off('keydown.blockenter').on('keydown.blockenter', function(e) {
        if(e.keyCode == 13) {
            if ($(this).prop('name') == 'new_options[]' && $(this).val() != '') {
                custom_field_options.add_option();
                $("input[name='new_options[]']:last").focus()
            }
            e.preventDefault();
            return false;
        }
    })
}

custom_field_options.add_option = function() {
    var template = $('#new_option_template').first();
    template.clone().prop('id', '').insertAfter('.new_options li:last').show()
    custom_field_options.bind_actions();
}
