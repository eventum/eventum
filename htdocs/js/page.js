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




function anon_post() {}

anon_post.ready = function()
{
    var project_form = $('form#project_form');
    project_form.find('input,select').filter(':visible').first().focus();

    project_form.submit(function() { return Validation.checkFormSubmission(project_form, anon_post.validateProjectForm) });

    var report_form = $('form#report_form');
    report_form.find('input,select').filter(':visible').first().focus();

    report_form.submit(function() { return Validation.checkFormSubmission(report_form, anon_post.validateForm) });
};

anon_post.validateProjectForm = function(form)
{
    var project_field = Eventum.getField('project');
    if (project_field.val() == '-1') {
        Validation.errors[Validation.errors.length] = new Option('Project', 'project');
    }
};

anon_post.validateForm = function(form)
{
    if (Validation.isFieldWhitespace('summary')) {
        Validation.errors[Validation.errors.length] = new Option('Summary', 'summary');
    }

    // replace special characters in description
    var description_field = Eventum.getField('description');
    description_field.val(Eventum.replaceSpecialCharacters(description_field.val()));

    if (Validation.isFieldWhitespace('description')) {
        Validation.errors[Validation.errors.length] = new Option('Description', 'description');
    }

    Validation.checkCustomFields(form);

};

/*
 * Stats page
 */
function stats() {}

stats.ready = function()
{
    $('#hide_closed').change(function hideClosed(e) {
        var target = $(e.target);
        if (target.is(':checked')) {
            window.location.href = "?" + Eventum.replaceParam(window.location.href, 'hide_closed', '1');
        } else {
            window.location.href = "?" + Eventum.replaceParam(window.location.href, 'hide_closed', '0');
        }
    });
};


/*
 * Product chooser functions used in multiplepages
 */
function product() {}

product.ready = function()
{
    $('#product').bind('change', product.display_product_version_howto).change();
};

product.display_product_version_howto = function()
{
    var howto = $('#product :selected').attr('data-desc');
    if (howto == undefined || howto == '') {
        $('#product_version_howto').hide();
    } else {
        $('#product_version_howto').text(howto).show();
    }
};

/*
 * Preferences page
 */
function preferences() {}

preferences.ready = function()
{
    $('#show_revoked').click(function() {
        $('body#preferences .api_token .revoked').show();
    });

    $('form#api_token_form').submit(preferences.confirmRegenerateToken);

    $('form.update_name_form').submit(preferences.validateName);
    $('form.update_email_form').submit(preferences.validateEmail);
    $('form.update_password_form').submit(preferences.validatePassword);
    $('input.api_token').focus(function() {
        var $this = $(this);
        $this.select();
    });
};

preferences.validateName = function()
{
    if (Validation.isFieldWhitespace('full_name')) {
        alert('Please enter your full name.');
        Validation.selectField('full_name');
        return false;
    }
    return true;
};

preferences.validateEmail = function()
{
    if (!Validation.isEmail(Eventum.getField('email').val())) {
        alert('Please enter a valid email address.');
        Validation.selectField('email');
        return false;
    }
    return true;
};

preferences.validatePassword = function()
{
    if (Validation.isWhitespace(Eventum.getField('password').val())) {
        alert('Please input current password.');
        Validation.selectField('password');
        return false;
    }

    var new_password = Eventum.getField('new_password').val();
    if (Validation.isWhitespace(new_password) || new_password.length < 6) {
        alert('Please enter your new password with at least 6 characters.');
        Validation.selectField('new_password');
        return false;
    }
    if (new_password != Eventum.getField('confirm_password').val()) {
        alert('The two passwords do not match. Please review your information and try again.');
        Validation.selectField('confirm_password');
        return false;
    }
    return true;
};

preferences.confirmRegenerateToken = function()
{
    if (confirm("Regenerating your API Key will revoke all previous keys. Do you want to procede?")) {
        return true;
    }
    return false;
};


function custom_field_options()
{
}

custom_field_options.ready = function()
{
    $('#sortable').sortable();

    custom_field_options.bind_actions();

    $('#add_option').click(custom_field_options.add_option);
    custom_field_options.add_option();
};

custom_field_options.bind_actions = function()
{
    $('.ui-sortable-handle .delete').off('click.delete').on('click.delete', function(e) {
        $(e.target).parent().fadeOut(function() {
            $(this).remove();
        });
    });

    $('#custom_field_options input').off('keydown.blockenter').on('keydown.blockenter', function(e) {
        if(e.keyCode == 13) {
            if ($(this).prop('name') == 'new_options[]' && $(this).val() != '') {
                custom_field_options.add_option();
                $("input[name='new_options[]']:last").focus()
            }
            e.preventDefault();
            return false;
        }
    })
};

custom_field_options.add_option = function() {
    var template = $('#new_option_template').first();
    template.clone().prop('id', '').insertAfter('.new_options li:last').show()
    custom_field_options.bind_actions();
};
