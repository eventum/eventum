<!--
// @(#) $Id: s.validation.js 1.13 03/10/20 21:24:54-00:00 jpradomaia $
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

function getCustomFieldTitle(field_name)
{
    for (var i = 0; i < custom_fields.length; i++) {
        if (custom_fields[i].text == field_name) {
            return custom_fields[i].value;
        }
    }
}

function checkRequiredCustomFields(f, required_fields)
{
    for (var i = 0; i < required_fields.length; i++) {
        var field = getFormElement(f, required_fields[i].text);

        if ((field != false) && (field.parentNode.parentNode.style.display == 'none')) {
            continue;
        }

        if (required_fields[i].value == 'combo') {
            if (getSelectedOption(f, field.name) == '-1') {
                errors[errors.length] = new Option(getCustomFieldTitle(required_fields[i].text), required_fields[i].text);
            }
        } else if (required_fields[i].value == 'multiple') {
            if (!hasOneSelected(f, field.name)) {
                errors[errors.length] = new Option(getCustomFieldTitle(required_fields[i].text), required_fields[i].text);
            }
        } else if (required_fields[i].value == 'whitespace') {
            if (isWhitespace(field.value)) {
                errors[errors.length] = new Option(getCustomFieldTitle(required_fields[i].text), required_fields[i].text);
            }
        } else if (required_fields[i].value == 'date') {
            if (getFormElement(f, required_fields[i].text + '[Month]').selectedIndex == 0) {
                errors[errors.length] = new Option(getCustomFieldTitle(required_fields[i].text) + ' (Month)', required_fields[i].text + '[Month]');
            }
            if (getFormElement(f, required_fields[i].text + '[Day]').selectedIndex == 0) {
                errors[errors.length] = new Option(getCustomFieldTitle(required_fields[i].text) + ' (Day)', required_fields[i].text + '[Day]');
            }
            if (getFormElement(f, required_fields[i].text + '[Year]').selectedIndex == 0) {
                errors[errors.length] = new Option(getCustomFieldTitle(required_fields[i].text) + ' (Year)', required_fields[i].text + '[Year]');
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
            if (old_onchange != false) {
                field.onchange = old_onchange;
                eval('trash = ' + old_onchange + '(e)');
            }
        }
    } else if (field.type == 'select-one') {
        if (getSelectedOption(f, field_name) != '-1') {
            errorDetails(f, field_name, false);
            if (old_onchange != false) {
                field.onchange = old_onchange;
                eval('trash = ' + old_onchange + '(e)');
            }
        }
    } else if (field.type == 'select-multiple') {
        if (hasOneSelected(f, field_name)) {
            errorDetails(f, field_name, false);
            if (old_onchange != false) {
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
function checkFormSubmission(f, callback_func)
{
    errors = new Array();
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
    } else {
        return true;
    }
}
//-->