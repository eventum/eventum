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
