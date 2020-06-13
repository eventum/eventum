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
import SelectProject from "./pages/SelectProject.js";
import ListIssues from "./pages/ListIssues.js";
import IssueView from "./pages/IssueView.js";
import IssueUpdate from "./pages/IssueUpdate.js";
import CloseIssue from "./pages/CloseIssue.js";
import AdvSearch from "./pages/AdvSearch.js";
import NewIssue from "./pages/NewIssue.js";
import AnonPost from "./pages/AnonPost.js";
import Stats from "./pages/Stats.js";
import Product from "./pages/Product.js";
import Preferences from "./pages/Preferences.js";
import CustomFieldOptions from "./pages/CustomFieldOptions.js";

window.Eventum = new Eventum();
window.ExpandableCell = new ExpandableCell();
window.CustomField = new CustomField();
window.GrowingFileField = new GrowingFileField();
window.Validation = new Validation();
window.Cookie = new Cookie(Cookies.noConflict());

// pages
window.select_project = new SelectProject();
window.list_issues = new ListIssues();
window.issue_view = new IssueView();
window.issue_update = new IssueUpdate();
window.close_issue = new CloseIssue();
window.adv_search = new AdvSearch();
window.new_issue = new NewIssue();
window.anon_post = new AnonPost();
window.stats = new Stats();
window.product = new Product();
window.preferences = new Preferences();
window.custom_field_options = new CustomFieldOptions();
