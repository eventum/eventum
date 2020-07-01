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
 * Product chooser functions used in multiplepages
 */
export default class {
    ready() {
        const page = this;

        $('#product').bind('change', function() {
            page.display_product_version_howto();
        }).change();
    };

    display_product_version_howto() {
        const howto = $('#product :selected').attr('data-desc');
        if (!howto) {
            $('#product_version_howto').hide();
        } else {
            $('#product_version_howto').text(howto).show();
        }
    }
}
