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

import { ExpandableCell } from "./ExpandableCell.js";
import { CustomField } from "./CustomField.js";
import { GrowingFileField } from "./GrowingFileField.js";
import { Validation } from "./Validation.js";
import { Cookie } from "./Cookie.js";
import { Eventum } from "./Eventum.js";

window.Eventum = new Eventum();
window.ExpandableCell = new ExpandableCell();
window.CustomField = new CustomField();
window.GrowingFileField = new GrowingFileField();
window.Validation = new Validation();
window.Cookie = new Cookie(Cookies.noConflict());
