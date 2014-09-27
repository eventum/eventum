<?php

class Date_HelperTest extends PHPUnit_Framework_TestCase
{
    /**
     * @covers       Date_Helper::isAM
     * @dataProvider IsAMData
     */
    public function testIsAM($input, $exp)
    {
        $res = Date_Helper::isAM($input);
        $this->assertEquals($exp, $res);
    }

    public function IsAMData()
    {
        return array(
            array(0, true),
            array(10, true),
            array('20', false),
            array(42, false),
        );
    }

    /**
     * @covers Date_Helper::isPM
     * @dataProvider IsPMData
     */
    public function testIsPM($input, $exp)
        {
            $res = Date_Helper::isPM($input);
            $this->assertEquals($exp, $res);
    }

    public function IsPMData()
    {
        return array(
            array(0, false),
            array(10, false),
            array('20', true),
            array(42, false),
        );
    }

    /**
     * @covers Date_Helper::getDateGMT
     * @todo   Implement testGetDateGMT().
     */
    public function testGetDateGMT()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Date_Helper::getCurrentUnixTimestampGMT
     * @todo   Implement testGetCurrentUnixTimestampGMT().
     */
    public function testGetCurrentUnixTimestampGMT()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Date_Helper::getFormattedDateDiff
     * @todo   Implement testGetFormattedDateDiff().
     */
    public function testGetFormattedDateDiff()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Date_Helper::getUnixTimestamp
     * @todo   Implement testGetUnixTimestamp().
     */
    public function testGetUnixTimestamp()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Date_Helper::getRFC822Date
     * @todo   Implement testGetRFC822Date().
     */
    public function testGetRFC822Date()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Date_Helper::getCurrentDateGMT
     * @todo   Implement testGetCurrentDateGMT().
     */
    public function testGetCurrentDateGMT()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Date_Helper::getTimezoneList
     * @todo   Implement testGetTimezoneList().
     */
    public function testGetTimezoneList()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Date_Helper::getTimezoneShortName
     * @todo   Implement testGetTimezoneShortName().
     */
    public function testGetTimezoneShortName()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Date_Helper::getTimezoneShortNameByUser
     * @todo   Implement testGetTimezoneShortNameByUser().
     */
    public function testGetTimezoneShortNameByUser()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers       Date_Helper::getFormattedDate
     * @dataProvider getFormattedDateData
     */
    public function testGetFormattedDate($input, $exp)
    {
        $res = Date_Helper::getFormattedDate($input);
        $this->assertEquals($exp, $res);
    }

    public function getFormattedDateData()
    {
        return array(
            array('0', 'Thu, 01 Jan 1970, 03:00:00 UTC'),
            array(1411840837, 'Sat, 27 Sep 2014, 21:00:37 UTC'),
        );
    }

    /**
     * @covers Date_Helper::getSimpleDate
     * @todo   Implement testGetSimpleDate().
     */
    public function testGetSimpleDate()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Date_Helper::getPreferredTimezone
     * @todo   Implement testGetPreferredTimezone().
     */
    public function testGetPreferredTimezone()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Date_Helper::getDefaultTimezone
     * @todo   Implement testGetDefaultTimezone().
     */
    public function testGetDefaultTimezone()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Date_Helper::getDefaultWeekday
     * @todo   Implement testGetDefaultWeekday().
     */
    public function testGetDefaultWeekday()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Date_Helper::convertDateGMT
     * @todo   Implement testConvertDateGMT().
     */
    public function testConvertDateGMT()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Date_Helper::convertDateGMTByTS
     * @todo   Implement testConvertDateGMTByTS().
     */
    public function testConvertDateGMTByTS()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Date_Helper::getWeekOptions
     * @todo   Implement testGetWeekOptions().
     */
    public function testGetWeekOptions()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Date_Helper::getCurrentWeek
     * @todo   Implement testGetCurrentWeek().
     */
    public function testGetCurrentWeek()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Date_Helper::getSecondsDiff
     * @todo   Implement testGetSecondsDiff().
     */
    public function testGetSecondsDiff()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }
}
