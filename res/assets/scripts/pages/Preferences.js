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

        $('#show_revoked').click(function () {
            $('body#preferences .api_token .revoked').show();
        });
        $('form#api_token_form').submit(function() {
            return page.confirmRegenerateToken();
        });
        $('form.update_name_form').submit(function() {
            return page.validateName();
        });
        $('form.update_email_form').submit(function() {
            return page.validateEmail();
        });
        $('form.update_password_form').submit(function() {
            return page.validatePassword();
        });
        $('input.api_token').focus(function () {
            const $this = $(this);
            $this.select();
        });
    }

    validateName() {
        if (Validation.isFieldWhitespace('full_name')) {
            alert('Please enter your full name.');
            Validation.selectField('full_name');
            return false;
        }

        return true;
    }

    validateEmail() {
        if (!Validation.isEmail(Eventum.getField('email').val())) {
            alert('Please enter a valid email address.');
            Validation.selectField('email');
            return false;
        }

        return true;
    }

    validatePassword() {
        if (Validation.isWhitespace(Eventum.getField('password').val())) {
            alert('Please input current password.');
            Validation.selectField('password');
            return false;
        }

        const new_password = Eventum.getField('new_password').val();
        if (Validation.isWhitespace(new_password) || new_password.length < 6) {
            alert('Please enter your new password with at least 6 characters.');
            Validation.selectField('new_password');
            return false;
        }

        if (new_password !== Eventum.getField('confirm_password').val()) {
            alert('The two passwords do not match. Please review your information and try again.');
            Validation.selectField('confirm_password');
            return false;
        }

        return true;
    }

    confirmRegenerateToken() {
        return confirm("Regenerating your API Key will revoke all previous keys. Do you want to proceed?");
    }
}
