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

export class Eventum {
    construct() {
        this.checkClose = false;
        this.closeConfirmMessage = 'Do you want to close this window?';
        this.rel_url = '';
    }

    TrimmedEmailToggleFunction() {
        const $div = $(this).parent().parent().find('div.email-trimmed');
        if ($div.hasClass('hidden')) {
            $div.removeClass('hidden')
        } else {
            $div.addClass('hidden')
        }

        return false;
    }

    // click to open trimmed emails
    setupTrimmedEmailToggle() {
        $('span.toggle-trimmed-email').find('a')
            .off('click', this.TrimmedEmailToggleFunction)
            .on('click', this.TrimmedEmailToggleFunction);
    }

    toggle_section_visibility(id) {
        const element = $('#' + id);

        let display = '';
        let link_title = '';
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

        Cookie.set('visibility_' + id, display);
    }

    close_and_refresh(noparent) {
        if (opener && !noparent) {
            opener.location.href = opener.location;
        }

        window.close();
    }

    displayFixedWidth(element) {
        element.addClass('fixed_width')
    }

    selectOnlyValidOption(select) {
        if (select[0].selectedIndex === 0) {
            if (select[0].length === 1) {
                select[0].selectedIndex = 0;
                return;
            }
            if (select[0].length <= 2 && select[0].options[0].value == -1) {
                select[0].selectedIndex = 1;
                return;
            }
        }
    }

    escapeSelector(selector) {
        return selector.replace(/(\[|\])/g, '\\$1')
    }

    getField(name_or_obj, $form) {
        if ($.type(name_or_obj) === 'string') {
            if ($form) {
                return $form.find('[name="' + name_or_obj + '"]');
            } else {
                return $('[name="' + name_or_obj + '"]')
            }
        }

        return name_or_obj;
    }

    getOpenerPageElement(id) {
        return window.opener.$('#' + id);
    };

    toggleCheckAll(field_name) {
        const fields = this.getField(field_name).not(':disabled');
        fields.prop('checked', !fields.prop('checked'));
    }

    clearSelectedOptions(field) {
        field = this.getField(field);
        field.val('');
    }

    // adds the specified values to the list of select options
    selectOption(field, new_values) {
        field = this.getField(field);

        let values = field.val();

        if (!jQuery.isArray(values)) {
            field.val(new_values);
        } else {
            if (values == null) {
                values = [];
            }
            values.push(new_values);
            field.val(values);
        }
    }

    removeOptionByValue(field, value) {
        field = this.getField(field);
        for (let i = 0; i < field[0].options.length; i++) {
            if (field[0].options[i].value == value) {
                field[0].options[i] = null;
            }
        }
    }

    selectAllOptions(field) {
        this.getField(field).find('option').each(function () {
            this.selected = true;
        });
    }

    addOptions(field, options) {
        const eventum = this;
        field = this.getField(field);
        $.each(options, function (index, value) {
            const option = new Option(value.text, value.value);
            if (!eventum.optionExists(field, option)) {
                field.append(option);
            }
        })
    }

    optionExists(field, option) {
        field = this.getField(field);
        option = $(option);

        return field.find('option[value="' + this.escapeSelector(option.val()) + '"]').length > 0;
    }

    removeAllOptions(field) {
        field = this.getField(field);
        field.html('');
    }

    replaceParam(str, param, new_value) {
        if (str.indexOf("?") === -1) {
            return param + "=" + new_value;
        }

        const pieces = str.split("?");
        const params = pieces[1].split("&");
        const new_params = [];
        for (let i = 0; i < params.length; i++) {
            if (params[i].indexOf(param + "=") === 0) {
                params[i] = param + "=" + new_value;
            }
            new_params[i] = params[i];
        }
        // check if the parameter doesn't exist on the URL
        if (str.indexOf("?" + param + "=") === -1 && str.indexOf("&" + param + "=") === -1) {
            new_params[new_params.length] = param + "=" + new_value;
        }

        return new_params.join("&");
    }

    handleClose() {
        if (this.checkClose == true) {
            return this.closeConfirmMessage;
        }
    }

    checkWindowClose(msg) {
        if (!msg) {
            this.checkClose = false;
        } else {
            this.checkClose = true;
            this.closeConfirmMessage = msg;
        }
    }

    updateTimeFields(f, year_field, month_field, day_field, hour_field, minute_field, date) {
        function padDateValue(str) {
            str = String(str);
            if (str.length === 1) {
                str = '0' + str;
            }
            return str + '';// hack to make this a string
        }

        if (typeof date == 'undefined') {
            date = new Date();
        }
        this.selectOption(month_field, padDateValue(date.getMonth() + 1));
        this.selectOption(day_field, padDateValue(date.getDate()));
        this.selectOption(year_field, date.getFullYear());
        this.selectOption(hour_field, padDateValue(date.getHours()));
        // minutes need special case due the 5 minute granularity
        const minutes = Math.floor(date.getMinutes() / 5) * 5;
        this.selectOption(minute_field, padDateValue(minutes));
    }

    setupShowSelections(select_box) {
        select_box.change(this.showSelections);
        select_box.change();
    }

    showSelections(e) {
        const select_box = $(e.target);
        const selected = [];
        if (select_box.val() != null) {
            $.each(select_box.val(), function (index, value) {
                selected.push(select_box.find("option[value='" + value + "']").text());
            });
        }

        const display_div = $('#selection_' + select_box.attr('id'));
        display_div.text("Current Selection: " + select_box.children(':selected').map(function () {
            return this.text
        }).get().join(", "));
    }

    changeVisibility(dom_id, visibility) {
        $('#' + dom_id).toggle(visibility);
    }

    // Replace special characters MS uses for quotes with normal versions
    replaceSpecialCharacters(s) {
        let newString = '';
        let thisChar;
        let charCode;
        for (let i = 0; i < s.length; i++) {
            thisChar = s.charAt(i);
            charCode = s.charCodeAt(i);
            if ((charCode === 8220) || (charCode === 8221)) {
                thisChar = '"';
            } else if (charCode === 8217) {
                thisChar = "'";
            } else if (charCode === 8230) {
                thisChar = "...";
            } else if (charCode === 8226) {
                thisChar = "*";
            } else if (charCode === 8211) {
                thisChar = "-";
            }
            newString = newString + thisChar;
        }
        return newString;
    }

    /**
     * Make javascript Date() object from datetime form selection.
     *
     * @param   {String}  name    Form element prefix for date.
     */
    makeDate(name) {
        const d = new Date();
        d.setFullYear(this.getField(name + '[Year]').val());
        d.setMonth(this.getField(name + '[Month]').val() - 1);
        d.setMonth(this.getField(name + '[Month]').val() - 1, this.getField(name + '[Day]').val());
        d.setHours(this.getField(name + '[Hour]').val());
        d.setMinutes(this.getField(name + '[Minute]').val());
        d.setSeconds(0);
        return d;
    }

    /**
     * @param   {Object}  f       Form object
     * @param   {int} type    The type of update occurring.
     *                          0 = Duration was updated.
     *                          1 = Start time was updated.
     *                          2 = End time was updated.
     *                          11 = Start time refresh icon was clicked.
     *                          12 = End time refresh icon was clicked.
     * @param {String} element Name of the element changed
     */
    calcDateDiff(f, type, element) {
        let duration = this.getField('time_spent').val();
        // enforce 5 minute granularity.
        duration = Math.floor(duration / 5) * 5;

        const d1 = this.makeDate('date');
        const d2 = this.makeDate('date2');

        const minute = 1000 * 60;
        /*
        - if time is adjusted, duration is calculated,
        - if duration is adjusted, the end time is adjusted,
        - clicking refresh icon on either icons will make that time current date
          and recalculate duration.
        */

        if (type == 0) { // duration
            d1.setTime(d2.getTime() - duration * minute);
        } else if (type == 1) { // start time
            if (element === 'date[Year]' || element === 'date[Month]' || element === 'date[Day]') {
                d2.setTime(d1.getTime() + duration * minute);
            } else {
                duration = (d2.getTime() - d1.getTime()) / minute;
            }
        } else if (type == 2) { // end time
            duration = (d2.getTime() - d1.getTime()) / minute;
        } else if (type == 11) { // refresh start time
            if (duration) {
                d2.setTime(d1.getTime() + duration * minute);
            } else {
                duration = (d2.getTime() - d1.getTime()) / minute;
            }
        } else if (type == 12) { // refresh end time
            if (duration) {
                d1.setTime(d2.getTime() - duration * minute);
            } else {
                duration = (d2.getTime() - d1.getTime()) / minute;
            }
        }

        /* refill form after calculation */
        this.updateTimeFields(f, 'date[Year]', 'date[Month]', 'date[Day]', 'date[Hour]', 'date[Minute]', d1)
        this.updateTimeFields(f, 'date2[Year]', 'date2[Month]', 'date2[Day]', 'date2[Hour]', 'date2[Minute]', d2)

        duration = parseInt(duration);
        if (duration > 0) {
            this.getField('time_spent').val(duration);
        }
    }

    changeClockStatus() {
        window.location.href = this.rel_url + 'clock_status.php?current_page=' + window.location.pathname;
        return false;
    }

    openHelp(e) {
        const $target = $(e.target);
        const topic = $target.closest('a.help').attr('data-topic');
        const width = 500;
        const height = 450;
        const w_offset = 30;
        const h_offset = 30;
        let location = 'top=' + h_offset + ',left=' + w_offset + ',';
        if (screen.width) {
            location = 'top=' + h_offset + ',left=' + (screen.width - (width + w_offset)) + ',';
        }
        const features = 'width=' + width + ',height=' + height + ',' + location + 'resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
        const helpWin = window.open(this.rel_url + 'help.php?topic=' + topic, '_help', features);
        helpWin.focus();

        return false;
    }

    clearAutoSave(prefix) {
        let i;
        let key;
        for (i = localStorage.length; i >= 0; i--) {
            key = localStorage.key(i);
            if (key && key.startsWith(prefix)) {
                localStorage.removeItem(localStorage.key(i));
            }
        }
    }

    showPreview(input_id, preview_id, params) {
        const $input = $(input_id);
        $input.hide();

        const $preview = $(preview_id);
        $preview.load(this.rel_url + 'get_remote_data.php?action=preview', { ...params, source: $input.val() }, function () {
            $(document).trigger("md_preview.eventum", [$preview, preview_id]);
        });
        $preview.show();
    }

    hidePreview(input_id, preview_id) {
        const $input = $(input_id);
        $input.show();

        const $preview = $(preview_id);
        $preview.hide();
    }

}
