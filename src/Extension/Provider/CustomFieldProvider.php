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

namespace Eventum\Extension\Provider;

interface CustomFieldProvider extends ExtensionProvider
{
    /**
     * Return Custom Field Class names the extension provides.
     *
     * @return string[]
     * @since 3.6.6
     */
    public function getAvailableCustomFields(): array;
}
