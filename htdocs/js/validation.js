/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */

last_issue_number_validation_value = '';
function validateIssueNumberField(baseURL, form_name, field_name, options)
{
    form_value = getFormElement(getForm(form_name), field_name).value;
    if (last_issue_number_validation_value == form_value) {
        return;
    } else {
        last_issue_number_validation_value = form_value;
    }
    if (options.check_project != undefined) {
        var check_project = options.check_project;
    } else {
        var check_project = 1;
    }

    jQuery.ajax({
            url: baseURL + '/validate.php',
            data: {
                action: 'validateIssueNumbers',
                values: form_value,
                field_name: field_name,
                form_name: form_name,
                check_project: check_project,
                exclude_issue: options.exclude_issue,
                exclude_duplicates: options.exclude_duplicates
            },
            error_message: options.error_message,
            success: function(data, textStatus) {
                var chunks = data.split(':',3);
                f = getForm(chunks[0]);
                error_span = getPageElement(chunks[1] + '_error');
                if (chunks[2] != 'ok') {
                    selectField(f, chunks[1]);
                    error_span.innerHTML = '<b>Error</b>: The following issues are invalid: ' + chunks[2];
                    if (this.error_message != undefined) {
                        error_span.innerHTML += '. ' + this.error_message;
                    }
                } else {
                    errorDetails(f, chunks[1], false);
                    error_span.innerHTML = '';
                }
            }
     });
}

function isValidDate(f, field_prefix)
{
    var selected_date = new Date();
    selected_date.setMonth(getSelectedOption(f, field_prefix + '[Month]')-1);
    selected_date.setDate(getSelectedOption(f, field_prefix + '[Day]'));
    selected_date.setYear(getSelectedOption(f, field_prefix + '[Year]'));

    if (selected_date.getDate() != getSelectedOption(f, field_prefix + '[Day]')) {
        return false;
    } else {
        return true;
    }
}

function resetForm(f)
{
    if (confirm('This action will clear out any changes you performed on this form.')) {
        f.reset();
        return true;
    } else {
        return false;
    }
}

function confirmCloseWindow()
{
    if (confirm('Closing this window will mean losing any changes you may have performed.')) {
        checkWindowClose(false);
        window.close();
    }
}

function isWhitespace(s)
{
    var whitespace = " \t\n\r";

    if (s.length == 0) {
        // empty field!
        return true;
    } else {
        // check for whitespace now!
        for (var z = 0; z < s.length; z++) {
            // Check that current character isn't whitespace.
            var c = s.charAt(z);
            if (whitespace.indexOf(c) == -1) return false;
        }
        return true;
    }
}

function isEmail(s)
{
    // email text field.
    var sLength = s.length;
    var denied_chars = new Array(" ", "\n", "\t", "\r", "%", "$", "#", "!", "~", "`", "^", "&", "*", "(", ")", "=", "{", "}", "[", "]", ",", ";", ":", "'", "\"", "?", "<", ">", "/", "\\", "|");

    // look for @
    if (s.indexOf("@") == -1) return false;

    // look for more than one @ sign
    if (s.indexOf("@") != s.lastIndexOf("@")) return false;

    // look for any special character
    for (var z = 0; z < denied_chars.length; z++) {
        if (s.indexOf(denied_chars[z]) != -1) return false;
    }

    // look for a dot, but also allow for a user@localhost address
    if ((s.indexOf(".") == -1) && (s.substring(s.lastIndexOf('@'), s.length) != '@localhost')) {
        return false;
    }

    // no two dots alongside each other
    if (s.indexOf("..") != -1) return false;

    // you can't have and @ and a dot
    if (s.indexOf("@.") != -1) return false;

    // the last character cannot be a .
    if ((s.substring(s.lastIndexOf('@'), s.length) != '@localhost.') && (
            (s.charAt(sLength-1) == ".") ||
            (s.charAt(sLength-1) == "_"))) {
        return false;
    }

    return true;
}

function hasDeniedChars(s)
{
    var denied_chars = new Array(" ", "\n", "\t", "\r", "%", "$", "#", "!", "~", "`", "^", "&", "*", "(", ")", "=", "+", "{", "}", "[", "]", ",", ";", ":", "'", "\"", "?", "<", ">", "/", "\\", "|");

    for (var z = 0; z < denied_chars.length; z++) {
        if (s.indexOf(denied_chars[z]) != -1) return true;
        // checking for any non-ascii character
        if (s.charCodeAt(z) > 128) return true;
    }

    return false;
}

function hasOneSelected(f, field_name)
{
    for (var i = 0; i < f.elements.length; i++) {
        if (f.elements[i].name == field_name) {
            var multi = f.elements[i];
            for (var y = 0; y < multi.options.length; y++) {
                if (multi.options[y].selected) {
                    return true;
                }
            }
        }
    }
    return false;
}

function hasSelected(field, value)
{
    return field.options[field.selectedIndex].value == value;
}

function hasOneChecked(f, field_name)
{
    var found = 0;
    for (var i = 0; i < f.elements.length; i++) {
        if ((f.elements[i].name == field_name) && (f.elements[i].checked)) {
            found = 1;
        }
    }
    if (found == 0) {
        return false;
    } else {
        return true;
    }
}

function isNumberOnly(s)
{
    var check = parseFloat(s).toString();
    if ((s.length == check.length) && (check != "NaN")) {
        return true;
    } else {
        return false;
    }
}

function isDigit(c)
{
    return ((c >= "0") && (c <= "9"));
}

function isFloat(s)
{
    if (isWhitespace(s)) {
        return false;
    }

    var seenDecimalPoint = false;
    if (s == '.') {
        return false;
    }
    // Search through string's characters one by one
    // until we find a non-numeric character.
    // When we do, return false; if we don't, return true.
    for (var i = 0; i < s.length; i++) {
        // Check that current character is number.
        var c = s.charAt(i);
        if ((c == '.') && !seenDecimalPoint) {
            seenDecimalPoint = true;
        } else if (!isDigit(c)) {
            return false;
        }
    }

    // All characters are numbers.
    return true;
}

function startsWith(s, substr)
{
    if (s.indexOf(substr) == 0) {
        return true;
    } else {
        return false;
    }
}

function errorDetails(f, field_name, show)
{
    var field = getFormElement(f, field_name);
    var icon = getPageElement('error_icon_' + field_name);
    if (icon == null) {
        return false;
    }
    if (show) {
        field.style.backgroundColor = '#FF9999';
        icon.style.visibility = 'visible';
        icon.width = 14;
        icon.height = 14;
    } else {
        field.style.backgroundColor = '#FFFFFF';
        icon.style.visibility = 'hidden';
        icon.width = 1;
        icon.height = 1;
    }
}

function checkCustomFields(f)
{
    // requires the variable custom_fields_info to be set
    for (var i = 0; i < custom_fields_info.length; i++) {
        var info = custom_fields_info[i];
        var field = $('#custom_field_' + info.id);

        if (((field.val() == null || field.val().length < 1) || 
                (field.val() == -1)) && 
                (field.parent().parent().css('display') == 'none')) {
            continue;
        }

        if (info.required == 1) {
            if (info.type == 'combo') {
                if (field.val() == '' || getSelectedOption(f, field.attr('name')) == '-1') {
                    errors[errors.length] = new Option(info.title, field.attr('name'));
                }
            } else if (info.type == 'multiple') {
                if (!hasOneSelected(f, field.attr('name'))) {
                    errors[errors.length] = new Option(info.title, field.attr('name'));
                }
            } else {
                if (isWhitespace(field.val())) {
                    errors[errors.length] = new Option(info.title, field.attr('name'));
                }
            }
        }
        if (info.validation_js != '') {
            eval("validation_result = " + info.validation_js + '()');
            if (validation_result != true) {
                errors_extra[errors_extra.length] = new Option(info.title + ': ' + validation_result, field.attr('name'));
            }
        } else {
            if (info.type == 'integer') {
                if ((!isWhitespace(field.val())) && (!isNumberOnly(field.val()))) {
                    errors_extra[errors_extra.length] = new Option(info.title + ': This field can only contain numbers', field.attr('name'));
                }
            }
        }

    }
}

function checkErrorCondition(e, form_name, field_name, old_onchange)
{
    var f = getForm(form_name);
    var field = getFormElement(f, field_name);
    if ((field.type == 'text') || (field.type == 'textarea') || (field.type == 'password')) {
        if (!isWhitespace(field.value)) {
            errorDetails(f, field_name, false);
            if (old_onchange != false && old_onchange != undefined) {
                field.onchange = old_onchange;
                eval('trash = ' + old_onchange + '(e)');
            }
        }
    } else if (field.type == 'select-one') {
        if (getSelectedOption(f, field_name) != '-1') {
            errorDetails(f, field_name, false);
            if (old_onchange != false && old_onchange != undefined) {
                field.onchange = old_onchange;
                eval('trash = ' + old_onchange + '(e)');
            }
        }
    } else if (field.type == 'select-multiple') {
        if (hasOneSelected(f, field_name)) {
            errorDetails(f, field_name, false);
            if (old_onchange != false && old_onchange != undefined) {
                field.onchange = old_onchange;
                eval('trash = ' + old_onchange + '(e)');
            }
        }
    }
}

function selectField(f, field_name, old_onchange)
{
    for (var i = 0; i < f.elements.length; i++) {
        if (f.elements[i].name == field_name) {
            if (f.elements[i].type != 'hidden') {
                f.elements[i].focus();
            }
            errorDetails(f, field_name, true);
            if (isWhitespace(f.name)) {
                return false;
            }
            f.elements[i].onchange = new Function('e', 'checkErrorCondition(e, \'' + f.name + '\', \'' + field_name + '\', ' + old_onchange + ');');
            if (f.elements[i].select) {
                f.elements[i].select();
            }
        }
    }
}

function getSelectedOption(f, field_name)
{
    for (var i = 0; i < f.elements.length; i++) {
        if (f.elements[i].name == field_name) {
            if (f.elements[i].options.length > 0) {
                if (f.elements[i].selectedIndex == -1) {
                    return -1;
                }
                return f.elements[i].options[f.elements[i].selectedIndex].value;
            } else {
                return -1;
            }
        }
    }
}

function getSelectedOptionObject(f, field_name)
{
    for (var i = 0; i < f.elements.length; i++) {
        if (f.elements[i].name == field_name) {
            return f.elements[i].options[f.elements[i].selectedIndex];
        }
    }
}

var errors = null;
var errors_extra = null;
function checkFormSubmission(f, callback_func)
{
    errors = new Array();
    errors_extra = new Array();
    eval(callback_func + '(f);');
    if (errors.length > 0) {
        // loop through all of the broken fields and select them
        var fields = '';
        for (var i = 0; i < errors.length; i++) {
            if (getFormElement(f, errors[i].value).onchange != undefined) {
                old_onchange = getFormElement(f, errors[i].value).onchange;
            } else {
                old_onchange = false;
            }
            selectField(f, errors[i].value, old_onchange);
            fields += '- ' + errors[i].text + "\n";
        }
        // show a big alert box with the missing information
        alert("The following required fields need to be filled out:\n\n" + fields + "\nPlease complete the form and try again.");
        return false;
    } else if (errors_extra.length > 0) {
        // loop through all of the broken fields and select them
        var fields = '';
        for (var i = 0; i < errors_extra.length; i++) {
            if (getFormElement(f, errors_extra[i].value).onchange != undefined) {
                old_onchange = getFormElement(f, errors_extra[i].value).onchange;
            } else {
                old_onchange = false;
            }
            selectField(f, errors_extra[i].value, old_onchange);
            fields += '- ' + errors_extra[i].text + "\n";
        }
        // show a big alert box with the missing information
        alert("The following fields have errors that need to be resolved:\n\n" + fields + "\nPlease resolve these errors and try again.");
        return false;
    } else {
        return true;
    }
}
