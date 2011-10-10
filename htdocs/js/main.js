
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
});


function Eventum()
{
    expires = new Date(today.getTime() + (56 * 86400000));
}

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
