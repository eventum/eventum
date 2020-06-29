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
 * Update Issue Page
 */
export default class {
    ready(page_id) {
        const page = this;
        const $updateForm = $('#update_form');

        const validateAction = function () {
            return Validation.checkFormSubmission($updateForm, page.validateForm);
        };
        $updateForm.submit(validateAction);

        // remove validation if hitting cancel
        $updateForm.find('[name=cancel]').click(function () {
            $updateForm.unbind('submit', validateAction);
        });

        $('#clear_selected').click(function () {
            Eventum.clearSelectedOptions('assignments[]');
        });

        $('.close_issue').unbind('click', issue_view.closeIssue).click(page.closeIssue);

        Eventum.setupShowSelections($('#assignments'));

        $('.open_history').click(function() {
            return issue_view.openHistory();
        });
        $('.open_nl').click(function() {
            return issue_view.openNotificationList();
        });
        $('.open_ar').click(function() {
            return issue_view.openAuthorizedReplier();
        });

        $('#severity').bind('change', function() {
            page.display_severity_description();
        }).change();
    }

    validateForm() {
        const $form = $('#update_form');

        if (Validation.isFieldWhitespace('summary')) {
            Validation.errors[Validation.errors.length] = new Option('Summary', 'summary');
        }
        if (Validation.isFieldWhitespace('description')) {
            Validation.errors[Validation.errors.length] = new Option('Description', 'description');
        }
        var percent_complete = Eventum.getField('percent_complete').val();
        if (percent_complete !== '' && (percent_complete < 0 || percent_complete > 100)) {
            Validation.errors[Validation.errors.length] = new Option('Percentage complete should be between 0 and 100', 'percent_complete');

            return false;
        }

        Validation.checkCustomFields($form);

        return true;
    }

    closeIssue(e) {
        if (confirm('Warning: All changes to this issue will be lost if you continue and close this issue.')) {
            window.location.href = 'close.php?id=' + issue_view.get_issue_id();
        }
        e.preventDefault();
    }

    display_severity_description() {
        const description = $('#severity :selected').attr('data-desc');
        if (!description) {
            $('#severity_desc').hide();
        } else {
            $('#severity_desc').text(description).show();
        }
    }
}
