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
        const page = this;
        const $projectForm = $('form#project_form');
        const $reportForm = $('form#report_form');

        $projectForm.find('input,select').filter(':visible').first().focus();
        $projectForm.submit(function () {
            return Validation.checkFormSubmission($projectForm, page.validateProjectForm)
        });

        $reportForm.find('input,select').filter(':visible').first().focus();
        $reportForm.submit(function () {
            return Validation.checkFormSubmission($reportForm, page.validateForm)
        });
    }

    validateProjectForm(form) {
        const $form = Eventum.getField('project');
        if ($form.val() == '-1') {
            Validation.errors[Validation.errors.length] = new Option('Project', 'project');
        }
    }

    validateForm(form) {
        if (Validation.isFieldWhitespace('summary')) {
            Validation.errors[Validation.errors.length] = new Option('Summary', 'summary');
        }

        // replace special characters in description
        const $description_field = Eventum.getField('description');
        $description_field.val(Eventum.replaceSpecialCharacters($description_field.val()));

        if (Validation.isFieldWhitespace('description')) {
            Validation.errors[Validation.errors.length] = new Option('Description', 'description');
        }

        Validation.checkCustomFields(form);
    }
}
