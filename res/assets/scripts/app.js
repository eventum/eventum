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

require('./bootstrap');

$(document).ready(function () {
    // see http://api.jquery.com/jQuery.param/
    jQuery.ajaxSettings.traditional = true;

    const $body = $("body");
    const $head = $("head");
    const $textarea = $("textarea");

    Eventum.rel_url = $head.attr("data-rel-url");

    // check the class of the body and try to execute the prep functions if there is a class defined for that
    const classes = $body.attr('class').split(" ");
    const page_id = $body.attr('id');
    classes.push(page_id);

    $.each(classes, function (indexInArray, className) {
        className = className.replace(/-/g, '_');
        if (className === '' || className === "new") {
            return;
        }

        if (typeof window[className] !== "undefined" && typeof window[className].ready === "function") {
            window[className].ready(page_id);
        }
    });

    // focus on the first text input field in the first field on the page
    $(":text:visible:enabled .autofocus").first().focus();

    $(".close_window").click(function () {
        window.close();
    });

    window.onbeforeunload = Eventum.handleClose;

    $('form.validate').submit(function(e) {
        return Validation.callback(e);
    });

    ExpandableCell.ready();

    $('#project_chooser').change(function () {
        $(this).find("form").submit();
    });

    $('#change_clock_status').click(function() {
        return Eventum.changeClockStatus();
    });

    $(".date_picker").datepicker({
        dateFormat: "yy-mm-dd",
        firstDay: user_prefs.week_firstday
    });

    $('#shortcut_form').submit(function (e) {
        const target = $("#shortcut");
        const value = target.val().replace(/\D/g, '');
        if (Validation.isWhitespace(value)) {
            alert("Please enter a valid Issue ID");
            return false;
        }
        target.val(value);
    });

    $("a.help").click(function(e) {
        return Eventum.openHelp(e);
    });

    $("input.issue_field").blur(function(e) {
        Validation.validateIssueNumberField(e);
    });

    // % complete progressbar
    $("div.iss_percent_complete").each(function () {
        const $e = $(this);
        $e.progressbar({value: $e.data("percent")});
    });

    // configure chosen
    // https://harvesthq.github.io/chosen/
    // https://harvesthq.github.io/chosen/options.html
    $(".chosen-select").chosen({search_contains: true});

    // https://github.com/jackmoore/autosize
    autosize($textarea);

    // https://github.com/widernet/cmd-ctrl-enter
    $textarea.cmdCtrlEnter();

    // https://mermaid-js.github.io/mermaid/#/usage
    mermaid.initialize({startOnLoad:true});

    // jquery timeago
    jQuery.timeago.settings.allowFuture = true;

    const $timeago = $("time.timeago");
    // on click toggle between views
    const timeago_toggle = function () {
        const $el = $(this);
        const old = $el.attr("title");
        $el.attr('title', $el.text());
        $el.text(old);
    };
    if (user_prefs.relative_date) {
        // if enabled, then enable for all elements
        $timeago
            .timeago()
            .click(timeago_toggle);
    } else {
        // otherwise enable only on click
        $timeago.click(function () {
            $(this)
                .timeago()
                .unbind('click')
                .click(timeago_toggle);
        });
    }

    $(document).on("ec_expand.eventum", function() {
        Eventum.setupTrimmedEmailToggle();
        mermaid.init();
    });
    $(document).on("md_preview.eventum", function() {
        mermaid.init();
    });
});
