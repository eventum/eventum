<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright 2011, Elan Ruusamäe <glen@delfi.ee>                        |
// | Copyright (c) 2011 - 2014 Eventum Team.                              |
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
// | 51 Franklin Street, Suite 330                                          |
// | Boston, MA 02110-1301, USA.                                          |
// +----------------------------------------------------------------------+

/**
 * Test class for Misc.
 */
class MiscTest extends TestCase
{
    /**
     * Method used to strip HTML from a string or array
     *
     * @param   string $str The original string or array
     * @return  string The escaped (or not) string
     * @dataProvider StripHTMLData
     */
    public function testStripHTML($str, $exp)
    {
        $this->assertEquals($exp, Misc::stripHTML($str));
    }

    /**
     * @param   string $str The original string or array
     * @return  string The escaped (or not) string
     * @dataProvider StripInputData
     */
    public function testStripInput($str, $exp)
    {
        Misc::stripInput($str);
        $this->assertEquals($exp, $str);
    }

    public function StripHTMLData()
    {
        return array(
            array('plain', 'plain'),
            array('<b>bold</b>', '&#60;b&#62;bold&#60;/b&#62;'),
            array(array('<b>bold</b>'), array('&#60;b&#62;bold&#60;/b&#62;')),
        );
    }

    public function StripInputData()
    {
        return array(
            array('plain', 'plain'),
            // nothing bad happens with empty array
            array(array(), array()),
            // ctrl char: \r
            array(
                array('a' => "a\r\nb"),
                array('a' => "a\nb"),
            ),
            // some emoji
            array(
                array('a' => self::unichr(0x1F6B2).self::unichr(0x1F4A8)),
                array('a' => ''),
            ),
        );
    }

    /**
     * Return unicode char by its code
     *
     * @link http://php.net/manual/en/function.chr.php#88611

     * @param int $u
     * @return string
     */
    private function unichr($u)
    {
        return mb_convert_encoding('&#' . intval($u) . ';', 'UTF-8', 'HTML-ENTITIES');
    }
}
