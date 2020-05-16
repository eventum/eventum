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

export class CustomField {
    constructor() {
        this.field_info = [];
    }

    // load information from the current page regarding fields
    // this method is invoked on jQuery ready when page classname matches
    ready() {
        this.loadFieldInfo();
    };

    loadFieldInfo() {
        const cf = this;
        $('.custom_field').each(function () {
            const field = $(this);
            cf.field_info.push({
                id: field.attr('data-custom-id'),
                type: field.attr('data-custom-type'),
                title: field.attr('data-custom-title'),
                required: field.attr('data-custom-required'),
                validation_js: field.attr('data-custom-validation-js')
            })
        });
    };

    getFieldInfo() {
        return this.field_info;
    };
}
