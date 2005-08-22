var dynamic_options = new Array();

{foreach from=$fields item=field name=fields}
i = dynamic_options.length;
dynamic_options[i] = new Object();
dynamic_options[i].target_field_id = {$field.fld_id};
dynamic_options[i].controlling_field_id = {$field.controlling_field_id};
dynamic_options[i].controlling_field_name = '{$field.controlling_field_name}';
dynamic_options[i].groups = new Array();
    {foreach from=$field.structured_data key=key item=options}
    j = dynamic_options[i].groups.length;
    dynamic_options[i].groups[j] = new Object();
    dynamic_options[i].groups[j].key = '{$key}';
    dynamic_options[i].groups[j].options = new Array();
        {foreach from=$options item=option}
        dynamic_options[i].groups[j].options[dynamic_options[i].groups[j].options.length] = new Option('{$option}', '{$option}');
        {/foreach}
    {/foreach}
{/foreach}
{literal}
function custom_field_get_details_by_controller(controller_id)
{
    for (i = 0; i < dynamic_options.length; i++) {
        if (dynamic_options[i].controlling_field_id = controller_id) {
            return dynamic_options[i];
        }
    }
}

function custom_field_get_details_by_target(target_id)
{
    for (i = 0; i < dynamic_options.length; i++) {
        if (dynamic_options[i].target_field_id = target_id) {
            return dynamic_options[i];
        }
    }
}


function custom_field_init_dynamic_options(fld_id)
{
    for (i = 0; i < dynamic_options.length; i++) {
        if (dynamic_options[i].target_field_id = fld_id) {
            // set alert on target field prompting them to choose controlling field first
            target_field = getPageElement('custom_field_' + dynamic_options[i].target_field_id);
            target_field.onfocus = custom_field_prompt_choose_controller;
            
            // set event handler for controlling field
            controlling_field = getPageElement('custom_field_' + dynamic_options[i].controlling_field_id);
            controlling_field.onchange = custom_field_handle_controller_change;
            custom_field_set_new_options(controlling_field, true);
        }
    }
}

function custom_field_handle_controller_change(e)
{
    if (!e) var e = window.event;
    controller = getEventTarget(e);
    custom_field_set_new_options(controller, false);
}

function custom_field_set_new_options(controller, keep_target_value) {
    chunks = controller.id.split('_');
    controller_id = chunks[2];

    // find the object
    details = custom_field_get_details_by_controller(controller_id);
    
    // get current value of controller field
    value = controller.options[controller.selectedIndex].text;
    
    // see if this value has a set of options for the child field
    target = getPageElement('custom_field_' + details.target_field_id);
    if (keep_target_value) {
        // get the current value
        current_value = target.options[target.selectedIndex].value;
    }
    target.options.length = 1;
    for (var i = 0; i < details.groups.length; i++) {
        if (details.groups[i].key == value) {
            for (var j = 0; j < details.groups[i].options.length; j++) {
                target.options.add(details.groups[i].options[j]);
            }
            target.onfocus = '';
            if (keep_target_value) {
                selectOption(target.form, target.name, current_value);
            } else {
                target.selectedIndex = 0;
            }
            break;
        }
    }
}

function custom_field_prompt_choose_controller(e) {
    if (!e) var e = window.event;
    target_field = getEventTarget(e);
    chunks = target_field.id.split('_');
    target_id = chunks[2];
    
    details = custom_field_get_details_by_target(target_id);
    
    selectField(target_field.form, 'custom_fields[' + details['controlling_field_id'] + ']', custom_field_error_callback);
    alert('Please choose ' + details.controlling_field_name + ' first');
    return false;
}

function custom_field_error_callback(form_name, field_name)
{
    var f = getForm(form_name);
    var field = getFormElement(f, field_name);
    field.onchange = custom_field_handle_controller_change;
    custom_field_set_new_options(field, false);
}
{/literal}