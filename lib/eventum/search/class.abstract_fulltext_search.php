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

/**
 * Abstract class for fulltext searching.
 */
abstract class Abstract_Fulltext_Search
{
    /**
     * Returns the issue IDs matching the search query.
     *
     * @abstract
     * @param   array   $options    the array of search options
     * @return  array   An array of issues IDs matching the given search options
     */
    abstract public function getIssueIDs($options): array;

    /**
     * Returns the list of match modes the backend supports. Should return false if only one
     * mode is supported. Otherwise format should be array('mode_id' => 'Mode Title')
     *
     * @abstract
     * @return  array   An associative array of match modes
     */
    abstract public function getMatchModes(): array;

    /**
     * Returns an array of excerpts for the last search.
     *
     * @abstract
     * @return array
     */
    abstract public function getExcerpts(): array;

    /**
     * If the backend supports excerpts
     *
     * @return  bool
     */
    public function supportsExcerpts(): bool
    {
        return false;
    }
}
