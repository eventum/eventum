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
        const $form = $('form#report_form');

        $form.find('input,select').filter(':visible').first().focus();

        $form.submit(function () {
            return Validation.checkFormSubmission($form, page.validateForm)
        });

        $('#severity').bind('change', function () {
            page.display_severity_description();
        }).change();
        product.ready();
    };

    validateForm() {
        const $form = $('form#report_form');

        const $category_field = Eventum.getField('category');
        if ($category_field.attr('type') !== 'hidden' && $category_field.val() == -1 && $category_field.data('required')) {
            Validation.errors[Validation.errors.length] = new Option('Category', 'category');
        }

        const $priority_field = Eventum.getField('priority');
        if ($priority_field.attr('type') !== 'hidden' && $priority_field.val() == -1 && $priority_field.data('required')) {
            Validation.errors[Validation.errors.length] = new Option('Priority', 'priority');
        }

        const $severity_field = Eventum.getField('severity');
        if ($severity_field.attr('type') !== 'hidden' && $severity_field.val() == -1 && $severity_field.data('required')) {
            Validation.errors[Validation.errors.length] = new Option('Severity', 'severity');
        }

        const $release_field = Eventum.getField('release');
        if ($release_field.attr('type') !== 'hidden' && $release_field.val() == 0 && $release_field.data('required')) {
            Validation.errors[Validation.errors.length] = new Option('Scheduled Release', 'release');
        }

        const $expected_res_date_field = Eventum.getField('expected_resolution_date');
        if ($expected_res_date_field.attr('type') !== 'hidden' && $expected_res_date_field.val() == '' &&
            $expected_res_date_field.data('required')) {
            Validation.errors[Validation.errors.length] = new Option('Expected Resolution Date', 'expected_resolution_date');
        }

        const $associated_issues_field = Eventum.getField('associated_issues');
        if ($associated_issues_field.attr('type') !== 'hidden' && $associated_issues_field.val() == '' &&
            $associated_issues_field.data('required')) {
            Validation.errors[Validation.errors.length] = new Option('Associated Issues', 'associated_issues_field');
        }

        const $group_field = Eventum.getField('group');
        if ($group_field.attr('type') !== 'hidden' && $group_field.val() == '' && $group_field.data('required')) {
            Validation.errors[Validation.errors.length] = new Option('Group', 'group');
        }

        const $product_field = Eventum.getField('product');
        if ($product_field.attr('type') !== 'hidden' && $product_field.val() == -1 && $product_field.data('required')) {
            Validation.errors[Validation.errors.length] = new Option('Product', 'product');
        }

        const $user_field = Eventum.getField('users[]');
        if ($user_field.length > 0 && $user_field.data('required') && $user_field.attr('type') !== 'hidden' &&
            !Validation.hasOneSelected($user_field)) {
            Validation.errors[Validation.errors.length] = new Option('Assignment', 'users');
        }

        if (Validation.isFieldWhitespace('summary')) {
            Validation.errors[Validation.errors.length] = new Option('Summary', 'summary');
        }

        // replace special characters in description
        const $description_field = Eventum.getField('description');
        $description_field.val(Eventum.replaceSpecialCharacters($description_field.val()));

        if (Validation.isFieldWhitespace('description')) {
            Validation.errors[Validation.errors.length] = new Option('Description', 'description');
        }

        const $estimated_dev_field = Eventum.getField('estimated_dev_time');
        if ($estimated_dev_field.attr('type') != 'hidden' && !Validation.isFieldWhitespace($estimated_dev_field) &&
            !Validation.isFloat($estimated_dev_field.val()) && $estimated_dev_field.data('required')) {
            Validation.errors[Validation.errors.length] = new Option('Estimated Dev. Time (only numbers)', 'estimated_dev_time');
        }

        Validation.checkCustomFields($form);

        // check customer fields (if function exists
        if (window.validateCustomer) {
            validateCustomer();
        }
    };

    display_severity_description() {
        const description = $('#severity :selected').attr('data-desc');

        if (description == undefined || description == '') {
            $('#severity_desc').hide();
        } else {
            $('#severity_desc').text(description).show();
        }
    }
}

