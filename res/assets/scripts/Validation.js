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

export class Validation {
    constructor() {
        this.errors = null;
        this.errors_extra = null;
        this.last_issue_number_validation_value = '';
    }

    selectField(field) {
        field = Eventum.getField(field);
        if (field.attr('type') !== 'hidden') {
            field.focus();
        }
        this.showErrorIcon(field, true);
        const validation = this;
        field.bind('change.validation', function () {
            validation.showErrorIcon(field, false);
        });
        if (this.isWhitespace(field.val())) {
            return false;
        }
    };

    showErrorIcon(field, show) {
        const icon = $('#error_icon_' + Eventum.escapeSelector(field.attr('name')));
        if (!icon.length) {
            return false;
        }
        if (show) {
            icon.show();
            field.addClass('error_field');
        } else {
            icon.hide();
            field.removeClass('error_field');
        }
    };

    isFieldWhitespace(field) {
        field = Eventum.getField(field);
        return this.isWhitespace(field.val());
    };

    isWhitespace(s) {
        if (s == null) {
            return true;
        }

        const whitespace = " \t\n\r";

        if (s.length === 0) {
            return true;
        }

        // check for whitespace now!
        for (let z = 0; z < s.length; z++) {
            // Check that current character isn't whitespace.
            const c = s.charAt(z);
            if (whitespace.indexOf(c) === -1) {
                return false;
            }
        }

        return true;
    };

    isNumberOnly(s) {
        const check = parseFloat(s).toString();

        return s.length === check.length && check !== "NaN";
    };

    /**
     * Checks if field value is valid RGB hex number.
     * @param {String} field name of the field
     * @returns {boolean}
     */
    isFieldRGBhex(field) {
        const s = Eventum.getField(field).val();

        return !!s.match(/^#[a-f0-9]{6}$/i);
    };

    hasOneSelected(field) {
        field = Eventum.getField(field);

        return (field.val() != null && field.val().length > 0);
    };

    isEmail(s) {
        // email text field.
        const sLength = s.length;
        const denied_chars = [" ", "\n", "\t", "\r", "%", "$", "#", "!", "~", "`", "^", "&", "*", "(", ")", "=", "{", "}", "[", "]", ",", ";", ":", "'", "\"", "?", "<", ">", "/", "\\", "|"];

        // look for @
        if (s.indexOf("@") === -1) {
            return false;
        }

        // look for more than one @ sign
        if (s.indexOf("@") !== s.lastIndexOf("@")) {
            return false;
        }

        // look for any special character
        for (let z = 0; z < denied_chars.length; z++) {
            if (s.indexOf(denied_chars[z]) !== -1) {
                return false;
            }
        }

        // look for a dot, but also allow for a user@localhost address
        if (s.indexOf(".") === -1 && s.substring(s.lastIndexOf('@'), s.length) !== '@localhost') {
            return false;
        }

        // no two dots alongside each other
        if (s.indexOf("..") !== -1) {
            return false;
        }

        // you can't have and @ and a dot
        if (s.indexOf("@.") !== -1) {
            return false;
        }

        // the last character cannot be a .
        return !(s.substring(s.lastIndexOf('@'), s.length) !== '@localhost.' && (
            s.charAt(sLength - 1) === "." ||
            s.charAt(sLength - 1) === "_"));
    };

    hasOneChecked(field) {
        field = Eventum.getField(field);

        return (field.filter(':checked').length > 0);
    };

    isValidDate(field_prefix) {
        const selected_date = new Date();

        selected_date.setMonth(Eventum.getField(field_prefix + '[Month]').val() - 1);
        selected_date.setDate(Eventum.getField(field_prefix + '[Day]').val());
        selected_date.setYear(Eventum.getField(field_prefix + '[Year]').val());

        return selected_date.getDate() === Number(Eventum.getField(field_prefix + '[Day]').val());
    };

    checkFormSubmission(form, callback_func) {
        let i;
        let res, fields;

        this.errors = [];
        this.errors_extra = [];

        if (typeof (callback_func) == 'string') {
            res = eval(callback_func + '(form)');
        } else {
            res = callback_func(form);
        }
        if (res === false) {
            return false;
        }

        if (this.errors.length > 0) {
            // loop through all of the broken fields and select them
            fields = '';
            for (i = 0; i < this.errors.length; i++) {
                this.selectField(form.find("[name=" + Eventum.escapeSelector(this.errors[i].value) + "]"));
                fields += '- ' + this.errors[i].text + "\n";
            }
            // show a big alert box with the missing information
            alert("The following required fields need to be filled out:\n\n" + fields + "\nPlease complete the form and try again.");
            return false;
        }

        if (this.errors_extra.length > 0) {
            // loop through all of the broken fields and select them
            fields = '';
            for (i = 0; i < this.errors_extra.length; i++) {
                this.selectField(f, this.errors_extra[i].value);
                fields += '- ' + this.errors_extra[i].text + "\n";
            }
            // show a big alert box with the missing information
            alert("The following fields have errors that need to be resolved:\n\n" + fields + "\nPlease resolve these errors and try again.");
            return false;
        }

        return true;
    };

    checkCustomFields(form) {
        const validation = this;
        $.each(CustomField.getFieldInfo(), function (index, info) {
            let field = Eventum.getField('custom_fields[' + info.id + ']');
            if (field.length < 1) {
                field = Eventum.getField('custom_fields[' + info.id + '][]');
            }

            let value = field.val();

            if ((value == null || value.length < 1 || value == -1) &&
                field.parent().parent().css('display') === 'none') {
                return null;
            }

            if (info.required == 1) {
                if (info.type === 'combo') {
                    if (value === '' || value === '-1') {
                        validation.errors.push(new Option(info.title, field.attr('name')));
                    }
                } else if (info.type === 'multiple') {
                    if (!validation.hasOneSelected(field)) {
                        validation.errors.push(new Option(info.title, field.attr('name')));
                    }
                } else if (info.type === 'checkbox') {
                    if (!validation.hasOneChecked(field)) {
                        validation.errors.push(new Option(info.title, field.attr('name')));
                    }
                } else {
                    if (validation.isWhitespace(value)) {
                        validation.errors.push(new Option(info.title, field.attr('name')));
                    }
                }
            }
            if (info.validation_js !== '') {
                let validation_result = false;
                eval("validation_result = " + info.validation_js + '()');
                if (validation_result != true) {
                    validation.errors.push(new Option(info.title + ': ' + validation_result, field.attr('name')));
                }
            } else {
                if (info.type === 'integer') {
                    if ((!validation.isWhitespace(value)) && (!validation.isNumberOnly(value))) {
                        validation.errors.push(new Option(info.title + ': This field can only contain numbers', field.attr('name')));
                    }
                }
            }
        });
    };

    callback(e) {
        const $target = $(e.target);

        return this.checkFormSubmission($target, $target.attr('data-validation-function'))
    };

    isDigit(c) {
        return c >= "0" && c <= "9";
    };

    isFloat(s) {
        if (this.isWhitespace(s)) {
            return false;
        }

        let seenDecimalPoint = false;
        if (s === '.') {
            return false;
        }
        // Search through string's characters one by one
        // until we find a non-numeric character.
        // When we do, return false; if we don't, return true.
        for (let i = 0; i < s.length; i++) {
            // Check that current character is number.
            const c = s.charAt(i);
            if (c === '.' && !seenDecimalPoint) {
                seenDecimalPoint = true;
            } else if (!this.isDigit(c)) {
                return false;
            }
        }

        // All characters are numbers.
        return true;
    };

    validateIssueNumberField(e) {
        const $target = $(e.target);
        const form_value = $target.val();
        if (this.last_issue_number_validation_value === form_value) {
            return;
        }

        this.last_issue_number_validation_value = form_value;
        const options = {
            check_project: $target.attr('data-check-project'),
            exclude_issue: $target.attr('data-exclude-issue'),
            exclude_duplicates: $target.attr('data-exclude-duplicates'),
            error_message: $target.attr('data-error-message')
        };

        const validation = this;
        jQuery.ajax({
            url: Eventum.rel_url + 'validate.php',
            data: {
                action: 'validateIssueNumbers',
                values: form_value,
                field_id: $target.attr('id'),
                check_project: options.check_project,
                exclude_issue: options.exclude_issue,
                exclude_duplicates: options.exclude_duplicates
            },
            error_message: options.error_message,
            success: function (data) {
                const $error_span = $('#' + $target.attr('id') + '_error');
                if (data !== 'ok') {
                    validation.selectField($target);
                    let error_message = '<b>Error</b>: The following issues are invalid: ' + data;
                    if (this.error_message !== undefined) {
                        error_message += '. ' + this.error_message;
                    }
                    $error_span.html(error_message);
                } else {
                    validation.showErrorIcon($target, false);
                    $error_span.html('');
                }
            }
        });
    };
}
