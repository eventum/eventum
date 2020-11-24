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

use Symfony\Component\HttpFoundation\Request;

interface LinkFilterInterface
{
    public function getPatterns(): array;

    /**
     * Method used as a callback with the regular expression code that parses
     * text and creates links to other issues.
     *
     * @param   array $matches Regular expression matches
     * @param Request $request
     * @return  string The link to the appropriate issue
     * @since 3.9.8 Adds $request parameter
     * @since 3.10.0 The $request parameter will be mandatory
     */
    public function __invoke(array $matches/*, Request $request*/): string;
}
