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

export default class {
    ready() {
        const page = this;

        page.toggleNotificationList();
        $('input[name=notification_list]').change(function() {
            page.toggleNotificationList();
        });
        $('input[name=add_email_signature]').change(function() {
            page.toggleEmailSignature();
        });

        $('form[name=close_form]').submit(function () {
            return Validation.checkFormSubmission($('form[name=close_form]'), page.validateForm);
        });

        const status_options = Eventum.getField('status').children('option');
        if (status_options.length === 2) {
            status_options[1].selected = true;
        }
    };

    toggleNotificationList() {
        const cell = $('#reason_cell');

        if ($('input#notification_internal:checked').length > 0) {
            cell.addClass("internal");
        } else {
            cell.removeClass("internal");
        }
    };

    toggleEmailSignature() {
        const $reason = $('textarea[name=reason]');
        const signature = $('#signature').text();

        if ($('input[name=add_email_signature]:checked').length > 0) {
            $reason.val($reason.val() + "\n" + signature);
        } else {
            $reason.val($reason.val().replace("\n" + signature, ''));
        }
    };

    validateForm() {
        const $form = $('form[name=close_form]');

        if ($('form [name=status]').val() == "-1") {
            Validation.errors[Validation.errors.length] = new Option('Status', 'status');
        }
        if (Validation.isWhitespace($('form [name=reason]').val())) {
            Validation.errors[Validation.errors.length] = new Option('Reason to close', 'reason');
        }

        const time_spent = $('form [name=time_spent]').val();
        if (!Validation.isWhitespace(time_spent)) {
            if (!Validation.isNumberOnly(time_spent)) {
                Validation.errors[Validation.errors.length] = new Option('Please enter integers (or floating point numbers) on the time spent field.', 'time_spent');
            }
            if ($('form [name=category]').val() === '') {
                Validation.errors[Validation.errors.length] = new Option('Time tracking category', 'category');
            }
        }

        Validation.checkCustomFields($form);

        // TODO: this needs to be double checked with a customer backend
        const has_per_incident_contract = $('tr.per_incident').length > 0;
        if (Validation.errors.length < 1 && has_per_incident_contract) {
            if ($('input[type=checkbox][name^=redeem]:checked').length > 0) {
                return confirm('This customer has a per incident contract. You have chosen not to redeem any incidents. Press \'OK\' to confirm or \'Cancel\' to revise.');
            }
        }

        return true;
    };
}
