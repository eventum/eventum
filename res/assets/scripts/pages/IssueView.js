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
 * View Issue Page
 */
export default class {
    ready() {
        const page = this;

        $('#toggle_time_tracking').click(function () {
            page.toggle_issue_section('time_tracking');
        });
        $('#toggle_checkins').click(function () {
            page.toggle_issue_section('checkins');
        });
        $('#toggle_remote_links').click(function () {
            page.toggle_issue_section('remote_links');
        });
        $('#toggle_custom_fields').click(function () {
            page.toggle_issue_section('custom_fields');
        });
        $('#toggle_internal_notes').click(function () {
            page.toggle_issue_section('internal_notes');
        });
        $('#toggle_phone_calls').click(function () {
            page.toggle_issue_section('phone_calls');
        });
        $('#toggle_drafts').click(function () {
            page.toggle_issue_section('drafts');
        });
        $('#toggle_support_emails').click(function () {
            page.toggle_issue_section('support_emails');
        });

        $('#issue_description_link')
            .click(function() {
                page.toggle_issue_description();
            })
            .ready(function() {
                page.display_description_collapse_message();
            });

        /* Main Issue actions */
        $('.open_history').click(function() {
            return page.openHistory();
        });
        $('.open_nl').click(function() {
            return page.openNotificationList();
        });
        $('.open_ar').click(function() {
            return page.openAuthorizedReplier();
        });
        $('.self_assign').click(function() {
            page.selfAssign();
        });
        $('.unassign').click(function() {
            page.unassign();
        });
        $('.self_notification').click(function() {
            page.selfNotification();
        });
        $('.self_authorized_replier').click(function() {
            page.signupAsAuthorizedReplier();
        });
        $('.change_status').click(function(e) {
            page.changeIssueStatus(e);
        });
        $('.change_access').click(function() {
            return page.changeAccess();
        });
        $('.remove_quarantine').click(function() {
            page.removeQuarantine();
        });
        $('.clear_duplicate').click(function() {
            page.clearDuplicateStatus();
        });
        $('.reply_issue').click(function() {
            page.replyIssue();
        });
        $('.reply_issue_note').click(function() {
            page.replyIssueNote();
        });
        $('.reply_email').click(function(e) {
            page.reply(e);
        });
        $('.reply_email_note').click(function(e) {
            page.replyAsNote(e);
        });
        $('.edit_incident_redemption').click(function() {
            page.editIncidentRedemption();
        });
        $('a.edit_time_entry').click(function(e) {
            return page.editTimeEntry(e);
        });
        $('a.delete_time_entry').click(function(e) {
            return page.deleteTimeEntry(e);
        });
        $('.add_time_entry').click(function() {
            page.addTimeEntry();
        });
        $('.mark_duplicate').click(function () {
            window.location.href = 'close.php?cat=duplicate&id=' + page.get_issue_id();
        });
        $('.close_issue').click(function () {
            window.location.href = 'close.php?id=' + page.get_issue_id();
        });
        $('.display_fixed_width').click(function () {
            page.toggle_plain_view();
        });

        /* Attachments Section */
        $('#toggle_attachments').click(function () {
            page.toggle_issue_section('attachments');
        });
        $('#upload_file').click(function(e) {
            page.upload_file(e);
        });
        $('#attachments .delete_attachment').click(function(e) {
            return page.delete_attachment(e);
        });
        $('#attachments .delete_file').click(function(e) {
            return page.delete_file(e);
        });
    }

    get_issue_id() {
        return $('#issue_overview').attr('data-issue-id');
    }

    get_ema_id() {
        return $('#issue_overview').attr('data-ema-id');
    }

    toggle_issue_description() {
        Eventum.toggle_section_visibility('issue_description');

        this.display_description_collapse_message();
    }

    display_description_collapse_message() {
        const hidden = $('#description_hidden');

        if ($('#issue_description:visible').length > 0) {
            hidden.hide();
        } else {
            hidden.show();
        }
    }

    toggle_plain_view() {
        const $formatted = $('#description_formatted');
        const $plain = $('#description_plain');
        const $link = $('#fixed_width_link');

        if (!$plain.is(':visible')) {
            $formatted.hide();
            $plain.show();
            $link.text($link.attr('data-html'));
        } else {
            $plain.hide();
            $formatted.show();
            $link.text($link.attr('data-plain'));
        }
    }

    toggle_issue_section(id) {
        const $element = $('#' + id + ' div.content');
        let display = '';
        let link_title = '';

        if ($element.is(':visible')) {
            display = 'none';
            $element.hide();
            link_title = 'show';
        } else {
            display = 'block';
            $element.show();
            link_title = 'hide';
        }

        $('#toggle_' + id).text(link_title);
        Cookie.set('visibility_' + id, display);
    }

    openHistory() {
        const features = 'width=890,height=400,top=30,left=30,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
        const issueId = this.get_issue_id();
        const url = `history.php?iss_id=${issueId}`;
        const popupWin = window.open(url, '_history', features);
        popupWin.focus();

        return false;
    }

    openNotificationList() {
        const features = 'width=540,height=400,top=30,left=30,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
        const issueId = this.get_issue_id();
        const url = `notification.php?iss_id=${issueId}`;
        const popupWin = window.open(url, '_notification', features);
        popupWin.focus();

        return false;
    }

    openAuthorizedReplier() {
        const features = 'width=440,height=400,top=30,left=30,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
        const issueId = this.get_issue_id();
        const url = `authorized_replier.php?iss_id=${issueId}`;
        const popupWin = window.open(url, '_replier', features);
        popupWin.focus();

        return false;
    }

    changeAccess() {
        const features = 'width=440,height=400,top=30,left=30,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
        const issueId = this.get_issue_id();
        const url = `access.php?iss_id=${issueId}`;
        const popupWin = window.open(url, '_access', features);
        popupWin.focus();

        return false;
    }

    selfAssign() {
        const features = 'width=420,height=170,top=30,left=30,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
        const issueId = this.get_issue_id();
        const url = `self_assign.php?iss_id=${issueId}`;
        const popupWin = window.open(url, '_selfAssign', features);

        popupWin.focus();
    }

    unassign() {
        const features = 'width=420,height=400,top=30,left=30,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
        const issueId = this.get_issue_id();
        const url = `popup.php?cat=unassign&iss_id=${issueId}`;
        const popupWin = window.open(url, '_unassign', features);

        popupWin.focus();
    }

    selfNotification() {
        const features = 'width=440,height=400,top=30,left=30,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
        const issueId = this.get_issue_id();
        const url = `popup.php?iss_id=${issueId}&cat=selfnotify`;
        const popupWin = window.open(url, '_notification', features);

        popupWin.focus();
    }

    signupAsAuthorizedReplier() {
        const features = 'width=420,height=400,top=30,left=30,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
        const issueId = this.get_issue_id();
        const url = `popup.php?cat=authorize_reply&iss_id=${issueId}`;
        const popupWin = window.open(url, '_authorizeReply', features);

        popupWin.focus();
    }

    changeIssueStatus(e) {
        const $target = $(e.target);
        const current_status_id = $target.attr('data-status-id');
        const $newStatus = $('#new_status');
        const new_status = $newStatus.val();

        if (new_status === current_status_id) {
            Validation.selectField($newStatus);
            alert('Please select the new status for this issue.');
            return;
        }

        const features = 'width=420,height=400,top=30,left=30,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
        const issueId = this.get_issue_id();
        const url = `popup.php?cat=new_status&iss_id=${issueId}&new_sta_id=${new_status}`;

        const popupWin = window.open(url, '_newStatus', features);
        popupWin.focus();
    }

    updateCustomFields(e) {
        const issue_id = $(e.target).attr('data-issue-id');
        const features = 'width=560,height=460,top=30,left=30,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
        const url = `custom_fields.php?issue_id=${issue_id}`;
        const customWin = window.open(url, '_custom_fields', features);

        customWin.focus();
    }

    upload_file(e) {
        const $target = $(e.target);
        const issue_id = $target.attr('data-issue-id');
        const features = 'width=600,height=350,top=30,left=30,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
        const url = `file_upload.php?iss_id=${issue_id}`;
        const popupWin = window.open(url, 'file_upload_' + issue_id, features);

        popupWin.focus();
    }

    delete_attachment(e) {
        const iat_id = $(e.target).attr('data-iat-id');
        if (!confirm('This action will permanently delete the selected attachment.')) {
            return false;
        }

        const features = 'width=420,height=200,top=30,left=30,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
        const url = `popup.php?cat=delete_attachment&id=${iat_id}`;
        const popupWin = window.open(url, '_popup', features);

        popupWin.focus();
    }

    delete_file(e) {
        const $target = $(e.target);
        const iaf_id = $target.attr('data-iaf-id');
        if (!confirm('This action will permanently delete the selected file.')) {
            return false;
        }

        const features = 'width=420,height=200,top=30,left=30,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
        const url = `popup.php?cat=delete_file&id=${iaf_id}`;
        const popupWin = window.open(url, '_popup', features);

        popupWin.focus();
    }

    removeQuarantine() {
        const features = 'width=420,height=400,top=30,left=30,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
        const issueId = this.get_issue_id();
        const url = `popup.php?cat=remove_quarantine&iss_id=${issueId}`;
        const popupWin = window.open(url, '_removeQuarantine', features);

        popupWin.focus();
    }

    replyIssue() {
        const features = 'width=740,height=680,top=30,left=30,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
        const issueId = this.get_issue_id();
        const emaId = this.get_ema_id();
        const url = `send.php?cat=reply&ema_id=${emaId}&issue_id=${issueId}`;
        const popupWin = window.open(url, '_replyIssue' + issueId, features);

        popupWin.focus();
    }

    replyIssueNote() {
        const features = 'width=740,height=580,top=30,left=30,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
        const issueId = this.get_issue_id();
        const url = `post_note.php?cat=issue_reply&issue_id=${issueId}`;
        const popupWin = window.open(url, '_replyIssueNote' + issueId, features);

        popupWin.focus();
    }

    replyAsNote(e) {
        const $target = $(e.target);
        const email_id = $target.data('sup_id');
        const iss_id = this.get_issue_id();
        const account_id = this.get_ema_id();

        const features = 'width=740,height=740,top=30,left=30,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
        const url = `post_note.php?cat=email_reply&issue_id=${iss_id}&ema_id=${account_id}&id=${email_id}`;
        const emailWin = window.open(url, '_noteReply' + email_id, features);

        emailWin.focus();
    }

    reply(e) {
        const $target = $(e.target);
        const email_id = $target.data('sup_id');
        const iss_id = this.get_issue_id();
        const account_id = this.get_ema_id();

        const features = 'width=740,height=580,top=30,left=30,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
        const url = `send.php?issue_id=${iss_id}&ema_id=${account_id}&id=${email_id}`;
        const emailWin = window.open(url, '_emailReply' + email_id, features);

        emailWin.focus();
    }

    clearDuplicateStatus() {
        const features = 'width=420,height=400,top=30,left=30,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
        const issueId = this.get_issue_id();
        const url = `popup.php?cat=clear_duplicate&iss_id=${issueId}`;
        const popupWin = window.open(url, '_clearDuplicate', features);

        popupWin.focus();
    }

    editIncidentRedemption() {
        const features = 'width=300,height=300,top=30,left=30,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
        const issueId = this.get_issue_id();
        const url = 'redeem_incident.php?iss_id=' + issueId;
        const popupWin = window.open(url, '_flagIncident', features);

        popupWin.focus();
    }

    openReporter(issue_id) {
        const features = 'width=440,height=400,top=30,left=30,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
        const url = `edit_reporter.php?iss_id=${issue_id}`;
        const popupWin = window.open(url, '_reporter', features);

        popupWin.focus();
    }

    deleteTimeEntry(e) {
        const $target = $(e.target);
        const ttr_id = $target.data('ttr-id');
        const warning_msg = $target.closest('form').data('delete-warning');
        if (!confirm(warning_msg)) {
            return false;
        }

        const features = 'width=420,height=200,top=30,left=30,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
        const url = `popup.php?cat=delete_time&id=${ttr_id}`;
        const popupWin = window.open(url, '_popup', features);
        popupWin.focus();

        return false;
    }

    addTimeEntry() {
        const features = 'width=550,height=250,top=30,left=30,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
        const issueId = this.get_issue_id();
        const url = `time_tracking.php?iss_id=${issueId}`;
        const popupWin = window.open(url, 'time_tracking_' + issueId, features);

        popupWin.focus();
    };

    editTimeEntry(e) {
        const $target = $(e.target);
        const features = 'width=550,height=250,top=30,left=30,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';

        const ttr_id = $target.closest('a').data('ttr-id');
        const url = `time_tracking.php?ttr_id=${ttr_id}`;
        const popupWin = window.open(url, 'time_tracking_edit_' + ttr_id, features);

        popupWin.focus();

        return false;
    }
}

