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

issue_view.ready = function(page_id)
{
    $('#toggle_attachments').click(function() { issue_view.toggle_issue_section('attachments'); });
    $('#toggle_time_tracking').click(function() { issue_view.toggle_issue_section('time_tracking'); });
    $('#toggle_internal_notes').click(function() { issue_view.toggle_issue_section('internal_notes'); });
    $('#toggle_phone_calls').click(function() { issue_view.toggle_issue_section('phone_calls'); });
    $('#toggle_drafts').click(function() { issue_view.toggle_issue_section('drafts'); });
    $('#toggle_support_emails').click(function() { issue_view.toggle_issue_section('support_emails'); });


    /* Attachments Section */
    $('#upload_file').click(issue_view.upload_file);
    $('#attachments .delete_attachment').click(issue_view.delete_attachment);
    $('#attachments .delete_file').click(issue_view.delete_file);
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