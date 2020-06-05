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
        $('#project_select_form input[name=project]').on('change', function () {
            $(this).closest("form").submit();
        });
        $('#project_select_form .project_label').on('click', function () {
            $(this).closest("form").submit();
        });
    };
}
