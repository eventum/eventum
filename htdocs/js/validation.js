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