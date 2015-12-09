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

var dynamic_options = [];
var i, j;

{foreach from=$fields item=field name=fields}
    i = dynamic_options.length;
    dynamic_options[i] = {};
    dynamic_options[i].target_field_id = {$field.fld_id};
    dynamic_options[i].fld_type = '{$field.fld_type}';
    dynamic_options[i].controlling_field_id = '{$field.controlling_field_id}';
    dynamic_options[i].controlling_field_name = '{$field.controlling_field_name}';
    dynamic_options[i].hide_when_no_options = '{$field.hide_when_no_options}';
    dynamic_options[i].lookup_method = '{$field.lookup_method}';
    dynamic_options[i].groups = [];

    {foreach from=$field.structured_data key=key item=group}
        j = dynamic_options[i].groups.length;
        dynamic_options[i].groups[j] = {};
        dynamic_options[i].groups[j].keys = [];
        {foreach from=$group.keys item=key}
            dynamic_options[i].groups[j].keys[dynamic_options[i].groups[j].keys.length] = '{$key}';
        {/foreach}
        dynamic_options[i].groups[j].options = [];
        {foreach from=$group.options item=option key=option_value}
        dynamic_options[i].groups[j].options[dynamic_options[i].groups[j].options.length] = new Option('{$option|escape:'javascript'}', '{$option_value|escape:'javascript'}');
        {/foreach}
    {/foreach}
{/foreach}
