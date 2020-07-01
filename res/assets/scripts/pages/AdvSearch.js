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

export default class {
    ready() {
        const page = this;
        const $showDateFieldsCheckbox = $('#show_date_fields_checkbox');
        $showDateFieldsCheckbox.click(function () {
            page.toggle_date_row();
        });

        $('.date_filter_type').change(function (e) {
            const target = $(e.target);
            page.checkDateFilterType(target.attr('name').replace("[filter_type]", ""));
        });

        const $showCustomFieldsCheckbox = $('#show_custom_fields_checkbox');
        $showCustomFieldsCheckbox.click(function () {
            page.toggle_custom_fields()
        });

        $('.date_filter_checkbox').click(function (e) {
            const $target = $(e.target);
            const field_name = $target.attr('name').replace('filter[', '').replace(']', '');
            page.toggle_date_field(field_name);
        });

        $('#save_search').click(function (e) {
            page.saveCustomFilter();
        });
        $('#remove_filter').submit(function (e) {
            return page.validateRemove();
        });

        $('.select_all').click(function () {
            Eventum.toggleCheckAll('item[]');
        });

        const elements_to_hide = ['created_date', 'updated_date', 'first_response_date', 'last_response_date', 'closed_date'];
        for (let i = 0; i < elements_to_hide.length; i++) {
            page.checkDateFilterType(elements_to_hide[i]);
            page.toggle_date_field(elements_to_hide[i]);
        }

        $('form[name=custom_filter_form]').submit(function () {
            return Validation.checkFormSubmission($('form[name=custom_filter_form]'), page.validateForm);
        });

        if ($showDateFieldsCheckbox.is(':checked')) {
            page.toggle_date_row(true);
        }

        if ($showCustomFieldsCheckbox.is(':checked')) {
            page.toggle_custom_fields(true);
        }
    };

    checkDateFilterType(field_name) {
        const filter_type = Eventum.getField(field_name + '[filter_type]').val();

        if (filter_type === 'between') {
            Eventum.changeVisibility(field_name + '1', true);
            Eventum.changeVisibility(field_name + '2', true);
            Eventum.changeVisibility(field_name + '_last', false);
        } else if (filter_type === 'in_past') {
            Eventum.changeVisibility(field_name + '1', false);
            Eventum.changeVisibility(field_name + '2', false);
            Eventum.changeVisibility(field_name + '_last', true);
        } else {
            Eventum.changeVisibility(field_name + '1', true);
            Eventum.changeVisibility(field_name + '2', false);
            Eventum.changeVisibility(field_name + '_last', false);
        }
    };

    toggle_custom_fields(show) {
        if (show == undefined) {
            if ($('#show_custom_fields_checkbox').is(':checked')) {
                show = true;
            } else {
                show = false;
            }
        }
        $('tr#custom_fields_row').toggle(show);

        $('#custom_fields_row select').add('#custom_fields_row input').each(function (index) {
            this.disabled = !show;
        });

        // enable/disable hidden field
        $('#custom_field_hidden').attr('disabled', show);
    };

    toggle_date_row(show) {
        if (show == undefined) {
            if ($('#show_date_fields_checkbox').is(':checked')) {
                show = true;
            } else {
                show = false;
            }
        }
        $('tr#date_fields').toggle(show);

        if (show == false) {
            $('#date_fields select').add('#date_fields input').not('.date_filter_checkbox').each(function () {
                this.disabled = !show;
            });
            $('.date_filter_checkbox').attr('checked', false);
        }
    };

    validateForm() {
        if (!Eventum.getField('hide_closed').is(':checked')) {
            Eventum.getField('hidden1').attr('name', 'hide_closed').val(0);
        }
        if (!Eventum.getField('show_authorized_issues').is(':checked')) {
            Eventum.getField('hidden2').attr('name', 'show_authorized_issues').val('');
        }
        if (!Eventum.getField('show_notification_list_issues').is(':checked')) {
            Eventum.getField('hidden3').attr('name', 'show_notification_list_issues').val('');
        }

        return true;
    };

    toggle_date_field(field_name) {
        const $checkbox = Eventum.getField('filter[' + field_name + ']');
        const $filter_type = Eventum.getField(field_name + '[filter_type]');
        const $month_field = Eventum.getField(field_name + '[Month]');
        const $day_field = Eventum.getField(field_name + '[Day]');
        const $year_field = Eventum.getField(field_name + '[Year]');
        const $month_end_field = Eventum.getField(field_name + '_end[Month]');
        const $day_end_field = Eventum.getField(field_name + '_end[Day]');
        const $year_end_field = Eventum.getField(field_name + '_end[Year]');
        const $time_period_field = Eventum.getField(field_name + '[time_period]');

        const disabled = !$checkbox.is(':checked');
        $filter_type.attr('disabled', disabled);
        $month_field.attr('disabled', disabled);
        $day_field.attr('disabled', disabled);
        $year_field.attr('disabled', disabled);
        $month_end_field.attr('disabled', disabled);
        $day_end_field.attr('disabled', disabled);
        $year_end_field.attr('disabled', disabled);
        $time_period_field.attr('disabled', disabled);

        Eventum.getField(field_name + '_hidden').disabled = !disabled;
    };

    saveCustomFilter() {
        const $form = $('form[name=custom_filter_form]');
        if (Validation.isFieldWhitespace('title')) {
            Validation.selectField('title');
            alert('Please enter the title for this saved search.');
            return false;
        }

        const features = 'width=420,height=200,top=30,left=30,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
        const popupWin = window.open('', '_customFilter', features);
        popupWin.focus();

        Eventum.getField('cat').val('save_filter');
        $form
            .attr('target', '_customFilter')
            .attr('method', 'post')
            .attr('action', 'popup.php')
            .submit();
    };

    validateRemove() {
        if (!Validation.hasOneChecked('item[]')) {
            alert('Please choose which entries need to be removed.');
            return false;
        }
        if (!confirm('This action will permanently delete the selected entries.')) {
            return false;
        }

        const features = 'width=420,height=200,top=30,left=30,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
        const popupWin = window.open('', '_removeFilter', features);

        popupWin.focus();
        return true;
    }
}
