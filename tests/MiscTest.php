<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright 2011, Elan Ruusamäe <glen@delfi.ee>                        |
// | Copyright (c) 2011 - 2012 Eventum Team.                              |
// +----------------------------------------------------------------------+
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

require_once 'PHPUnit/Framework.php';
require_once 'TestSetup.php';

/**
 * Test class for Misc.
 */
class MiscTest extends PHPUnit_Framework_TestCase
{
    /**
     * Method used to strip HTML from a string or array
     *
     * @access  public
     * @param   string $str The original string or array
     * @return  string The escaped (or not) string
     * @dataProvider StripHTMLData
     */
    public function testStripHTML($str, $exp)
    {
        $this->assertEquals($exp, Misc::stripHTML($str));
    }

    public function StripHTMLData()
    {
        return array(
            array('plain', 'plain'),
            array('<b>bold</b>', '&#60;b&#62;bold&#60;/b&#62;'),
            array(array('<b>bold</b>'), array('&#60;b&#62;bold&#60;/b&#62;')),
        );
    }
}
