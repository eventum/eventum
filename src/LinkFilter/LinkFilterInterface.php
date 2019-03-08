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

namespace Eventum\LinkFilter;

interface LinkFilterInterface
{
    /**
     * Method used as a callback with the regular expression code that parses
     * text and creates links to other issues.
     *
     * @param   array $matches Regular expression matches
     * @return  string The link to the appropriate issue
     */
    public function __invoke(array $matches): string;
}
