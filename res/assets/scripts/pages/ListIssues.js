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

/*
 * List Issues Page
 */
export default class {
    constructor() {
        this.current_page = 0;
        this.last_page = 0;
        this.page_url = '';
    }

    ready(page_id) {
        const page = this;
        const $listForm = $('#list_form');

        page.current_page = parseInt($listForm.attr('data-current-page'));
        page.last_page = parseInt($listForm.attr('data-last-page'));
        page.page_url = Eventum.rel_url + 'list.php';

        $('#toggle_quick_filter').click(function () {
            Eventum.toggle_section_visibility('quick_filter');
        });
        $('#toggle_current_filters').click(function () {
            Eventum.toggle_section_visibility('current_filters');
        });
        $('#toggle_bulk_update').click(function () {
            Eventum.toggle_section_visibility('bulk_update');
        });
        $('#reset_bulk_update').click(function() {
            page.reset_bulk_update();
        });
        $('#bulk_update_button').click(function() {
            return page.bulk_update();
        });
        $('#clear_filters').click(function() {
            page.clearFilters();
        });
        $('#hide_closed').click(function() {
            page.hideClosed();
        });
        $('#show_all_projects').click(function() {
            page.showAllProjects();
        });
        $('#page_size').change(function() {
            page.resizePager();
        });
        $('#resize_page').click(function() {
            page.resizePager();
        });
        $('#custom_filter').change(function() {
            return page.runCustomFilter();
        });
        $('.select_all').click(function () {
            Eventum.toggleCheckAll('item[]');
        });

        Eventum.getField('first').click(function () {
            return page.setPage(0);
        });
        Eventum.getField('previous').click(function () {
            return page.setPage(page.page - 1);
        });
        Eventum.getField('next').click(function () {
            return page.setPage(page.current_page + 1);
        });
        Eventum.getField('last').click(function () {
            return page.setPage(page.last_page);
        });
        Eventum.getField('go').click(function() {
            return page.goPage();
        });
        Eventum.getField('page').keydown(function (e) {
            if (e.which === 13) {
                page.goPage();
            }
        });

        $('#export_csv').click(function() {
            return page.downloadCSV();
        });
        $('.custom_field').click(function(e) {
            return page.updateCustomFields(e);
        });

        page.disableFields();

        const refreshRate = parseInt($listForm.attr('data-refresh-rate')) * 1000;
        if (refreshRate) {
            setTimeout(function () {
                location.reload();
            }, refreshRate);
        }
    }

    reset_bulk_update() {
        Eventum.clearSelectedOptions('users[]');
        Eventum.clearSelectedOptions('status');
        Eventum.clearSelectedOptions('release');
        Eventum.clearSelectedOptions('priority');
        Eventum.clearSelectedOptions('category');
        Eventum.clearSelectedOptions('closed_status');
    }

    bulk_update() {
        const $form = $('#list_form');

        if (!Validation.hasOneChecked('item[]')) {
            alert('Please choose which issues to update.');
            return false;
        }

        // figure out what is changing
        const changed = [];
        if (Validation.hasOneSelected('users[]')) {
            changed[changed.length] = 'Assignment';
        }
        if (Eventum.getField('status', $form).val() !== '') {
            changed[changed.length] = 'Status';
        }
        if (Eventum.getField('release', $form).val() !== '') {
            changed[changed.length] = 'Release';
        }
        if (Eventum.getField('priority', $form).val() !== '') {
            changed[changed.length] = 'Priority';
        }
        if (Eventum.getField('category', $form).val() !== '') {
            changed[changed.length] = 'Category';
        }
        if (Eventum.getField('closed_status', $form).val() !== '') {
            changed[changed.length] = 'Closed Status';
        }
        if (changed.length < 1) {
            alert('Please choose new values for the selected issues');
            return false;
        }

        let msg = 'Warning: If you continue, you will change the ';
        for (let i = 0; i < changed.length; i++) {
            msg += changed[i];
            if ((changed.length > 1) && (i === (changed.length - 2))) {
                msg += ' and ';
            } else {
                if (i !== (changed.length - 1)) {
                    msg += ', ';
                }
            }
        }
        msg += ' for all selected issues. Are you sure you want to continue?';
        if (!confirm(msg)) {
            return false;
        }

        const features = 'width=420,height=200,top=30,left=30,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
        const popupWin = window.open('', '_popup', features);
        popupWin.focus();

        $form.attr('action', 'popup.php');
        $form.attr('target', '_popup');
        $form.submit();
    };

    clearFilters() {
        const $form = $('#quick_filter_form');

        $form.find('input,select').val('');
        $form.submit();
    }

    runCustomFilter() {
        const $customFilter = $('#custom_filter');
        const cst_url = $customFilter.val();

        if (Validation.isWhitespace(cst_url)) {
            alert('Please select the custom filter to search against.');
            $customFilter.focus();
            return false;
        }

        location.href = 'list.php?cat=search&' + cst_url;
        return false;
    };

    hideClosed() {
        if ($('#hide_closed').is(':checked')) {
            window.location.href = this.page_url + "?" + Eventum.replaceParam(window.location.href, 'hide_closed', '1');
        } else {
            window.location.href = this.page_url + "?" + Eventum.replaceParam(window.location.href, 'hide_closed', '0');
        }
    }

    showAllProjects() {
        if ($('#show_all_projects').is(':checked')) {
            window.location.href = this.page_url + "?" + Eventum.replaceParam(window.location.href, 'show_all_projects', '1');
        } else {
            window.location.href = this.page_url + "?" + Eventum.replaceParam(window.location.href, 'show_all_projects', '0');
        }
    }

    resizePager() {
        window.location.href = this.page_url + "?" + Eventum.replaceParam(window.location.href, 'rows', $('#page_size').val());
    }

    setPage(new_page) {
        if ((new_page > this.last_page) || (new_page < 0) ||
            (new_page === this.current_page)) {
            return false;
        }

        window.location.href = this.page_url + "?" + Eventum.replaceParam(window.location.href, 'pagerRow', new_page);
    }

    goPage() {
        const new_page = Eventum.getField('page').val();

        if (new_page > this.last_page + 1 || new_page <= 0 ||
            new_page === this.current_page + 1 || !Validation.isNumberOnly(new_page)) {
            Eventum.getField('page').val(this.current_page + 1);
            return false;
        }
        this.setPage(new_page - 1);
    }

    disableFields() {
        if (this.current_page === 0) {
            Eventum.getField('first').attr('disabled', 'disabled');
            Eventum.getField('previous').attr('disabled', 'disabled');
        }

        if (this.current_page === this.last_page || this.last_page <= 0) {
            Eventum.getField('next').attr('disabled', 'disabled');
            Eventum.getField('last').attr('disabled', 'disabled');
        }

        if (this.current_page === 0 && this.last_page <= 0) {
            Eventum.getField('page').attr('disabled', 'disabled');
            Eventum.getField('go').attr('disabled', 'disabled');
        }
    }

    downloadCSV() {
        $('#csv_form').submit();

        return false;
    }

    updateCustomFields(e) {
        const $target = $(e.target);
        const issue_id = $target.parents('tr').attr('data-issue-id');
        const features = 'width=560,height=460,top=30,left=30,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
        const customWin = window.open('custom_fields.php?issue_id=' + issue_id, '_custom_fields', features);

        customWin.focus();
        return false;
    }
}
