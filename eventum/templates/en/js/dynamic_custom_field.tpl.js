var dynamic_options = new Array();

{foreach from=$fields item=field name=fields}
i = dynamic_options.length;
dynamic_options[i] = new Object();
dynamic_options[i].target_field_id = {$field.fld_id};
dynamic_options[i].fld_type = '{$field.fld_type}';
dynamic_options[i].controlling_field_id = {$field.controlling_field_id};
dynamic_options[i].controlling_field_name = '{$field.controlling_field_name}';
dynamic_options[i].hide_when_no_options = '{$field.hide_when_no_options}';
dynamic_options[i].groups = new Array();
    {foreach from=$field.structured_data key=key item=group}
    j = dynamic_options[i].groups.length;
    dynamic_options[i].groups[j] = new Object();
    dynamic_options[i].groups[j].keys = new Array();
        {foreach from=$group.keys item=key}
        dynamic_options[i].groups[j].keys[dynamic_options[i].groups[j].keys.length] = '{$key}';
        {/foreach}
    dynamic_options[i].groups[j].options = new Array();
        {foreach from=$group.options item=option key=option_value}
        dynamic_options[i].groups[j].options[dynamic_options[i].groups[j].options.length] = new Option('{$option}', '{$option_value}');
        {/foreach}
    {/foreach}
{/foreach}
{literal}
function custom_field_get_details_by_controller(controller_id)
{
    var details = new Array();
    for (var i = 0; i < dynamic_options.length; i++) {
        if (dynamic_options[i].controlling_field_id == controller_id) {
            details[details.length] = dynamic_options[i];
        }
    }
    return details;
}

function custom_field_get_details_by_target(target_id)
{
    for (i = 0; i < dynamic_options.length; i++) {
        if (dynamic_options[i].target_field_id == target_id) {
            return dynamic_options[i];
        }
    }
}


function custom_field_init_dynamic_options(fld_id)
{
    for (var i = 0; i < dynamic_options.length; i++) {
        if (dynamic_options[i].target_field_id == fld_id) {
            if (dynamic_options[i].fld_type == 'date') {
                // date fields need special care
                // set alert on target field prompting them to choose controlling field first
                target_field = getPageElement('custom_field_' + dynamic_options[i].target_field_id + '_month');
                target_field.onmousedown = custom_field_prompt_choose_controller;
                target_field.onkeypress = custom_field_prompt_choose_controller;
                
                target_field = getPageElement('custom_field_' + dynamic_options[i].target_field_id + '_day');
                target_field.onmousedown = custom_field_prompt_choose_controller;
                target_field.onkeypress = custom_field_prompt_choose_controller;
                
                target_field = getPageElement('custom_field_' + dynamic_options[i].target_field_id + '_year');
                target_field.onmousedown = custom_field_prompt_choose_controller;
                target_field.onkeypress = custom_field_prompt_choose_controller;
            } else {
                // set alert on target field prompting them to choose controlling field first
                target_field = getPageElement('custom_field_' + dynamic_options[i].target_field_id);
                target_field.onmousedown = custom_field_prompt_choose_controller;
                target_field.onkeypress = custom_field_prompt_choose_controller;
            }
            // set event handler for controlling field
            controlling_field = getPageElement('custom_field_' + dynamic_options[i].controlling_field_id);
            controlling_field.onchange = custom_field_handle_controller_change;
            custom_field_set_new_options(controlling_field, true, fld_id);
            break;
        }
    }
}

function custom_field_handle_controller_change(e)
{
    if (!e) var e = window.event;
    controller = getEventTarget(e);
    custom_field_set_new_options(controller, false);
}

function custom_field_set_new_options(controller, keep_target_value, target_fld_id) {
    chunks = controller.id.split('_');
    controller_id = chunks[2];

    // get current value of controller field
    value = controller.options[controller.selectedIndex].value;
    
    // find the object
    if (target_fld_id != undefined) {
        details = new Array();
        details[0] = custom_field_get_details_by_target(target_fld_id);
    } else {
        details = custom_field_get_details_by_controller(controller_id, target_fld_id);
    }

    for (var i = 0; i < details.length; i++) {
        // get the target/targets
        var targets = new Array();
        if (details[i].fld_type == 'date') {
            targets[0] = getPageElement('custom_field_' + details[i].target_field_id + '_month');
            targets[1] = getPageElement('custom_field_' + details[i].target_field_id + '_day');
            targets[2] = getPageElement('custom_field_' + details[i].target_field_id + '_year');
        } else {
            targets[0] = target = getPageElement('custom_field_' + details[i].target_field_id);
        }
        
        for (var targ_num = 0; targ_num < targets.length; targ_num++) {
            target = targets[targ_num];    
            // see if this value has a set of options for the child field
            if (keep_target_value) {
                // get the current value
                if (target.type == 'text' || target.type == 'textarea') {
                    current_value = target.value;
                } else {
                    current_value = target.options[target.selectedIndex].value;
                }
            }
            if (target.type != 'text' && target.type != 'textarea' && details[i].fld_type != 'date') {
                target.options.length = 1;
            }
            var show = false;
            for (var j = 0; j < details[i].groups.length; j++) {
                for (var k = 0; k < details[i].groups[j].keys.length; k++) {
                    if (details[i].groups[j].keys[k] == value) {
                        show = true;
                        for (var l = 0; l < details[i].groups[j].options.length; l++) {
                            target.options[target.options.length] = details[i].groups[j].options[l];
                        }
                        target.onmousedown = '';
                        target.onkeypress = '';
                        if (keep_target_value) {
                            if (target.type == 'text' || target.type == 'textarea') {
                                target.value = current_value;
                            } else {
                                selectOption(target.form, target.name, current_value);
                            }
                        } else {
                            if (target.type == 'text' || target.type == 'textarea') {
                                target.value = '';
                            } else {
                                target.selectedIndex = 0;
                            }
                        }
                    }
                }
            }
    
            if (details[i].hide_when_no_options == 1) {
                if (show == false) {
                    target.parentNode.parentNode.style.display = 'none';
                } else {
                    target.parentNode.parentNode.style.display = getDisplayStyle();
                }
            }
        }
    }
    
}

function custom_field_prompt_choose_controller(e) {
    if (!e) var e = window.event;
    target_field = getEventTarget(e);
    chunks = target_field.id.split('_');
    target_id = chunks[2];
    
    details = custom_field_get_details_by_target(target_id);
    
    alert('Please choose ' + details.controlling_field_name + ' first');
    target_field.blur();
    e.cancelBubble = true;
    return false;
}
{/literal}