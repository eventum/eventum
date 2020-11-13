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

export class ExpandableCell {
    ready() {
        const ec = this;
        $('.expandable_buttons .expand').click(function (e) {
            const $target = $(e.target).parent();
            const expand_type = $target.attr('data-expand-type');
            const list_id = $target.attr('data-list-id');
            if (list_id !== '') {
                ec.expand(expand_type, list_id);
            } else {
                $.each(expand_type.split(","), function (index, value) {
                    $('.expandable_buttons.' + value + ' .expand').each(function () {
                        this.click();
                    })
                });
            }
        });
        $('.expandable_buttons .collapse').click(function (e) {
            const $target = $(e.target).parent();
            const expand_type = $target.attr('data-expand-type');
            const list_id = $target.attr('data-list-id');
            if (list_id !== '') {
                ec.collapse(expand_type, list_id);
            } else {
                $.each(expand_type.split(","), function (index, value) {
                    $('.expandable_buttons.' + value + ' .collapse').each(function () {
                        this.click();
                    })
                });
            }
        });
    };

    expand(expand_type, list_id) {
        const $row = $('#ec_' + expand_type + '_item_' + list_id + '_row');
        const $cell = $row.find('td');
        if ($cell.html() === '') {
            $cell.load(Eventum.rel_url + 'get_remote_data.php?action=' + expand_type + '&ec_id=' + expand_type +
                '&list_id=' + list_id, function () {
                $(document).trigger("ec_expand.eventum", [$row, expand_type, list_id]);
            });
        }
        $row.show();
    };

    collapse(expand_type, list_id) {
        const $row = $('#ec_' + expand_type + '_item_' + list_id + '_row');
        $row.hide();
    };
}
