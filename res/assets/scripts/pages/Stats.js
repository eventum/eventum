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
        $('#hide_closed').change(function hideClosed(e) {
            const $target = $(e.target);
            if ($target.is(':checked')) {
                window.location.href = "?" + Eventum.replaceParam(window.location.href, 'hide_closed', '1');
            } else {
                window.location.href = "?" + Eventum.replaceParam(window.location.href, 'hide_closed', '0');
            }
        });
    }
}
