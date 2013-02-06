/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */

var today = new Date();
var expires = new Date(today.getTime() + (56 * 86400000));

function addFileRow(element_name, field_name)
{
    if (document.all) {
        var fileTable = document.all[element_name];
    } else if (!document.all && document.getElementById) {
        var fileTable = document.getElementById(element_name);
    }
    if (!fileTable) {
        return;
    }
    rows = fileTable.rows.length;

    // check if last box is empty and if it is, don't add another
    if (document.all) {
        var last_field = document.all[field_name + '_' + rows];
    } else if (!document.all && document.getElementById) {
        var last_field = document.getElementById(field_name + '_' + rows);
    }
    if (last_field && last_field.value == '') {
        return;
    }

    var newRow = fileTable.insertRow(rows);
    var cell = newRow.insertCell(0);
    if (document.all) {
        cell.innerHTML = '<input id="' + field_name + '_' + (rows+1) + '" class="shortcut" size="40" type="file" name="' + field_name + '" onChange="javascript:addFileRow(\'' + element_name + '\', \'' + field_name + '\');">';
    } else {
        var input = document.createElement('INPUT');
        input.setAttribute('type', 'file');
        input.name = field_name;
        input.className = 'shortcut';
        input.size = 40;
        input.onchange = new Function('addFileRow(\'' + element_name + '\', \'' + field_name + '\');');
        input.id = field_name + '_' + (rows+1);
        cell.appendChild(input);
    }
}

function inArray(value, stack)
{
    for (var i = 0; i < stack.length; i++) {
        if (stack[i] == value) {
            return true;
        }
    }
    return false;
}

function getEmailFromAddress(str)
{
    var first_pos = str.lastIndexOf('<');
    var second_pos = str.lastIndexOf('>');
    if ((first_pos != -1) && (second_pos != -1)) {
        return str.substring(first_pos+1, second_pos);
    } else {
        return str;
    }
}

/**
 * reload parent window (if defined) and close current window
 *
 * 'noparent' if true, means that parent should not be reloaded (for example if
 * you call the popup via bookmark)
 */
function closeAndRefresh(noparent)
{
    if (opener && !noparent) {
        opener.location.href = opener.location;
    }
    window.close();
}

function str_replace(s, srch, rplc)
{
    var tmp = s;
    var tmp_before = new String();
    var tmp_after = new String();
    var tmp_output = new String();
    var int_before = 0;
    var int_after = 0;

    while (tmp.toUpperCase().indexOf(srch.toUpperCase()) > -1) {
        int_before = tmp.toUpperCase().indexOf(srch.toUpperCase());
        tmp_before = tmp.substring(0, int_before);
        tmp_output = tmp_output + tmp_before;
        tmp_output = tmp_output + rplc;
        int_after = tmp.length - srch.length + 1;
        tmp = tmp.substring(int_before + srch.length);
    }

    return tmp_output + tmp;
}

function showSelections(form_name, field_name)
{
    var f = getForm(form_name);
    var field = getFormElement(f, field_name);
    var selections = getSelectedItems(field);
    var selected_names = new Array();
    for (var i = 0; i < selections.length; i++) {
        selected_names.push(selections[i].text);
    }
    var display_div = getPageElement('selection_' + field_name);
    display_div.innerHTML = 'Current Selections: ' + selected_names.join(', ');
}

function replaceWords(str, original, replacement)
{
    var lines = str.split("\n");
    for (var i = 0; i < lines.length; i++) {
        lines[i] = replaceWordsOnLine(lines[i], original, replacement);
    }
    return lines.join("\n");
}

function replaceWordsOnLine(str, original, replacement)
{
    var words = str.split(' ');
    for (var i = 0; i < words.length; i++) {
        words[i] = words[i].replace(/^\s*/, '').replace(/\s*$/, '');
        if (words[i] == original) {
            words[i] = replacement;
        }
    }
    return words.join(' ');
}

function checkSpelling(form_name, field_name)
{
    var features = 'width=420,height=400,top=30,left=30,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
    var popupWin = window.open('spell_check.php?form_name=' + form_name + '&field_name=' + field_name, '_spellChecking', features);
    popupWin.focus();
}

function resizeTextarea(page_name, form_name, field_name, change)
{
    var f = getForm(form_name);
    var field = getFormElement(f, field_name);
    field.cols = field.cols + change;
    var cookie_name = 'textarea_' + page_name + '_' + field_name;
    setCookie(cookie_name, field.cols, expires);
}

function removeOptionByValue(f, field_name, value)
{
    var field = getFormElement(f, field_name);
    for (var i = 0; i < field.options.length; i++) {
        if (field.options[i].value == value) {
            field.options[i] = null;
        }
    }
}

function getTotalCheckboxes(f, field_name)
{
    var total = 0;
    for (var i = 0; i < f.elements.length; i++) {
        if (f.elements[i].name == field_name) {
            total++;
        }
    }
    return total;
}

function getTotalCheckboxesChecked(f, field_name)
{
    var total = 0;
    for (var i = 0; i < f.elements.length; i++) {
        if ((f.elements[i].name == field_name) && (f.elements[i].checked)) {
            total++;
        }
    }
    return total;
}

function hideComboBoxes(except_field)
{
    for (var i = 0; i < document.forms.length; i++) {
        for (var y = 0; y < document.forms[i].elements.length; y++) {
            if (((document.forms[i].elements[y].type == 'select-one') ||
            (document.forms[i].elements[y].type == 'select-multiple')) &&
            (document.forms[i].elements[y].name != except_field) &&
            (document.forms[i].elements[y].name != 'lookup') &&
            (document.forms[i].elements[y].name != 'lookup[]')) {
                document.forms[i].elements[y].style.visibility = 'hidden';
            }
        }
    }
}

function showComboBoxes()
{
    for (var i = 0; i < document.forms.length; i++) {
        for (var y = 0; y < document.forms[i].elements.length; y++) {
            if (((document.forms[i].elements[y].type == 'select-one') ||
            (document.forms[i].elements[y].type == 'select-multiple')) &&
            (document.forms[i].elements[y].name != 'lookup') &&
            (document.forms[i].elements[y].name != 'lookup[]')) {
                document.forms[i].elements[y].style.visibility = 'visible';
            }
        }
    }
}

function getOverlibContents(options, target_form, target_field, is_multiple)
{
    hideComboBoxes(target_field);
    var html = '<form name="overlib_form" onSubmit="javascript:return lookupOption(this, \'' + target_form + '\', \'' + target_field + '\');">' + options + '<br /><input name="search" class="lookup_field_overlib" type="text" size="24" value="paste or start typing here" onBlur="javascript:this.value=\'paste or start typing here\';" onFocus="javascript:this.value=\'\';" onKeyUp="javascript:lookupField(this.form, this, \'lookup';
    if ((is_multiple != null) && (is_multiple == true)) {
        html += '[]';
    }
    html += '\');"><input class="button_overlib" type="submit" value="Lookup"><br />'
    + '<input type="text" name="id_number" size="24" class="lookup_field_overlib" value="id #" onFocus="javascript:this.value=\'\';">'
    + '<input type="button" class="button_overlib" value="Add By ID" onClick="lookupByID(document.forms[\'overlib_form\'].id_number, \'' + target_form + '\', \'' + target_field + '\')"></form>';
    return html;
}

function getFillInput(options, target_form, target_field)
{
    hideComboBoxes(target_field);
    return '<form onSubmit="javascript:return fillInput(this, \'' + target_form + '\', \'' + target_field + '\');">' + options + '<input class="button_overlib" type="submit" value="Lookup"><br><input name="search" class="lookup_field_overlib" type="text" size="24" value="paste or start typing here" onBlur="javascript:this.value=\'paste or start typing here\';" onFocus="javascript:this.value=\'\';" onKeyUp="javascript:lookupField(this.form, this, \'lookup\');"></form>';
}

function lookupOption(f, target_form, target_field)
{
    var w = document;
    for (var i = 0; i < w.forms.length; i++) {
        if (w.forms[i].name == target_form) {
            var test = getFormElement(f, 'lookup');
            if (!test) {
                var field = getFormElement(f, 'lookup[]');
                var target = getFormElement(getForm(target_form), target_field);
                clearSelectedOptions(target);
                selectOptions(w.forms[i], target_field, getSelectedItems(field));
            } else {
                options = getSelectedOption(f, 'lookup');
                if (options == -1) {
                    return false;
                }
                selectOption(w.forms[i], target_field, options);
            }
            nd();
            showComboBoxes();
            break;
        }
    }
    return false;
}

function lookupByID(field, target_form, target_field)
{
    if (!isNumberOnly(field.value)) {
        alert('Please enter numbers only');
    } else {
        // try to find value in targer field.
        target_obj = document.forms[target_form].elements[target_field];
        found = false;
        for (i = 0;i<target_obj.options.length; i++) {
            if (target_obj.options[i].value == field.value) {
                found = true;
                target_obj.options[i].selected = true;
            }
        }
        if (found == false) {
            alert('ID #' + field.value + ' was not found');
        } else {
            field.value = '';
            // check if we should call "showSelection"
            if (document.getElementById('selection_' + target_field) != null) {
                showSelections(target_form, target_field)
            }
        }
    }
    return false;
}

function fillInput(f, target_form, target_field)
{
    var exists = getFormElement(f, 'lookup');
    var target_f = getForm(target_form);
    if (!exists) {
        var field = getFormElement(f, 'lookup[]');
        var target_field = getFormElement(target_f, target_field);
        target_field.value = '';
        var values = getValues(getSelectedItems(field));
        target_field.value = values.join('; ');
        errorDetails(target_f, target_field, false);
    } else {
        var field_value = getSelectedOption(f, 'lookup');
        var field = getFormElement(target_f, target_field);
        field.value = field_value;
        errorDetails(target_f, target_field, false);
    }
    nd();
    showComboBoxes();
    return false;
}

function lookupField(f, search_field, field_name, callbacks)
{
    var search = search_field.value;
    if (isWhitespace(search)) {
        return false;
    }
    var target_field = getFormElement(f, field_name);
    if (!target_field) {
        target_field = getFormElement(f, field_name + '[]');
    }
    for (var i = 0; i < target_field.options.length; i++) {
        var value = target_field.options[i].text.toUpperCase();
        if (target_field.type == 'select-multiple') {
            // if we are targetting a multiple select box, then unselect everything
            // before selecting the matched option
            if (startsWith(value, search.toUpperCase())) {
                clearSelectedOptions(target_field);
                target_field.options[i].selected = true;
                // handle calling any callbacks
                if (callbacks != null) {
                    for (var y = 0; y < callbacks.length; y++) {
                        eval(callbacks[y] + ';');
                    }
                }
                return true;
            }
        } else {
            // normal drop-down boxes will search across the option value, and
            // not just the beginning of it (e.g. '*hello*' instead of 'hello*')
            if (value.indexOf(search.toUpperCase()) != -1) {
                target_field.options[i].selected = true;
                // handle calling any callbacks
                if (callbacks != null) {
                    for (var y = 0; y < callbacks.length; y++) {
                        eval(callbacks[y] + ';');
                    }
                }
                return true;
            }
        }
    }
    target_field.selectedIndex = 0;
}

function clearSelectedOptions(field)
{
    for (var i = 0; i < field.options.length; i++) {
        field.options[i].selected = false;
    }
}

function selectAllOptions(f, field_name)
{
    var field = getFormElement(f, field_name);
    for (var y = 0; y < field.options.length; y++) {
        field.options[y].selected = true;
    }
}

function selectOptions(f, field_name, values)
{
    var field = getFormElement(f, field_name);
    for (var i = 0; i < values.length; i++) {
        for (var y = 0; y < field.options.length; y++) {
            if (field.options[y].value == values[i].value) {
                field.options[y].selected = true;
            }
        }
    }
}

function setHiddenFieldValue(f, field_name, value)
{
    var field = getFormElement(f, field_name);
    field.value = value;
}

function getForm(form_name)
{
    for (var i = 0; i < document.forms.length; i++) {
        if (document.forms[i].name == form_name) {
            return document.forms[i];
        }
    }
}

function getPageElement(id)
{
    if (document.getElementById) {
        return document.getElementById(id);
    } else if (document.all) {
        return document.all[id];
    }
}

function getOpenerPageElement(name)
{
    if (window.opener.document.getElementById) {
        return window.opener.document.getElementById(name);
    } else if (window.opener.document.all) {
        return window.opener.document.all[name];
    }
}

function getFormElement(f, field_name, num)
{
    var elements = document.getElementsByName(field_name);
    var y = 0;
    for (var i = 0; i < elements.length; i++) {
        if (f != elements[i].form) {
            continue;
        }
        if (num != null) {
            if (y == num) {
                return elements[i];
            }
            y++;
        } else {
            return elements[i];
        }
    }
    return false;
}

function getSelectedItems(field)
{
    var selected = new Array();
    for (var i = 0; i < field.options.length; i++) {
        if (field.options[i].selected) {
            selected[selected.length] = field.options[i];
        }
    }
    return selected;
}

function getSelectedOptionValues(f, field_name)
{
    var field = getFormElement(f, field_name);
    var selected = new Array();
    for (var i = 0; i < field.options.length; i++) {
        if (field.options[i].selected) {
            selected[selected.length] = field.options[i].value;
        }
    }
    return selected;
}

function getValues(list)
{
    var values = new Array();
    for (var i = 0; i < list.length; i++) {
        values[values.length] = list[i].value;
    }
    return values;
}

function checkRadio(form_name, field_name, num)
{
    var f = getForm(form_name);
    var field = getFormElement(f, field_name, num);
    if (!field.disabled) {
        field.checked = true;
    }
}

function toggleCheckbox(form_name, field_name, num)
{
    var f = getForm(form_name);
    var checkbox = getFormElement(f, field_name, num);
    if (checkbox.disabled) {
        return false;
    }
    if (checkbox.checked) {
        checkbox.checked = false;
    } else {
        checkbox.checked = true;
    }
}

var toggle = 'off';
function toggleSelectAll(f, field_name)
{
    for (var i = 0; i < f.elements.length; i++) {
        if (f.elements[i].disabled) {
            continue;
        }
        if (f.elements[i].name == field_name) {
            if (toggle == 'off') {
                f.elements[i].checked = true;
            } else {
                f.elements[i].checked = false;
            }
        }
    }
    if (toggle == 'off') {
        toggle = 'on';
    } else {
        toggle = 'off';
    }
}

function getCookies()
{
    var t = new Array();
    var pieces = new Array();
    var cookies = new Object();
    if (document.cookie) {
        t = document.cookie.split(';');
        for (var i = 0; i < t.length; i++) {
            pieces = t[i].split('=');
            eval('cookies.' + pieces[0].replace('[', '_').replace(']', '_') + ' = "' + pieces[1] + '";');
        }
        return cookies;
    }
}

function isElementVisible(element)
{
    if ((!element.style.display) || (element.style.display == getDisplayStyle())) {
        return true;
    } else {
        return false;
    }
}

function toggleVisibility(title, create_cookie, use_inline)
{
    var element = getPageElement(title + '1');
    if (isElementVisible(element)) {
        var new_style = 'none';
    } else {
        var new_style = getDisplayStyle(use_inline);
    }
    var i = 1;
    while (1) {
        element = getPageElement(title + i);
        if (!element) {
            break;
        }
        element.style.display = new_style;
        i++;
    }
    // if any elements were found, then...
    if (i > 1) {
        var link_element = getPageElement(title + '_link');
        if (link_element) {
            if (new_style == 'none') {
                link_element.innerHTML = 'show';
                link_element.title = 'show details about this section';
            } else {
                link_element.innerHTML = 'hide';
                link_element.title = 'hide details about this section';
            }
        }
    }
    if (((create_cookie == null) || (create_cookie == false)) && (create_cookie != undefined)) {
        return false;
    } else {
        setCookie('visibility_' + title, new_style, expires);
    }
}

function changeVisibility(title, visibility, use_inline)
{
    var element = getPageElement(title);
    if (visibility) {
        var new_style = getDisplayStyle(use_inline);
    } else {
        var new_style = 'none';
    }
    element.style.display = new_style;
}

function getDisplayStyle(use_inline)
{
    // kind of hackish, but it works perfectly with IE6 and Mozilla 1.1
    if (is_ie5up) {
        if (use_inline == true) {
            return 'inline';
        } else {
            return 'block';
        }
    } else {
        return '';
    }
}

function getCookie(name)
{
    var start = document.cookie.indexOf(name+"=");
    var len = start+name.length+1;
    if ((!start) && (name != document.cookie.substring(0,name.length))) return null;
    if (start == -1) return null;
    var end = document.cookie.indexOf(";",len);
    if (end == -1) end = document.cookie.length;
    return unescape(document.cookie.substring(len,end));
}

function setCookie(name, value, expires, path, domain, secure)
{
    document.cookie = name + "=" +escape(value) +
    ( (expires) ? ";expires=" + expires.toGMTString() : "") +
    ( (path) ? ";path=" + path : "") +
    ( (domain) ? ";domain=" + domain : "") +
    ( (secure) ? ";secure" : "");
}

function openHelp(rel_url, topic)
{
    var width = 500;
    var height = 450;
    var w_offset = 30;
    var h_offset = 30;
    var location = 'top=' + h_offset + ',left=' + w_offset + ',';
    if (screen.width) {
        location = 'top=' + h_offset + ',left=' + (screen.width - (width + w_offset)) + ',';
    }
    var features = 'width=' + width + ',height=' + height + ',' + location + 'resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
    var helpWin = window.open(rel_url + 'help.php?topic=' + topic, '_help', features);
    helpWin.focus();
}

// Replace special characters MS uses for quotes with normal versions
function replaceSpecialCharacters(e)
{
    var s = new String(e.value);
    var newString = '';
    var thisChar;
    var charCode;
    for (i = 0; i < s.length; i++) {
        thisChar = s.charAt(i);
        charCode = s.charCodeAt(i);
        if ((charCode == 8220) || (charCode == 8221)) {
            thisChar = '"';
        } else if (charCode == 8217) {
            thisChar = "'";
        } else if (charCode == 8230) {
            thisChar = "...";
        } else if (charCode == 8226) {
            thisChar = "*";
        } else if (charCode == 8211) {
            thisChar = "-";
        }
        newString = newString + thisChar;
    }
    e.value = newString;
}


function getEventTarget(e)
{
    var targ;
    if (!e) var e = window.event;
    if (e.target) targ = e.target;
    else if (e.srcElement) targ = e.srcElement;
    if (targ.nodeType == 3) // defeat Safari bug
    targ = targ.parentNode;

    return targ;
}

// call when document ready
$(document).ready(function() {
    $('.date_picker').datepicker({
        dateFormat: 'yy-mm-dd',
        firstDay: user_prefs.week_firstday
    });
});

// dialog type calender isn't working in Konqueror beacuse it's not a supported browser by either jQuery or jQuery UI
// http://groups.google.com/group/jquery-ui/browse_thread/thread/ea61238c34cb5f33/046837b02fb90b5c
if (navigator.appName != 'Konqueror') {
    $(document).ready(function() {
        $(".inline_date_pick").click(function() {
            var masterObj = this;
            var masterObjPos = $(masterObj).offset();
            // offset gives uses top and left but datepicker needs pageX and pageY
            var masterObjPos = {pageX: masterObjPos.left, pageY: masterObjPos.top};

            // as i cannot find any documentation about ui.datepicker in dialog mode + blockUI, then i'll disable blockui while showing datepicker
            // i found in ui.datepicker when in dialog mode: "if ($.blockUI) $.blockUI(this.dpDiv);" so i assume the point was to show the calender in blockUI?
            var tmp_blockUI = $.blockUI;
            $.blockUI = false;

            $(this).datepicker(
                // we use dialog type calender so we won't haveto have a hidden element on the page
                'dialog',
                // selected date
                masterObj.innerHTML,
                // onclick handler
                function (date, dteObj) {
                    var field_name = masterObj.id.substr(0,masterObj.id.indexOf('|'));
                    var issue_id = masterObj.id.substr(masterObj.id.indexOf('|')+1);
                    if (date == '') {
                        // clear button
                        dteObj.selectedDay = 0;
                        dteObj.selectedMonth = 0;
                        dteObj.selectedYear = 0;
                    }
                    $.post("/ajax/update.php", {field_name: field_name, issue_id: issue_id, day: dteObj.selectedDay, month: (dteObj.selectedMonth+1), year: dteObj.selectedYear}, function(data) {
                        masterObj.innerHTML = data;
                    }, "text");
                },
                // config
                {dateFormat: 'dd M yy', duration: "", firstDay: 1},
                // position of the datepicker calender - taken from div's offset
                masterObjPos
            );

            // restore blockUI
            $.blockUI = tmp_blockUI;

            return false;
        });
    });
}
