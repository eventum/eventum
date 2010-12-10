var dynamic_options = new Array();

{foreach from=$fields item=field name=fields}
i = dynamic_options.length;
dynamic_options[i] = new Object();
dynamic_options[i].target_field_id = {$field.fld_id};
dynamic_options[i].fld_type = '{$field.fld_type}';
dynamic_options[i].controlling_field_id = '{$field.controlling_field_id}';
dynamic_options[i].controlling_field_name = '{$field.controlling_field_name}';
dynamic_options[i].hide_when_no_options = '{$field.hide_when_no_options}';
dynamic_options[i].lookup_method = '{$field.lookup_method}';
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
        dynamic_options[i].groups[j].options[dynamic_options[i].groups[j].options.length] = new Option('{$option|escape:'javascript'}', '{$option_value|escape:'javascript'}');
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
            // set alert on target field prompting them to choose controlling field first
            target_field = $('#custom_field_' + dynamic_options[i].target_field_id);
            target_field.bind("focus.choose_controller", dynamic_options[i].target_field_id, prompt_choose_controller_first);

            // set event handler for controlling field
            controlling_field = $('#' + dynamic_options[i].controlling_field_id);
            controlling_field.bind('change.change_options', dynamic_options[i].controlling_field_id, function(e) {
                custom_field_set_new_options($(e.target), false);
            });
            custom_field_set_new_options(controlling_field, true, fld_id);
            break;
        }
    }
}

function prompt_choose_controller_first(e) {
    target_field = e.target;
    target_id = e.data;
    details = custom_field_get_details_by_target(target_id);

    alert('{/literal}{t escape=js}Please choose{/t} ' + details.controlling_field_name + ' {t}first{/t}{literal}');

    target_field.blur();
    return false;
}


function custom_field_set_new_options(controller, keep_target_value, target_fld_id) {
    // get current value of controller field
    value = controller.val();

    // find the object
    if (target_fld_id != undefined) {
        details = new Array();
        details[0] = custom_field_get_details_by_target(target_fld_id);
    } else {
        details = custom_field_get_details_by_controller(controller.attr('id'), target_fld_id);
    }
    for (var i = 0; i < details.length; i++) {
        // get the target/targets
        var targets = new Array();
        targets[0] = target = $('#custom_field_' + details[i].target_field_id);

        for (var targ_num = 0; targ_num < targets.length; targ_num++) {
            wrapped_target = targets[targ_num];
            target = wrapped_target.get(0);
            // see if this value has a set of options for the child field
            if (keep_target_value) {
                // get the current value
                current_value = wrapped_target.val();
            }
            if (target.type != 'text' && target.type != 'textarea' && details[i].fld_type != 'date') {
                target.options.length = 1;
            }
            var show = false;
            if (details[i].lookup_method == 'local') {
                for (var j = 0; j < details[i].groups.length; j++) {
                    for (var k = 0; k < details[i].groups[j].keys.length; k++) {
                        if (((typeof value == 'object') && (value.indexOf(details[i].groups[j].keys[k]) > -1)) || (details[i].groups[j].keys[k] == value)) {
                            show = true;
                            for (var l = 0; l < details[i].groups[j].options.length; l++) {
                                target.options[target.options.length] = details[i].groups[j].options[l];
                            }
                            // unbind "choose a controller" message
                            wrapped_target.unbind("focus.choose_controller");
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
            } else if (details[i].lookup_method == 'ajax') {
                // submit form via ajax trying to get data
                $('#report_form').ajaxSubmit({
                    'type':   'GET',
                    'url':  'rpc/get_custom_field_dynamic_options.php',
                    'dataType': 'json',
                    'data': {
                        'fld_id':   details[i].target_field_id
                    },
                    'success': function(options, status) {
                        var wrapped_target = $(target);
                        var target_id_chunks = wrapped_target.attr('id').split('_');
                        var details = custom_field_get_details_by_target(target_id_chunks[2]);
                        if (options != null) {
                            target.options.length = 0;
                            $.each(options, function(key, val) {
                                target.options[target.options.length] = new Option(val, key);
                                return true;
                            });
                            $(target).unbind("focus.choose_controller");
                            show = true;
                        } else {
                            target.options.length = 0;
                            target.options[0] = new Option('Please choose an option', "");
                            wrapped_target.bind("focus.choose_controller", details.target_field_id, prompt_choose_controller_first);
                            show = false;
                        }
                        if (details.hide_when_no_options == 1) {
                            custom_field_change_visibility(target, show);
                        }
                    }
                });
            }

            if (details[i].hide_when_no_options == 1) {
                custom_field_change_visibility(target, show);
            }
        }
    }
}

function custom_field_change_visibility(target, show)
{
    if (show == false) {
        target.parentNode.parentNode.style.display = 'none';
    } else {
        target.parentNode.parentNode.style.display = getDisplayStyle();
    }
}
{/literal}
