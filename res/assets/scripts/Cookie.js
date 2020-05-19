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

export class Cookie {
    constructor(cookies) {
        this.attributes = {
            expires: new Date(new Date().getTime() + (56 * 86400000)),
        }
        this.cookies = cookies;
    }

    get(name) {
        return this.cookies.get(name);
    }

    set(name, value) {
        this.cookies.set(name, value, this.attributes);
    }

    remove(name) {
        this.cookies.remove(name, this.attributes);
    }
}
