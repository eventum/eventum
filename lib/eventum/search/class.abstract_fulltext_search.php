<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// |                                                                      |
// | This program is free software; you can redistribute it and/or modify |
// | it under the terms of the GNU General Public License as published by |
// | the Free Software Foundation; either version 2 of the License, or    |
// | (at your option) any later version.                                  |
// |                                                                      |
// | This program is distributed in the hope that it will be useful,      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        |
// | GNU General Public License for more details.                         |
// |                                                                      |
// | You should have received a copy of the GNU General Public License    |
// | along with this program; if not, write to:                           |
// |                                                                      |
// | Free Software Foundation, Inc.                                       |
// | 59 Temple Place - Suite 330                                          |
// | Boston, MA 02111-1307, USA.                                          |
// +----------------------------------------------------------------------+
// | Authors: Bryan Alsdorf <balsdorf@gmail.com>                          |
// +----------------------------------------------------------------------+
//


/**
 * Abstract class for fulltext searching.
 */
abstract class Abstract_Fulltext_Search
{
    /**
     * Returns the issue IDs matching the search query.
     *
     * @abstract
     * @param   array   $options    The array of search options.
     * @return  array   An array of issues IDs matching the given search options
     */
    abstract public function getIssueIDs($options);

    /**
     * Returns the list of match modes the backend supports. Should return false if only one
     * mode is supported. Otherwise format should be array('mode_id' => 'Mode Title')
     *
     * @abstract
     * @return  array   An associative array of match modes
     */
    abstract public function getMatchModes();


    /**
     * Returns an array of excerpts for the last search.
     *
     * @abstract
     * @return array
     */
    abstract public function getExcerpts();


    /**
     * If the backend supports excerpts
     *
     * @return  bool
     */
    public function supportsExcerpts()
    {
        return false;
    }
}