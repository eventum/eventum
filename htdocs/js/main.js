
$(document).ready(function() {

    // see http://api.jquery.com/jQuery.param/
    jQuery.ajaxSettings.traditional = true;

    // check the class of the body and try to execute the prep functions if there is a class defined for that
    var page_id = $("body").attr('id');
    $.each($("body").attr('class').split(" "), function(indexInArray, valueOfElement) {
        if (valueOfElement == '') {
            return
        }
        valueOfElement = valueOfElement.replace('-', '_');
        if (eval("typeof " + valueOfElement) !== "undefined" &&
                eval("typeof " + valueOfElement + '.ready') == 'function') {
            eval(valueOfElement + '.ready(page_id)');
        }
    });

    // focus on the first text input field in the first field on the page
    $(":text:visible:enabled .autofocus").first().focus();

    $('.close_window').click(function() { window.close(); });

    window.onbeforeunload = Eventum.handleClose;

    $('form.validate').submit(Validation.callback);

    ExpandableCell.ready();

    Eventum.rel_url = $('head').attr('data-rel-url');

    $('#project_chooser').change(function() {
        $(this).find('form').submit();
    });
});


function Eventum()
{
}

Eventum.expires = new Date(new Date().getTime() + (56 * 86400000));
Eventum.checkClose = false;
Eventum.closeConfirmMessage = 'Do you want to close this window?';
Eventum.rel_url = '';

Eventum.toggle_section_visibility = function(id) {
    var element = $('#' + id);
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

    $('#' + id + '_link').text(link_title);

    $.cookie('visibility_' + id, display, {expires: Eventum.expires});
}

Eventum.close_and_refresh = function(noparent)
{
    if (opener && !noparent) {
        opener.location.href = opener.location;
    }
    window.close();
}

Eventum.displayFixedWidth = function(element)
{
    element.addClass('fixed_width')
}

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
}

Eventum.escapeSelector = function(selector)
{
    return selector.replace(/\[/, '\\[').replace(/\]/, '\\]');
}

Eventum.getField = function(name_or_obj)
{
    if ($.type(name_or_obj) == 'string') {
        return $('[name="' + name_or_obj + '"]')
    }
    return name_or_obj;
}

Eventum.toggleCheckAll = function(field_name)
{
    var fields = Eventum.getField(field_name).not(':disabled');
    fields.prop('checked', !fields.prop('checked'));
}

Eventum.selectOption = function(field, new_values)
{
    // adds the specified values to the list of select options

    field = Eventum.getField(field)

    var values = field.val()

    if (!jQuery.isArray(values)) {
        field.val(new_values);
    } else {
        if (values == null) {
            values = new Array();
        }
        values.push(new_values);
        field.val(values);
    }
}

Eventum.removeOptionByValue = function(field, value)
{
    var field = Eventum.getField(field);
    for (var i = 0; i < field[0].options.length; i++) {
        if (field[0].options[i].value == value) {
            field[0].options[i] = null;
        }
    }
}

Eventum.selectAllOptions = function(field)
{
    Eventum.getField(field).find('option').each(function() { this.selected = true; });
}

Eventum.addOptions = function(field, options)
{
    var field = Eventum.getField(field);
    $.each(options, function(index, value) {
        var option = new Option(value.text, value.value);
        if (!Eventum.optionExists(field, option)) {
            field.append(option);
        }
    });
}

Eventum.optionExists = function(field, option)
{
    var field = Eventum.getField(field);
    var option = $(option);
    if (field.find('option[value="' + Eventum.escapeSelector(option.val()) + '"]').length > 0) {
        return true;
    }
    return false;
}

Eventum.removeAllOptions = function(field)
{
    var field = Eventum.getField(field);
    field.html('');
}



Eventum.replaceParam = function(str, param, new_value)
{
    if (str.indexOf("?") == -1) {
        return param + "=" + new_value;
    } else {
        var pieces = str.split("?");
        var params = pieces[1].split("&");
        var new_params = new Array();
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
}

Eventum.handleClose = function()
{
    if (Eventum.checkClose == true) {
        return Eventum.closeConfirmMessage;
    } else {
        return;
    }
}

Eventum.checkWindowClose = function(msg)
{
    if (!msg) {
        Eventum.checkClose = false;
    } else {
        Eventum.checkClose = true;
        Eventum.closeConfirmMessage = msg;
    }
}



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
}

Eventum.setup_show_selections = function(select_box)
{
    select_box.change(Eventum.show_selections);
    select_box.change();
}

Eventum.show_selections = function(e)
{
        var select_box = $(e.target);
        var selected = [];
        if (select_box.val() != null) {
            $.each(select_box.val(), function(index, value) {
                selected.push(select_box.find("option[value='" + value + "']").text());
            });
        }

        var display_div = $('#selection_' + select_box.attr('id'));
        display_div.html(selected.join(', '));
}


Eventum.changeVisibility = function(dom_id, visibility)
{
    $('#' + dom_id).toggle(visibility);
}



/**
 * Make javascript Date() object from datetime form selection.
 *
 * @param   String  name    Form element prefix for date.
 */
Eventum.makeDate = function(name) {
    var d = new Date();
    d.setFullYear(Eventum.getField(name + '[Year]').val());
    d.setMonth(Eventum.getField(name + '[Month]').val() - 1);
    d.setDate(Eventum.getField(name + '[Day]').val());
    d.setHours(Eventum.getField(name + '[Hour]').val());
    d.setMinutes(Eventum.getField(name + '[Minute]').val());
    d.setSeconds(0);
    return d;
}

/**
 * @param   Object  f       Form object
 * @param   Integer type    The type of update occurring.
 *                          0 = Duration was updated.
 *                          1 = Start time was updated.
 *                          2 = End time was updated.
 *                          11 = Start time refresh icon was clicked.
 *                          12 = End time refresh icon was clicked.
 * @param String element Name of the element changed
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
}


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
}

Validation.showErrorIcon = function(field, show)
{
    var icon = $('#error_icon_' + field.attr('name'));
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
}

Validation.isFieldWhitespace = function(field)
{
    field = Eventum.getField(field);
    return Validation.isWhitespace(field.val());
}

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
}

Validation.isNumberOnly = function(s)
{
    var check = parseFloat(s).toString();
    if ((s.length == check.length) && (check != "NaN")) {
        return true;
    } else {
        return false;
    }
}

Validation.hasOneSelected = function(field)
{
    field = Eventum.getField(field);
    if (field.val() != null && field.val().length > 0) {
        return true;
    } else {
        return false;
    }
}

Validation.isEmail = function(s)
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


Validation.hasOneChecked = function(field)
{
    field = Eventum.getField(field);
    if (field.filter(':checked').length > 0) {
        return true;
    } else {
        return false;
    }
}

Validation.isValidDate = function(field_prefix)
{
    var selected_date = new Date();
    selected_date.setMonth(Eventum.getField(field_prefix + '[Month]').val()-1);
    selected_date.setDate(Eventum.getField(field_prefix + '[Day]').val());
    selected_date.setYear(Eventum.getField(field_prefix + '[Year]').val());

    if (selected_date.getDate() != Eventum.getField(field_prefix + '[Day]').val()) {
        return false;
    } else {
        return true;
    }
}


Validation.errors = null;
Validation.errors_extra = null;
Validation.checkFormSubmission = function(form, callback_func)
{
    Validation.errors = new Array();
    Validation.errors_extra = new Array();

    if (typeof(callback_func) == 'string') {
        eval(callback_func + '()');
    } else {
        callback_func();
    }
    if (Validation.errors.length > 0) {
        // loop through all of the broken fields and select them
        var fields = '';
        for (var i = 0; i < Validation.errors.length; i++) {
            Validation.selectField(form.find("[name=" + Eventum.escapeSelector(Validation.errors[i].value) + "]"));
            fields += '- ' + Validation.errors[i].text + "\n";
        }
        // show a big alert box with the missing information
        alert("The following required fields need to be filled out:\n\n" + fields + "\nPlease complete the form and try again.");
        return false;
    } else if (Validation.errors_extra.length > 0) {
        // loop through all of the broken fields and select them
        var fields = '';
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
}

Validation.checkCustomFields = function(form)
{
    $.each(CustomField.getFieldInfo(), function(index, info) {
        var field = $('#custom_field_' + info.id);

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
}

Validation.callback = function(e)
{
    var f = $(e.target);
    return Validation.checkFormSubmission(f, $(e.target).attr('data-validation-function'))
}





function CustomField()
{
}

CustomField.field_info = []

CustomField.ready = function()
{
    // load information from the current page regarding fields
    CustomField.loadFieldInfo();
}


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
}

CustomField.getFieldInfo = function()
{
    return CustomField.field_info;
}



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
            $('.expandable_buttons.' + expand_type + ' .expand').each(function() {
                this.click();
            })
        }
    });
    $('.expandable_buttons .collapse').click(function(e) {
        var target = $(e.target).parent();
        var expand_type = target.attr('data-expand-type');
        var list_id = target.attr('data-list-id');
        if (list_id != '') {
            ExpandableCell.collapse(expand_type, list_id);
        } else {
            $('.expandable_buttons.' + expand_type + ' .collapse').each(function() {
                this.click();
            })
        }
    });
}

ExpandableCell.expand = function(expand_type, list_id) {
    var row = $('#ec_' + expand_type + '_item_' + list_id + '_row');
    var cell = row.find('td');
    if (cell.html() == '') {
        cell.load(Eventum.rel_url + 'get_remote_data.php?action=' + expand_type + '&ec_id=' + expand_type +
            '&list_id=' + list_id);
    }
    row.show();
}

ExpandableCell.collapse = function(expand_type, list_id) {
    var row = $('#ec_' + expand_type + '_item_' + list_id + '_row');
    row.hide();
}