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

(function (exports, dynamic_options) {

    exports.custom_field_init_dynamic_options = custom_field_init_dynamic_options;
    exports.custom_field_set_new_options = custom_field_set_new_options;

    /**
     * @access private
     * @param controller_id
     * @returns {Array}
     */
    function custom_field_get_details_by_controller(controller_id) {
        var details = [];
        for (var i = 0; i < dynamic_options.length; i++) {
            if (dynamic_options[i].controlling_field_id == controller_id) {
                details[details.length] = dynamic_options[i];
            }
        }
        return details;
    }

    /**
     * @access private
     * @param target_id
     * @returns {*}
     */
    function custom_field_get_details_by_target(target_id) {
        for (var i = 0; i < dynamic_options.length; i++) {
            if (dynamic_options[i].target_field_id == target_id) {
                return dynamic_options[i];
            }
        }
    }

    /**
     * @access public
     * @param fld_id
     */
    function custom_field_init_dynamic_options(fld_id) {
        var cf_set_options = function (e) {
            custom_field_set_new_options($(e.target), false);
        };
        for (var i = 0; i < dynamic_options.length; i++) {
            if (dynamic_options[i].target_field_id == fld_id) {
                // set alert on target field prompting them to choose controlling field first
                var target_field = $('#custom_field_' + dynamic_options[i].target_field_id);
                target_field.bind("focus.choose_controller", dynamic_options[i].target_field_id, prompt_choose_controller_first);

                // set event handler for controlling field
                var controlling_field = $('#' + dynamic_options[i].controlling_field_id);
                controlling_field.bind('change.change_options', dynamic_options[i].controlling_field_id, cf_set_options);
                custom_field_set_new_options(controlling_field, true, fld_id);
                break;
            }
        }
    }

    /**
     * @access private
     * @param e
     * @returns {boolean}
     */
    function prompt_choose_controller_first(e) {
        var target_field = e.target;
        var target_id = e.data;
        var details = custom_field_get_details_by_target(target_id);

        alert('Please choose ' + details.controlling_field_name + ' first');

        target_field.blur();
        return false;
    }

    /**
     * @access private
     * @param controller
     * @param keep_target_value
     * @param target_fld_id
     */
    function custom_field_set_new_options(controller, keep_target_value, target_fld_id) {
        // get current value of controller field
        var value = controller.val();
        var details;

        // find the object
        if (target_fld_id != undefined) {
            details = [];
            details[0] = custom_field_get_details_by_target(target_fld_id);
        } else {
            details = custom_field_get_details_by_controller(controller.attr('id'), target_fld_id);
        }
        for (var i = 0; i < details.length; i++) {
            // get the target/targets
            var targets = [];
            var target;
            targets[0] = $('#custom_field_' + details[i].target_field_id);

            for (var targ_num = 0; targ_num < targets.length; targ_num++) {
                var wrapped_target = targets[targ_num];
                var current_value;
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
                        'type': 'GET',
                        'url': 'ajax/get_custom_field_dynamic_options.php',
                        'dataType': 'json',
                        'data': {
                            'fld_id': details[i].target_field_id
                        },
                        'success': getSuccessCallback(target)
                    });
                }

                if (details[i].hide_when_no_options == 1) {
                    custom_field_change_visibility(target, show);
                }
            }
        }
    }

    /**
     * @access private
     * @param target
     * @returns {Function}
     */
    function getSuccessCallback(target) {
        return function (options, status) {
            var wrapped_target = $(target);
            var target_id_chunks = wrapped_target.attr('id').split('_');
            var details = custom_field_get_details_by_target(target_id_chunks[2]);
            var show;
            if (options != null) {
                target.options.length = 0;
                $.each(options, function (key, val) {
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
    }

    /**
     * @access private
     * @param target
     * @param show
     */
    function custom_field_change_visibility(target, show) {
        if (show == false) {
            $(target.parentNode.parentNode).hide();
        } else {
            $(target.parentNode.parentNode).show();
        }
    }

})(window, dynamic_options);
