<?php

/**
 * Test DB layer to work as expected
 */
class DbTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var DbInterface
     */
    private $db;

    public function setUp()
    {
        $this->db = DB_Helper::getInstance();
    }

    public function testGetOne()
    {
        $res = $this->db->getOne(
            'SELECT usr_id FROM {{%user}} WHERE usr_email=?', array('nosuchemail@.-')
        );
        $this->assertNull($res);

        $res = $this->db->getOne(
            'SELECT usr_id FROM {{%user}} WHERE usr_email=?', array('admin@example.com')
        );
        $this->assertEquals(2, $res);
    }

    public function testGetAssoc()
    {
        $sql
            = 'SELECT
                    prj_id,
                    prj_title
                 FROM
                    {{%project}},
                    {{%project_user}}
                 WHERE
                    prj_id=pru_prj_id AND
                    pru_usr_id=? AND
                    (
                        prj_status != ? OR
                        pru_role >= ?
                    )
                 ORDER BY
                    prj_title';
        $res = $this->db->getPair(
            $sql, array('2', 'archived', '6')
        );
        $this->assertEquals(array('1' => 'Default Project'), $res);
    }
}
