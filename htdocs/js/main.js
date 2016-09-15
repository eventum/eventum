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

function Eventum()
{
}

$(document).ready(function() {

    // see http://api.jquery.com/jQuery.param/
    jQuery.ajaxSettings.traditional = true;

    // check the class of the body and try to execute the prep functions if there is a class defined for that
    var $body = $("body");
    var classes = $body.attr('class').split(" ");
    var page_id = $body.attr('id');
    classes.push(page_id);
    $.each(classes, function(indexInArray, className) {
        if (className == '') {
            return
        }
        className = className.replace('-', '_');
        if (className != 'new' && eval("typeof " + className) !== "undefined" &&
                eval("typeof " + className + '.ready') == 'function') {
            eval(className + '.ready(page_id)');
        }
    });

    // focus on the first text input field in the first field on the page
    $(":text:visible:enabled .autofocus").first().focus();

    $('.close_window').click(function() { window.close(); });

    window.onbeforeunload = Eventum.handleClose;

    $('form.validate').submit(Validation.callback);

    ExpandableCell.ready();

    var $head = $('head');
    Eventum.rel_url = $head.attr('data-rel-url');

    $('#project_chooser').change(function() {
        $(this).find('form').submit();
    });

    $('#change_clock_status').click(Eventum.changeClockStatus);

    $( ".date_picker" ).datepicker({
        dateFormat: "yy-mm-dd",
        firstDay: user_prefs.week_firstday
    });

    $('#shortcut_form').submit(function(e) {
        var target = $('#shortcut');
        if (!Validation.isNumberOnly(target.val())) {
            alert('Please enter numbers only');
            return false;
        }
    });

    $("a.help").click(Eventum.openHelp);

    $("input.issue_field").blur(Validation.validateIssueNumberField);

    // % complete progressbar
    $("div.iss_percent_complete").each(function() {
        var $e = $(this);
        $e.progressbar({value: $e.data('percent')});
    });

    // chosen config
    var config = {
        '.chosen-select'           : {},
        '.chosen-select-deselect'  : {allow_single_deselect:true},
        '.chosen-select-no-single' : {disable_search_threshold:10},
        '.chosen-select-no-results': {no_results_text:'Oops, nothing found!'},
        '.chosen-select-width'     : {width:"95%"}
    };
    for (var selector in config) {
        $(selector).chosen(config[selector]);
    }

    // autosize
    autosize($('textarea'));

    // jquery timeago
    var $timeago = $('time.timeago');
    // on click toggle between views
    var timeago_toggle = function () {
        var $el = $(this);
        var old = $el.attr('title');
        $el.attr('title', $el.text());
        $el.text(old);
    };
    if (user_prefs.relative_date) {
        // if enabled, then enable for all elements
        $timeago
            .timeago()
            .click(timeago_toggle);
    } else {
        // otherwise enable only on click
        $timeago.click(function () {
            $(this)
                .timeago()
                .unbind('click')
                .click(timeago_toggle);
        })
    }

    Eventum.setupTrimmedEmailToggle();
});

Eventum.TrimmedEmailToggleFunction = function () {
    var $div = $(this).parent().parent().find('div.email-trimmed');
    if ($div.hasClass('hidden')) {
        $div.removeClass('hidden')
    } else {
        $div.addClass('hidden')
    }
    return false;
};

// click to open trimmed emails
Eventum.setupTrimmedEmailToggle = function () {
    $('span.toggle-trimmed-email').find('a')
        .off('click', Eventum.TrimmedEmailToggleFunction)
        .on('click', Eventum.TrimmedEmailToggleFunction);
};

Eventum.expires = new Date(new Date().getTime() + (56 * 86400000));
Eventum.checkClose = false;
Eventum.closeConfirmMessage = 'Do you want to close this window?';
Eventum.rel_url = '';

Eventum.toggle_section_visibility = function(id) {
    var element = $('#' + id);
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

    $('#' + id + '_link').text(link_title);

    $.cookie('visibility_' + id, display, {expires: Eventum.expires});
};

Eventum.close_and_refresh = function(noparent)
{
    if (opener && !noparent) {
        opener.location.href = opener.location;
    }
    window.close();
};

Eventum.displayFixedWidth = function(element)
{
    element.addClass('fixed_width')
};

Eventum.selectOnlyValidOption = function(select)
{
    var val = select.val();
    if (select[0].selectedIndex == 0) {
        if (select[0].length == 1) {
            select[0].selectedIndex = 0;
            return;
        }
        if (select[0].length <= 2 && select[0].options[0].value == -1) {
            select[0].selectedIndex = 1;
            return;
        }
    }
};

Eventum.escapeSelector = function(selector)
{
    return selector.replace(/(\[|\])/g, '\\$1')
};

Eventum.getField = function(name_or_obj, form)
{
    if ($.type(name_or_obj) == 'string') {
        if (form) {
            return form.find('[name="' + name_or_obj + '"]');
        } else {
            return $('[name="' + name_or_obj + '"]')
        }
    }
    return name_or_obj;
};

Eventum.getOpenerPageElement = function(id)
{
    return window.opener.$('#' + id);
};

Eventum.toggleCheckAll = function(field_name)
{
    var fields = Eventum.getField(field_name).not(':disabled');
    fields.prop('checked', !fields.prop('checked'));
};

Eventum.clearSelectedOptions = function(field)
{
    field = Eventum.getField(field);
    field.val('');
};

Eventum.selectOption = function(field, new_values)
{
    // adds the specified values to the list of select options

    field = Eventum.getField(field);

    var values = field.val();

    if (!jQuery.isArray(values)) {
        field.val(new_values);
    } else {
        if (values == null) {
            values = [];
        }
        values.push(new_values);
        field.val(values);
    }
};

Eventum.removeOptionByValue = function(field, value)
{
    field = Eventum.getField(field);
    for (var i = 0; i < field[0].options.length; i++) {
        if (field[0].options[i].value == value) {
            field[0].options[i] = null;
        }
    }
};

Eventum.selectAllOptions = function(field)
{
    Eventum.getField(field).find('option').each(function() { this.selected = true; });
};

Eventum.addOptions = function(field, options)
{
    field = Eventum.getField(field);
    $.each(options, function(index, value) {
        var option = new Option(value.text, value.value);
        if (!Eventum.optionExists(field, option)) {
            field.append(option);
        }
    });
};

Eventum.optionExists = function(field, option)
{
    field = Eventum.getField(field);
    option = $(option);
    if (field.find('option[value="' + Eventum.escapeSelector(option.val()) + '"]').length > 0) {
        return true;
    }
    return false;
};

Eventum.removeAllOptions = function(field)
{
    field = Eventum.getField(field);
    field.html('');
};

Eventum.replaceParam = function(str, param, new_value)
{
    if (str.indexOf("?") == -1) {
        return param + "=" + new_value;
    } else {
        var pieces = str.split("?");
        var params = pieces[1].split("&");
        var new_params = [];
        for (var i = 0; i < params.length; i++) {
            if (params[i].indexOf(param + "=") == 0) {
                params[i] = param + "=" + new_value;
            }
            new_params[i] = params[i];
        }
        // check if the parameter doesn't exist on the URL
        if ((str.indexOf("?" + param + "=") == -1) && (str.indexOf("&" + param + "=") == -1)) {
            new_params[new_params.length] = param + "=" + new_value;
        }
        return new_params.join("&");
    }
};

Eventum.handleClose = function()
{
    if (Eventum.checkClose == true) {
        return Eventum.closeConfirmMessage;
    }
};

Eventum.checkWindowClose = function(msg)
{
    if (!msg) {
        Eventum.checkClose = false;
    } else {
        Eventum.checkClose = true;
        Eventum.closeConfirmMessage = msg;
    }
};

Eventum.updateTimeFields = function(f, year_field, month_field, day_field, hour_field, minute_field, date)
{
    function padDateValue(str)
    {
        str = new String(str);
        if (str.length == 1) {
            str = '0' + str;
        }
        return str + '';// hack to make this a string
    }
    if (typeof date == 'undefined') {
        date = new Date();
    }
    Eventum.selectOption(month_field, padDateValue(date.getMonth()+1));
    Eventum.selectOption(day_field, padDateValue(date.getDate()));
    Eventum.selectOption(year_field, date.getFullYear());
    Eventum.selectOption(hour_field, padDateValue(date.getHours()));
    // minutes need special case due the 5 minute granularity
    var minutes = Math.floor(date.getMinutes() / 5) * 5;
    Eventum.selectOption(minute_field, padDateValue(minutes));
};

Eventum.setupShowSelections = function(select_box)
{
    select_box.change(Eventum.showSelections);
    select_box.change();
};

Eventum.showSelections = function(e)
{
        var select_box = $(e.target);
        var selected = [];
        if (select_box.val() != null) {
            $.each(select_box.val(), function(index, value) {
                selected.push(select_box.find("option[value='" + value + "']").text());
            });
        }

        var display_div = $('#selection_' + select_box.attr('id'));
        display_div.text("Current Selection: " +select_box.children(':selected').map(function(){
            return this.text
        }).get().join(", "));
};

Eventum.changeVisibility = function(dom_id, visibility)
{
    $('#' + dom_id).toggle(visibility);
};

// Replace special characters MS uses for quotes with normal versions
Eventum.replaceSpecialCharacters = function(s)
{
    var newString = '';
    var thisChar;
    var charCode;
    for (var i = 0; i < s.length; i++) {
        thisChar = s.charAt(i);
        charCode = s.charCodeAt(i);
        if ((charCode == 8220) || (charCode == 8221)) {
            thisChar = '"';
        } else if (charCode == 8217) {
            thisChar = "'";
        } else if (charCode == 8230) {
            thisChar = "...";
        } else if (charCode == 8226) {
            thisChar = "*";
        } else if (charCode == 8211) {
            thisChar = "-";
        }
        newString = newString + thisChar;
    }
    return newString;
};

/**
 * Make javascript Date() object from datetime form selection.
 *
 * @param   {String}  name    Form element prefix for date.
 */
Eventum.makeDate = function(name) {
    var d = new Date();
    d.setFullYear(Eventum.getField(name + '[Year]').val());
    d.setMonth(Eventum.getField(name + '[Month]').val() - 1);
    d.setMonth(Eventum.getField(name + '[Month]').val() - 1, Eventum.getField(name + '[Day]').val());
    d.setHours(Eventum.getField(name + '[Hour]').val());
    d.setMinutes(Eventum.getField(name + '[Minute]').val());
    d.setSeconds(0);
    return d;
};

/**
 * @param   {Object}  f       Form object
 * @param   {int} type    The type of update occurring.
 *                          0 = Duration was updated.
 *                          1 = Start time was updated.
 *                          2 = End time was updated.
 *                          11 = Start time refresh icon was clicked.
 *                          12 = End time refresh icon was clicked.
 * @param {String} element Name of the element changed
 */
Eventum.calcDateDiff = function(f, type, element)
{
    var duration = Eventum.getField('time_spent').val();
    // enforce 5 minute granularity.
    duration = Math.floor(duration / 5) * 5;

    var d1 = Eventum.makeDate('date');
    var d2 = Eventum.makeDate('date2');

    var minute = 1000 * 60;
    /*
    - if time is adjusted, duration is calculated,
    - if duration is adjusted, the end time is adjusted,
    - clicking refresh icon on either icons will make that time current date
      and recalculate duration.
    */

    if (type == 0) { // duration
        d1.setTime(d2.getTime() - duration * minute);
    } else if (type == 1) { // start time
        if (element == 'date[Year]' || element == 'date[Month]' || element == 'date[Day]') {
            d2.setTime(d1.getTime() + duration * minute);
        } else {
            duration = (d2.getTime() - d1.getTime()) / minute;
        }
    } else if (type == 2) { // end time
        duration = (d2.getTime() - d1.getTime()) / minute;
    } else if (type == 11) { // refresh start time
        if (duration) {
            d2.setTime(d1.getTime() + duration * minute);
        } else {
            duration = (d2.getTime() - d1.getTime()) / minute;
        }
    } else if (type == 12) { // refresh end time
        if (duration) {
            d1.setTime(d2.getTime() - duration * minute);
        } else {
            duration = (d2.getTime() - d1.getTime()) / minute;
        }
    }

    /* refill form after calculation */
    Eventum.updateTimeFields(f, 'date[Year]', 'date[Month]', 'date[Day]', 'date[Hour]', 'date[Minute]', d1)
    Eventum.updateTimeFields(f, 'date2[Year]', 'date2[Month]', 'date2[Day]', 'date2[Hour]', 'date2[Minute]', d2)

    duration = parseInt(duration);
    if (duration > 0) {
        Eventum.getField('time_spent').val(duration);
    }
};

Eventum.changeClockStatus = function()
{
    window.location.href = Eventum.rel_url + 'clock_status.php?current_page=' + window.location.href;
    return false;
};

Eventum.openHelp = function(e)
{
    var target = $(e.target);
    var topic = target.parent().attr('data-topic');
    var width = 500;
    var height = 450;
    var w_offset = 30;
    var h_offset = 30;
    var location = 'top=' + h_offset + ',left=' + w_offset + ',';
    if (screen.width) {
        location = 'top=' + h_offset + ',left=' + (screen.width - (width + w_offset)) + ',';
    }
    var features = 'width=' + width + ',height=' + height + ',' + location + 'resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
    var helpWin = window.open(Eventum.rel_url + 'help.php?topic=' + topic, '_help', features);
    helpWin.focus();

    return false;
};

Eventum.clearAutoSave = function(prefix)
{
    var i;
    var key;
    for (i = localStorage.length; i >= 0; i--)   {
        key = localStorage.key(i);
        if (key && key.startsWith(prefix)) {
            localStorage.removeItem(localStorage.key(i));
        }
    }
};

function Validation()
{
}

Validation.selectField = function(field)
{
    field = Eventum.getField(field);
    if (field.attr('type') != 'hidden') {
        field.focus();
    }
    Validation.showErrorIcon(field, true);
    field.bind('change.validation', function() {
        Validation.showErrorIcon(field, false);
    });
    if (Validation.isWhitespace(field.val())) {
        return false;
    }
};

Validation.showErrorIcon = function(field, show)
{
    var icon = $('#error_icon_' + Eventum.escapeSelector(field.attr('name')));
    if (icon.length == 0) {
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

Validation.isFieldWhitespace = function(field)
{
    field = Eventum.getField(field);
    return Validation.isWhitespace(field.val());
};

Validation.isWhitespace = function(s)
{
    if (s == null) {
        return true;
    }

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
};

Validation.isNumberOnly = function(s)
{
    var check = parseFloat(s).toString();
    if ((s.length == check.length) && (check != "NaN")) {
        return true;
    } else {
        return false;
    }
};

/**
 * Checks if field value is valid RGB hex number.
 * @param {String} field name of the field
 * @returns {boolean}
 */
Validation.isFieldRGBhex = function(field) {
    var s = Eventum.getField(field).val();

    return !!s.match(/^#[a-f0-9]{6}$/i);
};

Validation.hasOneSelected = function(field)
{
    field = Eventum.getField(field);
    return (field.val() != null && field.val().length > 0);
};

Validation.isEmail = function(s)
{
    // email text field.
    var sLength = s.length;
    var denied_chars = [" ", "\n", "\t", "\r", "%", "$", "#", "!", "~", "`", "^", "&", "*", "(", ")", "=", "{", "}", "[", "]", ",", ";", ":", "'", "\"", "?", "<", ">", "/", "\\", "|"];

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
};

Validation.hasOneChecked = function(field)
{
    field = Eventum.getField(field);
    return (field.filter(':checked').length > 0);
};

Validation.isValidDate = function(field_prefix)
{
    var selected_date = new Date();
    selected_date.setMonth(Eventum.getField(field_prefix + '[Month]').val()-1);
    selected_date.setDate(Eventum.getField(field_prefix + '[Day]').val());
    selected_date.setYear(Eventum.getField(field_prefix + '[Year]').val());

    return selected_date.getDate() == Eventum.getField(field_prefix + '[Day]').val();
};

Validation.errors = null;
Validation.errors_extra = null;
Validation.checkFormSubmission = function(form, callback_func)
{
    var res, fields;
    Validation.errors = [];
    Validation.errors_extra = [];

    if (typeof(callback_func) == 'string') {
        res = eval(callback_func + '(form)');
    } else {
        res = callback_func(form);
    }
    if (res === false) {
        return false;
    }

    if (Validation.errors.length > 0) {
        // loop through all of the broken fields and select them
        fields = '';
        for (var i = 0; i < Validation.errors.length; i++) {
            Validation.selectField(form.find("[name=" + Eventum.escapeSelector(Validation.errors[i].value) + "]"));
            fields += '- ' + Validation.errors[i].text + "\n";
        }
        // show a big alert box with the missing information
        alert("The following required fields need to be filled out:\n\n" + fields + "\nPlease complete the form and try again.");
        return false;
    } else if (Validation.errors_extra.length > 0) {
        // loop through all of the broken fields and select them
        fields = '';
        for (var i = 0; i < Validation.errors_extra.length; i++) {
            Validation.selectField(f, Validation.errors_extra[i].value);
            fields += '- ' + Validation.errors_extra[i].text + "\n";
        }
        // show a big alert box with the missing information
        alert("The following fields have errors that need to be resolved:\n\n" + fields + "\nPlease resolve these errors and try again.");
        return false;
    } else {
        return true;
    }
};

Validation.checkCustomFields = function(form)
{
    $.each(CustomField.getFieldInfo(), function(index, info) {
        var field = Eventum.getField('custom_fields[' + info.id + ']');
        if (field.length < 1) {
            field = Eventum.getField('custom_fields[' + info.id + '][]');
        }

        if (((field.val() == null || field.val().length < 1) ||
                (field.val() == -1)) &&
                (field.parent().parent().css('display') == 'none')) {
            return null;
        }

        if (info.required == 1) {
            if (info.type == 'combo') {
                if (field.val() == '' || field.val() == '-1') {
                    Validation.errors.push(new Option(info.title, field.attr('name')));
                }
            } else if (info.type == 'multiple') {
                if (!Validation.hasOneSelected(field)) {
                    Validation.errors.push(new Option(info.title, field.attr('name')));
                }
            } else if (info.type == 'checkbox') {
                if (!Validation.hasOneChecked(field)) {
                    Validation.errors.push(new Option(info.title, field.attr('name')));
                }
            } else {
                if (Validation.isWhitespace(field.val())) {
                    Validation.errors.push(new Option(info.title, field.attr('name')));
                }
            }
        }
        if (info.validation_js != '') {
            var validation_result = false;
            eval("validation_result = " + info.validation_js + '()');
            if (validation_result != true) {
                Validation.errors.push(new Option(info.title + ': ' + validation_result, field.attr('name')));
            }
        } else {
            if (info.type == 'integer') {
                if ((!Validation.isWhitespace(field.val())) && (!Validation.isNumberOnly(field.val()))) {
                    Validation.errors.push(new Option(info.title + ': This field can only contain numbers', field.attr('name')));
                }
            }
        }
    });
};

Validation.callback = function(e)
{
    var f = $(e.target);
    return Validation.checkFormSubmission(f, $(e.target).attr('data-validation-function'))
};

Validation.isDigit = function(c)
{
    return ((c >= "0") && (c <= "9"));
};

Validation.isFloat = function(s)
{
    if (Validation.isWhitespace(s)) {
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
        } else if (!Validation.isDigit(c)) {
            return false;
        }
    }

    // All characters are numbers.
    return true;
};

Validation.last_issue_number_validation_value = '';
Validation.validateIssueNumberField = function(e)
{
    var target = $(e.target);
    var form_value = target.val();
    if (Validation.last_issue_number_validation_value == form_value) {
        return;
    }

    Validation.last_issue_number_validation_value = form_value;
    var options = {
        check_project: target.attr('data-check-project'),
        exclude_issue: target.attr('data-exclude-issue'),
        exclude_duplicates: target.attr('data-exclude-duplicates'),
        error_message: target.attr('data-error-message')
    };

    jQuery.ajax({
            url: Eventum.rel_url + 'validate.php',
            data: {
                action: 'validateIssueNumbers',
                values: form_value,
                field_id: target.attr('id'),
                check_project: options.check_project,
                exclude_issue: options.exclude_issue,
                exclude_duplicates: options.exclude_duplicates
            },
            error_message: options.error_message,
            success: function(data, textStatus) {
                var error_span = $('#' + target.attr('id') + '_error');
                if (data != 'ok') {
                    Validation.selectField(target);
                    var error_message = '<b>Error</b>: The following issues are invalid: ' + data;
                    if (this.error_message != undefined) {
                        error_message += '. ' + this.error_message;
                    }
                    error_span.html(error_message);
                } else {
                    Validation.showErrorIcon(target, false);
                    error_span.html('');
                }
            }
     });
};


function CustomField()
{
}

CustomField.field_info = [];

CustomField.ready = function()
{
    // load information from the current page regarding fields
    CustomField.loadFieldInfo();
};

CustomField.loadFieldInfo = function()
{
    $('.custom_field').each(function(index, Element) {
        var field = $(this);
        CustomField.field_info.push({
            id: field.attr('data-custom-id'),
            type: field.attr('data-custom-type'),
            title: field.attr('data-custom-title'),
            required: field.attr('data-custom-required'),
            validation_js: field.attr('data-custom-validation-js')
        })
    });
};

CustomField.getFieldInfo = function()
{
    return CustomField.field_info;
};


function ExpandableCell()
{
}

ExpandableCell.ready = function()
{
    $('.expandable_buttons .expand').click(function(e) {
        var target = $(e.target).parent();
        var expand_type = target.attr('data-expand-type');
        var list_id = target.attr('data-list-id');
        if (list_id != '') {
            ExpandableCell.expand(expand_type, list_id);
        } else {
            $.each(expand_type.split(","), function(index, value) {
                $('.expandable_buttons.' + value + ' .expand').each(function() {
                    this.click();
                })
            });
        }
    });
    $('.expandable_buttons .collapse').click(function(e) {
        var target = $(e.target).parent();
        var expand_type = target.attr('data-expand-type');
        var list_id = target.attr('data-list-id');
        if (list_id != '') {
            ExpandableCell.collapse(expand_type, list_id);
        } else {
            $.each(expand_type.split(","), function(index, value) {
                $('.expandable_buttons.' + value + ' .collapse').each(function() {
                    this.click();
                })
            });
        }
    });
};

ExpandableCell.expand = function(expand_type, list_id) {
    var row = $('#ec_' + expand_type + '_item_' + list_id + '_row');
    var cell = row.find('td');
    if (cell.html() == '') {
        cell.load(Eventum.rel_url + 'get_remote_data.php?action=' + expand_type + '&ec_id=' + expand_type +
            '&list_id=' + list_id, function() {
            Eventum.setupTrimmedEmailToggle();
        });

    }
    row.show();
};

ExpandableCell.collapse = function(expand_type, list_id) {
    var row = $('#ec_' + expand_type + '_item_' + list_id + '_row');
    row.hide();
};

function GrowingFileField() {
}

GrowingFileField.ready = function()
{
    $('.growing_file_field').bind('change', GrowingFileField.copy_row);
};

GrowingFileField.copy_row = function(e)
{
    var target = $(e.target);
    if (target.val() == '') {
        return;
    }
    var new_row = target.parents('tr').first().clone(true);
    target.parents('tbody').first().append(new_row);
};
