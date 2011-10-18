
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
    $(":text:visible:enabled").not(".noautofocus").first().focus();

    $('.close_window').click(function() { window.close(); });
});


function Eventum()
{
}

Eventum.expires = new Date(new Date().getTime() + (56 * 86400000));

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

Eventum.close_and_refresh = function()
{
    if (opener) {
        opener.location.href = opener.location;
    }
    window.close();
}

Eventum.displayFixedWidth = function(element)
{
    console.debug('fixed');
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



function Validation()
{
}

Validation.selectField = function(field)
{
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

Validation.isWhitespace = function(s)
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
    if (field.val().length > 0) {
        return true;
    } else {
        return false;
    }
}


Validation.errors = null;
Validation.errors_extra = null;
Validation.checkFormSubmission = function(form, callback_func)
{
    Validation.errors = new Array();
    Validation.errors_extra = new Array();
    callback_func();
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