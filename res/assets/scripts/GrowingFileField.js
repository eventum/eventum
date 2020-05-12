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

/**
 * This is used in new issue and update issue page
 * for file up0load to add new file row once first is used
 *
 * This is used only if dropzone is not activated
 * and can be likely just dropped.
 */
export class GrowingFileField {
    ready() {
        $('.growing_file_field').bind('change', this.copy_row);
    };

    copy_row(e) {
        const $target = $(e.target);
        if ($target.val() === '') {
            return;
        }

        const $new_row = $target.parents('tr').first().clone(true);
        $target.parents('tbody').first().append($new_row);
    };
}
