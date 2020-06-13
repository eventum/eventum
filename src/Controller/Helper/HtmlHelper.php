<?php

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

namespace Eventum\Controller\Helper;

class HtmlHelper
{
    /**
     * Creates array needed to use {html_radios} Smarty function.
     *
     * Example:
     * PHP:
     * $tpl->assign('encryption', enableRadioButtons($setup['encryption']),
     * Template:
     * {html_radios name='encryption' options=$encryption.options selected=$encryption.selected}
     *
     * @param string $value
     * @return array
     */
    public function enableRadioButtons($value)
    {
        return [
            'options' => [
                1 => ev_gettext('Enabled'),
                0 => ev_gettext('Disabled'),
            ],
            'selected' => (int)($value === 'enabled'),
        ];
    }

    public function radioYesNoButtons(bool $value): array
    {
        return [
            'options' => [
                1 => ev_gettext('Yes'),
                0 => ev_gettext('No'),
            ],
            'selected' => (int)$value,
        ];
    }

    /**
     * @see https://www.smarty.net/docsv2/en/language.function.html.checkboxes.tpl
     */
    public function checkboxes(array $options, array $selected): array
    {
        return [
            'options' => $options,
            'selected' => $selected,
        ];
    }
}
